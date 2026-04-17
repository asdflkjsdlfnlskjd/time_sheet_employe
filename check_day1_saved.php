<?php
require 'vendor/autoload.php';
require 'bootstrap/app.php';

use App\Models\TimeRecord;

// Проверяем данные дня 1 в БД
echo "📊 ПРОВЕРКА ДАННЫХ ДНЯ 1 В БД\n";
echo "================================\n\n";

$day1Records = TimeRecord::where('date', '2026-04-01')->get();

echo "Всего записей для дня 1: " . count($day1Records) . "\n\n";

if (count($day1Records) > 0) {
    echo "Примеры записей:\n";
    foreach ($day1Records as $record) {
        echo sprintf(
            "Сотр %d: статус=%s, часы=%.1f, дата=%s\n",
            $record->employee_id,
            $record->status,
            $record->hours,
            $record->date
        );
    }
    echo "\n✅ Данные дня 1 сохранены в БД!\n";
} else {
    echo "❌ Данные дня 1 НЕ найдены в БД!\n";
}

// Проверяем дни 2 и 16 для сравнения
echo "\n" . str_repeat("=", 40) . "\n";
echo "Проверка дней 2 и 16 для сравнения:\n";
echo str_repeat("=", 40) . "\n\n";

$day2Records = TimeRecord::where('date', '2026-04-02')->count();
$day16Records = TimeRecord::where('date', '2026-04-16')->count();

echo "День 2 (04-02): $day2Records записей\n";
echo "День 16 (04-16): $day16Records записей\n";

// Общая статистика
$allRecords = TimeRecord::count();
echo "\nВсего записей в БД: $allRecords\n";
