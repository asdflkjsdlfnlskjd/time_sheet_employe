<?php
$db = new PDO('sqlite:' . __DIR__ . '/database/database.sqlite');

echo "📋 ПРОВЕРКА ДАННЫХ ДЛЯ ДНЯ 1\n\n";

// День 1
$stmt = $db->query("SELECT COUNT(*) as cnt FROM time_records WHERE date = '2026-04-01'");
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Записи для 2026-04-01: " . $result['cnt'] . " шт\n";

if ($result['cnt'] > 0) {
    $stmt = $db->query("SELECT employee_id, status, hours FROM time_records WHERE date = '2026-04-01' ORDER BY employee_id");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "  Сотр {$row['employee_id']}: {$row['status']} ({$row['hours']}ч)\n";
    }
} else {
    echo "  ❌ НЕТ ДАННЫХ!\n";
}

// День 2 для сравнения
echo "\nДень 2 для сравнения:\n";
$stmt = $db->query("SELECT COUNT(*) as cnt FROM time_records WHERE date = '2026-04-02'");
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Записи для 2026-04-02: " . $result['cnt'] . " шт\n";

// День 16 (сегодня)
echo "\nДень 16 (сегодня):\n";
$stmt = $db->query("SELECT COUNT(*) as cnt FROM time_records WHERE date = '2026-04-16'");
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Записи для 2026-04-16: " . $result['cnt'] . " шт\n";
