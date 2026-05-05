<?php

namespace App\Imports;

use App\Models\TimeRecord;
use App\Models\Employee;
use App\Models\Department;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\IOFactory;

class TimeRecordImportNew
{
    private $filepath;
    private $month;
    private $year;
    private $errors = [];
    private $importedCount = 0;
    private $skippedCount = 0;
    private $processedEmployeesCount = 0;
    private $createdEmployeesCount = 0;
    private $employeeIndexBuilt = false;
    private $employeesByTab = [];
    private $employeesByDigits = [];
    private $employeesByName = [];
    private $departmentsById = [];
    private $departmentsByName = [];
    private $departmentsIndexBuilt = false;

    public function __construct($filepath, $month, $year)
    {
        $this->filepath = $filepath;
        $this->month = $month;
        $this->year = $year;
    }

    /**
     * Импортирует данные из CSV или XLSX файла
     */
    public function import()
    {
        // Импорт может быть объемным, снимаем ограничение 30 секунд.
        @set_time_limit(300);

        // Проверяем существование файла
        if (!file_exists($this->filepath)) {
            $this->errors[] = "Файл не найден: {$this->filepath}";
            return $this->getResult();
        }

        // Проверяем доступность файла для чтения
        if (!is_readable($this->filepath)) {
            $this->errors[] = "Файл не доступен для чтения: {$this->filepath}";
            return $this->getResult();
        }

        // Определяем тип файла по расширению
        $extension = strtolower(pathinfo($this->filepath, PATHINFO_EXTENSION));
        
        if ($extension === 'xlsx' || $extension === 'xls') {
            // Обрабатываем Excel файл
            return $this->importFromExcel();
        } else {
            // Обрабатываем CSV файл
            return $this->importFromCsv();
        }
    }

