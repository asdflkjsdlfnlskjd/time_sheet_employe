<?php
$dbPath = __DIR__ . '/database/database.sqlite';
$db = new PDO('sqlite:' . $dbPath);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "🧹 ОЧИСТКА ДУБЛИКАТОВ В БД\n\n";

// Проверяем дубликаты
$stmt = $db->query("
    SELECT employee_id, date, COUNT(*) as cnt 
    FROM time_records 
    WHERE date BETWEEN '2026-04-01' AND '2026-04-30'
    GROUP BY employee_id, date
    HAVING cnt > 1
");

$duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($duplicates) === 0) {
    echo "✅ Дубликатов не найдено\n";
} else {
    echo "❌ Найдено " . count($duplicates) . " дубликатов:\n";
    foreach ($duplicates as $dup) {
        echo "   - Сотр {$dup['employee_id']}, Дата {$dup['date']}: {$dup['cnt']} записей\n";
    }
    
    echo "\n🔄 УДАЛЯЕМ ДУБЛИКАТЫ:\n";
    
    // Удаляем все дубликаты, оставляя одну запись с максимальным ID
    $sql = "
        DELETE FROM time_records
        WHERE id NOT IN (
            SELECT MAX(id)
            FROM time_records
            WHERE date BETWEEN '2026-04-01' AND '2026-04-30'
            GROUP BY employee_id, date
        )
        AND date BETWEEN '2026-04-01' AND '2026-04-30'
    ";
    
    $db->exec($sql);
    
    // Проверяем результат
    $stmt = $db->query("SELECT COUNT(*) as cnt FROM time_records WHERE date BETWEEN '2026-04-01' AND '2026-04-30'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "   ✅ После очистки: " . $result['cnt'] . " записей\n";
    
    // Проверяем, исчезли ли дубликаты
    $stmt = $db->query("
        SELECT employee_id, date, COUNT(*) as cnt 
        FROM time_records 
        WHERE date BETWEEN '2026-04-01' AND '2026-04-30'
        GROUP BY employee_id, date
        HAVING cnt > 1
    ");
    $remaining = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($remaining) === 0) {
        echo "   ✅ Все дубликаты удалены!\n";
    } else {
        echo "   ❌ Остались дубликаты: " . count($remaining) . "\n";
    }
}

echo "\n📊 ФИНАЛЬНАЯ СТАТИСТИКА:\n";
$stmt = $db->query("
    SELECT employee_id, COUNT(*) as days_count
    FROM time_records
    WHERE date BETWEEN '2026-04-01' AND '2026-04-30'
    GROUP BY employee_id
    ORDER BY employee_id
");

$total = 0;
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "Сотр {$row['employee_id']}: {$row['days_count']} дней\n";
    $total += $row['days_count'];
}
echo "Всего записей: $total (должно быть 300 = 10 сотр × 30 дней)\n";
