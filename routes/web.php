<?php
// routes/web.php

use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Auth\AdminAuthController;
use App\Http\Controllers\Auth\IndexController as AuthIndexController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\Main\MainController;
use App\Http\Controllers\Main\DebugController;
use Illuminate\Support\Facades\Route;

// Вход
Route::get('/login', [AuthIndexController::class, '__invoke'])->name('admin.login');
Route::post('/login', [AdminAuthController::class, 'login'])->name('admin.login.process');
Route::post('/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

// Основные страницы - УБРАЛИ MIDDLEWARE
Route::get('/main', [MainController::class, 'index'])->name('admin.main.index');
Route::post('/main/save-time-records', [MainController::class, 'saveTimeRecords'])->name('admin.main.save');
Route::get('/main/export-excel', [MainController::class, 'exportExcel'])->name('admin.main.export');
Route::get('/main/download-import-template', [MainController::class, 'downloadImportTemplate'])->name('admin.main.download-template');
Route::post('/main/import-excel', [MainController::class, 'importExcel'])->name('admin.main.import');
Route::get('/main/generate-employees', [MainController::class, 'generateEmployeesExcel'])->name('admin.main.generate');
Route::patch('/time-records/{employeeId}/{date}', [MainController::class, 'updateTimeRecord'])->name('admin.time-record.update');
Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard.index');

// DEBUG маршрут для проверки данных
Route::get('/debug/check-data', [DebugController::class, 'checkData'])->name('admin.debug.check');

// Маршруты для сотрудников - УБРАЛИ MIDDLEWARE
Route::post('/employees', [EmployeeController::class, 'store'])->name('admin.employees.store');
Route::put('/employees/{id}', [EmployeeController::class, 'update'])->name('admin.employees.update');
Route::delete('/employees/{id}', [EmployeeController::class, 'destroy'])->name('admin.employees.destroy');

// Маршруты для отделов - УБРАЛИ MIDDLEWARE
Route::post('/departments', [DepartmentController::class, 'store'])->name('admin.departments.store');
Route::delete('/departments/{id}', [DepartmentController::class, 'destroy'])->name('admin.departments.destroy');

// Профиль админа и руководителей отделов
Route::get('/profile', [ProfileController::class, 'edit'])->name('admin.profile.edit');
Route::put('/profile', [ProfileController::class, 'update'])->name('admin.profile.update');
Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('admin.profile.password');

// Редирект
Route::get('/', function () {
    return redirect()->route('admin.login');
});
