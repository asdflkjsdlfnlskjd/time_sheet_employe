<?php

namespace App\Http\Controllers\Auth;

 class IndexController
{
    public function __invoke()
    {
        return view('admin.login.index');
    }
}
