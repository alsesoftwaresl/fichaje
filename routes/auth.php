<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\DosPasosController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\ForzarCambioPasswordController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\DosPasosPerfilController;
use Illuminate\Support\Facades\Route;

// Rutas de acceso (login, recuperar contraseña, verificar email). El alta de
// empresas es pública (ver /registro en web.php); los empleados los crea su admin.
Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->name('password.store');

    // Segundo paso del login (contraseña ya comprobada, falta el código del móvil).
    Route::get('dos-pasos', [DosPasosController::class, 'reto'])->name('dos-pasos.reto');
    Route::post('dos-pasos', [DosPasosController::class, 'verificar'])
        ->middleware('throttle:10,1')
        ->name('dos-pasos.verificar');
});

Route::middleware('auth')->group(function () {
    // Verificación en dos pasos desde el perfil.
    Route::post('profile/dos-pasos', [DosPasosPerfilController::class, 'activar'])->name('dos-pasos.activar');
    Route::post('profile/dos-pasos/confirmar', [DosPasosPerfilController::class, 'confirmar'])
        ->middleware('throttle:10,1')
        ->name('dos-pasos.confirmar');
    Route::post('profile/dos-pasos/desactivar', [DosPasosPerfilController::class, 'desactivar'])->name('dos-pasos.desactivar');
    Route::post('profile/dos-pasos/codigos', [DosPasosPerfilController::class, 'regenerarCodigos'])->name('dos-pasos.codigos');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');

    // Primer acceso con contraseña temporal: hay que elegir la propia
    // (ForzarCambioPassword manda aquí a quien tenga el aviso pendiente).
    Route::get('elegir-password', [ForzarCambioPasswordController::class, 'create'])->name('password.forzar');
    Route::post('elegir-password', [ForzarCambioPasswordController::class, 'store'])->name('password.forzar.store');

    // Solo admin_empresa del registro público pasa realmente por aquí — ver
    // RequireEmailVerificado. El resto de usuarios nace ya verificado.
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');
});
