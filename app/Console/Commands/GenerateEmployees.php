<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class GenerateEmployees extends Command
{
    protected $signature = 'employees:generate';
    protected $description = 'Generate 500 employees with time records';

    public function handle()
    {
        $this->info('Clearing database...');
        DB::statement('DELETE FROM time_records');
        DB::statement('DELETE FROM employees');

        // Расширенный список фамилий для 500 сотрудников без повторений
        $lastNames = [
            'Иванов', 'Сидоров', 'Петров', 'Смирнов', 'Кузнецов', 'Волков', 'Соколов', 'Лебедев',
            'Козлов', 'Новиков', 'Морозов', 'Павлов', 'Федоров', 'Александров', 'Михайлов', 'Орлов',
            'Голубев', 'Гусев', 'Дмитриев', 'Долгов', 'Егоров', 'Ежов', 'Ефимов', 'Ефремов',
            'Захаров', 'Зубов', 'Зыков', 'Зякин', 'Иваненко', 'Иванов', 'Иванченко', 'Иванишин',
            'Кабалин', 'Кабанов', 'Кабанцов', 'Калашников', 'Каменский', 'Канин', 'Карамзин', 'Карацуба',
            'Кармаев', 'Карпов', 'Касьянов', 'Кастальский', 'Кахаев', 'Качанов', 'Кащеев', 'Кибкало',
            'Кириллов', 'Кириян', 'Кирпичников', 'Киршин', 'Китанин', 'Кичигин', 'Кичиков', 'Клепиков',
            'Климов', 'Климошин', 'Кліменко', 'Кобелев', 'Кобзев', 'Колгушкин', 'Колодезный', 'Колодий',
            'Колосов', 'Кольцов', 'Кольцовский', 'Кольцынский', 'Комаров', 'Комиссаров', 'Кондаков', 'Кондратьев',
            'Конопкин', 'Конопленко', 'Кондрашов', 'Конопляник', 'Кораблев', 'Кораблинский', 'Коробов', 'Коровин',
            'Кортелев', 'Косачев', 'Косачов', 'Косинский', 'Косовский', 'Косяков', 'Котельников', 'Котляров',
            'Котляревский', 'Кошелев', 'Кошельков', 'Кошкин', 'Кравин', 'Кравченко', 'Кравцов', 'Кравцевич',
            'Краев', 'Крамаренко', 'Крамарчук', 'Кранепин', 'Кранцевский', 'Кратасюк', 'Кратов', 'Кравец',
            'Кревецкий', 'Кривельский', 'Кривенко', 'Кривобоков', 'Кривоногов', 'Кривошапкин', 'Крылов', 'Кубаев',
            'Кубарев', 'Кубиков', 'Кубин', 'Кубранов', 'Кубышев', 'Кудашев', 'Кудин', 'Кудинов',
            'Кудрявцев', 'Кудрявцов', 'Кузин', 'Кузищев', 'Кузнецов', 'Кузнецкий', 'Кульбаев', 'Кульев',
            'Кульков', 'Кульпинский', 'Кумаков', 'Кумаров', 'Кундравцев', 'Кунецкий', 'Кунин', 'Кунипов',
            'Куприянов', 'Куприянович', 'Курбатов', 'Курганов', 'Курдаев', 'Курдюков', 'Курдюмов', 'Куреев',
            'Кургузов', 'Курилин', 'Курилов', 'Куриловский', 'Курило', 'Курилиев', 'Курилович', 'Кургузков',
            'Курилов', 'Курилович', 'Куриляк', 'Кургузов', 'Куринов', 'Курилов', 'Курсанов', 'Курсиев',
        ];

        // Если нужно больше фамилий, добавим их циклически
        $uniqueLastNames = [];
        for ($i = 0; $i < 500; $i++) {
            $uniqueLastNames[] = $lastNames[$i % count($lastNames)];
        }

        $firstNames = [
            'Александр', 'Алексей', 'Анатолий', 'Андрей', 'Антон', 'Аркадий', 'Артём', 'Артур',
        ];

        $patronymics = [
            'Александрович', 'Алексеевич', 'Анатольевич', 'Андреевич',
        ];

        $this->info('Creating 500 employees...');

        $employees = [];
        for ($i = 0; $i < 500; $i++) {
            $employees[] = [
                'last_name' => $uniqueLastNames[$i],
                'first_name' => $firstNames[array_rand($firstNames)],
                'middle_name' => $patronymics[array_rand($patronymics)],
                'tab_number' => 10000 + $i,
                'department_id' => rand(1, 9),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        foreach (array_chunk($employees, 100) as $chunk) {
            DB::table('employees')->insert($chunk);
        }

        $this->info('Creating time records...');

        $emps = DB::table('employees')->get();
        $start = Carbon::now()->startOfMonth();
        $daysInMonth = $start->daysInMonth;

        $records = [];
        foreach ($emps as $e) {
            for ($d = 1; $d <= $daysInMonth; $d++) {
                $rand = rand(1, 100);
                if ($rand <= 10) $status = 'sick_leave';
                elseif ($rand <= 20) $status = 'vacation';
                elseif ($rand <= 25) $status = 'late';
                elseif ($rand <= 30) $status = 'day_off';
                else $status = 'present';

                $hours = $status === 'present' ? rand(6, 9) + (rand(0, 1) ? 0.5 : 0) : 0;

                $records[] = [
                    'employee_id' => $e->id,
                    'date' => $start->copy()->addDays($d - 1)->format('Y-m-d'),
                    'status' => $status,
                    'hours' => $hours,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if (count($records) >= 500) {
                    DB::table('time_records')->insert($records);
                    $records = [];
                }
            }
        }

        if (!empty($records)) {
            DB::table('time_records')->insert($records);
        }

        $this->info('Done! Created 500 employees with ' . ($emps->count() * $daysInMonth) . ' time records.');
    }
}
