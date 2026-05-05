<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckTabNumbers extends Command
{
    protected $signature = 'check:tabs';
    protected $description = 'Check tab numbers in database';

    public function handle()
    {
        $total = DB::table('employees')->count();
        $this->info("Total employees: $total");

        $first = DB::table('employees')->orderBy('id')->limit(30)->get(['id', 'tab_number', 'last_name']);
        $this->line("\nFirst 30 employees:");
        foreach ($first as $emp) {
            $this->line("ID {$emp->id}: tab#{$emp->tab_number} - {$emp->last_name}");
        }

        $result = DB::table('employees')->selectRaw('MIN(tab_number) as min_tab, MAX(tab_number) as max_tab, COUNT(DISTINCT tab_number) as unique_tabs')->first();
        $this->line("\nMin tab: {$result->min_tab}");
        $this->line("Max tab: {$result->max_tab}");
        $this->line("Unique tabs: {$result->unique_tabs}");

        // Check for duplicates
        $dupes = DB::table('employees')
            ->selectRaw('tab_number, COUNT(*) as cnt')
            ->groupBy('tab_number')
            ->having(DB::raw('COUNT(*)'), '>', 1)
            ->get();
        
        if ($dupes->count() > 0) {
            $this->line("\n⚠️ Found duplicate tab numbers:");
            foreach ($dupes as $d) {
                $this->line("Tab# {$d->tab_number} appears {$d->cnt} times");
            }
        } else {
            $this->line("\n✅ No duplicate tab numbers");
        }
    }
}
