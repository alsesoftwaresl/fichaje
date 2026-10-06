<?php

use App\Http\Controllers\Admin\AdminFichajeController;
use App\Http\Controllers\Admin\AusenciaController as AdminAusenciaController;
use App\Http\Controllers\Admin\EmpleadoController;
use App\Http\Controllers\Admin\FacturacionController;
use App\Http\Controllers\Admin\FichajeCorreccionController;
use App\Http\Controllers\Admin\IncidenciaController;
use App\Http\Controllers\Admin\PanelController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\AusenciaController;
use App\Http\Controllers\CitaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeployController;
use App\Http\Controllers\FichajeController;
use App\Http\Controllers\GestionNominaController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\KioskoController;
use App\Http\Controllers\NominaController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RegistroCompletarController;
use App\Http\Controllers\RegistroController;
use App\Http\Controllers\SuperAdmin\EmpresaController;
use App\Http\Controllers\SuperAdmin\TarifaController;
use Illuminate\Support\Facades\Route;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierWebhookController;
use Laravel\Cashier\Http\Middleware\VerifyWebhookSignature;

// Controlador (no Route::view) a propósito: la tarifa se calcula en cada
// petición, no una vez al cachear rutas ("php artisan route:cache"), que
// dejaría el precio mostrado congelado si luego cambia desde super_admin.
Route::get('/', HomeController::class)->name('home');

Route::get('/registro', [RegistroController::class, 'create'])->name('registro.create');
Route::post('/registro', [RegistroController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('registro.store');

// Segundo paso del alta cuando se viene de "Continuar con Google" — requiere
// haber pasado antes por GoogleAuthController (deja el email en sesión).
Route::get('/registro/completar', [RegistroCompletarController::class, 'create'])->name('registro.completar');
Route::post('/registro/completar', [RegistroCompletarController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('registro.completar.store');

// "Continuar con Google" — solo admin_empresa (login y alta), nunca empleados.
Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])
    ->middleware('throttle:10,1')
    ->name('auth.google.redirect');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])
    ->middleware('throttle:10,1')
    ->name('auth.google.callback');

Route::view('/legal/terminos', 'legal.terminos')->name('legal.terminos');
Route::view('/legal/privacidad', 'legal.privacidad')->name('legal.privacidad');
Route::view('/legal/encargo-tratamiento', 'legal.encargo-tratamiento')->name('legal.encargo-tratamiento');
Route::view('/legal/cookies', 'legal.cookies')->name('legal.cookies');

// Sustituto de "artisan migrate" para hosting sin SSH (ver DeployController).
// Inerte salvo que DEPLOY_TOKEN esté configurado en .env — nunca lo está en
// local ni en un hosting con terminal.
Route::get('/deploy/ejecutar', [DeployController::class, 'ejecutar'])
    ->middleware('throttle:5,1')
    ->name('deploy.ejecutar');

// Kiosco: sin login, identificado solo por el token (no adivinable) de la
// empresa en la URL. El PIN protege la identidad del empleado que ficha.
//
// Importante: GET y POST van en grupos de throttle SEPARADOS a propósito.
// El limitador genérico de Laravel usa como clave solo IP (no la ruta), así
// que si fueran el mismo grupo, cada carga de la pantalla del kiosco
// consumiría del mismo cupo que los envíos de PIN — con eso, fichar dos
// veces ya agotaba el límite. Cargar la pantalla no es sensible (solo
// muestra un teclado), así que lleva un límite amplio; el envío del PIN,
// que sí hay que proteger de fuerza bruta, lleva uno más ajustado pero
// realista para un kiosco con muchos empleados fichando seguido.
Route::get('/kiosko/{token}', [KioskoController::class, 'show'])
    ->middleware('throttle:60,1')
    ->name('kiosko.show');

Route::post('/kiosko/{token}/fichar', [KioskoController::class, 'fichar'])
    ->middleware('throttle:30,1')
    ->name('kiosko.fichar');

// Stripe envía aquí los eventos de la suscripción (pago confirmado, fallido,
// cancelada...). VerifyWebhookSignature comprueba que viene realmente de
// Stripe; sin sesión de usuario, así que también hay que eximirla de CSRF
// (ver bootstrap/app.php).
Route::post('/stripe/webhook', [CashierWebhookController::class, 'handleWebhook'])
    ->middleware(VerifyWebhookSignature::class)
    ->name('cashier.webhook');

Route::get('/dashboard', DashboardController::class)
    ->middleware('auth')
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

// Empleado y admin_empresa (cualquier usuario con empresa): fichar y ver su
// propio historial. Requiere que la empresa tenga suscripción activa — es el
// producto en sí, no tiene sentido dejarlo usar sin pagar. "verificado" no
// afecta a empleados (la mayoría sin email) — ver RequireEmailVerificado.
Route::middleware(['auth', 'tenant', 'verificado', 'suscripcion'])->group(function () {
    Route::get('/mis-fichajes', [FichajeController::class, 'misFichajes'])->name('fichajes.mis');
    Route::post('/fichajes', [FichajeController::class, 'store'])->name('fichajes.store');

    Route::get('/mis-ausencias', [AusenciaController::class, 'misAusencias'])->name('ausencias.mis');
    Route::post('/ausencias', [AusenciaController::class, 'store'])->name('ausencias.store');

    // Aviso de una cita (médico...) dentro de la jornada; anular lo puede el
    // propio empleado o un admin de su empresa.
    Route::post('/citas', [CitaController::class, 'store'])->name('citas.store');
    Route::patch('/citas/{cita}/anular', [CitaController::class, 'anular'])->name('citas.anular');
});

