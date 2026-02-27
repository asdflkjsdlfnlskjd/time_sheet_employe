<?php
// app/Http/Controllers/Main/MainController.php

namespace App\Http\Controllers\Main;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MainController extends Controller
{
    public function index(Request $request)
    {
        // ПРОВЕРЯЕМ АВТОРИЗАЦИЮ
        if (!Auth::check()) {
            return redirect('/login'); // Явный редирект на страницу входа
        }

        // Получаем текущего админа
        $admin = Auth::user();

        // Если админ не найден (редкий случай)
        if (!$admin) {
            Auth::logout();
            return redirect('/login');
        }

        // Данные для фильтров
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

        // Получаем сотрудников с учетом прав доступа
        $query = Employee::with('department');

        // Если не супер-админ - показываем только сотрудников своего отдела
        if ($admin->role !== 'super_admin') {
            $adminDepartmentId = $admin->employee->department_id ?? null;

            if ($adminDepartmentId) {
                $query->where('department_id', $adminDepartmentId);
            } else {
                // Если у админа нет отдела - показываем пустой результат
                $query->whereRaw('1 = 0');
            }
        } else {
            // Супер-админ может фильтровать по отделам
            if ($request->filled('department') && $request->department !== 'all') {
                $query->where('department_id', $request->department);
            }
        }

        // Поиск
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('middle_name', 'like', "%{$search}%")
                    ->orWhere('tab_number', 'like', "%{$search}%");
            });
        }

        // Сортировка и пагинация
        $employees = $query->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(15);

        return view('admin.main.index', compact(
            'admin',
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
            'search'
        ));
    }
}
