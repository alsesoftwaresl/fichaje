<?php

namespace App\Http\Controllers;

use App\Models\Nomina;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Lado del empleado: ver sus nóminas y descargarlas. Estas rutas NO pasan por
 * el middleware "suscripcion" a propósito: que la empresa deje de pagar no
 * debe quitarle al empleado el acceso a sus propios documentos.
 */
class NominaController extends Controller
{
    /**
     * Lista las nóminas propias, de más reciente a más antigua.
     */
    public function mis(): View
    {
        $nominas = Nomina::where('user_id', Auth::id())
            ->orderByDesc('periodo')
            ->orderByDesc('id')
            ->paginate(24);

        return view('nominas.mis-nominas', compact('nominas'));
    }

    /**
     * Descarga una nómina en PDF: el empleado solo la suya, y quien gestiona nóminas las
     * de su empresa. Sin caché del navegador.
     */
    public function descargar(Nomina $nomina): StreamedResponse
    {
        $usuario = Auth::user();

        // El empleado, solo la suya; quien gestiona nóminas, las de su
        // empresa (el scope de tenant ya deja fuera las de otras empresas).
        abort_unless($nomina->user_id === $usuario->id || $usuario->puedeGestionarNominas(), 403);

        abort_unless(Storage::disk(Nomina::DISCO)->exists($nomina->ruta), 404);

        // Se anota la primera vez que la abre el propio empleado (para que
        // quien sube sepa si ya la ha visto); si la abre quien la gestiona no
        // cuenta.
        if ($nomina->user_id === $usuario->id && ! $nomina->descargada_en) {
            $nomina->update(['descargada_en' => now()]);
        }

        return Storage::disk(Nomina::DISCO)->download($nomina->ruta, $nomina->nombreDescarga(), [
            'Content-Type' => 'application/pdf',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
