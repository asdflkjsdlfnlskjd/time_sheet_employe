<?php
$dbPath = __DIR__ . '/database/database.sqlite';
$db = new PDO('sqlite:' . $dbPath);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "🔄 БЫСТРАЯ ОЧИСТКА БД\n\n";

// Удаляем все записи за апрель
$db->exec("DELETE FROM time_records WHERE date BETWEEN '2026-04-01' AND '2026-04-30'");

// Вставляем простые правильные данные через один SQL запрос
$sql = <<<SQL
INSERT INTO time_records (employee_id, date, status, hours, created_at, updated_at)
SELECT 
    e.id,
    '2026-04-' || CASE WHEN d < 10 THEN '0' || d ELSE d END,
    'present',
    8.0,
    datetime('now'),
    datetime('now')
FROM (
    SELECT id FROM employees
) e
CROSS JOIN (
    SELECT 1 as d UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5
    UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10
    UNION SELECT 11 UNION SELECT 12 UNION SELECT 13 UNION SELECT 14 UNION SELECT 15
    UNION SELECT 16 UNION SELECT 17 UNION SELECT 18 UNION SELECT 19 UNION SELECT 20
    UNION SELECT 21 UNION SELECT 22 UNION SELECT 23 UNION SELECT 24 UNION SELECT 25
    UNION SELECT 26 UNION SELECT 27 UNION SELECT 28 UNION SELECT 29 UNION SELECT 30
) d;
SQL;

$db->exec($sql);

// Проверяем
$stmt = $db->query("SELECT COUNT(*) as cnt, COUNT(DISTINCT date) as days FROM time_records WHERE date BETWEEN '2026-04-01' AND '2026-04-30'");
$result = $stmt->fetch(PDO::FETCH_ASSOC);

echo "✅ Создано " . $result['cnt'] . " записей за " . $result['days'] . " дней\n";
echo "Ожидаемо: 300 записей за 30 дней\n";
