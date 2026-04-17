<?php

require 'vendor/autoload.php';
require 'bootstrap/app.php';

use Illuminate\Support\Facades\DB;

// Check if table exists
$tables = DB::select("SELECT name FROM sqlite_master WHERE type='table';");
echo "Tables in database:\n";
foreach ($tables as $table) {
    echo "  - " . $table->name . "\n";
}

// Try to get admins
try {
    $admins = DB::select('SELECT id, name, password, role FROM admins');
    
    echo "\n========== ADMINS IN DATABASE ==========\n";
    foreach ($admins as $admin) {
        echo "ID: " . $admin->id . "\n";
        echo "Name (Login): " . $admin->name . "\n";
        echo "Password Hash: " . substr($admin->password, 0, 20) . "...\n";
        echo "Role: " . $admin->role . "\n";
        echo "---\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\nDefault login credentials:\n";
echo "Login: super_admin\n";
echo "Password: 123\n";
