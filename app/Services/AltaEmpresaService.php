<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Empresa;
use App\Models\LegalAceptacion;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Crea una empresa junto con su primer admin_empresa y los registros legales
 * de aceptación. Lo usan tanto el alta manual de super_admin como el
 * registro público de autoservicio — misma lógica, distinto punto de
 * entrada.
 */
class AltaEmpresaService
{
    /**
     * $requiereVerificacionEmail: false (alta manual de super_admin) deja al
     * admin ya verificado — el super_admin ha tecleado ese email él mismo,
     * es un canal de confianza. true (registro público) lo deja sin
     * verificar; quien llama es responsable de enviar el correo de
     * verificación después (ver RegistroController).
     *
     * @param  array{nombre: string, nif: ?string, email_contacto: string, admin_name: string, admin_email: string, admin_password: string}  $data
     */
    public static function crear(array $data, string $ip, bool $requiereVerificacionEmail = false): array
    {
        return DB::transaction(function () use ($data, $ip, $requiereVerificacionEmail) {
            $empresa = Empresa::create([
                'nombre' => $data['nombre'],
                'nif' => $data['nif'] ?? null,
                'email_contacto' => $data['email_contacto'],
                'activa' => true,
            ]);

            $admin = User::create([
                'empresa_id' => $empresa->id,
                'name' => $data['admin_name'],
                'email' => $data['admin_email'],
                'rol' => 'admin_empresa',
                'activo' => true,
                'password' => $data['admin_password'],
                'email_verified_at' => $requiereVerificacionEmail ? null : now(),
            ]);

            foreach (['terminos_condiciones' => 'version_terminos', 'encargo_tratamiento' => 'version_encargo'] as $documento => $configKey) {
                LegalAceptacion::create([
                    'empresa_id' => $empresa->id,
                    'user_id' => $admin->id,
                    'documento' => $documento,
                    'version' => config('legal.'.$configKey),
                    'aceptado_at' => now(),
                    'ip_address' => $ip,
                ]);
            }

            // Si hay alguien ya autenticado (super_admin dando de alta la
            // empresa a mano) se le atribuye a él; si no (registro público,
            // nadie ha iniciado sesión todavía) se atribuye al propio admin
            // recién creado, que es quien de hecho ha hecho la operación.
            AuditLog::registrar('empresa_creada', $empresa, ['admin_email' => $admin->email], Auth::user() ?? $admin);

            return ['empresa' => $empresa, 'admin' => $admin];
        });
    }
}
