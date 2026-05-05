<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личные данные</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/main.css') }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    <style>
        body {
            background: linear-gradient(180deg, #f8fbff 0%, #f5f7fa 220px);
        }
        .profile-card {
            border-radius: 14px;
            border: 1px solid #e5e7eb;
        }
        .profile-card .card-header {
            border-bottom: 1px solid #eef2f7;
        }
        .profile-btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            border: none;
        }
        .profile-btn-primary:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        }
    </style>
</head>
<body>
<header class="header d-flex justify-content-between align-items-center">
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('admin.main.index') }}">
            <img src="{{ asset('images/logo.png') }}" alt="TimeFlow" width="122" height="82">
        </a>
    </div>
    <div class="persons d-flex align-items-center gap-3 p-4">
        <div class="text-end">
            <div class="fw-medium">{{ Session::get('admin_name') }}</div>
        </div>
        <div class="logo-circle d-flex align-items-center justify-content-center">
            @php
                $name = Session::get('admin_name');
                $initials = '';
                $nameParts = explode(' ', $name);
                foreach ($nameParts as $part) {
                    if (!empty($part)) {
                        $initials .= mb_substr($part, 0, 1);
                    }
                }
                $initials = mb_strtoupper($initials);
            @endphp
            {{ $initials }}
        </div>
        <div class="dropdown-menu-custom">
            <a href="{{ route('admin.main.index') }}" class="dropdown-item-custom">
                <i class="fas fa-table me-2"></i> Табель
            </a>
            <a href="{{ route('admin.dashboard.index') }}" class="dropdown-item-custom">
                <i class="fas fa-chart-bar me-2"></i> Статистика
            </a>
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit" class="dropdown-item-custom logout-item">
                    <i class="fas fa-sign-out-alt me-2"></i> Выйти
                </button>
            </form>
        </div>
    </div>
</header>

<main class="main-content">
    <div class="container-fluid px-4 py-3">
        <h1 class="page-title mb-3"><i class="fas fa-user-circle me-2"></i>Личные данные</h1>
        <p class="page-subtitle mb-4">Личные данные администратора или руководителя отдела</p>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card shadow-sm border-0 profile-card">
                    <div class="card-header bg-white">
                        <strong><i class="fas fa-id-card me-2"></i>Основные данные</strong>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.profile.update') }}">
                            @csrf
                            @method('PUT')

                            <div class="mb-3">
                                <label class="form-label">Логин</label>
                                <input type="text" class="form-control" name="name" value="{{ old('name', $admin->name) }}" required>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Фамилия</label>
                                    <input type="text" class="form-control" name="last_name" value="{{ old('last_name', optional($admin->employee)->last_name) }}">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Имя</label>
                                    <input type="text" class="form-control" name="first_name" value="{{ old('first_name', optional($admin->employee)->first_name) }}">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Отчество</label>
                                    <input type="text" class="form-control" name="middle_name" value="{{ old('middle_name', optional($admin->employee)->middle_name) }}">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email" value="{{ old('email', optional($admin->employee)->email) }}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Телефон</label>
                                    <input type="text" class="form-control" name="phone" value="{{ old('phone', optional($admin->employee)->phone) }}">
                                </div>
                            </div>

                            <button type="submit" class="btn profile-btn-primary text-white">
                                <i class="fas fa-save me-2"></i>Сохранить профиль
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card shadow-sm border-0 mb-4 profile-card">
                    <div class="card-header bg-white">
                        <strong><i class="fas fa-lock me-2"></i>Смена пароля</strong>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.profile.password') }}">
                            @csrf
                            @method('PUT')

                            <div class="mb-3">
                                <label class="form-label">Текущий пароль</label>
                                <input type="password" class="form-control" name="current_password" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Новый пароль</label>
                                <input type="password" class="form-control" name="password" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Подтверждение нового пароля</label>
                                <input type="password" class="form-control" name="password_confirmation" required>
                            </div>

                            <button type="submit" class="btn btn-outline-primary">
                                <i class="fas fa-key me-2"></i>Обновить пароль
                            </button>
                        </form>
                    </div>
                </div>

                <div class="card shadow-sm border-0 profile-card">
                    <div class="card-header bg-white">
                        <strong><i class="fas fa-user-shield me-2"></i>Роль и доступ</strong>
                    </div>
                    <div class="card-body">
                        <p class="mb-2"><strong>Роль:</strong> {{ $admin->role === 'super_admin' ? 'Супер-админ' : 'Руководитель отдела' }}</p>
                        <p class="mb-0">
                            <strong>Отдел:</strong>
                            @if($admin->role === 'super_admin')
                                Все отделы (не привязан к конкретному отделу)
                            @else
                                {{ optional(optional($admin->employee)->managedDepartment)->name ?? optional(optional($admin->employee)->department)->name ?? 'Не назначен' }}
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