// Nóminas. El empleado ve y descarga las suyas SIN pasar por "suscripcion":
// que la empresa deje de pagar no debe quitarle sus propios documentos.
Route::middleware(['auth', 'tenant', 'verificado'])->group(function () {
    Route::get('/mis-nominas', [NominaController::class, 'mis'])->name('nominas.mis');
    Route::get('/nominas/{nomina}/descargar', [NominaController::class, 'descargar'])->name('nominas.descargar');
});

// Subir y gestionar nóminas: admin_empresa o quien tenga el permiso de contable.
Route::middleware(['auth', 'tenant', 'verificado', 'suscripcion', 'gestor.nominas'])
    ->prefix('gestion-nominas')
    ->name('nominas.gestion.')
    ->group(function () {
        Route::get('/', [GestionNominaController::class, 'index'])->name('index');
        Route::post('/', [GestionNominaController::class, 'store'])->name('store');
        Route::delete('/{nomina}', [GestionNominaController::class, 'destroy'])->name('destroy');
    });

// admin_empresa: gestión de fichajes y empleados de su propia empresa.
// "verificado" envuelve incluso Facturación a propósito: hasta que no
// verifique el correo (solo aplica a quien vino del registro público, ver
// RequireEmailVerificado) no puede hacer nada, ni siquiera suscribirse.
Route::middleware(['auth', 'tenant', 'role:admin_empresa', 'verificado'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        // Facturación queda FUERA del middleware "suscripcion" a propósito:
        // es la única sección que debe seguir accesible sin estar suscrito,
        // porque es desde donde se suscriben.
        Route::get('/facturacion', [FacturacionController::class, 'index'])->name('facturacion.index');
        Route::post('/facturacion/suscribir', [FacturacionController::class, 'suscribir'])->name('facturacion.suscribir');
        Route::get('/facturacion/portal', [FacturacionController::class, 'portal'])->name('facturacion.portal');
        Route::get('/facturacion/facturas/{factura}', [FacturacionController::class, 'descargarFactura'])->name('facturacion.facturas.descargar');

        Route::middleware('suscripcion')->group(function () {
            Route::get('/panel', [PanelController::class, 'index'])->name('panel.index');
            Route::get('/incidencias', [IncidenciaController::class, 'index'])->name('incidencias.index');

            Route::get('/fichajes', [AdminFichajeController::class, 'index'])->name('fichajes.index');
            Route::get('/fichajes/exportar/{formato}', [AdminFichajeController::class, 'exportar'])
                ->whereIn('formato', ['csv', 'excel', 'pdf'])
                ->name('fichajes.exportar');
            Route::post('/fichajes/{fichaje}/correcciones', [FichajeCorreccionController::class, 'store'])->name('fichajes.correcciones.store');

            Route::get('/empleados', [EmpleadoController::class, 'index'])->name('empleados.index');
            Route::get('/empleados/crear', [EmpleadoController::class, 'create'])->name('empleados.create');
            Route::post('/empleados', [EmpleadoController::class, 'store'])->name('empleados.store');
            Route::get('/empleados/{empleado}/editar', [EmpleadoController::class, 'edit'])->name('empleados.edit');
            Route::patch('/empleados/{empleado}', [EmpleadoController::class, 'update'])->name('empleados.update');
            Route::patch('/empleados/{empleado}/activar', [EmpleadoController::class, 'activar'])->name('empleados.activar');
            Route::patch('/empleados/{empleado}/desactivar', [EmpleadoController::class, 'desactivar'])->name('empleados.desactivar');
            Route::patch('/empleados/{empleado}/regenerar-pin', [EmpleadoController::class, 'regenerarPin'])->name('empleados.regenerar-pin');

            Route::get('/ausencias', [AdminAusenciaController::class, 'index'])->name('ausencias.index');
            Route::patch('/ausencias/{ausencia}/aprobar', [AdminAusenciaController::class, 'aprobar'])->name('ausencias.aprobar');
            Route::patch('/ausencias/{ausencia}/rechazar', [AdminAusenciaController::class, 'rechazar'])->name('ausencias.rechazar');
        });
    });

// super_admin: gestión de empresas clientes (tenants).
Route::middleware(['auth', 'role:super_admin'])
    ->prefix('super-admin')
    ->name('super-admin.')
    ->group(function () {
        Route::get('/empresas', [EmpresaController::class, 'index'])->name('empresas.index');
        Route::get('/empresas/crear', [EmpresaController::class, 'create'])->name('empresas.create');
        Route::post('/empresas', [EmpresaController::class, 'store'])->name('empresas.store');
        Route::get('/empresas/{empresa}', [EmpresaController::class, 'show'])->name('empresas.show');
        Route::patch('/empresas/{empresa}/activar', [EmpresaController::class, 'activar'])->name('empresas.activar');
        Route::patch('/empresas/{empresa}/desactivar', [EmpresaController::class, 'desactivar'])->name('empresas.desactivar');

        Route::get('/tarifas', [TarifaController::class, 'edit'])->name('tarifas.edit');
        Route::patch('/tarifas', [TarifaController::class, 'update'])->name('tarifas.update');
    });

require __DIR__.'/auth.php';
