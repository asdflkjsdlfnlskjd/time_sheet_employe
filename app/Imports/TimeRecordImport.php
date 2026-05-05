<?php

namespace App\Imports;

use App\Models\TimeRecord;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\IOFactory;

class TimeRecordImport
{
    private $filepath;
    private $month;
    private $year;
    private $errors = [];
    private $importedCount = 0;
    private $skippedCount = 0;

    public function __construct($filepath, $month, $year)
    {
        $this->filepath = $filepath;
        $this->month = $month;
        $this->year = $year;
    }

    /**
     * Импортирует данные из CSV файла
     */
    public function import()
    {
        // Проверяем существование файла
        if (!file_exists($this->filepath)) {
            $this->errors[] = "Файл не найден: {$this->filepath}";
            return [
                'imported' => 0,
                'skipped' => 0,
                'errors' => $this->errors
            ];
        }

        // Проверяем доступность файла для чтения
        if (!is_readable($this->filepath)) {
            $this->errors[] = "Файл не доступен для чтения: {$this->filepath}";
            return [
                'imported' => 0,
                'skipped' => 0,
                'errors' => $this->errors
            ];
        }

        $records = [];
        $row = 0;
        
        if (($handle = fopen($this->filepath, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $row++;
                
                // Пропускаем заголовки (первые 4 строки)
                if ($row <= 4) continue;

                // Ищем сотрудника по табельному номеру
                $tabNumber = $data[1] ?? null;
                if (!$tabNumber) {
                    $this->skippedCount++;
                    continue;
                }

                $employee = Employee::where('tab_number', $tabNumber)->first();
                if (!$employee) {
                    $this->errors[] = "Строка $row: Сотрудник с табельным номером '$tabNumber' не найден";
                    $this->skippedCount++;
                    continue;
                }

                // Проверка прав доступа
                $admin = Auth::user();
                if ($admin && $admin->role !== 'super_admin') {
                    $adminDeptId = $admin->employee->department_id ?? null;
                    if ($adminDeptId && $employee->department_id !== $adminDeptId) {
                        $this->errors[] = "Строка $row: Нет доступа к сотруднику '$tabNumber'";
                        $this->skippedCount++;
                        continue;
                    }
                }

                // Парсим данные по дням
                for ($day = 1; $day <= 31; $day++) {
                    $cellIndex = 3 + ($day - 1);
                    if (!isset($data[$cellIndex]) || empty($data[$cellIndex])) {
                        continue;
                    }

                    $cellData = trim($data[$cellIndex]);
                    if ($cellData === '-' || $cellData === '') {
                        continue;
                    }

                    // Парсим статус и часы (например: "ОТ(8.5ч)" или "П(8ч)")
                    // Расширенная проверка для различных форматов
                    $parseResult = $this->parseTimeData($cellData);
                    
                    if (!$parseResult) {
                        continue;
                    }

                    list($status, $hours) = $parseResult;

                    // Валидируем дату
                    if (!checkdate($this->month, $day, $this->year)) {
                        continue;
                    }

                    $records[] = [
                        'employee_id' => $employee->id,
                        'day' => $day,
                        'status' => $status,
                        'hours' => $hours,
                        'month' => $this->month,
                        'year' => $this->year
                    ];
                }
            }
            fclose($handle);
        } else {
            throw new \Exception('Не удалось открыть файл для импорта');
        }

        // Сохраняем импортированные данные
        $this->saveRecords($records);

        return [
            'imported' => $this->importedCount,
            'skipped' => $this->skippedCount,
            'errors' => $this->errors
        ];
    }

    /**
     * Парсит строку с данными времени
     */
    private function parseTimeData($cellData)
    {
        // Попытка 1: Стандартный формат "АБ(8.5ч)"
        if (preg_match('/^([А-Яа-яОВПБР]+)\(([0-9.,]+)ч\)$/', $cellData, $matches)) {
            $statusShort = $matches[1];
            $hours = floatval(str_replace(',', '.', $matches[2]));

            $status = $this->findStatusByShort($statusShort);
            return [$status, $hours];
        }

        // Попытка 2: Просто часы (например: "8.5" или "8,5")
        if (preg_match('/^([0-9.,]+)$/', $cellData)) {
            $hours = floatval(str_replace(',', '.', $cellData));
            if ($hours > 0) {
                return ['present', $hours];
            }
        }

        return null;
    }

    /**
     * Находит статус по короткому коду
     */
    private function findStatusByShort($statusShort)
    {
        $statusMap = TimeRecord::getStatusMap();
        
        foreach ($statusMap as $key => $value) {
            if ($value['short'] === $statusShort) {
                return $key;
            }
        }

        // Если не найдено, ищем по содержимому
        $search = mb_strtolower($statusShort);
        foreach ($statusMap as $key => $value) {
            if (mb_strpos(mb_strtolower($value['short']), $search) !== false) {
                return $key;
            }
        }

        return 'present'; // Значение по умолчанию
    }

    /**
     * Сохраняет записи в базу данных
     */
    private function saveRecords($records)
    {
        foreach ($records as $record) {
            try {
                $date = Carbon::createFromDate($record['year'], $record['month'], $record['day']);
                
                TimeRecord::updateOrCreate(
                    [
                        'employee_id' => $record['employee_id'],
                        'date' => $date->format('Y-m-d')
                    ],
                    [
                        'status' => $record['status'],
                        'hours' => $record['hours']
                    ]
                );

                $this->importedCount++;
            } catch (\Exception $e) {
                $this->errors[] = "Ошибка при сохранении: " . $e->getMessage();
                $this->skippedCount++;
            }
        }
    }

    /**
     * Возвращает список ошибок
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Возвращает количество импортированных записей
     */
    public function getImportedCount()
    {
        return $this->importedCount;
    }

    /**
     * Возвращает количество пропущенных записей
     */
    public function getSkippedCount()
    {
        return $this->skippedCount;
    }
}
