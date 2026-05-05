<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Employee;
use App\Models\Department;

class FixEmployeeTabNumbers extends Command
{
    protected $signature = 'employees:fix-numbers';
    protected $description = 'Fix employee tab_numbers to match import template';

    public function handle()
    {
        $this->info('🔧 Fixing employee tab_numbers...');

        // Сотрудники уже существуют, просто обновляем их tab_number
        $employees = Employee::all();

        if ($employees->isEmpty()) {
            $this->error('No employees found. Run employees:generate first.');
            return 1;
        }

        $startTabNumber = 10000;
        $count = 0;

        foreach ($employees as $index => $employee) {
            $tabNumber = $startTabNumber + ($index + 1);  // 10001, 10002, 10003...
            $employee->update(['tab_number' => (string)$tabNumber]);
            $count++;

            if ($count % 100 === 0) {
                $this->line("  ✓ Updated $count employees");
            }
        }

        $this->info("\n✅ Fixed $count employees!");
        $this->line("Tab numbers now range from 10001 to " . ($startTabNumber + $count));

        return 0;
    }
}
