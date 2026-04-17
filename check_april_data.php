<?php
$dbPath = __DIR__ . '/database/database.sqlite';
$db = new PDO('sqlite:' . $dbPath);

echo "🔍 ДЕТАЛЬНАЯ ПРОВЕРКА АПРЕЛЯ 2026\n\n";

// Проверяем все дни апреля для каждого сотрудника
$stmt = $db->query("
    SELECT employee_id, 
           COUNT(*) as days_count,
           MIN(date) as first_date,
           MAX(date) as last_date,
           GROUP_CONCAT(CAST(CAST(substr(date, 9, 2) as INTEGER) as TEXT), ',') as days
    FROM time_records
    WHERE date BETWEEN '2026-04-01' AND '2026-04-30'
    GROUP BY employee_id
    ORDER BY employee_id
");

$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($data as $row) {
    echo "👤 Сотрудник {$row['employee_id']}:\n";
    echo "   Дней записей: {$row['days_count']}\n";
    echo "   Первая запись: {$row['first_date']}\n";
    echo "   Последняя запись: {$row['last_date']}\n";
    
    $days_array = array_map('intval', explode(',', $row['days']));
    sort($days_array);
    
    echo "   Дни: ";
    if (count($days_array) <= 30) {
        echo implode(', ', $days_array);
    } else {
        echo "Начало: " . implode(', ', array_slice($days_array, 0, 5)) . "... Конец: " . implode(', ', array_slice($days_array, -5));
    }
    echo "\n\n";
}

// Итоговая статистика
echo "📊 СТАТИСТИКА АПРЕЛЯ 2026:\n";
$stmt = $db->query("
    SELECT COUNT(DISTINCT employee_id) as employees,
           COUNT(*) as total_records,
           COUNT(DISTINCT date) as days_with_records,
           AVG(hours) as avg_hours,
           SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count,
           SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count
    FROM time_records
    WHERE date BETWEEN '2026-04-01' AND '2026-04-30'
");
$stats = $stmt->fetch(PDO::FETCH_ASSOC);
echo "- Сотрудников: {$stats['employees']}\n";
echo "- Всего записей: {$stats['total_records']}\n";
echo "- Дней с записями: {$stats['days_with_records']}\n";
echo "- Среднее часов: {$stats['avg_hours']}\n";
echo "- Статус present: {$stats['present_count']}\n";
echo "- Статус absent: {$stats['absent_count']}\n";
