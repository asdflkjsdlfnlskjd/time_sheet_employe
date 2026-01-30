<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;

class IndexController extends Controller
{
    public function __invoke()
    {
        // Проверка авторизации
        if (!Session::has('admin_id')) {
            return redirect()->route('admin.login')->with('error', 'Пожалуйста, войдите в систему.');
        }

        return view('admin.dashboard.index');
    }
}
