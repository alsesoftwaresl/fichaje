<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FichajesExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    public function __construct(protected Collection $jornadas) {}

    public function collection(): Collection
    {
        return $this->jornadas;
    }

    public function headings(): array
    {
        return ['Empleado', 'DNI/NIE', 'Fecha', 'Entrada', 'Salida', 'Horas', 'Correcciones'];
    }

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

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
