<?php

namespace App\Http\Controllers\Main;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\TimeRecord;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DebugController extends Controller
{
    public function checkData()
    {
        echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><style>
        body { font-family: Arial; margin: 20px; }
        .section { border: 1px solid #ccc; padding: 15px; margin: 10px 0; }
        .error { color: red; }
        .success { color: green; }
        table { border-collapse: collapse; width: 100%; }
        td, th { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        </style></head><body>";
        
        echo "<h1>🔍 Отладка системы табеля</h1>";
        
        // 1. Проверка сотрудников
        echo "<div class='section'>";
        echo "<h2>1. Сотрудники в БД</h2>";
        $employees = Employee::take(5)->get();
        echo "<table><tr><th>ID</th><th>Имя</th><th>Отдел</th></tr>";
        foreach ($employees as $emp) {
            $deptName = ($emp->department) ? $emp->department->name : 'N/A';
            echo "<tr><td>{$emp->id}</td><td>{$emp->first_name} {$emp->last_name}</td><td>{$deptName}</td></tr>";
        }
        echo "</table>";
        echo "<p class='success'>✅ Найдено: " . Employee::count() . " сотрудников</p>";
        echo "</div>";
        
        // 2. Проверка TimeRecords
        echo "<div class='section'>";
        echo "<h2>2. TimeRecords в БД</h2>";
        $totalRecords = TimeRecord::count();
        $aprilRecords = TimeRecord::whereMonth('date', 4)->whereYear('date', 2026)->count();
        echo "<p class='success'>✅ Всего записей: $totalRecords</p>";
        echo "<p class='success'>✅ За апрель 2026: $aprilRecords</p>";
        
        // Sample records
        echo "<h3>Примеры записей за 16 апреля:</h3>";
        $sample = TimeRecord::where('date', '2026-04-16')->get();
        echo "<table><tr><th>Сотр ID</th><th>Дата</th><th>Статус</th><th>Часы</th></tr>";
        foreach ($sample as $rec) {
            echo "<tr><td>{$rec->employee_id}</td><td>{$rec->date}</td><td>{$rec->status}</td><td>{$rec->hours}</td></tr>";
        }
        echo "</table>";
        echo "<p>Найдено " . $sample->count() . " записей за 16.04.2026</p>";
        echo "</div>";
        
        // 3. Проверка загрузки контроллером
        echo "<div class='section'>";
        echo "<h2>3. Загрузка данных контроллером (как в index())</h2>";
        
        $currentMonth = 4;
        $currentYear = 2026;
        $monthStart = Carbon::createFromDate($currentYear, $currentMonth, 1);
        $monthEnd = $monthStart->copy()->endOfMonth();
        
        // Загружаем первых 5 сотрудников
        $employees = Employee::take(5)->get();
        $employeeIds = $employees->pluck('id')->toArray();
        
        echo "<p>Employee IDs для загрузки: " . json_encode($employeeIds) . "</p>";
        
        $timeRecords = TimeRecord::whereBetween('date', [$monthStart, $monthEnd])
            ->whereIn('employee_id', $employeeIds)
            ->get()
            ->groupBy('employee_id')
            ->map(fn($records) => $records->keyBy(fn($r) => $r->date->day));
        
        echo "<p class='success'>✅ Загруженные TimeRecords: " . $timeRecords->count() . " сотрудников</p>";
        
        foreach ($timeRecords as $empId => $days) {
            echo "<p>Сотр $empId: " . $days->count() . " дней с данными</p>";
            if (isset($days[16])) {
                echo "  - День 16: " . $days[16]->status . " (" . $days[16]->hours . "ч)<br>";
            } else {
                echo "  - День 16: НЕТ ДАННЫХ<br>";
            }
        }
        
        echo "</div>";
        
        // 4. Прямое внесение тестовых данных
        echo "<div class='section'>";
        echo "<h2>4. Тестовое внесение данных</h2>";
        
        if ($request->has('test-insert')) {
            $testRecords = [
                [
                    'employee_id' => 1,
                    'date' => '2026-04-16',
                    'status' => 'present',
                    'hours' => 8.5,
                ],
                [
                    'employee_id' => 2,
                    'date' => '2026-04-16',
                    'status' => 'absent',
                    'hours' => 0,
                ]
            ];
            
            foreach ($testRecords as $data) {
                TimeRecord::updateOrCreate(
                    ['employee_id' => $data['employee_id'], 'date' => $data['date']],
                    $data
                );
            }
            
            echo "<p class='success'>✅ Тестовые данные внесены</p>";
        }
        
        echo "<form method='GET'>";
        echo "<button type='submit' name='test-insert' value='1'>Внести тестовые данные</button>";
        echo "</form>";
        echo "</div>";
        
        echo "</body></html>";
    }
}
