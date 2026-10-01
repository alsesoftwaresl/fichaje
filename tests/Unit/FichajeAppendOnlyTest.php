<?php

namespace Tests\Unit;

use App\Models\Empresa;
use App\Models\Fichaje;
use App\Models\User;
use App\Support\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FichajeAppendOnlyTest extends TestCase
{
    use RefreshDatabase;

    protected function crearFichaje(): Fichaje
    {
        $empresa = Empresa::factory()->create();
        $empleado = User::factory()->for($empresa)->create();

        Tenant::set($empresa->id);

        return Fichaje::create([
            'user_id' => $empleado->id,
            'tipo' => 'entrada',
            'fecha_hora' => now(),
            'origen' => 'web',
        ]);
    }

    public function test_no_se_puede_actualizar_un_fichaje_existente(): void
    {
        $fichaje = $this->crearFichaje();

        $this->expectException(\RuntimeException::class);

        $fichaje->tipo = 'salida';
        $fichaje->save();
    }

    public function test_no_se_puede_hacer_update_masivo_sobre_un_fichaje(): void
    {
        $fichaje = $this->crearFichaje();

        $this->expectException(\RuntimeException::class);

        Fichaje::whereKey($fichaje->id)->first()->update(['tipo' => 'salida']);
    }

    public function test_no_se_puede_borrar_un_fichaje(): void
    {
        $fichaje = $this->crearFichaje();

        $this->expectException(\RuntimeException::class);

        $fichaje->delete();
    }
}
