<?php
// app/Http/Controllers/Dashboard/DashboardController.php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Employee;
use App\Models\TimeRecord;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // ПРОВЕРЯЕМ АВТОРИЗАЦИЮ
        if (!Auth::check()) {
            return redirect('/login');
        }

        $admin = Auth::user();
        $period = $request->get('period', 'month'); // day, week, month, year
        $departmentId = $request->get('department'); // для фильтрации по отделу

        // Получаем диапазон дат в зависимости от периода
        $dates = $this->getDateRange($period);
        $startDate = $dates['start'];
        $endDate = $dates['end'];

        // Получаем доступные отделы для фильтра
        $departmentQuery = Department::query();
        if ($admin->role !== 'super_admin') {
            $adminDeptId = $admin->employee->department_id ?? null;
            if ($adminDeptId) {
                $departmentQuery->where('id', $adminDeptId);
            }
        }
        $departments = $departmentQuery->get();

        // Получаем сотрудников с учетом прав доступа
        $employeeQuery = Employee::with('department');
        if ($admin->role !== 'super_admin') {
            $adminDeptId = $admin->employee->department_id ?? null;
            if ($adminDeptId) {
                $employeeQuery->where('department_id', $adminDeptId);
            }
        }
        $employees = $employeeQuery->get();
        $employeeIds = $employees->pluck('id')->toArray();

        // Получаем TimeRecords за период
        $timeRecords = TimeRecord::with('employee')
            ->whereIn('employee_id', $employeeIds)
            ->whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate)
            ->get();

        // СТАТИСТИКА
        $stats = $this->getStatistics($timeRecords, $employees, $startDate, $endDate);

        // АНАЛИТИКА ВРЕМЕНИ ПО ОТДЕЛАМ
        $analyticsData = $this->getAnalyticsData($timeRecords, $startDate, $endDate, $period, $employees, $departmentId);
        
        // Логирование для отладки
        \Log::info('Dashboard Analytics Data', [
            'period' => $period,
            'departmentId' => $departmentId,
            'timeRecordsCount' => count($timeRecords),
            'employeesCount' => count($employees),
            'datasetsCount' => count($analyticsData['datasets']),
            'labelsCount' => count($analyticsData['labels']),
            'datasetsNames' => array_column($analyticsData['datasets'], 'label')
        ]);

        // ПОСЛЕДНИЕ ДЕЙСТВИЯ
        $recentActivities = $this->getRecentActivities($admin);

        // РАСПРЕДЕЛЕНИЕ СТАТУСОВ ДЛЯ КРУГОВОЙ ДИАГРАММЫ
        $statusDistribution = $this->getStatusDistribution($stats);

        // Данные для фильтра
        $months = [
            1 => 'Январь', 2 => 'Февраль', 3 => 'Март', 4 => 'Апрель',
            5 => 'Май', 6 => 'Июнь', 7 => 'Июль', 8 => 'Август',
            9 => 'Сентябрь', 10 => 'Октябрь', 11 => 'Ноябрь', 12 => 'Декабрь'
        ];

        return view('admin.dashboard.index', compact(
            'admin',
            'stats',
            'analyticsData',
            'recentActivities',
            'period',
            'months',
            'startDate',
            'endDate',
            'departments',
            'departmentId',
            'statusDistribution'
        ));
    }

    /**
     * Получить диапазон дат для периода
     */
    private function getDateRange($period)
    {
        $now = now();
        
        switch($period) {
            case 'day':
                return [
                    'start' => $now->copy()->startOfDay(),
                    'end' => $now->copy()->endOfDay()
                ];
            case 'week':
                return [
                    'start' => $now->copy()->startOfWeek(),
                    'end' => $now->copy()->endOfWeek()
                ];
            case 'year':
                return [
                    'start' => $now->copy()->startOfYear(),
                    'end' => $now->copy()->endOfYear()
                ];
            case 'month':
            default:
                return [
                    'start' => $now->copy()->startOfMonth(),
                    'end' => $now->copy()->endOfMonth()
                ];
        }
    }

    /**
     * Получить статистику
     */
    private function getStatistics($timeRecords, $employees, $startDate, $endDate)
    {
        $statusMap = TimeRecord::getStatusMap();
        
        // Считаем по статусам (только дни с часами > 0 считаются отработанными)
        $present = 0;
        $absent = 0;
        $late = 0;
        $earlyLeave = 0;
        $vacation = 0;
        $sickLeave = 0;
        $dayOff = 0;
        $totalHours = 0;
        $overtimeHours = 0;

        foreach ($timeRecords as $record) {
            switch($record->status) {
                case 'present':
                    // Считаем только если есть часы
                    if ($record->hours > 0) {
                        $present++;
                        $totalHours += $record->hours;
                        if ($record->hours > 8) {
                            $overtimeHours += $record->hours - 8;
                        }
                    }
                    break;
                case 'absent':
                    $absent++;
                    break;
                case 'late':
                    // Считаем только если есть часы
                    if ($record->hours > 0) {
                        $late++;
                        $totalHours += $record->hours;
                    }
                    break;
                case 'early_leave':
                    // Считаем только если есть часы
                    if ($record->hours > 0) {
                        $earlyLeave++;
                        $totalHours += $record->hours;
                    }
                    break;
                case 'vacation':
                    $vacation++;
                    break;
                case 'sick_leave':
                    $sickLeave++;
                    break;
                case 'day_off':
                    $dayOff++;
                    break;
            }
        }

        $workingDaysCount = $present + $late + $earlyLeave;
        $absentDaysCount = $absent + $vacation + $sickLeave;

        return [
            'totalEmployees' => count($employees),
            'working' => $workingDaysCount,
            'absent' => $absent,
            'late' => $late,
            'earlyLeave' => $earlyLeave,
            'vacation' => $vacation,
            'sickLeave' => $sickLeave,
            'dayOff' => $dayOff,
            'totalHours' => round($totalHours, 1),
            'overtimeHours' => round($overtimeHours, 1),
            'avgHoursPerDay' => $workingDaysCount > 0 ? round($totalHours / $workingDaysCount, 1) : 0,
            'absentDaysCount' => $absentDaysCount
        ];
    }

    /**
     * Получить данные для аналитики с разбивкой по отделам
     */
    private function getAnalyticsData($timeRecords, $startDate, $endDate, $period, $employees, $departmentId = null)
    {
        // Получаем уникальные отделы из сотрудников
        $availableDepartmentIds = $employees->pluck('department_id')->unique()->toArray();
        
        // Если выбран конкретный отдел, фильтруем по нему
        if ($departmentId) {
            $availableDepartmentIds = array_intersect($availableDepartmentIds, [$departmentId]);
        }

        $departments = Department::whereIn('id', $availableDepartmentIds)->get();

        // Цвета для каждого отдела
        $colors = [
            ['border' => '#667eea', 'bg' => 'rgba(102, 126, 234, 0.1)'],
            ['border' => '#10b981', 'bg' => 'rgba(16, 185, 129, 0.1)'],
            ['border' => '#f59e0b', 'bg' => 'rgba(245, 158, 11, 0.1)'],
            ['border' => '#ef4444', 'bg' => 'rgba(239, 68, 68, 0.1)'],
            ['border' => '#8b5cf6', 'bg' => 'rgba(139, 92, 246, 0.1)'],
            ['border' => '#ec4899', 'bg' => 'rgba(236, 72, 153, 0.1)'],
            ['border' => '#14b8a6', 'bg' => 'rgba(20, 184, 166, 0.1)'],
            ['border' => '#f97316', 'bg' => 'rgba(249, 115, 22, 0.1)'],
        ];

        $labels = [];
        $datasets = [];

        if ($period === 'day') {
            // За текущий день
            $labels[] = $startDate->format('d.m.Y');

            foreach ($departments as $index => $department) {
                $deptEmployees = $employees->where('department_id', $department->id);
                $deptEmployeeIds = $deptEmployees->pluck('id')->toArray();
                $deptTimeRecords = $timeRecords->whereIn('employee_id', $deptEmployeeIds);

                $sum = $deptTimeRecords->filter(function($r) use ($startDate) {
                    return $r->date->format('Y-m-d') === $startDate->format('Y-m-d');
                })->sum('hours');

                $colorIndex = $index % count($colors);
                $datasets[] = [
                    'label' => $department->name,
                    'data' => [round($sum, 1)],
                    'borderColor' => $colors[$colorIndex]['border'],
                    'backgroundColor' => $colors[$colorIndex]['bg'],
                ];
            }
        } elseif ($period === 'week') {
            // По дням недели - начиная с понедельника текущей недели
            $weekDays = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'];
            
            for ($day = 0; $day < 7; $day++) {
                $labels[] = $weekDays[$day];
            }

            foreach ($departments as $index => $department) {
                $deptEmployees = $employees->where('department_id', $department->id);
                $deptEmployeeIds = $deptEmployees->pluck('id')->toArray();
                $deptTimeRecords = $timeRecords->whereIn('employee_id', $deptEmployeeIds);

                $data = [];
                for ($day = 0; $day < 7; $day++) {
                    $date = $startDate->copy()->addDays($day);
                    $sum = $deptTimeRecords->filter(function($r) use ($date) {
                        return $r->date->format('Y-m-d') === $date->format('Y-m-d');
                    })->sum('hours');
                    $data[] = round($sum, 1);
                }

                $colorIndex = $index % count($colors);
                $datasets[] = [
                    'label' => $department->name,
                    'data' => $data,
                    'borderColor' => $colors[$colorIndex]['border'],
                    'backgroundColor' => $colors[$colorIndex]['bg'],
                ];
            }
        } elseif ($period === 'month') {
            // По дням месяца
            $daysInMonth = $startDate->daysInMonth;
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $labels[] = $day;
            }

            foreach ($departments as $index => $department) {
                $deptEmployees = $employees->where('department_id', $department->id);
                $deptEmployeeIds = $deptEmployees->pluck('id')->toArray();
                $deptTimeRecords = $timeRecords->whereIn('employee_id', $deptEmployeeIds);

                $data = [];
                for ($day = 1; $day <= $daysInMonth; $day++) {
                    $date = $startDate->copy()->startOfMonth()->addDays($day - 1);
                    $sum = $deptTimeRecords->filter(function($r) use ($date) {
                        return $r->date->format('Y-m-d') === $date->format('Y-m-d');
                    })->sum('hours');
                    $data[] = round($sum, 1);
                }

                $colorIndex = $index % count($colors);
                $datasets[] = [
                    'label' => $department->name,
                    'data' => $data,
                    'borderColor' => $colors[$colorIndex]['border'],
                    'backgroundColor' => $colors[$colorIndex]['bg'],
                ];
            }
        } elseif ($period === 'year') {
            // По месяцам года
            $monthNames = ['Янв', 'Фев', 'Мар', 'Апр', 'Май', 'Июн', 'Июл', 'Авг', 'Сен', 'Окт', 'Ноя', 'Дек'];
            for ($month = 1; $month <= 12; $month++) {
                $labels[] = $monthNames[$month - 1];
            }

            foreach ($departments as $index => $department) {
                $deptEmployees = $employees->where('department_id', $department->id);
                $deptEmployeeIds = $deptEmployees->pluck('id')->toArray();
                $deptTimeRecords = $timeRecords->whereIn('employee_id', $deptEmployeeIds);

                $data = [];
                for ($month = 1; $month <= 12; $month++) {
                    $monthStart = Carbon::createFromDate($startDate->year, $month, 1);
                    $monthEnd = $monthStart->copy()->endOfMonth();
                    $count = $deptTimeRecords->filter(function($r) use ($monthStart, $monthEnd) {
                        return $r->date >= $monthStart && $r->date <= $monthEnd;
                    })->sum('hours');
                    $data[] = round($count, 1);
                }

                $colorIndex = $index % count($colors);
                $datasets[] = [
                    'label' => $department->name,
                    'data' => $data,
                    'borderColor' => $colors[$colorIndex]['border'],
                    'backgroundColor' => $colors[$colorIndex]['bg'],
                ];
            }
        }

        return [
            'labels' => $labels,
            'datasets' => $datasets
        ];
    }

    /**
     * Конвертировать HEX цвет в RGB
     */
    private function hexToRgb($hex)
    {
        $hex = str_replace("#", "", $hex);
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        return "rgb($r, $g, $b, 1)";
    }

    /**
     * Получить распределение статусов для круговой диаграммы
     */
    private function getStatusDistribution($stats)
    {
        // Подготавливаем данные для диаграммы
        $labels = [];
        $data = [];
        $colors = [];
        $colorMap = [
            'present' => ['color' => 'rgba(16, 185, 129, 0.8)', 'border' => '#10b981'],      // green - присутствие
            'absent' => ['color' => 'rgba(239, 68, 68, 0.8)', 'border' => '#ef4444'],        // red - отсутствие
            'late' => ['color' => 'rgba(245, 158, 11, 0.8)', 'border' => '#f59e0b'],         // orange - опоздание
            'vacation' => ['color' => 'rgba(6, 182, 212, 0.8)', 'border' => '#06b6d4'],     // cyan - отпуск
            'sick_leave' => ['color' => 'rgba(168, 85, 247, 0.8)', 'border' => '#a855f7'], // purple - болезнь
            'day_off' => ['color' => 'rgba(100, 116, 139, 0.8)', 'border' => '#64748b'],    // slate - выходной
        ];

        $statusNames = [
            'present' => 'Присутствие',
            'absent' => 'Отсутствие',
            'late' => 'Опоздание',
            'vacation' => 'Отпуск',
            'sick_leave' => 'Болезнь',
            'day_off' => 'Выходной',
        ];

        // Проходим по каждому статусу
        if ($stats['working'] > 0 || true) {
            $labels[] = $statusNames['present'];
            $data[] = $stats['working'];
            $colors[] = $colorMap['present']['color'];
        }

        if ($stats['absent'] > 0 || true) {
            $labels[] = $statusNames['absent'];
            $data[] = $stats['absent'];
            $colors[] = $colorMap['absent']['color'];
        }

        if ($stats['late'] > 0 || true) {
            $labels[] = $statusNames['late'];
            $data[] = $stats['late'];
            $colors[] = $colorMap['late']['color'];
        }

        if ($stats['vacation'] > 0 || true) {
            $labels[] = $statusNames['vacation'];
            $data[] = $stats['vacation'];
            $colors[] = $colorMap['vacation']['color'];
        }

        if ($stats['sickLeave'] > 0 || true) {
            $labels[] = $statusNames['sick_leave'];
            $data[] = $stats['sickLeave'];
            $colors[] = $colorMap['sick_leave']['color'];
        }

        if ($stats['dayOff'] > 0 || true) {
            $labels[] = $statusNames['day_off'];
            $data[] = $stats['dayOff'];
            $colors[] = $colorMap['day_off']['color'];
        }

        return [
            'labels' => $labels,
            'data' => $data,
            'backgroundColor' => $colors,
            'borderWidth' => 2,
            'borderColor' => array_map(function($status) use ($colorMap) {
                foreach ($colorMap as $key => $value) {
                    if ($key !== 'present') continue;
                    return $value['border'];
                }
            }, array_keys($colorMap))
        ];
    }

    /**
     * Получить последние действия
     */
    private function getRecentActivities($admin)
    {
        $activities = [];

        // Получаем доступные сотрудников для админа
        $employeeQuery = Employee::with('department');
        if ($admin->role !== 'super_admin') {
            $adminDeptId = $admin->employee->department_id ?? null;
            if ($adminDeptId) {
                $employeeQuery->where('department_id', $adminDeptId);
            }
        }
        $employees = $employeeQuery->get();
        $employeeIds = $employees->pluck('id')->toArray();

        // Получаем последние обновленные записи (за последние 24 часа) 
        // и показываем только те, которые реально содержат часы
        $timeRecords = TimeRecord::with('employee')
            ->whereIn('employee_id', $employeeIds)
            ->where('updated_at', '>=', now()->subHours(24))
            ->whereDate('date', '>=', now()->subDays(30))  // Но дата может быть от любого дня за последний месяц
            ->orderBy('updated_at', 'desc')  // Сортируем только по времени обновления
            ->get();

        foreach ($timeRecords as $record) {
            // Показываем все действия, включая обновления статуса
            $statusMap = TimeRecord::getStatusMap();
            $statusLabel = $statusMap[$record->status]['label'] ?? 'Неизвестно';
            
            // Формируем детали с актуальными часами
            $details = 'Дата: ' . $record->date->format('d.m.Y') . ' | Часов: ' . round($record->hours, 1);
            if (!empty($record->notes)) {
                $details .= ' | Примечание: ' . $record->notes;
            }
            
            $activities[] = [
                'type' => 'time_record',
                'icon' => 'fa-clock',
                'color' => 'primary',
                'message' => $record->employee->full_name . ' - ' . $statusLabel,
                'date' => $record->updated_at,
                'details' => $details
            ];

            // Показываем максимум 5 действий
            if (count($activities) >= 5) {
                break;
            }
        }

        return $activities;
    }
}
