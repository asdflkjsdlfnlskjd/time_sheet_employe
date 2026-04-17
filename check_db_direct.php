<?php
echo "🔍 ПРЯМАЯ ПРОВЕРКА БД (без Eloquent)\n\n";

// Подключаемся к БД напрямую
$dbPath = __DIR__ . '/database/database.sqlite';
$db = new PDO('sqlite:' . $dbPath);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// 1. Проверяем employees
echo "1️⃣ Сотрудники:\n";
$stmt = $db->query("SELECT COUNT(*) as cnt FROM employees");
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "   Всего: " . $result['cnt'] . "\n";

$stmt = $db->query("SELECT id, first_name, last_name FROM employees LIMIT 3");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "   - ID {$row['id']}: {$row['first_name']} {$row['last_name']}\n";
}

// 2. Проверяем time_records
echo "\n2️⃣ Записи табеля:\n";
$stmt = $db->query("SELECT COUNT(*) as cnt FROM time_records");
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "   Всего: " . $result['cnt'] . "\n";

$stmt = $db->query("SELECT COUNT(*) as cnt FROM time_records WHERE date = '2026-04-16'");
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "   За 16 апреля: " . $result['cnt'] . "\n";

// 3. Примеры за 16 апреля
echo "\n   Примеры за 2026-04-16:\n";
$stmt = $db->query("SELECT employee_id, status, hours FROM time_records WHERE date = '2026-04-16' LIMIT 5");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "   - Сотр {$row['employee_id']}: {$row['status']} ({$row['hours']}ч)\n";
}

// 4. Проверяем, как это загружается в контроллере
echo "\n3️⃣ СИМУЛЯЦИЯ ЗАГРУЗКИ (как в контроллере):\n";
$monthStart = '2026-04-01';
$monthEnd = '2026-04-30';
$employee_ids = [1, 2, 3, 4, 5];

// Подготавливаем список ID
$placeholders = implode(',', array_fill(0, count($employee_ids), '?'));

$stmt = $db->prepare("
    SELECT employee_id, date, status, hours 
    FROM time_records 
    WHERE date BETWEEN ? AND ? 
    AND employee_id IN ($placeholders)
    ORDER BY employee_id, date
");
$stmt->execute(array_merge([$monthStart, $monthEnd], $employee_ids));

$records = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $emp_id = $row['employee_id'];
    $day = (int)substr($row['date'], 8, 2);
    
    if (!isset($records[$emp_id])) {
        $records[$emp_id] = [];
    }
    $records[$emp_id][$day] = [
        'status' => $row['status'],
        'hours' => $row['hours']
    ];
}

echo "   Загруженные данные для 5 сотрудников за апрель:\n";
foreach ($records as $emp_id => $days) {
    echo "   Сотр $emp_id: " . count($days) . " дней\n";
    if (isset($days[16])) {
        echo "     - День 16: {$days[16]['status']} ({$days[16]['hours']}ч)\n";
    } else {
        echo "     - День 16: НЕТ ДАННЫХ\n";
    }
}

echo "\n✅ Проверка завершена\n";
