<?php

namespace App\Exports;

use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Plantilla gratuita de registro diario de jornada para un mes: una fila por día, con
 * entrada, salida, pausa y las horas trabajadas calculadas con una fórmula. Se descarga
 * en blanco para rellenarla a mano o en el propio Excel.
 */
class PlantillaJornadaExport implements FromArray, WithColumnFormatting, WithColumnWidths, WithStyles, WithTitle
{
    /** Primera fila con datos de días (las anteriores son la cabecera de la plantilla). */
    const PRIMERA_FILA = 7;

    protected const DIAS = [1 => 'lun', 2 => 'mar', 3 => 'mié', 4 => 'jue', 5 => 'vie', 6 => 'sáb', 7 => 'dom'];

    protected const MESES = [1 => 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

    public function __construct(protected Carbon $mes) {}

    public static function nombreDelMes(Carbon $mes): string
    {
        return self::MESES[$mes->month].' '.$mes->year;
    }

    public function title(): string
    {
        return 'Registro de jornada';
    }

    /** Última fila de días del mes. */
    protected function ultimaFila(): int
    {
        return self::PRIMERA_FILA + $this->mes->daysInMonth - 1;
    }

    public function array(): array
    {
        $filas = [
            ['REGISTRO DIARIO DE JORNADA', '', '', '', '', ''],
            ['Empresa:', '', '', 'NIF/CIF:', '', ''],
            ['Trabajador/a:', '', '', 'DNI/NIE:', '', ''],
            ['Mes y año:', self::nombreDelMes($this->mes), '', '', '', ''],
            ['', '', '', '', '', ''],
            ['Día', 'Entrada', 'Salida', 'Pausa (min)', 'Horas trabajadas', 'Observaciones / firma'],
        ];

        for ($dia = 1; $dia <= $this->mes->daysInMonth; $dia++) {
            $fecha = $this->mes->copy()->day($dia);
            $fila = self::PRIMERA_FILA + $dia - 1;

            $filas[] = [
                sprintf('%02d %s', $dia, self::DIAS[$fecha->dayOfWeekIso]),
                null,
                null,
                null,
                // Horas = (salida - entrada) en horas, menos la pausa. Si falta algún dato, queda vacío.
                "=IF(AND(ISNUMBER(B{$fila}),ISNUMBER(C{$fila})),MAX(0,(C{$fila}-B{$fila})*24-N(D{$fila})/60),\"\")",
                null,
            ];
        }

        $ultima = $this->ultimaFila();

        $filas[] = ['Total de horas del mes', '', '', '', '=SUM(E'.self::PRIMERA_FILA.":E{$ultima})", ''];
        $filas[] = ['', '', '', '', '', ''];
        $filas[] = ['Escribe las horas como 08:30 (hh:mm). Conserva este registro cuatro años a disposición de la persona trabajadora, sus representantes y la Inspección de Trabajo.', '', '', '', '', ''];
        $filas[] = ['Plantilla orientativa de Achrono (achrono.es). No sustituye al asesoramiento laboral.', '', '', '', '', ''];

        return $filas;
    }

    public function columnWidths(): array
    {
        return ['A' => 22, 'B' => 12, 'C' => 12, 'D' => 14, 'E' => 18, 'F' => 38];
    }

    public function columnFormats(): array
    {
        return [
            'B' => NumberFormat::FORMAT_DATE_TIME4, // hh:mm
            'C' => NumberFormat::FORMAT_DATE_TIME4,
            'D' => NumberFormat::FORMAT_NUMBER,
            'E' => NumberFormat::FORMAT_NUMBER_00,
        ];
    }

    public function styles(Worksheet $hoja): array
    {
        $ultima = $this->ultimaFila();
        $total = $ultima + 1;
        $azul = '1B1B54';

        $hoja->mergeCells('A1:F1');
        $hoja->mergeCells("A".($total + 2).":F".($total + 2));
        $hoja->mergeCells("A".($total + 3).":F".($total + 3));
        $hoja->getRowDimension($total + 2)->setRowHeight(30);
        $hoja->getStyle("A".($total + 2))->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);

        // Cabecera de la tabla
        $hoja->getStyle('A6:F6')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $azul]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);
        $hoja->getRowDimension(6)->setRowHeight(30);

        // Bordes y alineación de los días
        $hoja->getStyle('A'.self::PRIMERA_FILA.":F{$total}")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
        ]);
        $hoja->getStyle('B'.self::PRIMERA_FILA.":E{$total}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Fines de semana en gris
        for ($dia = 1; $dia <= $this->mes->daysInMonth; $dia++) {
            if ($this->mes->copy()->day($dia)->isWeekend()) {
                $fila = self::PRIMERA_FILA + $dia - 1;
                $hoja->getStyle("A{$fila}:F{$fila}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('EEF2F7');
            }
        }

        $hoja->getStyle("A{$total}:F{$total}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2F8F2']],
        ]);

        // Datos del encabezado
        foreach (['A2', 'A3', 'A4', 'D2', 'D3'] as $celda) {
            $hoja->getStyle($celda)->getFont()->setBold(true);
        }
        foreach (['B2:C2', 'E2:F2', 'B3:C3', 'E3:F3'] as $rango) {
            $hoja->mergeCells($rango);
            $hoja->getStyle($rango)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
        }

        $hoja->getStyle("A".($total + 3))->getFont()->setItalic(true)->getColor()->setRGB('64748B');

        return [
            1 => ['font' => ['bold' => true, 'size' => 15, 'color' => ['rgb' => $azul]], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]],
        ];
    }
}
