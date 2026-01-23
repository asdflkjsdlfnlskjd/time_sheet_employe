<?php

use Illuminate\Support\Facades\Route;


Route::group(['namespace' => 'App\Http\Controllers\Auth' ], function (){
    Route::get('/login', 'IndexController');
});

Route::group(['namespace' => 'App\Http\Controllers\Main' ], function (){
    Route::get('/main', 'IndexController')->name('admin.main.index');
});


Route::group(['namespace' => 'App\Http\Controllers\Dashboard' ], function (){
    Route::get('/dashboard', 'IndexController')->name('admin.dashboard.index');
});

