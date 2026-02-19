<?php

namespace App\Http\Controllers\Main;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class MainController extends Controller
{
    public function index(Request $request)
    {
        // Проверка авторизации
        if (!Session::has('admin_id')) {
            return redirect()->route('admin.login')->with('error', 'Пожалуйста, войдите в систему.');
        }

        // Получаем параметры фильтрации
        $departmentId = $request->get('department', 'all');
        $search = $request->get('search', '');

        // Запрос сотрудников с отделами
        $employeesQuery = Employee::with('department');

        // Фильтр по отделу
        if ($departmentId && $departmentId != 'all') {
            $employeesQuery->where('department_id', $departmentId);
        }

        // Поиск по имени
        if ($search) {
            $employeesQuery->where(function($q) use ($search) {
                $q->where('last_name', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('middle_name', 'like', "%{$search}%");
            });
        }

        $employees = $employeesQuery->get();

        // ВАЖНО: загружаем отделы ВМЕСТЕ с руководителями (manager)
        $departments = Department::with('manager')->get();  // Добавлено with('manager')

        // Данные для календаря
        $today = now();
        $currentMonth = $today->format('m');
        $currentYear = $today->format('Y');
        $currentDay = $today->format('d');
        $daysInMonth = $today->format('t');

        $weekDaysShort = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'];
        $weekDaysFull = ['Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота', 'Воскресенье'];

        $months = [
            '01' => 'Январь', '02' => 'Февраль', '03' => 'Март', '04' => 'Апрель',
            '05' => 'Май', '06' => 'Июнь', '07' => 'Июль', '08' => 'Август',
            '09' => 'Сентябрь', '10' => 'Октябрь', '11' => 'Ноябрь', '12' => 'Декабрь'
        ];

        $reasons = [
            '' => '-',
            'vacation' => 'ОТ',
            'sick_leave' => 'Б',
            'business_trip' => 'К',
            'day_off' => 'ОГ',
            'remote' => 'У',
            'late' => 'ОП',
            'other' => 'Д'
        ];

        return view('admin.main.index', compact(
            'employees',
            'departments',
            'currentMonth',
            'currentYear',
            'currentDay',
            'daysInMonth',
            'weekDaysShort',
            'weekDaysFull',
            'months',
            'reasons',
            'departmentId',
            'search'
        ));
    }
}
