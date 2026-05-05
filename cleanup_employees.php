<?php
// Скрипт для удаления всех сотрудников и их временных записей

require 'vendor/autoload.php';
require 'bootstrap/app.php';

use App\Models\Employee;
use App\Models\TimeRecord;

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle($request = Illuminate\Http\Request::capture());

// Удаляем все временные записи
TimeRecord::truncate();
echo "✓ Удалены все временные записи\n";

// Удаляем всех сотрудников
Employee::truncate();
echo "✓ Удалены все сотрудники\n";

echo "\nБаза данных очищена!\n";
