<?php
// app/Http/Controllers/Auth/AdminAuthController.php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'name' => 'required|string',
            'password' => 'required|string',
        ]);

        // Явно указываем использовать guard 'web' (который теперь использует admins)
        if (Auth::guard('web')->attempt($credentials)) {
            $request->session()->regenerate();

            // Сохраняем имя админа в сессии
            session(['admin_name' => Auth::guard('web')->user()->name]);

            return redirect('/main');
        }

        return back()->withErrors([
            'name' => 'Неверные учетные данные',
        ])->onlyInput('name');
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
