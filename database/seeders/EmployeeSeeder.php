<?php
// database/seeders/EmployeeSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;
use App\Models\Department;
use Carbon\Carbon;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $departments = Department::all();

        // Супер-админ (будет во всех отделах? Нет, он в IT)
        $superAdmin = Employee::create([
            'first_name' => 'Иван',
            'last_name' => 'Иванов',
            'middle_name' => 'Иванович',
            'tab_number' => 'EMP-001',
            'position' => 'Главный администратор',
            'email' => 'ivan@company.com',
            'phone' => '+7 (999) 123-45-67',
            'hire_date' => Carbon::now()->subYears(5),
            'department_id' => $departments[0]->id, // IT отдел
            'is_active' => true,
        ]);

        // Руководители отделов
        $managers = [
            [
                'first_name' => 'Петр',
                'last_name' => 'Петров',
                'email' => 'petr@company.com',
                'position' => 'Руководитель IT отдела',
                'dept_index' => 0,
            ],
            [
                'first_name' => 'Мария',
                'last_name' => 'Сидорова',
                'email' => 'maria@company.com',
                'position' => 'Руководитель HR отдела',
                'dept_index' => 1,
            ],
            [
                'first_name' => 'Анна',
                'last_name' => 'Смирнова',
                'email' => 'anna@company.com',
                'position' => 'Главный бухгалтер',
                'dept_index' => 2,
            ],
        ];

        $createdManagers = [];
        foreach ($managers as $manager) {
            $createdManagers[] = Employee::create([
                'first_name' => $manager['first_name'],
                'last_name' => $manager['last_name'],
                'middle_name' => 'Петрович',
                'tab_number' => 'EMP-00' . (count($createdManagers) + 2),
                'position' => $manager['position'],
                'email' => $manager['email'],
                'phone' => '+7 (999) 234-56-78',
                'hire_date' => Carbon::now()->subYears(3),
                'department_id' => $departments[$manager['dept_index']]->id,
                'is_active' => true,
            ]);
        }

        // Обновляем manager_id в отделах
        foreach ($createdManagers as $index => $manager) {
            $department = $departments[$index];
            $department->manager_id = $manager->id;
            $department->save();
        }

        // Обычные сотрудники
        $employees = [
            ['IT Отдел', 'Алексей', 'alexey@company.com', 'Разработчик'],
            ['IT Отдел', 'Дмитрий', 'dmitry@company.com', 'Тестировщик'],
            ['HR Отдел', 'Елена', 'elena@company.com', 'HR-менеджер'],
            ['Бухгалтерия', 'Ольга', 'olga@company.com', 'Бухгалтер'],
            ['Отдел продаж', 'Сергей', 'sergey@company.com', 'Менеджер по продажам'],
            ['Отдел продаж', 'Татьяна', 'tatiana@company.com', 'Менеджер по продажам'],
        ];

        foreach ($employees as $index => $emp) {
            $dept = Department::where('name', 'like', "%{$emp[0]}%")->first();
            Employee::create([
                'first_name' => $emp[1],
                'last_name' => 'Сотрудников',
                'middle_name' => 'Сергеевич',
                'tab_number' => 'EMP-0' . ($index + 5),
                'position' => $emp[3],
                'email' => $emp[2],
                'phone' => '+7 (999) 345-67-89',
                'hire_date' => Carbon::now()->subYear(),
                'department_id' => $dept->id,
                'is_active' => true,
            ]);
        }

        $this->command->info('Сотрудники созданы успешно!');
    }
}
