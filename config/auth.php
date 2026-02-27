<?php
// config/auth.php

return [
    'defaults' => [
        'guard' => 'web',
        'passwords' => 'admins', // Изменяем на admins
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'admins', // Изменяем с 'users' на 'admins'
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
        ],

        // ДОБАВЛЯЕМ НОВЫЙ ПРОВАЙДЕР ДЛЯ АДМИНОВ
        'admins' => [
            'driver' => 'eloquent',
            'model' => App\Models\Admin::class,
        ],
    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],

        // ДОБАВЛЯЕМ ДЛЯ АДМИНОВ (если нужно восстановление пароля)
        'admins' => [
            'provider' => 'admins',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => 10800,
];
