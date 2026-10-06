<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Exportación de fichajes a Excel (.xlsx): una fila por jornada, con cabecera en negrita
 * y columnas ajustadas.
 */
class FichajesExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    /**
     * Recibe las jornadas ya agrupadas y filtradas.
     */
    public function __construct(protected Collection $jornadas) {}

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
        return ['Empleado', 'DNI/NIE', 'Fecha', 'Entrada', 'Salida', 'Horas', 'Correcciones'];
    }

    /**
     * Convierte una jornada en una fila de Excel.
     */
    public function map($jornada): array
    {
        $fichajeRef = $jornada['entrada'] ?? $jornada['salida'];

        return [
            $jornada['usuario']->name,
            $jornada['usuario']->dni_nie,
            $fichajeRef->fecha_hora->format('d/m/Y'),
            $jornada['entrada']?->fecha_hora->format('H:i:s') ?? '—',
            $jornada['salida']?->fecha_hora->format('H:i:s') ?? 'En curso',
            $jornada['horas'] !== null ? number_format($jornada['horas'], 2) : '—',
            $jornada['correcciones']->map(fn ($c) => $c->motivo)->implode(' | '),
        ];
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
