<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель - {{ $title ?? 'TimeFlow' }}</title>
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body>
<nav class="admin-navbar">
    <div class="navbar-brand">Админ-панель</div>
    <div class="navbar-user">
        Привет, {{ session('admin_name') }}!
        <form action="{{ route('admin.logout') }}" method="POST" style="display: inline;">
            @csrf
            <button type="submit" class="btn-logout">Выйти</button>
        </form>
    </div>
</nav>

<div class="admin-container">
    @yield('content')
</div>
</body>
</html>
