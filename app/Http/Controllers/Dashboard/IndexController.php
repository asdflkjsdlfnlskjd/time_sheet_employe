<?php

namespace App\Http\Controllers\Dashboard;

 class IndexController
{
    public function __invoke()
    {
        return view('admin.dashboard.index');
    }
}
