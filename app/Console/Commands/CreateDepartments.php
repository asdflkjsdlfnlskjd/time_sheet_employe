<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Department;

class CreateDepartments extends Command
{
    protected $signature = 'departments:create';
    protected $description = 'Create test departments';

    public function handle()
    {
        $this->info('📁 Creating departments...');

        $departments = [
            'IT Department',
            'HR Department',
            'Finance Department',
            'Marketing Department',
            'Operations Department',
            'Sales Department',
            'Customer Support',
            'Product Development',
            'Quality Assurance',
            'Business Analysis'
        ];

        $count = 0;
        foreach ($departments as $name) {
            Department::firstOrCreate(['name' => $name]);
            $count++;
        }

        $this->info("✅ Created $count departments");
        return 0;
    }
}
