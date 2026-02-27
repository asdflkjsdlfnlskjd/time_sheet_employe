<?php
// database/seeders/DatabaseSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DepartmentSeeder::class,
            EmployeeSeeder::class,
            AdminSeeder::class,
            TimeRecordSeeder::class,
        ]);
    }
}
