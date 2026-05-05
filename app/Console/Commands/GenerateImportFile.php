<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class GenerateImportFile extends Command
{
    protected $signature = 'import:generate';
    protected $description = 'Generate XLSX import file with all employees';

    public function handle()
    {
        $this->info('📊 Generating XLSX import file...');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Табель');

        // Styles
        $borderStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
        ];

        $headerFill = [
            'font' => [
                'bold' => true,
            ],
        ];

        $legendFill = [
            'font' => [
                'bold' => false,
            ],
        ];

        $currentMonth = now()->month;
        $currentYear = now()->year;
        $monthNames = [
            1 => 'Январь', 2 => 'Февраль', 3 => 'Март', 4 => 'Апрель',
            5 => 'Май', 6 => 'Июнь', 7 => 'Июль', 8 => 'Август',
            9 => 'Сентябрь', 10 => 'Октябрь', 11 => 'Ноябрь', 12 => 'Декабрь'
        ];

        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $currentMonth, $currentYear);

        // Row 1: Title
        $sheet->setCellValue('A1', 'Табель учета рабочего времени');
        $sheet->mergeCells('A1:E1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        // Row 2: Month and year
        $sheet->setCellValue('A2', $monthNames[$currentMonth] . ' ' . $currentYear);
        $sheet->mergeCells('A2:E2');
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);

        // Row 4: Legend
        $sheet->setCellValue('A4', 'Статусы:');
        $sheet->getStyle('A4')->applyFromArray($legendFill);

        $statuses = [
            '— = Присутствует',
            'ОТ = Отсутствовал',
            'ОП = Опоздал',
            'РУ = Ранний уход',
            'ОТП = Отпуск',
            'БО = Больничный',
            'ВЫХ = Выходной'
        ];

        $col = 2;
        foreach ($statuses as $status) {
            $sheet->setCellValue($sheet->getCellByColumnAndRow($col, 4)->getCoordinate(), $status);
            $sheet->getStyle($sheet->getCellByColumnAndRow($col, 4)->getCoordinate())->applyFromArray($legendFill);
            $col++;
        }

        // Row 5: Table headers
        $sheet->setCellValue('A5', 'Фамилия');
        $sheet->setCellValue('B5', 'Имя');
        $sheet->setCellValue('C5', 'Отчество');
        $sheet->setCellValue('D5', 'Табельный №');
        $sheet->setCellValue('E5', 'Отдел');

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $sheet->setCellValue($sheet->getCellByColumnAndRow($day + 5, 5)->getCoordinate(), "День " . $day);
        }

        // Apply header styles
        for ($c = 1; $c <= $daysInMonth + 5; $c++) {
            $cell = $sheet->getCellByColumnAndRow($c, 5);
            $cell->getStyle()->applyFromArray($headerFill);
            $cell->getStyle()->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER)
                ->setWrapText(true);
            $cell->getStyle()->applyFromArray($borderStyle);
        }

        // Get employees
        $employees = DB::table('employees')->orderBy('last_name')->get();

        $this->info('Adding ' . count($employees) . ' employees...');

        $row = 6;
        foreach ($employees as $emp) {
            $sheet->setCellValue('A' . $row, $emp->last_name);
            $sheet->setCellValue('B' . $row, $emp->first_name);
            $sheet->setCellValue('C' . $row, $emp->middle_name);
            $sheet->setCellValue('D' . $row, $emp->tab_number);
            $sheet->setCellValue('E' . $row, $emp->department_id);

            // Get time records
            $records = DB::table('time_records')
                ->where('employee_id', $emp->id)
                ->whereMonth('date', $currentMonth)
                ->whereYear('date', $currentYear)
                ->get()
                ->keyBy(function ($item) {
                    return date('d', strtotime($item->date));
                });

            $statusMap = [
                'present' => '—',
                'absent' => 'ОТ',
                'late' => 'ОП',
                'early_leave' => 'РУ',
                'vacation' => 'ОТП',
                'sick_leave' => 'БО',
                'day_off' => 'ВЫХ',
            ];

            // Fill days
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $cellCol = $day + 5;
                $record = $records->get(str_pad($day, 2, '0', STR_PAD_LEFT));

                if ($record) {
                    $statusShort = $statusMap[$record->status] ?? '—';
                    $hours = $record->hours > 0 ? $record->hours : 0;
                    $cellValue = $statusShort . '(' . $hours . 'ч)';
                } else {
                    $cellValue = '';
                }

                $sheet->setCellValue($sheet->getCellByColumnAndRow($cellCol, $row)->getCoordinate(), $cellValue);
            }

            // Apply borders
            for ($c = 1; $c <= $daysInMonth + 5; $c++) {
                $sheet->getCellByColumnAndRow($c, $row)->getStyle()->applyFromArray($borderStyle);
                $sheet->getCellByColumnAndRow($c, $row)->getStyle()->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);
            }

            $row++;

            if ($row % 100 === 5) {
                $this->line("  ✓ Added " . ($row - 6) . " employees");
            }
        }

        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(15);
        $sheet->getColumnDimension('B')->setWidth(12);
        $sheet->getColumnDimension('C')->setWidth(15);
        $sheet->getColumnDimension('D')->setWidth(12);
        $sheet->getColumnDimension('E')->setWidth(12);

        for ($i = 0; $i < $daysInMonth; $i++) {
            $sheet->getColumnDimensionByColumn($i + 6)->setWidth(10);
        }

        $sheet->freezePane('A6');

        // Save file
        $filename = 'import_' . $currentMonth . '_' . $currentYear . '.xlsx';
        $filepath = storage_path('app/imports/' . $filename);

        @mkdir(dirname($filepath), 0755, true);

        $writer = new Xlsx($spreadsheet);
        $writer->save($filepath);

        $this->info("\n✅ File created!");
        $this->line("📥 Filename: $filename");
        $this->line("📍 Location: storage/app/imports/");
        $this->line("\n You can now download and import this file!");
    }
}
