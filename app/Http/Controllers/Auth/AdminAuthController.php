<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

class AdminAuthController extends Controller
{
    // Обработка входа
    public function login(Request $request)
    {
        // Валидация данных
        $request->validate([
            'name' => 'required|string',
            'password' => 'required|string',
        ]);

        // Поиск администратора
        $admin = Admin::where('name', $request->name)->first();

        // Проверка существования администратора
        if (!$admin) {
            // Если администратор не найден
            return back()->withErrors([
                'name' => 'Пользователь с таким логином не найден.',
            ])->withInput($request->only('name'));
        }

        // Проверка пароля
        if (!Hash::check($request->password, $admin->password)) {
            // Если пароль неверный
            return back()->withErrors([
                'password' => 'Неверный пароль.',
            ])->withInput($request->only('name'));
        }

        // Если все хорошо - сохраняем администратора в сессии
        Session::put('admin_id', $admin->id);
        Session::put('admin_name', $admin->name);
        Session::put('admin_role', $admin->role);

        // Редирект на дашборд
        return redirect()->route('admin.dashboard.index');
    }

    // Выход
    public function logout()
    {
        Session::forget(['admin_id', 'admin_name', 'admin_role']);
        return redirect()->route('admin.login');
    }
}
