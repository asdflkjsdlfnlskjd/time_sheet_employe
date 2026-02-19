<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class AuthAdmin
{
    public function handle(Request $request, Closure $next)
    {
        if (!Session::has('admin_id')) {
            return redirect()->route('admin.login')->with('error', 'Пожалуйста, войдите в систему');
        }

        return $next($request);
    }
}
