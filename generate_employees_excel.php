<?php
// generate_employees_excel.php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Carbon\Carbon;

// Русские имена
$firstNames = [
    'Иван', 'Петр', 'Николай', 'Василий', 'Дмитрий',
    'Андрей', 'Алексей', 'Сергей', 'Михаил', 'Владимир',
    'Максим', 'Игорь', 'Константин', 'Александр', 'Борис',
    'Виктор', 'Геннадий', 'Евгений', 'Жан', 'Зинаида',
    'Инна', 'Юлия', 'Екатерина', 'Ольга', 'Анна',
    'Елена', 'Дарья', 'Светлана', 'Марина', 'Ирина',
    'Людмила', 'Виктория', 'Кристина', 'Маргарита', 'Беатриса',
    'Валентина', 'Наталья', 'Татьяна', 'Павел', 'Роман'
];

$lastNames = [
    'Иванов', 'Петров', 'Сидоров', 'Кузнецов', 'Орлов',
    'Соколов', 'Морозов', 'Волков', 'Новиков', 'Федоров',
    'Павлов', 'Воронов', 'Сафонов', 'Казаков', 'Лебедев',
    'Титов', 'Карпов', 'Успенский', 'Никифоров', 'Сысоев',
    'Власов', 'Бурцев', 'Громов', 'Гавриков', 'Данилов',
    'Ермолин', 'Журавлев', 'Зенин', 'Зуев', 'Иванцов',
    'Калугин', 'Киселев', 'Коваленко', 'Красильников', 'Лапин',
    'Лосев', 'Малахов', 'Мещеряков', 'Миллер', 'Молчанов'
];

$departments = [
    'IT отдел',
    'Бухгалтерия',
    'HR отдел',
    'Маркетинг',
    'Продажи',
    'Логистика',
    'Производство',
    'Склад',
    'Дизайн',
    'Аналитика',
    'Финансы',
    'Юридический отдел',
    'Закупки',
    'Обслуживание',
    'Безопасность',
    'Контроль качества',
    'Документооборот',
    'Информационные системы',
    'Инженерия',
    'Разработка',
    'Тестирование',
    'DevOps',
    'Поддержка',
    'Администрирование',
    'Развитие бизнеса',
    'Стратегия',
    'Планирование',
    'Исследования',
    'Инновации',
    'Обучение'
];

$statuses = ['present', 'absent', 'late', 'early_leave', 'vacation', 'sick_leave', 'day_off'];

// Создаем новую таблицу
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Заголовки
$headers = ['ID', 'Полное имя', 'Отдел', 'Email', 'Дата записи', 'Часы', 'Статус', 'Заметки'];
$sheet->fromArray([$headers], null, 'A1');

// Стилирование заголовка
$headerStyle = [
    'fill' => [
        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
        'startColor' => ['rgb' => '667eea'],
    ],
    'font' => [
        'bold' => true,
        'color' => ['rgb' => 'FFFFFF'],
    ],
];
$sheet->getStyle('A1:H1')->applyFromArray($headerStyle);

// Генерируем 500 сотрудников с данными о времени работы
$row = 2;
for ($i = 1; $i <= 500; $i++) {
    $firstName = $firstNames[array_rand($firstNames)];
    $lastName = $lastNames[array_rand($lastNames)];
    $fullName = "$firstName $lastName";
    $department = $departments[array_rand($departments)];
    $email = strtolower(str_replace(' ', '.', $fullName)) . $i . '@company.ru';
    
    // Случайная дата в апреле 2026
    $date = Carbon::createFromDate(2026, 4, rand(1, 30))->format('Y-m-d');
    
    // Часы (от 0 до 12)
    $hours = rand(0, 12) + (rand(0, 1) ? 0.5 : 0);
    
    // Статус
    $status = $statuses[array_rand($statuses)];
    
    // Заметки
    $notes = '';
    if ($status === 'late') {
        $notes = 'Опоздал на ' . rand(5, 60) . ' минут';
    } elseif ($status === 'early_leave') {
        $notes = 'Ушел раньше';
    } elseif ($status === 'vacation') {
        $notes = 'Отпуск';
    } elseif ($status === 'sick_leave') {
        $notes = 'Болничный лист';
    }
    
    $sheet->setCellValue("A$row", $i);
    $sheet->setCellValue("B$row", $fullName);
    $sheet->setCellValue("C$row", $department);
    $sheet->setCellValue("D$row", $email);
    $sheet->setCellValue("E$row", $date);
    $sheet->setCellValue("F$row", $hours);
    $sheet->setCellValue("G$row", $status);
    $sheet->setCellValue("H$row", $notes);
    
    $row++;
}

// Автоширина для колонок
foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'] as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Сохраняем файл
$fileName = 'public/employees_500.xlsx';
$writer = new Xlsx($spreadsheet);
$writer->save($fileName);

echo "✅ Excel файл успешно создан: $fileName\n";
echo "📊 Создано 500 сотрудников с реалистичными данными\n";
?>
