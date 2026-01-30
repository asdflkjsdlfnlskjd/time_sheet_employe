<?php

namespace App\Http\Controllers\Main;

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

        return view('admin.main.index');
    }
}
