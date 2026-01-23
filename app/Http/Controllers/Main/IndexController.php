<?php

namespace App\Http\Controllers\Main;

class IndexController
{
    public function __invoke()
    {
        return view('admin.main.index');
    }
}
