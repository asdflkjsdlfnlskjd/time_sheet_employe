<?php
$dbPath = __DIR__ . '/database/database.sqlite';
$db = new PDO('sqlite:' . $dbPath);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "🧹 ОЧИСТКА И СОЗДАНИЕ ПРАВИЛЬНЫХ ДАННЫХ\n\n";

// 1. Удаляем все записи за апрель 2026
echo "1️⃣  Удаление старых записей за апрель...\n";
$stmt = $db->prepare("DELETE FROM time_records WHERE date BETWEEN ? AND ?");
$stmt->execute(['2026-04-01', '2026-04-30']);
echo "   ✅ Удалено\n\n";

// 2. Создаем правильные данные для каждого дня апреля
echo "2️⃣  Создание новых записей (1 запись на сотрудника на каждый день)...\n";

$records = [];
for ($emp = 1; $emp <= 10; $emp++) {
    for ($day = 1; $day <= 30; $day++) {
        $date = sprintf('2026-04-%02d', $day);
        
        // Случайный статус (в основном present, но иногда absent)
        $statuses = ['present', 'present', 'present', 'present', 'absent', 'sick_leave'];
        $status = $statuses[array_rand($statuses)];
        
        // Часы
        $hours = ($status === 'present') ? 8 + rand(-2, 2) : 0;
        if ($hours < 0) $hours = 0;
        if ($hours > 24) $hours = 24;
        
        $records[] = [
            'employee_id' => $emp,
            'date' => $date,
            'status' => $status,
            'hours' => (float)$hours,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
    }
}

echo "   Подготовлено " . count($records) . " записей\n";

// 3. Вставляем все за один раз
$sql = "INSERT INTO time_records (employee_id, date, status, hours, created_at, updated_at) 
        VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $db->prepare($sql);

$inserted = 0;
foreach ($records as $r) {
    $stmt->execute([
        $r['employee_id'],
        $r['date'],
        $r['status'],
        $r['hours'],
        $r['created_at'],
        $r['updated_at']
    ]);
    $inserted++;
}

echo "   ✅ Вставлено " . $inserted . " записей\n\n";

// 4. Проверяем
echo "3️⃣  ПРОВЕРКА:\n";
$stmt = $db->query("
    SELECT employee_id, COUNT(*) as cnt,
           COUNT(DISTINCT date) as days_cnt
    FROM time_records
    WHERE date BETWEEN '2026-04-01' AND '2026-04-30'
    GROUP BY employee_id
");

$total = 0;
$days_total = 0;
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "   Сотр {$row['employee_id']}: {$row['cnt']} записей, {$row['days_cnt']} дней\n";
    $total += $row['cnt'];
    $days_total += $row['days_cnt'];
}

echo "\n   Всего записей: $total (должно быть 300)\n";
echo "   ✅ Данные готовы к использованию!\n";

// 5. Примеры
echo "\n4️⃣  ПРИМЕРЫ ДАННЫХ:\n";
$stmt = $db->query("SELECT date, employee_id, status, hours FROM time_records WHERE employee_id = 1 ORDER BY date LIMIT 10");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "   {$row['date']}: {$row['status']} ({$row['hours']}ч)\n";
}
