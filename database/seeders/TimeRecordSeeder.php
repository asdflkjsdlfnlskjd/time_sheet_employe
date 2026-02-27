<?php
// database/seeders/TimeRecordSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TimeRecord;
use App\Models\Employee;
use Carbon\Carbon;

class TimeRecordSeeder extends Seeder
{
    public function run(): void
    {
        $employees = Employee::all();
        $startDate = Carbon::now()->subDays(30);
        $endDate = Carbon::now();

        foreach ($employees as $employee) {
            $currentDate = clone $startDate;

            while ($currentDate <= $endDate) {
                // Пропускаем выходные
                if ($currentDate->isWeekend()) {
                    $currentDate->addDay();
                    continue;
                }

                // Случайный статус (90% присутствие)
                $status = rand(1, 100) <= 90 ? 'present' : 'absent';

                if ($status === 'present') {
                    $checkIn = Carbon::parse($currentDate->format('Y-m-d') . ' 09:00:00')
                        ->addMinutes(rand(-15, 15));
                    $checkOut = Carbon::parse($currentDate->format('Y-m-d') . ' 18:00:00')
                        ->addMinutes(rand(-30, 30));
                    $hours = 8;
                } else {
                    $checkIn = null;
                    $checkOut = null;
                    $hours = 0;
                }

                TimeRecord::create([
                    'employee_id' => $employee->id,
                    'date' => $currentDate->format('Y-m-d'),
                    'check_in' => $checkIn,
                    'check_out' => $checkOut,
                    'hours' => $hours,
                    'status' => $status,
                    'notes' => $status === 'absent' ? 'Неявка' : null,
                ]);

                $currentDate->addDay();
            }
        }

        $this->command->info('Записи времени созданы успешно!');
    }
}
