<?php
// Простой скрипт для генерации 500 сотрудников

require 'bootstrap/app.php';

$app->make(Illuminate\Contracts\Http\Kernel::class);

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "🗑️  Удаляем старые данные...\n";
DB::statement('DELETE FROM time_records');
DB::statement('DELETE FROM employees');
echo "✓ Данные удалены\n\n";

// Русские фамилии и имена
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
    'Фоменко', 'Фурс', 'Хмара', 'Ходоров', 'Холодный', 'Холопов', 'Цыганов', 'Чебышев',
    'Чернецов', 'Чернов', 'Чесноков', 'Четвериков', 'Чикунов', 'Чистяков', 'Чувильцев', 'Шайхутдинов',
    'Шалимов', 'Шамаев', 'Шаров', 'Шахов', 'Шведов', 'Швецов', 'Шевалдин', 'Шеремет', 'Щеглов', 'Щепин',
    'Щербаков', 'Щепотин', 'Щеснокоев', 'Щеголев', 'Щеголихин', 'Щейнер', 'Щербина', 'Щеркаев',
    'Щербатюк', 'Щербатенко', 'Щипцов', 'Щипунов', 'Щёголев', 'Щербунов', 'Щетинкин', 'Щетинников'
];

// Если не хватает фамилий, добавим
while (count($lastNames) < 500) {
    $lastNames = array_merge($lastNames, $lastNames);
}
$lastNames = array_slice($lastNames, 0, 500);
shuffle($lastNames);

$firstNames = [
    'Александр', 'Алексей', 'Анатолий', 'Андрей', 'Антон', 'Аркадий', 'Артём', 'Артур',
    'Афанасий', 'Борис', 'Валентин', 'Валерий', 'Василий', 'Виктор', 'Виталий', 'Владимир',
    'Вячеслав', 'Геннадий', 'Георгий', 'Герасим', 'Герман', 'Глеб', 'Гордей', 'Григорий',
    'Даниил', 'Данила', 'Денис', 'Дмитрий', 'Дорофей', 'Евгений', 'Евстафий', 'Егор',
    'Елизар', 'Еремей', 'Ермолай', 'Ерофей', 'Ефим', 'Ефремов', 'Захар', 'Зиновий'
];

$patronymics = [
    'Александрович', 'Алексеевич', 'Анатольевич', 'Андреевич', 'Антонович', 'Аркадьевич',
    'Артёмович', 'Артурович', 'Афанасьевич', 'Борисович', 'Валентинович', 'Валерьевич',
    'Васильевич', 'Викторович', 'Витальевич', 'Владимирович', 'Вячеславович', 'Геннадьевич',
    'Георгиевич', 'Герасимович', 'Германович', 'Глебович', 'Гордеевич', 'Григорьевич'
];

$departments = [1, 2, 3, 4, 5, 6, 7, 8, 9];

echo "👨‍💼 Создаем 500 сотрудников и их записи...\n";

$startTime = microtime(true);
$batchSize = 100;

for ($batch = 0; $batch < 5; $batch++) {
    $employeeData = [];
    
    for ($i = $batch * 100; $i < ($batch + 1) * 100; $i++) {
        $firstName = $firstNames[array_rand($firstNames)];
        $patronymic = $patronymics[array_rand($patronymics)];
        $department = $departments[array_rand($departments)];
        
        $employeeData[] = [
            'last_name' => $lastNames[$i],
            'first_name' => $firstName,
            'middle_name' => $patronymic,
            'tab_number' => 10000 + $i,
            'department_id' => $department,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
    
    DB::table('employees')->insert($employeeData);
    echo "✓ Батч " . ($batch + 1) . " из 5 - добавлено " . count($employeeData) . " сотрудников\n";
}

echo "\n⏰ Создаем временные записи...\n";

$employees = DB::table('employees')->get();
$currentMonth = now()->month;
$currentYear = now()->year;
$monthStart = Carbon::createFromDate($currentYear, $currentMonth, 1);
$daysInMonth = $monthStart->daysInMonth;

$recordBatch = [];
$batchCount = 0;
$totalRecords = 0;

foreach ($employees as $emp) {
    for ($day = 1; $day <= $daysInMonth; $day++) {
        $rand = rand(1, 100);
        
        if ($rand <= 10) {
            $status = 'sick';
            $hours = 0;
        } elseif ($rand <= 20) {
            $status = 'vacation';
            $hours = 0;
        } elseif ($rand <= 25) {
            $status = 'business_trip';
            $hours = 0;
        } elseif ($rand <= 28) {
            $status = 'day_off';
            $hours = 0;
        } else {
            $status = 'present';
            $hours = rand(6, 9) + (rand(0, 1) === 0 ? 0 : 0.5);
        }
        
        $recordBatch[] = [
            'employee_id' => $emp->id,
            'date' => $monthStart->copy()->addDays($day - 1)->format('Y-m-d'),
            'status' => $status,
            'hours' => $hours,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        
        $totalRecords++;
        
        if (count($recordBatch) >= 500) {
            DB::table('time_records')->insert($recordBatch);
            $batchCount++;
            echo "  ✓ Батч $batchCount - добавлено " . count($recordBatch) . " записей\n";
            $recordBatch = [];
        }
    }
}

// Вставляем оставшиеся записи
if (!empty($recordBatch)) {
    DB::table('time_records')->insert($recordBatch);
    $batchCount++;
    echo "  ✓ Батч $batchCount - добавлено " . count($recordBatch) . " записей\n";
}

$endTime = microtime(true);
$duration = round($endTime - $startTime, 2);

echo "\n✅ ГОТОВО!\n";
echo "   • Сотрудников создано: " . $employees->count() . "\n";
echo "   • Временных записей: $totalRecords\n";
echo "   • Время выполнения: {$duration}s\n";
