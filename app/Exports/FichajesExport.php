<?php

namespace App\Exports;

use App\Services\JornadasAgrupador;
use App\Services\RegistroFichajes;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Exportación de fichajes a Excel (.xlsx): una fila por jornada. Es la exportación
 * "completa" que se entrega a la Inspección o a los representantes: junto a la hora válida
 * lleva la hora registrada originalmente, las correcciones con su motivo y autor, el origen
 * de cada fichaje y su sello de integridad. El CSV usa las mismas columnas (columnas() y
 * fila()).
 */
class FichajesExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    /**
     * Recibe las jornadas ya agrupadas y filtradas.
     */
    public function __construct(protected Collection $jornadas) {}

    /**
     * Cabecera común del Excel y del CSV.
     *
     * @return list<string>
     */
    public static function columnas(): array
    {
        return [
            'Empleado', 'DNI/NIE', 'Fecha',
            'Entrada registrada', 'Entrada válida', 'Salida registrada', 'Salida válida',
            'Horas', 'Origen', 'Correcciones y notas', 'Sello entrada', 'Sello salida',
        ];
    }

    /**
     * Una jornada como fila de la exportación. "Registrada" es la hora que se guardó al
     * fichar (nunca cambia); "válida" es la que cuenta tras las correcciones.
     *
     * @return list<string|float|null>
     */
    public static function fila(array $jornada): array
    {
        $entrada = $jornada['entrada'];
        $salida = $jornada['salida'];
        $fichajeRef = $entrada ?? $salida;
        $sinSalida = RegistroFichajes::entradaOlvidada($entrada) ? 'Sin salida' : 'En curso';

        return [
            $jornada['usuario']->name,
            $jornada['usuario']->dni_nie,
            $fichajeRef->fecha_hora->format('d/m/Y'),
            $entrada?->fecha_hora->format('d/m/Y H:i:s') ?? '—',
            $entrada ? JornadasAgrupador::horaEfectiva($entrada)->format('d/m/Y H:i:s') : '—',
            $salida?->fecha_hora->format('d/m/Y H:i:s') ?? $sinSalida,
            $salida ? JornadasAgrupador::horaEfectiva($salida)->format('d/m/Y H:i:s') : $sinSalida,
            $jornada['horas'],
            collect([$entrada?->origen, $salida?->origen])->filter()->unique()->implode(' / '),
            static::correccionesYNotas($jornada),
            $entrada?->huella,
            $salida?->huella,
        ];
    }

    /**
     * Correcciones con fecha, autor y motivo, y los fichajes que añadió la empresa.
     */
    protected static function correccionesYNotas(array $jornada): string
    {
        $correcciones = $jornada['correcciones']->map(fn ($c) => sprintf(
            'Corregido a %s por %s el %s: %s',
            $c->fecha_hora_corregida->format('d/m/Y H:i'),
            $c->corregidoPor?->name ?? 'usuario #'.$c->corregido_por,
            $c->created_at?->format('d/m/Y H:i') ?? '—',
            $c->motivo
        ));

        $anadidos = collect([$jornada['entrada'], $jornada['salida']])
            ->filter(fn ($f) => $f?->origen === 'empresa')
            ->map(fn ($f) => sprintf(
                '%s añadida por %s: %s',
                ucfirst($f->tipo),
                $f->registradoPor?->name ?? 'la empresa',
                $f->motivo
            ));

        return $anadidos->concat($correcciones)->implode(' | ');
    }

    /**
     * Filas a exportar.
     */
    public function collection(): Collection
    {
        return $this->jornadas;
    }

    /**
     * Cabecera de las columnas.
     */
    public function headings(): array
    {
        return static::columnas();
    }

    /**
     * Convierte una jornada en una fila de Excel.
     */
    public function map($jornada): array
    {
        return static::fila($jornada);
    }

    /**
     * Pone la primera fila (cabecera) en negrita.
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
