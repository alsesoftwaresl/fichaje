<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Contacto;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Bandeja del super admin con los mensajes del formulario de contacto: ver
 * quién escribió, qué pidió y marcarlos como leídos.
 */
class MensajeController extends Controller
{
    /**
     * Lista los mensajes (50 por página): los no leídos y los urgentes primero.
     */
    public function index(): View
    {
        $mensajes = Contacto::query()
            ->orderByRaw('leido_en is null desc')
            ->orderByRaw("asunto = 'urgente' desc")
            ->orderByDesc('id')
            ->paginate(50);

        return view('super-admin.mensajes.index', compact('mensajes'));
    }

    /**
     * Marca un mensaje como leído o, si ya lo estaba, como no leído.
     */
    public function toggle(Contacto $contacto): RedirectResponse
    {
        $contacto->update(['leido_en' => $contacto->leido_en ? null : now()]);

        return back();
    }
}
