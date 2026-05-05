<?php

namespace App\Exports;

use App\Models\TimeRecord;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class TimeRecordExport
{
    private $currentMonth;
    private $currentYear;
    private $employees;
    private $departmentId;

    public function __construct($month, $year, $departmentId = null, $employees = null)
    {
        $this->currentMonth = $month;
        $this->currentYear = $year;
        $this->departmentId = $departmentId;
        $this->employees = $employees;
    }

    /**
     * Генерирует CSV данные для экспорта
     */
    public function generateCsv()
    {
        $output = [];

        $monthNames = [
            1 => 'Январь', 2 => 'Февраль', 3 => 'Март', 4 => 'Апрель',
            5 => 'Май', 6 => 'Июнь', 7 => 'Июль', 8 => 'Август',
            9 => 'Сентябрь', 10 => 'Октябрь', 11 => 'Ноябрь', 12 => 'Декабрь'
        ];

        $monthStart = Carbon::createFromDate($this->currentYear, $this->currentMonth, 1);
        $monthEnd = $monthStart->copy()->endOfMonth();
        $daysInMonth = $monthStart->daysInMonth;

        // Заголовок
        $output[] = ["Табель учета рабочего времени", $monthNames[$this->currentMonth], $this->currentYear];
        $output[] = [];

        // Легенда статусов
        $statusMap = TimeRecord::getStatusMap();
        $legendRow = ["Статусы:"];
        foreach ($statusMap as $key => $info) {
            $legendRow[] = $info['short'] . " = " . $info['label'];
        }
        $output[] = $legendRow;
        $output[] = [];

        // Заголовок таблицы
        $header = ['Сотрудник', 'Табельный номер', 'Отдел'];
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $header[] = "День " . $day;
        }
        $header[] = 'Рабочих дней';
        $header[] = 'Всего часов';
        $output[] = $header;

        // Получаем TimeRecords
        $employeeIds = $this->employees->pluck('id')->toArray();
        $timeRecords = TimeRecord::whereIn('employee_id', $employeeIds)
            ->whereDate('date', '>=', $monthStart->format('Y-m-d'))
            ->whereDate('date', '<=', $monthEnd->format('Y-m-d'))
            ->get()
            ->groupBy('employee_id')
            ->map(function($records) use ($daysInMonth) {
                $daysData = $records->keyBy(fn($r) => $r->date->day);
                return collect(range(1, $daysInMonth))
                    ->mapWithKeys(fn($day) => [$day => $daysData[$day] ?? null]);
            });

        // Данные сотрудников
        foreach ($this->employees as $employee) {
            $row = [
                $employee->last_name . ' ' . $employee->first_name . ' ' . $employee->middle_name,
                $employee->tab_number,
                $employee->department->name ?? 'Без отдела'
            ];

            $totalDays = 0;
            $totalHours = 0;

            for ($day = 1; $day <= $daysInMonth; $day++) {
                $record = $timeRecords[$employee->id][$day] ?? null;
                $hours = $record ? $record->hours : 0;
                $status = $record ? $record->status : 'present';

                $statusShort = $statusMap[$status]['short'] ?? '';
                
                $row[] = ($hours > 0) ? $statusShort . '(' . $hours . 'ч)' : '-';

                $totalHours += $hours;
                if ($hours > 0) {
                    $totalDays += 1;
                }
            }

            $row[] = $totalDays;
            $row[] = number_format($totalHours, 1, '.', '');
            $output[] = $row;
        }

        return $output;
    }

    /**
     * Экспортирует в CSV формат с табуляцией (разделитель - Tab)
     */
    public function toCsv()
    {
        $csvData = $this->generateCsv();
        $filename = 'timesheet_' . $this->currentMonth . '_' . $this->currentYear . '_' . now()->format('Y-m-d_His') . '.csv';
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        
        // UTF-8 BOM для правильного отображения в Excel
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Используем табуляцию в качестве разделителя
        foreach ($csvData as $row) {
            fputcsv($output, $row, "\t", '"');
        }

        fclose($output);
        exit;
    }

    /**
     * Экспортирует в XLSX формат с полными границами и форматированием
     */
    public function toXlsx()
    {
        $csvData = $this->generateCsv();
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Табель');

        // Определяем границы (все границы, тонкие)
        $borderStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
        ];

        // Добавляем данные в лист и применяем форматирование
        $row = 1;
        foreach ($csvData as $data) {
            $column = 1;
            foreach ($data as $value) {
                $cell = $sheet->getCellByColumnAndRow($column, $row);
                $cell->setValue($value);

                // Центрируем текст
                $cell->getStyle()->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setWrapText(true);

                // Применяем границы
                $cell->getStyle()->applyFromArray($borderStyle);

                $column++;
            }
            $row++;
        }

        // Устанавливаем оптимальную ширину столбцов
        foreach ($sheet->getColumnIterator() as $column) {
            $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
        }

        // Устанавливаем высоту строк для лучшей читаемости
        for ($i = 1; $i < $row; $i++) {
            $sheet->getRowDimension($i)->setRowHeight(25);
        }

        // Замораживаем первые 4 строки (заголовок)
        $sheet->freezePane('A5');

        // Генерируем файл
        $filename = 'timesheet_' . $this->currentMonth . '_' . $this->currentYear . '_' . now()->format('Y-m-d_His') . '.xlsx';
        $writer = new Xlsx($spreadsheet);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $writer->save('php://output');
        exit;
    }
}
