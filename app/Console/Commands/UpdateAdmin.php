<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Admin;

class UpdateAdmin extends Command
{
    protected $signature = 'admin:update {email?} {password?}';
    protected $description = 'Update admin credentials';

    public function handle()
    {
        $name = $this->argument('email') ?? 'admin';  // First argument is actually login name
        $password = $this->argument('password') ?? 'admin123';

        $admin = Admin::find(1);
        
        if (!$admin) {
            $this->error('Admin not found!');
            return;
        }

        $admin->update([
            'name' => $name,
            'password' => bcrypt($password)
        ]);

        $this->info('✅ Admin updated!');
        $this->line("👤 Login: $name");
        $this->line("🔑 Password: $password");
    }
}
