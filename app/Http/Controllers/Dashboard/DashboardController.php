<?php
// app/Http/Controllers/Dashboard/DashboardController.php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // ПРОВЕРЯЕМ АВТОРИЗАЦИЮ
        if (!Auth::check()) {
            return redirect('/login');
        }

        $admin = Auth::user();

        return view('admin.dashboard.index', compact('admin'));
    }
}
