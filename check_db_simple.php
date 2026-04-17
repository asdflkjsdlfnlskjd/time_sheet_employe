<?php
$db = new PDO('sqlite:' . __DIR__ . '/database/database.sqlite');
$stmt = $db->query("SELECT DISTINCT date FROM time_records WHERE employee_id = 1 ORDER BY date");
$dates = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "Дни для сотр 1: " . implode(', ', array_map(function($d) { return substr($d, 8); }, $dates)) . PHP_EOL;
echo "Всего: " . count($dates) . " дней" . PHP_EOL;

// Проверяем формат даты
if (count($dates) > 0) {
    echo "Первая дата: {$dates[0]}" . PHP_EOL;
    echo "Последняя дата: {$dates[count($dates)-1]}" . PHP_EOL;
}
