<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Employee;
use App\Models\TimeRecord;
use Carbon\Carbon;

class DebugDay1 extends Command
{
    protected $signature = 'debug:day1';
    protected $description = 'Debug day 1 loading';

    public function handle()
    {
        $currentMonth = 4;
        $currentYear = 2026;
        
        $this->line('📊 ПРОВЕРКА ЗАГРУЗКИ ДНЕЙ В КОНТРОЛЛЕРЕ');
        $this->line('=' . str_repeat('=', 50));
        
        // Получаем 3 сотрудников
        $employees = Employee::orderBy('last_name')->take(3)->get();
        
        $employeeIds = $employees->pluck('id')->toArray();
        $this->line("Сотрудники: " . implode(', ', $employeeIds));
        
        // Загружаем TimeRecords как в контроллере
        $monthStart = Carbon::createFromDate($currentYear, $currentMonth, 1);
        $monthEnd = $monthStart->copy()->endOfMonth();
        
        $this->line("Диапазон: {$monthStart->format('Y-m-d')} to {$monthEnd->format('Y-m-d')}");
        
        $timeRecords = TimeRecord::whereBetween('date', [$monthStart, $monthEnd])
            ->whereIn('employee_id', $employeeIds)
            ->get()
            ->groupBy('employee_id')
            ->map(fn($records) => {
                // Сортируем по дню (1-30) правильно, чтобы день 1 был в начале
                $daysInMonth = 30;
                $daysData = $records->keyBy(fn($r) => $r->date->day);
                // Переупорядочиваем: день 1 должен быть первым, а не последним
                return collect(range(1, $daysInMonth))
                    ->mapWithKeys(fn($day) => [$day => $daysData[$day] ?? null]);
            });
        
        $this->line("\nСтруктура данных:");
        
        foreach ($timeRecords as $empId => $daysData) {
            $this->line("\nСотрудник $empId:");
            $this->line("  Ключи в массиве: " . implode(', ', array_keys($daysData->toArray())));
            
            // Проверяем день 1
            foreach ([1, '1'] as $key) {
                if (isset($daysData[$key])) {
                    $day = $daysData[$key];
                    $this->line("  День 1 (ключ=$key): hours={$day->hours}, status={$day->status}");
                }
            }
        }
        
        // Прямая проверка БД
        $this->line("\nПрямая проверка БД:");
        $day1 = TimeRecord::where('date', '2026-04-01')->first();
        if ($day1) {
            $this->line("День 1, Сотр 1: hours={$day1->hours}, status={$day1->status}");
        }
    }
}
