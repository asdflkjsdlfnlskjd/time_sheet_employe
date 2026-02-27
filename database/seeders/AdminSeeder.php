<?php
// database/seeders/AdminSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Admin;
use App\Models\Employee;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Супер-админ (Иван Иванов)
        $superAdminEmployee = Employee::where('email', 'ivan@company.com')->first();
        if ($superAdminEmployee) {
            Admin::create([
                'name' => 'super_admin',
                'password' => Hash::make('123'),
                'employee_id' => $superAdminEmployee->id,
                'role' => 'super_admin',
                'is_active' => true,
            ]);
        }

        // Администраторы отделов (руководители)
        $managers = Employee::whereIn('position', [
            'Руководитель IT отдела',
            'Руководитель HR отдела',
            'Главный бухгалтер'
        ])->get();

        foreach ($managers as $manager) {
            Admin::create([
                'name' => 'admin_' . strtolower(str_replace(' ', '_', $manager->first_name)),
                'password' => Hash::make('123'),
                'employee_id' => $manager->id,
                'role' => 'department_admin',
                'is_active' => true,
            ]);
        }

        $this->command->info('Администраторы созданы успешно!');
    }
}
