<?php

namespace App\Console\Commands;

use App\Models\Empresa;
use App\Services\IntegridadRegistro;
use Illuminate\Console\Command;

/**
 * Comprueba la huella de integridad de todos los fichajes y correcciones (ver
 * IntegridadRegistro). Sirve para revisar el registro antes de entregarlo a la Inspección
 * o tras un incidente; termina con error si algo no cuadra.
 */
class VerificarRegistro extends Command
{
    protected $signature = 'registro:verificar {--empresa= : Solo esta empresa (id)}';

    protected $description = 'Comprueba que nadie ha modificado ni borrado fichajes o correcciones fuera de la aplicación';

    /**
     * Recorre las empresas (o la indicada) y muestra cuántos registros se han comprobado y
     * cuáles no cuadran.
     */
    public function handle(): int
    {
        $empresas = Empresa::query()
            ->when($this->option('empresa'), fn ($q, $id) => $q->whereKey($id))
            ->orderBy('id')
            ->get();

        $conProblemas = 0;

        foreach ($empresas as $empresa) {
            $resultado = IntegridadRegistro::verificar($empresa->id);
            $linea = "{$empresa->nombre}: {$resultado['fichajes']} fichajes y {$resultado['correcciones']} correcciones";

            if ($resultado['problemas'] === []) {
                $this->info($linea.' — correcto');

                continue;
            }

            $conProblemas++;
            $this->error($linea.' — '.count($resultado['problemas']).' con problemas');

            foreach ($resultado['problemas'] as $p) {
                $this->line("  {$p['tipo']} #{$p['id']} ({$p['fecha']}): {$p['detalle']}");
            }
        }

        return $conProblemas === 0 ? self::SUCCESS : self::FAILURE;
    }
}
