<?php

require 'bootstrap/app.php';
require 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\PatternFill;

$app = app();

echo "📊 Генерируем XLSX файл для импорта...\n";

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Табель');

// Стили
$borderStyle = [
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['argb' => 'FF000000'],
        ],
    ],
];

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

$legendFill = [
    'fill' => [
        'fillType' => PatternFill::FILL_SOLID,
        'startColor' => ['argb' => 'FFE7E6E6'],
        'endColor' => ['argb' => 'FFE7E6E6'],
    ],
    'font' => [
        'bold' => false,
    ],
];

$currentMonth = now()->month;
$currentYear = now()->year;
$monthNames = [
    1 => 'Январь', 2 => 'Февраль', 3 => 'Март', 4 => 'Апрель',
    5 => 'Май', 6 => 'Июнь', 7 => 'Июль', 8 => 'Август',
    9 => 'Сентябрь', 10 => 'Октябрь', 11 => 'Ноябрь', 12 => 'Декабрь'
];

$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $currentMonth, $currentYear);

// Строка 1: Заголовок
$sheet->setCellValue('A1', 'Табель учета рабочего времени');
$sheet->mergeCells('A1:E1');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

// Строка 2: Месяц и год
$sheet->setCellValue('A2', $monthNames[$currentMonth] . ' ' . $currentYear);
$sheet->mergeCells('A2:E2');
$sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);

// Строка 3: Пустая

// Строка 4: Легенда
$sheet->setCellValue('A4', 'Статусы:');
$sheet->getStyle('A4')->applyFromArray($legendFill);

$statuses = [
    '— = Присутствует',
    'ОТ = Отсутствовал',
    'ОП = Опоздал',
    'РУ = Ранний уход',
    'ОТП = Отпуск',
    'БО = Больничный',
    'ВЫХ = Выходной'
];

$col = 2;
foreach ($statuses as $status) {
    $sheet->setCellValue($sheet->getCellByColumnAndRow($col, 4)->getCoordinate(), $status);
    $sheet->getStyle($sheet->getCellByColumnAndRow($col, 4)->getCoordinate())->applyFromArray($legendFill);
    $col++;
}

// Строка 5: Заголовки таблицы
$sheet->setCellValue('A5', 'Фамилия');
$sheet->setCellValue('B5', 'Имя');
$sheet->setCellValue('C5', 'Отчество');
$sheet->setCellValue('D5', 'Табельный №');
$sheet->setCellValue('E5', 'Отдел');

for ($day = 1; $day <= $daysInMonth; $day++) {
    $sheet->setCellValue($sheet->getCellByColumnAndRow($day + 5, 5)->getCoordinate(), "День " . $day);
}

// Применяем стиль к заголовкам
for ($col = 1; $col <= $daysInMonth + 5; $col++) {
    $cell = $sheet->getCellByColumnAndRow($col, 5);
    $cell->getStyle()->applyFromArray($headerFill);
    $cell->getStyle()->getAlignment()
        ->setHorizontal(Alignment::HORIZONTAL_CENTER)
        ->setVertical(Alignment::VERTICAL_CENTER)
        ->setWrapText(true);
    $cell->getStyle()->applyFromArray($borderStyle);
}

// Получаем сотрудников
$employees = DB::table('employees')->orderBy('last_name')->get();

echo "Добавляем " . count($employees) . " сотрудников...\n";

$row = 6;
foreach ($employees as $emp) {
    // ФИО
    $sheet->setCellValue('A' . $row, $emp->last_name);
    $sheet->setCellValue('B' . $row, $emp->first_name);
    $sheet->setCellValue('C' . $row, $emp->middle_name);
    $sheet->setCellValue('D' . $row, $emp->tab_number);
    $sheet->setCellValue('E' . $row, $emp->department_id); // Department ID

    // Получаем временные записи сотрудника за месяц
    $records = DB::table('time_records')
        ->where('employee_id', $emp->id)
        ->whereMonth('date', $currentMonth)
        ->whereYear('date', $currentYear)
        ->get()
        ->keyBy(function ($item) {
            return $item->date ? (new \DateTime($item->date))->format('d') : null;
        });

    $statusMap = [
        'present' => '—',
        'absent' => 'ОТ',
        'late' => 'ОП',
        'early_leave' => 'РУ',
        'vacation' => 'ОТП',
        'sick_leave' => 'БО',
        'day_off' => 'ВЫХ',
    ];

    // Заполняем дни месяца
    for ($day = 1; $day <= $daysInMonth; $day++) {
        $cellCol = $day + 5;
        $record = $records->get(str_pad($day, 2, '0', STR_PAD_LEFT));

        if ($record) {
            $statusShort = $statusMap[$record->status] ?? '—';
            $hours = $record->hours > 0 ? $record->hours : 0;
            $cellValue = $statusShort . '(' . $hours . 'ч)';
        } else {
            $cellValue = '';
        }

        $sheet->setCellValue($sheet->getCellByColumnAndRow($cellCol, $row)->getCoordinate(), $cellValue);
    }

    // Применяем границы ко всей строке
    for ($col = 1; $col <= $daysInMonth + 5; $col++) {
        $sheet->getCellByColumnAndRow($col, $row)->getStyle()->applyFromArray($borderStyle);
        $sheet->getCellByColumnAndRow($col, $row)->getStyle()->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
    }

    $row++;

    if ($row % 100 === 5) {
        echo "  ✓ Добавлено " . ($row - 6) . " сотрудников\n";
    }
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
$sheet->freezePane('A6');

// Сохраняем файл
$filename = 'import_template_' . $currentMonth . '_' . $currentYear . '_' . date('Y-m-d_His') . '.xlsx';
$filepath = storage_path('app/imports/' . $filename);

@mkdir(dirname($filepath), 0755, true);

$writer = new Xlsx($spreadsheet);
$writer->save($filepath);

echo "\n✅ Файл создан: $filename\n";
echo "📍 Путь: $filepath\n";
echo "\nТеперь можете загрузить этот файл через форму импорта!\n";
