<?php
$dbPath = __DIR__ . '/database/database.sqlite';
$db = new PDO('sqlite:' . $dbPath);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "✨ ФИНАЛЬНАЯ ОЧИСТКА И ПРАВИЛЬНОЕ СОЗДАНИЕ ДАННЫХ\n\n";

// 1. Удаляем ВСЕ старые записи
$db->exec("DELETE FROM time_records");
echo "✅ Все старые записи удалены\n";

// 2. Создаем правильные данные
$sql = "INSERT INTO time_records (employee_id, date, status, hours, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $db->prepare($sql);

$now = date('Y-m-d H:i:s');
$count = 0;

for ($emp_id = 1; $emp_id <= 10; $emp_id++) {
    for ($day = 1; $day <= 30; $day++) {
        $date = sprintf('2026-04-%02d', $day);
        $stmt->execute([$emp_id, $date, 'present', 8.0, $now, $now]);
        $count++;
    }
}

echo "✅ Вставлено " . $count . " записей\n\n";

// 3. Проверяем
$stmt = $db->query("SELECT COUNT(*) as cnt FROM time_records");
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Проверка БД:\n";
echo "- Всего записей: " . $result['cnt'] . " (ожидается 300)\n";

$stmt = $db->query("SELECT COUNT(DISTINCT employee_id) as cnt FROM time_records");
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "- Сотрудников: " . $result['cnt'] . " (ожидается 10)\n";

$stmt = $db->query("SELECT COUNT(DISTINCT date) as cnt FROM time_records");
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "- Дней в апреле: " . $result['cnt'] . " (ожидается 30)\n";

// Проверяем дни
$stmt = $db->query("SELECT DISTINCT date FROM time_records WHERE employee_id = 1 ORDER BY date");
$dates = $stmt->fetchAll(PDO::FETCH_COLUMN);
$day_nums = array_map(function($d) { return (int)substr($d, 8, 2); }, $dates);
sort($day_nums);
echo "- Дни для сотр 1: " . implode(', ', $day_nums) . "\n";

if ($result['cnt'] == 30 && count($dates) == 30 && max($day_nums) == 30 && min($day_nums) == 1) {
    echo "\n✅ ВСЕ ДАННЫЕ ПРАВИЛЬНЫЕ!\n";
    echo "✅ БД готова к использованию\n";
} else {
    echo "\n❌ ЧТО-ТО НЕ ТАК\n";
}
