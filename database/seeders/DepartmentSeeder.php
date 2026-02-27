<?php
// database/seeders/DepartmentSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;
use Carbon\Carbon;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            [
                'name' => 'IT Отдел',
                'description' => 'Разработка и поддержка программного обеспечения',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'HR Отдел',
                'description' => 'Управление персоналом и кадровый учет',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Бухгалтерия',
                'description' => 'Финансовый учет и отчетность',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Отдел продаж',
                'description' => 'Продажи и работа с клиентами',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ];

        foreach ($departments as $department) {
            Department::create($department);
        }

        $this->command->info('Отделы созданы успешно!');
    }
}
