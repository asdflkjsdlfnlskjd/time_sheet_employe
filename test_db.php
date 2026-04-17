<?php
/**
 * Тестовый скрипт для проверки БД и системы сохранения TimeRecords
 */

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\TimeRecord;
use App\Models\Employee;
use Carbon\Carbon;

echo "=== ПРОВЕРКА БАЗЫ ДАННЫХ ===" . PHP_EOL . PHP_EOL;

// 1. Проверим таблицы
echo "1️⃣  ТАБЛИЦЫ:" . PHP_EOL;
echo "   Employees: " . Employee::count() . " записей" . PHP_EOL;
echo "   TimeRecords: " . TimeRecord::count() . " записей" . PHP_EOL . PHP_EOL;

// 2. Проверим сотрудников
echo "2️⃣  СОТРУДНИКИ:" . PHP_EOL;
$employees = Employee::with('department')->take(3)->get();
foreach ($employees as $emp) {
    echo "   - ID: {$emp->id} | {$emp->last_name} {$emp->first_name} | Отдел: {$emp->department?->name}" . PHP_EOL;
}
echo PHP_EOL;

// 3. Проверим последние записи времени
echo "3️⃣  ПОСЛЕДНИЕ ЗАПИСИ TIME_RECORDS:" . PHP_EOL;
$records = TimeRecord::with('employee')
    ->orderBy('id', 'desc')
    ->take(5)
    ->get();

if ($records->count() == 0) {
    echo "   ⚠️  Нет записей в time_records!" . PHP_EOL;
} else {
    foreach ($records as $record) {
        echo "   - ID: {$record->id} | Сотр: {$record->employee?->last_name} | Дата: {$record->date} | Часы: {$record->hours} | Статус: {$record->status}" . PHP_EOL;
    }
}
echo PHP_EOL;

// 4. Проверим записи за текущий месяц
echo "4️⃣  ЗАПИСИ ЗА ТЕКУЩИЙ МЕСЯЦ (" . now()->format('Y-m') . "):" . PHP_EOL;
$monthStart = Carbon::now()->startOfMonth();
$monthEnd = Carbon::now()->endOfMonth();
$monthRecords = TimeRecord::whereBetween('date', [$monthStart, $monthEnd])->get();
echo "   Записей за месяц: " . $monthRecords->count() . PHP_EOL;

if ($monthRecords->count() > 0) {
    $groupedByEmployee = $monthRecords->groupBy('employee_id');
    foreach ($groupedByEmployee as $empId => $records) {
        $employee = Employee::find($empId);
        echo "   • {$employee->last_name} {$employee->first_name}: {$records->count()} записей" . PHP_EOL;
    }
}
echo PHP_EOL;

// 5. Проверим структуру БД
echo "5️⃣  ПРОВЕРКА СТРУКТУРЫ ТАБЛИЦЫ time_records:" . PHP_EOL;
$schema = \Illuminate\Support\Facades\Schema::getColumns('time_records');
foreach ($schema as $column) {
    $type = $column['type_name'] ?? $column['type'] ?? 'unknown';
    echo "   - {$column['name']}: {$type}" . PHP_EOL;
}
echo PHP_EOL;

// 6. Проверим индексы
echo "6️⃣  ИНДЕКСЫ:" . PHP_EOL;
$indexes = \Illuminate\Support\Facades\DB::select("PRAGMA index_list(time_records)");
foreach ($indexes as $index) {
    echo "   - {$index->name}" . PHP_EOL;
}
echo PHP_EOL;

echo "=== ПРОВЕРКА ЗАВЕРШЕНА ===" . PHP_EOL;
