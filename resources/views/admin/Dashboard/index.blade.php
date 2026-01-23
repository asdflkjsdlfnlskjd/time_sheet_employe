<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Статистика</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{asset('main.css')}}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
</head>

<body>
<!-- HEADER -->
<header class="header d-flex justify-content-between align-items-center">
    <div class="d-flex align-items-center gap-2">
        <img src="{{asset('images/logo.png')}}" alt="TimeFlow" width="122" height="82">
    </div>
    <div class="persons d-flex align-items-center gap-3 p-4">
        <div class="text-end">
            <div class="fw-medium">Иван Иванов</div>
            <small class="opacity-75">Менеджер по персоналу</small>
        </div>
        <div class="logo-circle d-flex align-items-center justify-content-center">ИИ</div>
    </div>
</header>

<!-- TABS -->
<nav class="tabs-container">
    <ul class="nav nav-tabs">
        <li class="nav-item">
            <a class="nav-link" type="button" href="{{ route('admin.main.index') }}">Табель</a>
        </li>
        <li class="nav-item">
            <a class="nav-link active" type="button" href="{{route('admin.dashboard.index')}}">Статистика</a>
        </li>
    </ul>
</nav>






{{--<footer>--}}
{{--    © 2026 Табель - Система учета рабочего времени. Все права защищены.--}}
{{--</footer>--}}
