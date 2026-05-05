<?php
// Скрипт для генерации 500 сотрудников и временных записей в XLSX

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\PatternFill;

// Русские фамилии, имена, отчества
$lastNames = [
    'Иванов', 'Сидоров', 'Петров', 'Смирнов', 'Кузнецов', 'Волков', 'Соколов', 'Лебедев',
    'Козлов', 'Новиков', 'Морозов', 'Павлов', 'Федоров', 'Александров', 'Михайлов', 'Орлов',
    'Ковалев', 'Логинов', 'Сергеев', 'Никитин', 'Нестеров', 'Медведев', 'Фёдоров', 'Мальцев',
    'Прямухин', 'Соснин', 'Карпов', 'Гаврилов', 'Тарасов', 'Зубков', 'Бобров', 'Дубов',
    'Казаков', 'Киселев', 'Комаров', 'Костецкий', 'Кравцов', 'Лаврентьев', 'Панов', 'Полянский',
    'Растворов', 'Романов', 'Ростов', 'Ряжин', 'Сафонов', 'Селезнев', 'Сивцев', 'Сизов',
    'Синицын', 'Сисев', 'Славин', 'Сластёнов', 'Сменов', 'Смоляков', 'Сороков', 'Сорокин',
    'Стрелков', 'Сухарев', 'Талашкин', 'Теличенко', 'Тихонов', 'Третьяков', 'Трошин', 'Туманов',
    'Турок', 'Удалов', 'Успенский', 'Фалялеев', 'Фартушняк', 'Федосеев', 'Филатов', 'Флоров',
    'Фоменко', 'Фурс', 'Хмара', 'Ходоров', 'Холодный', 'Холопов', 'Цыганов', 'Чебышев'
];

$firstNames = [
    'Александр', 'Алексей', 'Анатолий', 'Андрей', 'Антон', 'Аркадий', 'Артём', 'Артур',
    'Афанасий', 'Борис', 'Валентин', 'Валерий', 'Василий', 'Виктор', 'Виталий', 'Владимир',
    'Вячеслав', 'Геннадий', 'Георгий', 'Герасим', 'Герман', 'Глеб', 'Гордей', 'Григорий',
    'Даниил', 'Данила', 'Денис', 'Дмитрий', 'Дорофей', 'Евгений', 'Евстафий', 'Егор',
    'Елизар', 'Еремей', 'Ермолай', 'Ерофей', 'Ефим', 'Ефремов', 'Захар', 'Зиновий',
    'Зосима', 'Иван', 'Игнатий', 'Игорь', 'Илия', 'Иосиф', 'Ираклий', 'Ириней',
    'Исай', 'Исаакий', 'Исидор', 'Исихий', 'Иустин', 'Иэремей', 'Кирилл', 'Клавдий',
    'Клемент', 'Климент', 'Кондрат', 'Конон', 'Конрад', 'Константин', 'Кузьма', 'Лавр',
    'Лазарь', 'Латвий', 'Леонид', 'Ливерий', 'Лимонад', 'Лисандр', 'Лукьян', 'Львов'
];

$patronymics = [
    'Александрович', 'Алексеевич', 'Анатольевич', 'Андреевич', 'Антонович', 'Аркадьевич',
    'Артёмович', 'Артурович', 'Афанасьевич', 'Борисович', 'Валентинович', 'Валерьевич',
    'Васильевич', 'Викторович', 'Витальевич', 'Владимирович', 'Вячеславович', 'Геннадьевич',
    'Георгиевич', 'Герасимович', 'Германович', 'Глебович', 'Гордеевич', 'Григорьевич',
    'Данилович', 'Данильевич', 'Денисович', 'Дмитриевич', 'Дорофеевич', 'Евгеньевич',
    'Евстафьевич', 'Егорович', 'Елизарович', 'Еремеевич', 'Ермолаевич', 'Ерофеевич',
    'Ефимович', 'Ефремович', 'Захарович', 'Зиновьевич', 'Зосимович', 'Иванович',
    'Игнатьевич', 'Игоревич', 'Илиевич', 'Иосифович', 'Ираклиевич', 'Иринеевич'
];

$statuses = ['П', 'Б', 'О', 'К', 'У']; // Присутствие, Больничный, Отпуск, Командировка, Учёба

// Создаём таблицу
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Сотрудники и часы');

// Стиль для границ
$borderStyle = [
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['argb' => 'FF000000'],
        ],
    ],
];

// Стиль для заголовка
$headerFill = [
    'fill' => [
        'fillType' => PatternFill::FILL_SOLID,
        'startColor' => ['argb' => 'FF4472C4'],
        'endColor' => ['argb' => 'FF4472C4'],
    ],
    'font' => [
        'bold' => true,
        'color' => ['argb' => 'FFFFFFFF'],
    ],
];

