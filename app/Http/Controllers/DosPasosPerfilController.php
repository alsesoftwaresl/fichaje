<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Support\Totp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Activar, confirmar y desactivar la verificación en dos pasos desde el perfil, y renovar
 * los códigos de recuperación. Muy recomendable para quien administra una empresa: ve los
 * datos de toda la plantilla.
 */
class DosPasosPerfilController extends Controller
{
    /** Códigos de recuperación que se generan cada vez. */
    const CODIGOS = 8;

    /**
     * Propone un secreto nuevo (aún no activo): el perfil muestra el QR para escanearlo.
     */
    public function activar(Request $request): RedirectResponse
    {
        $usuario = $request->user();

        if (! $usuario->tieneDosPasos()) {
            $usuario->forceFill([
                'dos_pasos_secreto' => Totp::secreto(),
                'dos_pasos_confirmada_en' => null,
                'dos_pasos_recuperacion' => null,
            ])->save();
        }

        return redirect()->to(route('profile.edit').'#dos-pasos');
    }

    /**
     * Activa la verificación al escribir el primer código bien (así se sabe que la app del
     * móvil quedó configurada) y muestra una sola vez los códigos de recuperación.
     */
    public function confirmar(Request $request): RedirectResponse
    {
        $request->validate(['codigo' => ['required', 'string', 'max:20']]);
        $usuario = $request->user();

        if ($usuario->tieneDosPasos() || ! $usuario->dos_pasos_secreto) {
            return redirect()->to(route('profile.edit').'#dos-pasos');
        }

        if (Totp::verificar($usuario->dos_pasos_secreto, (string) $request->input('codigo')) === null) {
            throw ValidationException::withMessages(['codigo' => 'El código no es correcto. Comprueba que la hora del móvil está bien y prueba con el siguiente.'])
                ->errorBag('dosPasos');
        }

        $codigos = $this->codigosNuevos();
        $usuario->forceFill(['dos_pasos_confirmada_en' => now(), 'dos_pasos_recuperacion' => $codigos])->save();
        AuditLog::registrar('dos_pasos_activada', $usuario);

        return redirect()->to(route('profile.edit').'#dos-pasos')->with('codigos_recuperacion', $codigos);
    }

    /**
     * Desactiva la verificación (pide la contraseña actual) o cancela una a medio activar.
     */
    public function desactivar(Request $request): RedirectResponse
    {
        $usuario = $request->user();

        if ($usuario->tieneDosPasos()) {
            $request->validateWithBag('dosPasos', ['password' => ['required', 'current_password']]);
        }

        $usuario->forceFill([
            'dos_pasos_secreto' => null,
            'dos_pasos_recuperacion' => null,
            'dos_pasos_confirmada_en' => null,
        ])->save();

        AuditLog::registrar('dos_pasos_desactivada', $usuario);

        return redirect()->to(route('profile.edit').'#dos-pasos')->with('status', 'dos-pasos-desactivada');
    }

    /**
     * Genera códigos de recuperación nuevos (los anteriores dejan de valer). Pide la
     * contraseña.
     */
    public function regenerarCodigos(Request $request): RedirectResponse
    {
        $request->validateWithBag('dosPasos', ['password' => ['required', 'current_password']]);
        $usuario = $request->user();

        abort_unless($usuario->tieneDosPasos(), 404);

        $codigos = $this->codigosNuevos();
        $usuario->forceFill(['dos_pasos_recuperacion' => $codigos])->save();
        AuditLog::registrar('dos_pasos_codigos_renovados', $usuario);

        return redirect()->to(route('profile.edit').'#dos-pasos')->with('codigos_recuperacion', $codigos);
    }

    /**
     * Códigos de recuperación tipo "K7P2-9XQM" (sin letras que se confunden: 0/O, 1/I).
     *
     * @return list<string>
     */
    protected function codigosNuevos(): array
    {
        $alfabeto = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        return array_map(function () use ($alfabeto) {
            $codigo = '';

            for ($i = 0; $i < 8; $i++) {
                $codigo .= $alfabeto[random_int(0, strlen($alfabeto) - 1)];
            }

            return Str::substr($codigo, 0, 4).'-'.Str::substr($codigo, 4);
        }, range(1, static::CODIGOS));
    }
}
