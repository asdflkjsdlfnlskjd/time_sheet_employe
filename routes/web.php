<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AdminAuthController;
use App\Http\Controllers\Auth\IndexController as AuthIndexController;

// Маршруты для аутентификации администратора
Route::group(['namespace' => 'App\Http\Controllers\Auth'], function () {
    Route::get('/login', [AuthIndexController::class, '__invoke'])->name('admin.login');
    Route::post('/login', [AdminAuthController::class, 'login'])->name('admin.login.process');
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');
});

// Маршруты для main и dashboard
Route::group(['namespace' => 'App\Http\Controllers\Main'], function () {
    Route::get('/main', 'IndexController')->name('admin.main.index');
});

Route::group(['namespace' => 'App\Http\Controllers\Dashboard'], function () {
    Route::get('/dashboard', 'IndexController')->name('admin.dashboard.index'); // Это имя используется
});

Route::get('/', function () {
    return redirect()->route('admin.login');
});
