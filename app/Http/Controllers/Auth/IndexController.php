<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;

class IndexController extends Controller
{
    public function __invoke()
    {
        return view('admin.login.index');
    }
}
