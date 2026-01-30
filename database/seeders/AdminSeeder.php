<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        Admin::create([
            'name' => 'Главный админимтратор',
            'password' => Hash::make('1'), // Измените на ваш пароль
            'role' => 'main_super',
        ]);

        // Можно добавить больше администраторов
        Admin::create([
            'name' => 'Менеджер',
            'password' => Hash::make('1'),
            'role' => 'manager',
        ]);
        Admin::create([
            'name' => 'HR',
            'password' => Hash::make('1'),
            'role' => 'human_resources',
        ]);
        Admin::create([
            'name' => 'Бухгалтерия ',
            'password' => Hash::make('1'),
            'role' => 'bookkeeping_accounting ',
        ]);
        Admin::create([
            'name' => 'Начальник цифрового отдела',
            'password' => Hash::make('1'),
            'role' => 'chief_digital_officer ',
        ]);

    }
}