// Заголовки
$headers = ['Фамилия', 'Имя', 'Отчество', 'Табельный номер', 'Отдел'];
$currentMonth = date('n');
$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $currentMonth, date('Y'));

for ($day = 1; $day <= $daysInMonth; $day++) {
    $headers[] = "День $day";
}

// Добавляем заголовки
$col = 1;
foreach ($headers as $header) {
    $cell = $sheet->getCellByColumnAndRow($col, 1);
    $cell->setValue($header);
    $cell->getStyle()->applyFromArray($headerFill);
    $cell->getStyle()->getAlignment()
        ->setHorizontal(Alignment::HORIZONTAL_CENTER)
        ->setVertical(Alignment::VERTICAL_CENTER)
        ->setWrapText(true);
    $cell->getStyle()->applyFromArray($borderStyle);
    $col++;
}

// Генерируем 500 уникальных сотрудников
$employees = [];
$usedLastNames = [];
$tabNumber = 1000;
$departments = ['IT', 'HR', 'Finance', 'Marketing', 'Operations', 'Sales', 'Support'];

for ($i = 0; $i < 500; $i++) {
    // Выбираем уникальную фамилию
    do {
        $lastNameIndex = array_rand($lastNames);
        $lastName = $lastNames[$lastNameIndex];
    } while (in_array($lastName, $usedLastNames));
    
    $usedLastNames[] = $lastName;
    
    $firstName = $firstNames[array_rand($firstNames)];
    $patronymic = $patronymics[array_rand($patronymics)];
    $tabNum = $tabNumber++;
    $department = $departments[array_rand($departments)];
    
    $employees[] = [
        'lastName' => $lastName,
        'firstName' => $firstName,
        'patronymic' => $patronymic,
        'tabNumber' => $tabNum,
        'department' => $department,
    ];
}

// Добавляем данные сотрудников
$row = 2;
foreach ($employees as $employee) {
    $col = 1;
    
    // ФИО
    $cell = $sheet->getCellByColumnAndRow($col++, $row);
    $cell->setValue($employee['lastName']);
    
    $cell = $sheet->getCellByColumnAndRow($col++, $row);
    $cell->setValue($employee['firstName']);
    
    $cell = $sheet->getCellByColumnAndRow($col++, $row);
    $cell->setValue($employee['patronymic']);
    
    $cell = $sheet->getCellByColumnAndRow($col++, $row);
    $cell->setValue($employee['tabNumber']);
    
    $cell = $sheet->getCellByColumnAndRow($col++, $row);
    $cell->setValue($employee['department']);
    
    // Заполняем часы для каждого дня
    for ($day = 1; $day <= $daysInMonth; $day++) {
        $rand = rand(1, 100);
        
        if ($rand <= 10) {
            // 10% больничный
            $value = 'Б(0ч)';
        } elseif ($rand <= 20) {
            // 10% отпуск
            $value = 'О(0ч)';
        } elseif ($rand <= 25) {
            // 5% командировка
            $value = 'К(0ч)';
        } elseif ($rand <= 28) {
            // 3% учёба
            $value = 'У(0ч)';
        } else {
            // 82% обычный день с часами
            $hours = rand(6, 9) . (rand(0, 1) === 0 ? '' : '.5');
            $value = 'П(' . $hours . 'ч)';
        }
        
        $cell = $sheet->getCellByColumnAndRow($col++, $row);
        $cell->setValue($value);
        $cell->getStyle()->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
    }
    
    // Применяем границы ко всем ячейкам строки
    for ($c = 1; $c <= $col - 1; $c++) {
        $sheet->getCellByColumnAndRow($c, $row)->getStyle()->applyFromArray($borderStyle);
    }
    
    $row++;
}

// Устанавливаем ширину столбцов
$sheet->getColumnDimension('A')->setWidth(15);
$sheet->getColumnDimension('B')->setWidth(12);
$sheet->getColumnDimension('C')->setWidth(15);
$sheet->getColumnDimension('D')->setWidth(12);
$sheet->getColumnDimension('E')->setWidth(12);

for ($i = 0; $i < $daysInMonth; $i++) {
    $sheet->getColumnDimensionByColumn($i + 6)->setWidth(10);
}

// Замораживаем заголовок
$sheet->freezePane('A2');

// Сохраняем файл
$filename = 'employees_' . date('Y-m-d_His') . '.xlsx';
$writer = new Xlsx($spreadsheet);

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$writer->save('php://output');
