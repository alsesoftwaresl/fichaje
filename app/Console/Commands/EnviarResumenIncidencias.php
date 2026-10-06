<?php

namespace App\Console\Commands;

use App\Models\Empresa;
use App\Notifications\ResumenIncidencias;
use App\Services\IncidenciasCalculador;
use App\Support\Avisos;
use App\Support\Tenant;
use Illuminate\Console\Command;

/**
 * Manda a los admins de cada empresa el resumen de incidencias de hoy. Se
 * programa en routes/console.php (laborables a las 11:00) y solo escribe a las
 * empresas con acceso vigente (suscripción o licencia) que tengan alguna
 * incidencia.
 */
class EnviarResumenIncidencias extends Command
{
    protected $signature = 'incidencias:resumen';

    protected $description = 'Envía por correo a los admins el resumen de incidencias de hoy';

    public function handle(): int
    {
        $enviados = 0;

        foreach (Empresa::where('activa', true)->get() as $empresa) {
            if (! $empresa->tieneAcceso()) {
                continue;
            }

            $incidencias = IncidenciasCalculador::delDia($empresa->id);

            if ($incidencias->isEmpty()) {
                continue;
            }

            $admins = Avisos::adminsDeEmpresa($empresa->id);
            Avisos::enviar($admins, new ResumenIncidencias($incidencias));
            $enviados += $admins->count();
        }

        // delDia() fija la empresa a mano; se limpia para no dejarla puesta.
        Tenant::clear();

        $this->info("Resumen enviado a {$enviados} admin(s).");

        return self::SUCCESS;
    }
}
