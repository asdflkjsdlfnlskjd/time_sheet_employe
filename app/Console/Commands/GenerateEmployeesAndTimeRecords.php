<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class GenerateEmployeesAndTimeRecords extends Command
{
    protected $signature = 'employees:generate';
    protected $description = 'Генерирует 500 сотрудников с временными записями';

    public function handle()
    {
        $this->info('🗑️  Очищаем БД...');
        DB::statement('DELETE FROM time_records');
        DB::statement('DELETE FROM employees');
        $this->info('✓ БД очищена');

        $lastNames = [
            'Иванов', 'Сидоров', 'Петров', 'Смирнов', 'Кузнецов', 'Волков', 'Соколов', 'Лебедев',
            'Козлов', 'Новиков', 'Морозов', 'Павлов', 'Федоров', 'Александров', 'Михайлов', 'Орлов',
            'Ковалев', 'Логинов', 'Сергеев', 'Никитин', 'Нестеров', 'Медведев', 'Фёдоров', 'Мальцев',
            'Прямухин', 'Соснин', 'Карпов', 'Гаврилов', 'Тарасов', 'Зубков', 'Бобров', 'Дубов',
        ];

        $firstNames = [
            'Александр', 'Алексей', 'Анатолий', 'Андрей', 'Антон', 'Аркадий', 'Артём', 'Артур',
            'Афанасий', 'Борис', 'Валентин', 'Валерий', 'Василий', 'Виктор', 'Виталий', 'Владимир',
        ];

        $patronymics = [
            'Александрович', 'Алексеевич', 'Анатольевич', 'Андреевич', 'Антонович', 'Аркадьевич',
            'Артёмович', 'Артурович', 'Афанасьевич', 'Борисович', 'Валентинович', 'Валерьевич',
        ];

        $this->info("\n👨‍💼 Создаем 500 сотрудников...");

        // Расширяем массивы до 500 уникальных фамилий
        for ($i = count($lastNames); $i < 500; $i++) {
            $lastNames[] = $lastNames[$i % count($lastNames)] . rand(1, 999);
        }

        $employees = [];
        $tabNum = 10000;

        for ($i = 0; $i < 500; $i++) {
            $employees[] = [
                'last_name' => $lastNames[$i],
                'first_name' => $firstNames[array_rand($firstNames)],
                'middle_name' => $patronymics[array_rand($patronymics)],
                'tab_number' => $tabNum++,
                'department_id' => rand(1, 9),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (($i + 1) % 100 === 0) {
                $this->line("  ✓ Подготовлено $i записей");
            }
        }

        // Вставляем батчами
        foreach (array_chunk($employees, 100) as $chunk) {
            DB::table('employees')->insert($chunk);
        }
        $this->info('✓ Создано 500 сотрудников');

        $this->info("\n⏰ Генерируем временные записи...");

        $emps = DB::table('employees')->get();
        $start = Carbon::now()->startOfMonth();
        $daysInMonth = $start->daysInMonth;
        $totalRecords = 0;

        $records = [];
        $empCount = 0;

        foreach ($emps as $e) {
            for ($d = 1; $d <= $daysInMonth; $d++) {
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
                } elseif ($rand <= 30) {
                    $status = 'day_off';
                    $hours = 0;
                } else {
                    $status = 'present';
                    $hours = rand(6, 9) + (rand(0, 1) ? 0.5 : 0);
                }

                $records[] = [
                    'employee_id' => $e->id,
                    'date' => $start->copy()->addDays($d - 1)->format('Y-m-d'),
                    'status' => $status,
                    'hours' => $hours,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $totalRecords++;

                if (count($records) >= 500) {
                    DB::table('time_records')->insert($records);
                    $records = [];
                }
            }

            $empCount++;
            if ($empCount % 50 === 0) {
                $this->line("  ✓ Обработано $empCount сотрудников");
            }
        }

        // Вставляем оставшиеся
        if (!empty($records)) {
            DB::table('time_records')->insert($records);
        }

        $this->info('✓ Создано ' . $totalRecords . ' временных записей');

        $this->info("\n✅ ГОТОВО!");
        $this->line("   • Сотрудников: 500");
        $this->line("   • Временных записей: $totalRecords");
        $this->line("   • Месяц: " . $start->format('F Y'));
    }
}
<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\TimeRecord;
use App\Models\Department;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateEmployeesAndTimeRecords extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-employees-and-time-records';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Генерирует 500 сотрудников с временными записями';

    public function handle()
    {
        $this->info('🗑️  Удаляем старые данные...');
        
        // Удаляем все временные записи
        TimeRecord::truncate();
        $this->info('✓ Удалены все временные записи');
        
        // Удаляем всех сотрудников
        Employee::truncate();
        $this->info('✓ Удалены все сотрудники');
        
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
            'Фоменко', 'Фурс', 'Хмара', 'Ходоров', 'Холодный', 'Холопов', 'Цыганов', 'Чебышев',
            'Чернецов', 'Чернов', 'Чесноков', 'Четвериков', 'Чикунов', 'Чистяков', 'Чувильцев', 'Шайхутдинов',
            'Шалимов', 'Шамаев', 'Шаров', 'Шахов', 'Шведов', 'Швецов', 'Шевалдин', 'Шеремет', 'Щеглов', 'Щепин'
        ];

        $firstNames = [
            'Александр', 'Алексей', 'Анатолий', 'Андрей', 'Антон', 'Аркадий', 'Артём', 'Артур',
            'Афанасий', 'Борис', 'Валентин', 'Валерий', 'Василий', 'Виктор', 'Виталий', 'Владимир',
            'Вячеслав', 'Геннадий', 'Георгий', 'Герасим', 'Герман', 'Глеб', 'Гордей', 'Григорий',
            'Даниил', 'Данила', 'Денис', 'Дмитрий', 'Дорофей', 'Евгений', 'Евстафий', 'Егор',
            'Елизар', 'Еремей', 'Ермолай', 'Ерофей', 'Ефим', 'Ефремов', 'Захар', 'Зиновий',
            'Зосима', 'Иван', 'Игнатий', 'Игорь', 'Илия', 'Иосиф', 'Ираклий', 'Ириней',
            'Исай', 'Исаакий', 'Исидор', 'Исихий', 'Иустин', 'Кирилл', 'Клавдий', 'Клемент',
            'Климент', 'Кондрат', 'Конон', 'Конрад', 'Константин', 'Кузьма', 'Лавр', 'Лазарь'
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

        $departments = [
            'IT', 'HR', 'Finance', 'Marketing', 'Operations', 'Sales', 'Support', 'Legal', 'Accounting'
        ];

        // Создаём тестовые отделы
        $deptObjects = [];
        foreach ($departments as $deptName) {
            $dept = Department::firstOrCreate(
                ['name' => $deptName],
                ['name' => $deptName]
            );
            $deptObjects[] = $dept;
        }

        $this->info("\n👨‍💼 Генерируем 500 сотрудников...");
        
        $usedLastNames = [];
        $tabNumber = 10000;
        $employeeData = [];
        $employees = [];
        
        for ($i = 0; $i < 500; $i++) {
            // Выбираем уникальную фамилию
            do {
                $lastNameIndex = array_rand($lastNames);
                $lastName = $lastNames[$lastNameIndex];
            } while (in_array($lastName, $usedLastNames));
            
            $usedLastNames[] = $lastName;
            
            $firstName = $firstNames[array_rand($firstNames)];
            $patronymic = $patronymics[array_rand($patronymics)];
            $department = $deptObjects[array_rand($deptObjects)];
            
            $employeeData[] = [
                'last_name' => $lastName,
                'first_name' => $firstName,
                'middle_name' => $patronymic,
                'tab_number' => $tabNumber++,
                'department_id' => $department->id,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        
        // Вставляем все сотрудников батчами
        $chunkSize = 50;
        foreach (array_chunk($employeeData, $chunkSize) as $chunk) {
            Employee::insert($chunk);
        }
        
        $this->info('✓ Создано 500 сотрудников');

        // Получаем всех сотрудников для создания временных записей
        $employees = Employee::all();

        $this->info("\n⏰ Генерируем временные записи...");
        
        $currentMonth = now()->month;
        $currentYear = now()->year;
        $monthStart = Carbon::createFromDate($currentYear, $currentMonth, 1);
        $monthEnd = $monthStart->copy()->endOfMonth();
        $daysInMonth = $monthStart->daysInMonth;

        $timeRecordData = [];
        $recordCount = 0;

        foreach ($employees as $index => $employee) {
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

                $timeRecordData[] = [
                    'employee_id' => $employee->id,
                    'date' => $monthStart->copy()->addDays($day - 1)->format('Y-m-d'),
                    'status' => $status,
                    'hours' => $hours,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                
                $recordCount++;
            }
            
            if (($index + 1) % 50 === 0) {
                $this->line("  ✓ Обработано " . ($index + 1) . " сотрудников (" . $recordCount . " записей)");
            }
        }
        
        // Вставляем все временные записи батчами
        $chunkSize = 100;
        foreach (array_chunk($timeRecordData, $chunkSize) as $chunk) {
            TimeRecord::insert($chunk);
        }
        
        $this->info('✓ Создано ' . $recordCount . ' временных записей');
        
        $this->info("\n✅ Готово! Добавлено:");
        $this->line("   • 500 сотрудников");
        $this->line("   • " . $recordCount . " временных записей");
        $this->line("   • Данные за " . $monthStart->format('F Y'));
    }
}
