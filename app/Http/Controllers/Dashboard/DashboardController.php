<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Department;
use Illuminate\Support\Facades\Session;

class DashboardController extends Controller
{
    public function index()
    {
        // Проверка авторизации
        if (!Session::has('admin_id')) {
            return redirect()->route('admin.login')->with('error', 'Пожалуйста, войдите в систему.');
        }

        // Данные для статистики
        $totalEmployees = Employee::count();
        $totalDepartments = Department::count();

        // Сотрудники по отделам
        $employeesByDepartment = Department::withCount('employees')->get();

        return view('admin.dashboard.index', compact(
            'totalEmployees',
            'totalDepartments',
            'employeesByDepartment'
        ));
    }
}
