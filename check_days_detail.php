<?php
$dbPath = __DIR__ . '/database/database.sqlite';
$db = new PDO('sqlite:' . $dbPath);

echo "🔍 ДЕТАЛЬНАЯ ПРОВЕРКА ДНЕЙ АПРЕЛЯ\n\n";

// Смотрим все дни для первого сотрудника
$stmt = $db->query("
    SELECT DISTINCT date, COUNT(*) as cnt 
    FROM time_records
    WHERE employee_id = 1 AND date BETWEEN '2026-04-01' AND '2026-04-30'
    ORDER BY date
");

$dates = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Записи для сотрудника 1 в апреле:\n";
foreach ($dates as $d) {
    echo "  {$d['date']}: {$d['cnt']} запись(и)\n";
}

echo "\nВсего дней: " . count($dates) . "\n";

// Извлекаем номера дней
$days = array_map(function($d) {
    return (int)substr($d['date'], 8, 2);
}, $dates);

echo "Номера дней: " . implode(', ', $days) . "\n";

// Проверяем, какие дни пропущены
echo "\nПропущенные дни апреля:\n";
$missing = [];
for ($i = 1; $i <= 30; $i++) {
    if (!in_array($i, $days)) {
        $missing[] = $i;
    }
}

if (count($missing) === 0) {
    echo "Нет пропущенных дней\n";
} else {
    echo "Дни: " . implode(', ', $missing) . "\n";
}

// Проверяем лишние дни
echo "\nЛишние дни (не из апреля):\n";
$extra = [];
for ($i = 1; $i <= 31; $i++) {
    if (!in_array($i, $days) && $i > 30) {
        $extra[] = $i;
    }
}

if (count($extra) === 0) {
    echo "Нет лишних дней\n";
} else {
    echo "Дни: " . implode(', ', $extra) . "\n";
}

// Если есть дни > 30, найдем их
if (count($dates) > 30) {
    echo "\n⚠️ НАЙДЕНЫ ЗАПИСИ ВНЕ ДИАПАЗОНА 1-30:\n";
    $stmt = $db->query("
        SELECT date, COUNT(*) as cnt 
        FROM time_records
        WHERE employee_id = 1 AND date BETWEEN '2026-04-01' AND '2026-04-30'
        GROUP BY date
        ORDER BY date
    ");
    
    $all_dates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($all_dates as $d) {
        $day = (int)substr($d['date'], 8, 2);
        if ($day < 1 || $day > 30) {
            echo "  День $day: {$d['date']}\n";
        }
    }
}
