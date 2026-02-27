<?php
// app/Http/Controllers/TimeRecordController.php

namespace App\Http\Controllers;

use App\Models\TimeRecord;
use App\Models\Employee;
use App\Models\Department; // Добавьте этот импорт
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TimeRecordController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        // Получаем текущего админа
        $admin = Auth::user();

        // ОТЛАДКА
        \Log::info('TimeRecordController - Admin role: ' . $admin->role);

        // Базовый запрос
        $query = TimeRecord::with(['employee.department']);

        // ПРОВЕРКА: если это НЕ супер-админ
        if ($admin->role !== 'super_admin') {
            // Получаем ID отдела админа
            $departmentId = null;

            if ($admin->employee && $admin->employee->department) {
                $departmentId = $admin->employee->department_id;
            }

            \Log::info('TimeRecordController - Filtering by department_id: ' . ($departmentId ?? 'null'));

            // Если есть отдел - фильтруем записи только этого отдела
            if ($departmentId) {
                $query->whereHas('employee', function($q) use ($departmentId) {
                    $q->where('department_id', $departmentId);
                });
            } else {
                // Если нет отдела - показываем пустой результат
                $query->whereRaw('1 = 0');
            }
        }

        // Фильтр по дате
        if ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        }

        // Сортировка
        $query->orderBy('date', 'desc');

        // Получаем результаты
        $records = $query->paginate(20);

        // Получаем список сотрудников для фильтра
        $employees = $this->getAccessibleEmployees($admin);

        // Дополнительные данные для шаблона
        $months = [
            1 => 'Январь', 2 => 'Февраль', 3 => 'Март', 4 => 'Апрель',
            5 => 'Май', 6 => 'Июнь', 7 => 'Июль', 8 => 'Август',
            9 => 'Сентябрь', 10 => 'Октябрь', 11 => 'Ноябрь', 12 => 'Декабрь'
        ];

        $currentMonth = now()->month;
        $currentYear = now()->year;
        $daysInMonth = now()->daysInMonth;
        $currentDay = now()->day;

        $weekDaysShort = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'];
        $weekDaysFull = ['Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота', 'Воскресенье'];

        $reasons = [
            '' => '—',
            'sick' => 'Больничный',
            'vacation' => 'Отпуск',
            'day_off' => 'Отгул',
            'business_trip' => 'Командировка'
        ];

        $departments = Department::orderBy('name')->get();
        $departmentId = $request->get('department', 'all');
        $search = $request->get('search', '');

        return view('time-records.index', compact(
            'records',
            'employees',
            'months',
            'currentMonth',
            'currentYear',
            'daysInMonth',
            'weekDaysShort',
            'weekDaysFull',
            'currentDay',
            'reasons',
            'departments',
            'departmentId',
            'search',
            'admin' // Добавляем админа в представление
        ));
    }

    private function getAccessibleEmployees($admin)
    {
        if ($admin->role === 'super_admin') {
            return Employee::orderBy('last_name')->get();
        }

        if ($admin->employee && $admin->employee->department_id) {
            return Employee::where('department_id', $admin->employee->department_id)
                ->orderBy('last_name')
                ->get();
        }

        return collect();
    }
}
