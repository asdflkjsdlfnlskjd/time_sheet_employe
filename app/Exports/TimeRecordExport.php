<?php

namespace App\Exports;

use App\Models\TimeRecord;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Collection;

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
     * Экспортирует в CSV формат с красивым форматированием для Excel
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

        foreach ($csvData as $row) {
            fputcsv($output, $row, ',', '"');
        }

        fclose($output);
        exit;
    }

    /**
     * Экспортирует в XLSX формат (через CSV)
     */
    public function toXlsx()
    {
        $this->toCsv();
    }
}
