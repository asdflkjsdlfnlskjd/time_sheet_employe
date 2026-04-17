#!/usr/bin/env php
<?php
// Используем artisan для тестирования
$commands = <<<'COMMANDS'
// Проверяем сотрудников
$employee = App\Models\Employee::first();
echo "Employee: {$employee->first_name}\n";

// Проверяем TimeRecords
$records = App\Models\TimeRecord::where('date', '2026-04-16')->get();
echo "Records for 2026-04-16: " . count($records) . "\n";
foreach ($records as $rec) {
  echo "  - Employee {$rec->employee_id}: {$rec->status} ({$rec->hours}h)\n";
}

// Пытаемся создать запись
$newRecord = App\Models\TimeRecord::updateOrCreate(
  ['employee_id' => 1, 'date' => '2026-04-16'],
  ['status' => 'present', 'hours' => 9.0]
);
echo "Created/Updated record: ID={$newRecord->id}\n";

// Проверяем что она в БД
$verify = App\Models\TimeRecord::find($newRecord->id);
echo "Verified in DB: {$verify->hours}h\n";
COMMANDS;

// Запускаем artisan tinker с этими командами
exec('cd d:\OSPanel\OSPanel\domains\localhost\time_sheet_employe && echo "' . str_replace('"', '\"', $commands) . '" | php artisan tinker');
