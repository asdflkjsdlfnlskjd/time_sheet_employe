<?php
require __DIR__ . '/vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as DB;

$config = require __DIR__ . '/config/database.php';

$capsule = new DB();
$capsule->addConnection($config['connections']['sqlite']);
$capsule->setAsGlobal();
$capsule->bootEloquent();

// Check employees table
$employees = DB::table('employees')->limit(50)->get(['id', 'tab_number', 'last_name']);

echo "First 50 employees tab numbers:\n";
foreach ($employees as $emp) {
    echo "ID: {$emp->id}, Tab#: {$emp->tab_number}, Name: {$emp->last_name}\n";
}

echo "\n\nTotal employees: " . DB::table('employees')->count() . "\n";

// Check min and max
$result = DB::table('employees')->selectRaw('MIN(tab_number) as min_tab, MAX(tab_number) as max_tab')->first();
echo "Min tab number: {$result->min_tab}\n";
echo "Max tab number: {$result->max_tab}\n";