    /**
     * Импортирует данные из CSV файла
     */
    private function importFromCsv()
    {
        $records = [];
        $row = 0;
        
        if (($handle = fopen($this->filepath, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $row++;
                
                // Пропускаем заголовки (первые 5 строк)
                if ($row <= 5) continue;

                // Ищем сотрудника по табельному номеру (колонка B - индекс 1)
                $tabNumber = $data[1] ?? null;
                $normalizedTabNumber = $this->normalizeTabNumber($tabNumber);
                if (!$normalizedTabNumber) {
                    $this->skippedCount++;
                    continue;
                }

                $employee = $this->findEmployeeByTabNumber($normalizedTabNumber);
                if (!$employee) {
                    $this->errors[] = "Строка $row: Сотрудник с табельным номером '$normalizedTabNumber' не найден";
                    $this->skippedCount++;
                    continue;
                }
                $this->processedEmployeesCount++;

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
                    $cellIndex = 2 + $day;  // День 1 = индекс 3, День 2 = индекс 4, и т.д.
                    if (!isset($data[$cellIndex]) || empty($data[$cellIndex])) {
                        continue;
                    }

                    $cellData = trim($data[$cellIndex]);
                    if ($cellData === '-' || $cellData === '') {
                        continue;
                    }

                    // Парсим статус и часы (например: "ОТ(8.5ч)" или "П(8ч)")
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
            $this->errors[] = 'Не удалось открыть CSV файл для импорта';
        }

        // Сохраняем импортированные данные
        $this->saveRecords($records);

        return $this->getResult();
    }

    /**
     * Импортирует данные из XLSX файла
     */
    private function importFromExcel()
    {
        try {
            $spreadsheet = IOFactory::load($this->filepath);
            $sheet = $spreadsheet->getActiveSheet();
            $records = [];

            // Получаем все данные из листа
            $highestRow = $sheet->getHighestRow();
            $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestColumn());

            // Автоопределение структуры по заголовкам 5-й строки
            $tabColumn = null;
            $dayStartColumn = null;
            for ($col = 1; $col <= $highestColumnIndex; $col++) {
                $headerValue = mb_strtolower(trim((string)$sheet->getCellByColumnAndRow($col, 5)->getValue()));
                if ($tabColumn === null && mb_strpos($headerValue, 'табель') !== false) {
                    $tabColumn = $col;
                }
                if ($dayStartColumn === null && mb_strpos($headerValue, 'день') !== false) {
                    $dayStartColumn = $col;
                }
            }

            // Fallback на частые варианты, если заголовок поврежден
            if ($tabColumn === null) {
                $tabColumn = 4; // D
            }
            if ($dayStartColumn === null) {
                $dayStartColumn = ($tabColumn >= 4) ? ($tabColumn + 2) : 4;
            }

            for ($row = 6; $row <= $highestRow; $row++) {
                $tabNumberFromDetectedColumn = $this->normalizeTabNumber($sheet->getCellByColumnAndRow($tabColumn, $row)->getValue());
                $tabNumberFromD = $this->normalizeTabNumber($sheet->getCellByColumnAndRow(4, $row)->getValue());
                $tabNumberFromB = $this->normalizeTabNumber($sheet->getCellByColumnAndRow(2, $row)->getValue());
                $lastName = trim((string)$sheet->getCellByColumnAndRow(1, $row)->getValue());
                $firstName = trim((string)$sheet->getCellByColumnAndRow(2, $row)->getValue());
                $middleName = trim((string)$sheet->getCellByColumnAndRow(3, $row)->getValue());

                $employee = null;
                $tabNumber = null;
                $candidates = array_filter([
                    $tabNumberFromDetectedColumn,
                    $tabNumberFromD,
                    $tabNumberFromB
                ]);

                foreach ($candidates as $candidate) {
                    $employee = $this->findEmployeeByTabNumber($candidate);
                    if ($employee) {
                        $tabNumber = $candidate;
                        break;
                    }
                }

                // Fallback для старого шаблона: ищем по ФИО, если табельный не совпал
                if (!$employee) {
                    $employee = $this->findEmployeeByName($lastName, $firstName, $middleName);
                }

                if (empty($candidates)) {
                    $this->skippedCount++;
                    continue;
                }

                if (!$employee) {
                    $tabNumber = $tabNumberFromDetectedColumn ?: ($tabNumberFromD ?: $tabNumberFromB);
                    $employee = $this->createEmployeeFromRow(
                        $tabNumber,
                        $lastName,
                        $firstName,
                        $middleName,
                        $sheet->getCellByColumnAndRow(5, $row)->getValue()
                    );
                    if (!$employee) {
                        $this->errors[] = "Строка $row: Сотрудник с табельным номером '$tabNumber' не найден";
                        $this->skippedCount++;
                        continue;
                    }
                }
                $this->processedEmployeesCount++;

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

                // Парсим данные по дням (начиная с колонки F/6)
                for ($day = 1; $day <= 31; $day++) {
                    $cellCol = $dayStartColumn + ($day - 1);  // День 1 = dayStartColumn
                    $cellData = trim((string)$sheet->getCellByColumnAndRow($cellCol, $row)->getValue());

                    if (empty($cellData) || $cellData === '-') {
                        continue;
                    }

                    // Парсим статус и часы
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

            // Сохраняем импортированные данные
            $this->saveRecords($records);

            return $this->getResult();
        } catch (\Exception $e) {
            $this->errors[] = "Ошибка при чтении Excel файла: " . $e->getMessage();
            return $this->getResult();
        }
    }

    /**
     * Парсит строку с данными времени
     */
    private function parseTimeData($cellData)
    {
        // Попытка 1: Стандартный формат "АБ(8.5ч)"
        if (preg_match('/^([А-Яа-яОВПБР\-–]+)\(([0-9.,]+)ч\)$/', $cellData, $matches)) {
            $statusShort = $matches[1];
            $hours = floatval(str_replace(',', '.', $matches[2]));

            $status = $this->findStatusByShort($statusShort);
            if ($status) {
                return [$status, $hours];
            }
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
        $statusShort = strtoupper(trim($statusShort));
        
        $statusMap = [
            '—' => 'present',      // Присутствовал
            'П' => 'present',      // Присутствовал
            'ОТ' => 'absent',      // Отсутствовал
            'ОП' => 'late',        // Опоздал
            'РУ' => 'early_leave', // Ранний уход
            'ОТП' => 'vacation',   // Отпуск
            'БО' => 'sick_leave',  // Больничный
            'ВЫХ' => 'day_off',    // Выходной
        ];

        return $statusMap[$statusShort] ?? null;
    }

    /**
     * Сохраняет импортированные записи в БД
     */
    private function saveRecords($records)
    {
        if (empty($records)) {
            $this->importedCount = 0;
            return;
        }

        try {
            // Убираем дубли внутри одного импорта по ключу employee_id + day.
            // Это важно, когда сотрудник мог быть сопоставлен fallback-логикой несколько раз.
            $deduplicated = [];
            foreach ($records as $record) {
                $key = $record['employee_id'] . '_' . $record['day'];
                $deduplicated[$key] = $record;
            }
            $records = array_values($deduplicated);
            $this->importedCount = count($records);

            // Сначала удаляем существующие записи за этот месяц
            TimeRecord::whereMonth('date', $this->month)
                ->whereYear('date', $this->year)
                ->delete();

            // Сохраняем новые записи порциями
            $chunks = array_chunk($records, 500);
            foreach ($chunks as $chunk) {
                // Преобразуем day в полную дату
                $recordsWithDate = array_map(function ($record) {
                    $record['date'] = Carbon::createFromDate(
                        $this->year,
                        $this->month,
                        $record['day']
                    )->toDateString();
                    unset($record['day']);
                    unset($record['month']);
                    unset($record['year']);
                    $record['created_at'] = now();
                    $record['updated_at'] = now();
                    return $record;
                }, $chunk);

                // Используем upsert, чтобы не падать на уникальном ключе
                // (employee_id, date) при случайных дублях.
                TimeRecord::upsert(
                    $recordsWithDate,
                    ['employee_id', 'date'],
                    ['status', 'hours', 'updated_at']
                );
            }
        } catch (\Exception $e) {
            $this->errors[] = "Ошибка при сохранении записей: " . $e->getMessage();
        }
    }

    /**
     * Получить результат импорта
     */
    private function getResult()
    {
        return [
            'imported' => $this->importedCount,
            'employees_processed' => $this->processedEmployeesCount,
            'employees_created' => $this->createdEmployeesCount,
            'skipped' => $this->skippedCount,
            'errors' => $this->errors
        ];
    }

    /**
     * Нормализует табельный номер из Excel/CSV:
     * - убирает пробелы
     * - превращает "12345.0" в "12345"
     */
    private function normalizeTabNumber($value)
    {
        $tabNumber = trim((string)$value);
        if ($tabNumber === '') {
            return null;
        }

        // Excel часто сохраняет числовые значения как "12345.0"
        if (preg_match('/^\d+\.0+$/', $tabNumber)) {
            $tabNumber = preg_replace('/\.0+$/', '', $tabNumber);
        }

        return trim($tabNumber);
    }

    /**
     * Ищет сотрудника по табельному номеру с нормализацией.
     */
    private function findEmployeeByTabNumber($tabNumber)
    {
        $tabNumber = $this->normalizeTabNumber($tabNumber);
        if (!$tabNumber) {
            return null;
        }
        $this->buildEmployeeIndexes();

        if (isset($this->employeesByTab[$tabNumber])) {
            return $this->employeesByTab[$tabNumber];
        }

        $digitsKey = $this->normalizeDigits($tabNumber);
        if ($digitsKey !== null && isset($this->employeesByDigits[$digitsKey])) {
            return $this->employeesByDigits[$digitsKey];
        }

        return null;
    }

    /**
     * Ищет сотрудника по ФИО (fallback для шаблонов, где табельный мог измениться).
     */
    private function findEmployeeByName($lastName, $firstName, $middleName = '')
    {
        $this->buildEmployeeIndexes();
        $nameKey = $this->normalizeNameKey($lastName, $firstName, $middleName);
        if (!$nameKey) {
            return null;
        }

        return $this->employeesByName[$nameKey] ?? null;
    }

    /**
     * Кэширует сотрудников для быстрого сопоставления по табельному и ФИО.
     */
    private function buildEmployeeIndexes()
    {
        if ($this->employeeIndexBuilt) {
            return;
        }

        $employees = Employee::all();
        foreach ($employees as $employee) {
            $tab = $this->normalizeTabNumber($employee->tab_number);
            if ($tab !== null && !isset($this->employeesByTab[$tab])) {
                $this->employeesByTab[$tab] = $employee;
            }

            $digitsKey = $this->normalizeDigits($tab);
            if ($digitsKey !== null && !isset($this->employeesByDigits[$digitsKey])) {
                $this->employeesByDigits[$digitsKey] = $employee;
            }

            $nameKey = $this->normalizeNameKey($employee->last_name, $employee->first_name, $employee->middle_name ?? '');
            if ($nameKey && !isset($this->employeesByName[$nameKey])) {
                $this->employeesByName[$nameKey] = $employee;
            }
        }

        $this->employeeIndexBuilt = true;
    }

    /**
     * Нормализует ключ из цифр (игнорирует префиксы и ведущие нули).
     */
    private function normalizeDigits($value)
    {
        if ($value === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', (string)$value);
        if ($digits === '') {
            return null;
        }

        // Защита от ложных совпадений (например department_id = 1..10):
        // цифровой fallback применяем только к значениям, похожим на табельный номер.
        if (strlen($digits) < 4) {
            return null;
        }

        $digits = ltrim($digits, '0');
        return $digits === '' ? '0' : $digits;
    }

    /**
     * Формирует нормализованный ключ ФИО.
     */
    private function normalizeNameKey($lastName, $firstName, $middleName = '')
    {
        $parts = [
            mb_strtolower(trim((string)$lastName)),
            mb_strtolower(trim((string)$firstName)),
            mb_strtolower(trim((string)$middleName)),
        ];

        $parts = array_map(function ($part) {
            return preg_replace('/\s+/u', ' ', $part);
        }, $parts);

        if ($parts[0] === '' || $parts[1] === '') {
            return null;
        }

        return implode('|', $parts);
    }

    /**
     * Создает сотрудника из строки импорта, если он отсутствует в БД.
     */
    private function createEmployeeFromRow($tabNumber, $lastName, $firstName, $middleName, $departmentRaw)
    {
        $tabNumber = $this->normalizeTabNumber($tabNumber);
        if (!$tabNumber) {
            return null;
        }

        $lastName = trim((string)$lastName);
        $firstName = trim((string)$firstName);
        $middleName = trim((string)$middleName);
        if ($lastName === '' || $firstName === '') {
            return null;
        }

        // Не создаем дубль, если сотрудник уже появился в текущем импорте
        $existing = $this->findEmployeeByTabNumber($tabNumber);
        if ($existing) {
            return $existing;
        }

        // Если сотрудник уже есть по ФИО — не создаем дубль, а обновляем его реквизиты.
        $byName = $this->findEmployeeByName($lastName, $firstName, $middleName);
        if ($byName) {
            $departmentId = $this->resolveDepartmentId($departmentRaw);

            $payload = [];
            if ($departmentId !== null) {
                $payload['department_id'] = $departmentId;
            }

            // Обновляем tab_number только если он свободен.
            if ((string)$byName->tab_number !== $tabNumber) {
                $tabInUse = Employee::where('tab_number', $tabNumber)
                    ->where('id', '!=', $byName->id)
                    ->exists();
                if (!$tabInUse) {
                    $payload['tab_number'] = $tabNumber;
                }
            }

            if (!empty($payload)) {
                $byName->update($payload);
            }

            // Синхронизируем индексы после потенциального обновления.
            $this->employeesByTab[$this->normalizeTabNumber($byName->tab_number)] = $byName;
            $digitsKey = $this->normalizeDigits($byName->tab_number);
            if ($digitsKey !== null) {
                $this->employeesByDigits[$digitsKey] = $byName;
            }

            return $byName;
        }

        $departmentId = $this->resolveDepartmentId($departmentRaw);

        try {
            $employee = Employee::create([
                'last_name' => $lastName,
                'first_name' => $firstName,
                'middle_name' => $middleName ?: null,
                'tab_number' => $tabNumber,
                'department_id' => $departmentId,
                'is_active' => true,
            ]);
        } catch (\Throwable $e) {
            return null;
        }

        // Обновляем индексы матчинга в памяти
        $this->employeesByTab[$tabNumber] = $employee;
        $digitsKey = $this->normalizeDigits($tabNumber);
        if ($digitsKey !== null && !isset($this->employeesByDigits[$digitsKey])) {
            $this->employeesByDigits[$digitsKey] = $employee;
        }
        $nameKey = $this->normalizeNameKey($lastName, $firstName, $middleName);
        if ($nameKey && !isset($this->employeesByName[$nameKey])) {
            $this->employeesByName[$nameKey] = $employee;
        }

        $this->createdEmployeesCount++;
        return $employee;
    }

    /**
     * Определяет department_id по значению из Excel (ID или название).
     */
    private function resolveDepartmentId($departmentRaw)
    {
        $this->buildDepartmentsIndexes();
        $value = trim((string)$departmentRaw);
        if ($value === '') {
            return null;
        }

        if (ctype_digit($value)) {
            $id = (int)$value;
            return $this->departmentsById[$id] ?? null;
        }

        return $this->departmentsByName[mb_strtolower($value)] ?? null;
    }

    /**
     * Кэш отделов для быстрого сопоставления при импорте.
     */
    private function buildDepartmentsIndexes()
    {
        if ($this->departmentsIndexBuilt) {
            return;
        }

        foreach (Department::all(['id', 'name']) as $department) {
            $this->departmentsById[(int)$department->id] = (int)$department->id;
            $this->departmentsByName[mb_strtolower(trim((string)$department->name))] = (int)$department->id;
        }

        $this->departmentsIndexBuilt = true;
    }
}
