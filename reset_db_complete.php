<?php
$dbPath = __DIR__ . '/database/database.sqlite';
$db = new PDO('sqlite:' . $dbPath);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "🧹 ПОЛНАЯ ОЧИСТКА И ПЕРЕСОЗДАНИЕ\n\n";

// 1. Удаляем ВСЕ записи полностью
$db->exec("DELETE FROM time_records");
$db->exec("VACUUM");  // Очищаем место
echo "✅ БД полностью очищена\n";

// 2. Создаем чистые данные - один простой INSERT за раз
$sql = "INSERT INTO time_records (employee_id, date, status, hours, created_at, updated_at) 
        VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $db->prepare($sql);

$now = date('Y-m-d H:i:s');
$created = 0;

// Для каждого сотрудника
for ($emp = 1; $emp <= 10; $emp++) {
    // Для каждого дня апреля
    for ($day = 1; $day <= 30; $day++) {
        $date = sprintf('2026-04-%02d', $day);
        $stmt->execute([
            $emp,                    // employee_id
            $date,                  // date
            'present',              // status
            8.0,                    // hours
            $now,                   // created_at
            $now                    // updated_at
        ]);
        $created++;
    }
}

echo "✅ Вставлено $created записей\n\n";

// 3. Проверяем
$stmt = $db->query("SELECT COUNT(*) as cnt FROM time_records");
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "ПРОВЕРКА:\n";
echo "- Всего записей: " . $result['cnt'] . " (должно быть 300)\n";

// День 1
$stmt = $db->query("SELECT COUNT(*) as cnt FROM time_records WHERE date = '2026-04-01'");
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "- День 1: " . $result['cnt'] . " записей\n";

// День 16
$stmt = $db->query("SELECT COUNT(*) as cnt FROM time_records WHERE date = '2026-04-16'");
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "- День 16: " . $result['cnt'] . " записей\n";

// Выборочно проверяем день 1
echo "\nПримеры для дня 1:\n";
$stmt = $db->query("SELECT employee_id, status, hours FROM time_records WHERE date = '2026-04-01' LIMIT 5");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "  Сотр {$row['employee_id']}: {$row['status']} ({$row['hours']}ч)\n";
}

echo "\n✅ ГОТОВО!\n";
