<?php
require 'vendor/autoload.php';
require 'bootstrap/app.php';

use App\Models\Employee;
use App\Models\TimeRecord;
use Carbon\Carbon;

echo "📊 ПРОВЕРКА КАК КОНТРОЛЛЕР ЗАГРУЖАЕТ ДАННЫЕ\n";
echo "=" . str_repeat("=", 50) . "\n\n";

$currentMonth = 4;
$currentYear = 2026;
$daysInMonth = 30;

// Получаем сотрудников (как в контроллере)
$employees = Employee::orderBy('last_name')
    ->orderBy('first_name')
    ->limit(10)  // первые 10
    ->get();

$employeeIds = $employees->pluck('id')->toArray();

echo "Сотрудники: " . implode(', ', $employeeIds) . "\n\n";

// Загружаем TimeRecords (как в контроллере)
$monthStart = Carbon::createFromDate($currentYear, $currentMonth, 1);
$monthEnd = $monthStart->copy()->endOfMonth();

echo "Диапазон дат: {$monthStart->format('Y-m-d')} to {$monthEnd->format('Y-m-d')}\n\n";

$timeRecords = TimeRecord::whereBetween('date', [$monthStart, $monthEnd])
    ->whereIn('employee_id', $employeeIds)
    ->get()
    ->groupBy('employee_id')
    ->map(fn($records) => $records->keyBy(fn($r) => $r->date->day));

echo "Структура timeRecords:\n";
foreach ($timeRecords as $empId => $daysData) {
    echo "  Сотр $empId:\n";
    
    // Проверяем день 1
    if (isset($daysData[1])) {
        $day1 = $daysData[1];
        echo sprintf(
            "    День 1 (ключ=1): hours=%s, status=%s\n",
            $day1->hours,
            $day1->status
        );
    } else {
        echo "    День 1: НЕ НАЙДЕН!\n";
    }
    
    // Проверяем есть ли ключ "1" как строка
    if (isset($daysData['1'])) {
        $day1str = $daysData['1'];
        echo sprintf(
            "    День 1 (ключ='1'): hours=%s, status=%s\n",
            $day1str->hours,
            $day1str->status
        );
    }
    
    echo "    Ключи в массиве: " . implode(', ', array_keys($daysData->toArray())) . "\n";
    
    break; // Только первого сотрудника
}
