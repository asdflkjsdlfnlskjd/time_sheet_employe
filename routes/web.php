<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AdminAuthController;
use App\Http\Controllers\Auth\IndexController as AuthIndexController;
use App\Http\Controllers\Main\MainController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\DepartmentController;

// Вход
Route::get('/login', [AuthIndexController::class, '__invoke'])->name('admin.login');
Route::post('/login', [AdminAuthController::class, 'login'])->name('admin.login.process');
Route::post('/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

// Основные страницы
Route::get('/main', [MainController::class, 'index'])->name('admin.main.index');
Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard.index');

// Маршруты для сотрудников
Route::post('/employees', [EmployeeController::class, 'store'])->name('admin.employees.store');
Route::delete('/employees/{id}', [EmployeeController::class, 'destroy'])->name('admin.employees.destroy');

// Маршруты для отделов
Route::post('/departments', [DepartmentController::class, 'store'])->name('admin.departments.store');
Route::delete('/departments/{id}', [DepartmentController::class, 'destroy'])->name('admin.departments.destroy');

// Редирект
Route::get('/', function () {
    return redirect()->route('admin.login');
});
