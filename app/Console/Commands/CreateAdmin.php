<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Admin;

class CreateAdmin extends Command
{
    protected $signature = 'admin:create {email?} {password?}';
    protected $description = 'Create super admin user';

    public function handle()
    {
        $email = $this->argument('email') ?? 'admin@test.com';
        $password = $this->argument('password') ?? 'admin123';

        // Create a dummy employee for the admin
        $employee = \App\Models\Employee::create([
            'last_name' => 'Administrator',
            'first_name' => 'System',
            'middle_name' => 'Admin',
            'tab_number' => 'ADMIN-001',
            'department_id' => 1
        ]);

        $admin = Admin::create([
            'name' => 'Administrator',
            'email' => $email,
            'password' => bcrypt($password),
            'role' => 'super_admin',
            'employee_id' => $employee->id
        ]);

        $this->info('✅ Admin created!');
        $this->line("📧 Email: $email");
        $this->line("🔑 Password: $password");
        $this->line("👤 Role: super_admin");
    }
}
