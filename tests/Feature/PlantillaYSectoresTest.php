<?php

namespace Tests\Feature;

use App\Http\Controllers\SeoController;
use App\Models\Tarifa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class PlantillaYSectoresTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_pagina_de_la_plantilla_es_indexable_y_trae_preguntas_y_datos_estructurados(): void
    {
        $html = $this->get('/plantilla-registro-jornada')
            ->assertOk()
            ->assertHeaderMissing('X-Robots-Tag')
            ->assertSee('Plantilla gratuita de registro de jornada')
            ->assertSee('Descargar en Excel')
            ->assertSee('Descargar en PDF')
            ->assertSee('¿Hay que firmarla?')
            ->getContent();

        $this->assertSame(1, substr_count($html, '<h1'));
        $this->assertStringContainsString('"@type":"FAQPage"', $html);
        $this->assertStringContainsString('"isAccessibleForFree":true', $html);
    }

    public function test_el_pdf_se_descarga_para_el_mes_pedido_y_ignora_valores_raros(): void
    {
        $this->get('/plantilla-registro-jornada/pdf?mes=2026-11')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'attachment; filename=plantilla-registro-jornada-2026-11.pdf');

        // Un mes inválido no rompe nada: se usa el actual.
        $this->get('/plantilla-registro-jornada/pdf?mes=hola')
            ->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename=plantilla-registro-jornada-'.now()->format('Y-m').'.pdf');
    }

    public function test_el_excel_tiene_un_dia_por_fila_del_mes_y_calcula_las_horas_con_formula(): void
    {
        $this->get('/plantilla-registro-jornada/excel?mes=2026-02')
            ->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename=plantilla-registro-jornada-2026-02.xlsx');

        $hoja = $this->hojaDelExcel('2026-02');

        $this->assertSame('REGISTRO DIARIO DE JORNADA', $hoja->getCell('A1')->getValue());
        $this->assertSame('Febrero 2026', $hoja->getCell('B4')->getValue());
        $this->assertSame('Día', $hoja->getCell('A6')->getValue());

        // Febrero de 2026 tiene 28 días: del 01 (fila 7) al 28 (fila 34); la fila 35 es el total.
        $this->assertSame('01 dom', $hoja->getCell('A7')->getValue());
        $this->assertSame('28 sáb', $hoja->getCell('A34')->getValue());
        $this->assertSame('Total de horas del mes', $hoja->getCell('A35')->getValue());
        $this->assertSame('=SUM(E7:E34)', $hoja->getCell('E35')->getValue());

        // Entrada 08:00, salida 16:30 y 30 min de pausa = 8 horas.
        $hoja->getCell('B7')->setValue(8 / 24);
        $hoja->getCell('C7')->setValue(16.5 / 24);
        $hoja->getCell('D7')->setValue(30);

        $this->assertEqualsWithDelta(8.0, $hoja->getCell('E7')->getCalculatedValue(), 0.0001);
        // Sin datos, la celda queda vacía y no da error.
        $this->assertSame('', (string) $hoja->getCell('E8')->getCalculatedValue());
        $this->assertEqualsWithDelta(8.0, $hoja->getCell('E35')->getCalculatedValue(), 0.0001);
    }

    protected function hojaDelExcel(string $mes)
    {
        $respuesta = $this->get('/plantilla-registro-jornada/excel?mes='.$mes);
        $archivo = $respuesta->baseResponse->getFile();

        return IOFactory::load($archivo->getPathname())->getActiveSheet();
    }

    public function test_todas_las_paginas_de_sector_existen_son_indexables_y_traen_sus_preguntas(): void
    {
        $sectores = config('sectores');
        $this->assertGreaterThanOrEqual(4, count($sectores));

        foreach ($sectores as $slug => $datos) {
            $html = $this->get("/control-horario/{$slug}")
                ->assertOk()
                ->assertHeaderMissing('X-Robots-Tag')
                ->assertSee($datos['titulo'])
                ->assertSee('Cómo lo resuelve')
                ->assertSee('Un ejemplo')
                ->getContent();

            $this->assertSame(1, substr_count($html, '<h1'), $slug);
            $this->assertStringContainsString('"@type":"FAQPage"', $html, $slug);
            $this->assertStringContainsString('rel="canonical" href="'.route('sector.show', $slug).'"', $html, $slug);
            // Los marcadores de la tarifa se sustituyen siempre.
            $this->assertStringNotContainsString('{precio}', $html, $slug);
            $this->assertStringNotContainsString('{incluidos}', $html, $slug);
            $this->assertStringNotContainsString('{extra}', $html, $slug);
            $this->assertStringNotContainsString('{nombre}', $html, $slug);
        }
    }

    public function test_un_sector_que_no_existe_da_404_y_el_listado_enlaza_a_todos(): void
    {
        $this->get('/control-horario/no-existe')->assertNotFound();

        $listado = $this->get('/control-horario')->assertOk()->assertSee('Control horario para cada tipo de negocio');
        foreach (array_keys(config('sectores')) as $slug) {
            $listado->assertSee(route('sector.show', $slug), false);
        }
    }

    public function test_el_precio_en_las_preguntas_del_sector_es_el_de_la_tarifa(): void
    {
        $tarifa = Tarifa::actual();

        $this->get('/control-horario/hosteleria')
            ->assertOk()
            ->assertSee($tarifa->precioBaseTexto().' € al mes con '.$tarifa->empleados_incluidos.' empleados incluidos');
    }

    public function test_el_contenido_de_los_sectores_no_promete_lo_que_la_app_no_hace(): void
    {
        $texto = json_encode(config('sectores'), JSON_UNESCAPED_UNICODE);

        // Nada de cifras inventadas ni de funciones que no existen.
        $this->assertStringNotContainsString('%', $texto);
        $this->assertStringNotContainsString('geolocalización activa', $texto);
        $this->assertStringNotContainsString('app nativa', $texto);
    }

    public function test_sitemap_llms_y_portada_incluyen_la_plantilla_y_los_sectores(): void
    {
        $sitemap = $this->get('/sitemap.xml')->assertOk();
        $sitemap->assertSee(route('plantilla.index'))->assertSee(route('sectores.index'));
        foreach (array_keys(config('sectores')) as $slug) {
            $sitemap->assertSee(route('sector.show', $slug));
        }
        $this->assertNotFalse(simplexml_load_string($sitemap->getContent()));

        $this->get('/llms.txt')->assertSee(route('plantilla.index'))->assertSee(route('sectores.index'));
        $this->get('/llms-full.txt')->assertSee(route('plantilla.index'), false)->assertSee(route('sector.show', 'hosteleria'), false);

        $this->get('/')
            ->assertOk()
            ->assertSee('Para cada tipo de negocio')
            ->assertSee('Plantilla gratuita de registro de jornada')
            ->assertSee(route('sector.show', 'hosteleria'), false);
    }

    public function test_las_urls_para_avisar_a_los_buscadores_incluyen_las_paginas_nuevas(): void
    {
        $urls = SeoController::urlsPublicas();

        $this->assertContains(route('plantilla.index'), $urls);
        $this->assertContains(route('sector.show', 'talleres'), $urls);
    }
}
