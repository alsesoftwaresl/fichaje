<?php

namespace App\Console\Commands;

use App\Models\Contacto;
use Illuminate\Console\Command;

/**
 * Borra los mensajes del formulario de contacto con más de 12 meses (plazo de conservación
 * que indica la política de privacidad; principio de limitación del plazo, art. 5.1.e RGPD).
 * Se programa una vez al mes en routes/console.php.
 */
class PurgarContactos extends Command
{
    /** Meses que se guarda un mensaje de contacto. */
    const MESES = 12;

    protected $signature = 'contactos:purgar';

    protected $description = 'Borra los mensajes de contacto con más de 12 meses';

    /**
     * Borra los antiguos y dice cuántos.
     */
    public function handle(): int
    {
        $borrados = Contacto::where('created_at', '<', now()->subMonths(static::MESES))->delete();

        $this->info("Mensajes de contacto borrados: {$borrados}");

        return self::SUCCESS;
    }
}
