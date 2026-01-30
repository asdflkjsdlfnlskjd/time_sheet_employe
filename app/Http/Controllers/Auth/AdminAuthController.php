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

        // Проверка пароля
        if ($admin && Hash::check($request->password, $admin->password)) {
            // Сохраняем администратора в сессии
            Session::put('admin_id', $admin->id);
            Session::put('admin_name', $admin->name);
            Session::put('admin_role', $admin->role);

            // Редирект на дашборд - используем правильное имя маршрута
            return redirect()->route('admin.dashboard.index'); // Изменено с admin.dashboard.index на admin.dashboard
        }

        // Если аутентификация не удалась
        return back()->withErrors([
            'name' => 'Неверные учетные данные.',
        ])->withInput($request->only('name'));
    }

    // Выход
    public function logout()
    {
        Session::forget(['admin_id', 'admin_name', 'admin_role']);
        return redirect()->route('admin.login');
    }
}
