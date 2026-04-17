<?php
// Прямой запрос к БД через SQLite
$db = new PDO('sqlite:' . __DIR__ . '/database/database.sqlite');

echo "🔍 ПОЛНАЯ ДИАГНОСТИКА ПРОБЛЕМЫ С ДНЁМ 1\n";
echo str_repeat("=", 60) . "\n\n";

// 1. Проверим, есть ли день 1 в БД
echo "1️⃣  ПРОВЕРКА БД - ДЕН Ь 1\n";
$stmt = $db->query("SELECT COUNT(*) as cnt FROM time_records WHERE date = '2026-04-01'");
$count = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
echo "День 1 в БД: $count записей\n";

if ($count > 0) {
    $stmt = $db->query("SELECT employee_id, hours FROM time_records WHERE date = '2026-04-01' ORDER BY employee_id");
    echo "Данные:\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "  Сотр {$row['employee_id']}: hours={$row['hours']}\n";
    }
}

// 2. Проверим диапазон дат
echo "\n2️⃣  ПРОВЕРКА ДИАПАЗОНА ДАТ\n";
echo "Сравнение через whereBetween:\n";

// Подобный запрос к тому, что делает контроллер
$start = '2026-04-01 00:00:00';  // startOfDay
$end = '2026-04-30 23:59:59';    // endOfMonth

$sql = "SELECT COUNT(*) as cnt FROM time_records WHERE date >= '$start' AND date <= '$end' AND employee_id = 1";
$result = $db->query($sql)->fetch(PDO::FETCH_ASSOC);
echo "Контроллер найдёт для emp_id=1 с whereBetween: {$result['cnt']} записей (должно быть 30)\n";

// Прямой запрос
$sql2 = "SELECT COUNT(*) as cnt FROM time_records WHERE employee_id = 1";
$result2 = $db->query($sql2)->fetch(PDO::FETCH_ASSOC);
echo "Всего записей для emp_id=1 в БД: {$result2['cnt']}\n";

// 3. Проверим, какие даты есть
echo "\n3️⃣  КАКИЕ ДАТЫ ЕСТЬ В БД ДЛЯ СОТРУДНИКА 1\n";
$sql3 = "SELECT DISTINCT strftime('%Y-%m-%d', date) as d FROM time_records WHERE employee_id = 1 ORDER BY d";
$dates = $db->query($sql3)->fetchAll(PDO::FETCH_ASSOC);
echo "Уникальные даты: ";
echo implode(', ', array_map(fn($r) => $r['d'], $dates)) . "\n";

// 4. Если день 1 есть в БД, но не загружается
if ($count > 0) {
    echo "\n4️⃣  ПРОБЛЕМА НАЙДЕНА!\n";
    echo "День 1 ЕСТЬ в БД, но whereBetween его не находит.\n";
    echo "Возможные причины:\n";
    echo "- Тип данных в поле date некорректен\n";
    echo "- Timezone проблема\n";
    echo "- День 1 заблокирован или удалён при обновлении\n\n";
    
    // Проверим, какие данные есть для дня 1
    $day1 = $db->query("SELECT * FROM time_records WHERE date = '2026-04-01' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    echo "Пример записи дня 1:\n";
    var_dump($day1);
}
