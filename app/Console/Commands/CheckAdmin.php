<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Admin;

class CheckAdmin extends Command
{
    protected $signature = 'admin:check';
    protected $description = 'Check admin users in database';

    public function handle()
    {
        $admins = Admin::all();
        
        $this->info('Total admins: ' . $admins->count());
        
        if ($admins->count() > 0) {
            $this->line('');
            foreach ($admins as $admin) {
                $this->line("ID: {$admin->id}");
                $this->line("Name: {$admin->name}");
                $this->line("Email: {$admin->email}");
                $this->line("Role: {$admin->role}");
                $this->line("Employee ID: {$admin->employee_id}");
                $this->line("---");
            }
        } else {
            $this->line('No admins found!');
        }
    }
}
