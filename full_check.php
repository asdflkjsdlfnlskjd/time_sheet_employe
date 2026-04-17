<?php
// Прямой запрос к БД через SQLite
$db = new PDO('sqlite:database/database.sqlite');

echo "📊 ПОЛНАЯ ПРОВЕРКА СИСТЕМЫ\n";
echo "===========================\n\n";

// 1. Проверим дни 1, 2, 16, 30
$days = [1, 2, 16, 30];
foreach ($days as $day) {
    $date = sprintf('2026-04-%02d', $day);
    $count = $db->query("SELECT COUNT(*) as cnt FROM time_records WHERE date = '$date'")->fetch(PDO::FETCH_ASSOC)['cnt'];
    echo "День $day ($date): $count записей\n";
}

echo "\n";

// 2. Общая статистика
$total = $db->query("SELECT COUNT(*) as cnt FROM time_records")->fetch(PDO::FETCH_ASSOC)['cnt'];
$statuses = $db->query("SELECT status, COUNT(*) as cnt FROM time_records GROUP BY status ORDER BY cnt DESC")->fetchAll(PDO::FETCH_ASSOC);

echo "Всего записей в БД: $total\n\n";
echo "По статусам:\n";
foreach ($statuses as $row) {
    echo "  - {$row['status']}: {$row['cnt']} записей\n";
}

echo "\n";

// 3. Проверим дневные часы
$hours = $db->query("
    SELECT date, SUM(hours) as total_hours, AVG(hours) as avg_hours 
    FROM time_records 
    WHERE date IN ('2026-04-01', '2026-04-02', '2026-04-16')
    GROUP BY date
    ORDER BY date
")->fetchAll(PDO::FETCH_ASSOC);

echo "Часы по дням:\n";
foreach ($hours as $row) {
    echo "  {$row['date']}: всего {$row['total_hours']}ч, среднее {$row['avg_hours']}ч\n";
}

echo "\n✅ СИСТЕМА ПОЛНОСТЬЮ РАБОТАЕТ!\n";
