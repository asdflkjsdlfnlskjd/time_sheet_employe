<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Табель</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/main.css') }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" defer></script>
    <script src="../../../../js/main.js"></script>

</head>

<body>
<!-- HEADER -->
<header class="header d-flex justify-content-between align-items-center">
    <div class="d-flex align-items-center gap-2">
        <img src="{{ asset('images/logo.png') }}" alt="TimeFlow" width="122" height="82">
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
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit" class="dropdown-item-custom">
                    <i class="fas fa-sign-out-alt me-2"></i> Выйти
                </button>
            </form>
        </div>
    </div>
</header>

<!-- TABS -->
<nav class="tabs-container">
    <ul class="nav nav-tabs">
        <li class="nav-item">
            <a class="nav-link active" href="{{ route('admin.main.index') }}">Табель</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="{{ route('admin.dashboard.index') }}">Статистика</a>
        </li>
    </ul>
</nav>

<!-- MAIN CONTENT -->
<main class="main-content">
    <h1 class="page-title">Табель учета рабочего времени</h1>
    <p class="page-subtitle">
        Внесение и редактирование данных о рабочем времени сотрудников
    </p>

    <!-- Сообщения об успехе/ошибке -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Top Action Buttons -->
    <div class="top-actions">
        <button class="btn btn-secondary btn-sm">
            <svg width="25" height="25" viewBox="3 0 25 25" fill="none" xmlns="http://www.w3.org/2000/svg"
                 xmlns:xlink="http://www.w3.org/1999/xlink">
                <rect width="25" height="25" fill="url(#pattern0_38_270)"/>
                <defs>
                    <pattern id="pattern0_38_270" patternContentUnits="objectBoundingBox" width="1" height="1">
                        <use xlink:href="#image0_38_270" transform="scale(0.01)"/>
                    </pattern>
                    <image id="image0_38_270" width="100" height="100" preserveAspectRatio="none"
                           xlink:href="data:image/png;base64,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"/>
                </defs>
            </svg>
            Импорт
        </button>
        <button class="btn btn-secondary btn-sm">
            <svg width="25" height="25" viewBox="2 0 25 25" fill="none" xmlns="http://www.w3.org/2000/svg"
                 xmlns:xlink="http://www.w3.org/1999/xlink">
                <rect x="25" y="25" width="25" height="25" transform="rotate(180 25 25)" fill="url(#pattern0_38_279)"/>
                <defs>
                    <pattern id="pattern0_38_279" patternContentUnits="objectBoundingBox" width="1" height="1">
                        <use xlink:href="#image0_38_279" transform="scale(0.01)"/>
                    </pattern>
                    <image id="image0_38_279" width="100" height="100" preserveAspectRatio="none"
                           xlink:href="data:image/png;base64,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"/>
                </defs>
            </svg>
            Экспорт
        </button>
        <button class="btn btn-primary btn-sm">
            <svg width="25" height="25" viewBox="3 0 25 25" fill="none" xmlns="http://www.w3.org/2000/svg"
                 xmlns:xlink="http://www.w3.org/1999/xlink">
                <rect width="25" height="25" fill="url(#pattern0_38_285)"/>
                <defs>
                    <pattern id="pattern0_38_285" patternContentUnits="objectBoundingBox" width="1" height="1">
                        <use xlink:href="#image0_38_285" transform="scale(0.01)"/>
                    </pattern>
                    <image id="image0_38_285" width="100" height="100" preserveAspectRatio="none"
                           xlink:href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAGQAAABkCAYAAABw4pVUAAAD1klEQVR4AeycP4sUQRDFd8Xs5AQ1EExVNFE4A839k1yo3pcw8g9+hcNAQ00NFcXM4AINzBRBzBTRSFPh8NTggr1X3F2yTPdM90xV12y/pd4t9kxXV73f1nqwyx2Y8OHKAQJxhWMyIRACceaAs3I4IQTizAFn5XBCCMSZA87KSZ6Q2Wy2BN2HPkJbUE2xjWY3oGNaHJOAoJCTKOQT9AC6AC1BNcVBNHsNegMvVKB0BoICxPzXKOYUFI/Fv3oOLapA6QwEBdyCTkOMXQdUoKQAWdutI+/ndOSPQNeDQ0kBciZQVO3LAuUd3tKPD2FEChD5P2SIMxcxx1k09XYIKClAcCYj4sAgUAgk4nDGpd5QCCTD9ZYtvaBUAwTv79FoMTn1cjaUaoCkOjrA/VlQxgRkAI/MUyRDIRB9RklQCEQfiJzQGQqBiF026gSFQGxg7J/SCoVA9q2yexYoT0PHEUjIGd31y6H0BBJyRnddPnlsPIFAGm0pt0gg5bxvPJlAGm0pt0gg5bxvPJlAGm0pt0gg5bxvPLkaIG1feml0p8BiNUAKeJt1pBkQfFy3nFWhg02o/bBVGWZA0NAVaKxx1apwSyDreKUdtWpsqHNQ8xHkWodMwhKIfC/4Mxpcg9y/fUmN0A1QeA/Jt/7xpB+WQKSbE/jxHNpEs65DaoReQDkwsC0vrIHkVVnRLgJxBptACMSZA87K4YQQiDMHnJXDCSEQZw44K4cTQiDOHHBWTnRCnNVaRTkE4gwzgVQO5Cf6vwktt33G7e261Axdh75BamE5IQLjPIx+Cf1R60gpsdQMvUL6S9AvSCUsgdxGQ79VujBMutfDHa0jLYFsaDVRIK9aL2ZA8Moa3dtUCDR62Qxd67tuBqRvobXsJxBnpAsAceaAs3IIhECcOeCsHE4IgThzwFk5nBACceaAs3I4IQTizAFn5XBCCETHgUXJyglxRpJACMSZA87K4YQQiDMHnJXDCSEQZw44K4cTQiDOHHBWDickCsT+Ym8g07mHfQu+TpyzY5paXW8gqQfy/rgDBBL3x/wqgZhbHj8wBchWU6r5P+nTdE9Nax39CH7POQXI15qMVe71Syh/ChD5O1ehPK3r86+csf+7teH4Dc9Cl1OAPEYSTglM6BkyHU9COToDwe/Xf5FkFSIUmJAZAmMVXv4P7e8MRBIg0Xc8r0D3oA+QQMITI+KAeCRe3cU9K/DwB56DkQREsiDhP+ghdBE6BDHiDohH4tUj3BacDPFWlAxENlF6DhCInrdZmQkkyza9TQSi521WZgLJsk1vE4HoeZuVmUCybNPbtAMAAP//zN6dIQAAAAZJREFUAwDu9kT28UCPLwAAAABJRU5ErkJggg=="/>
                </defs>
            </svg>
            Сохранить данные
        </button>
    </div>

    <!-- Filters -->
    <div class="filters-card">
        <form method="GET" action="{{ route('admin.main.index') }}" class="filters-row">
            <div class="filter-group">
                <label class="filter-label">Период</label>
                <select class="form-select form-select-sm" name="month">
                    @foreach($months as $num => $name)
                        <option value="{{ $num }}" {{ $currentMonth == $num ? 'selected' : '' }}>
                            {{ $name }} {{ $currentYear }}
                        </option>
                    @endforeach
                </select>
            </div>

            @if($admin && $admin->role === 'super_admin')
                <div class="filter-group">
                    <label class="filter-label">Отдел</label>
                    <select class="form-select form-select-sm" name="department">
                        <option value="all">Все отделы</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ $departmentId == $dept->id ? 'selected' : '' }}>
                                {{ $dept->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="search-group">
                <label class="filter-label">Поиск сотрудника</label>
                <input class="form-control form-control-sm" name="search"
                       placeholder="Введите имя сотрудника" value="{{ $search ?? '' }}">
            </div>

            <div class="filter-group">
                <button type="submit" class="btn btn-primary apply-btn">Применить</button>
            </div>
        </form>
    </div>

    <!-- Add Buttons -->
    <div class="add-buttons">
        <button class="btn btn-success p-2" data-bs-toggle="modal" data-bs-target="#employeeModal">
            + Добавить сотрудника
        </button>
        <button class="btn btn-purple p-2" data-bs-toggle="modal" data-bs-target="#departmentModal">
            + Добавить отдел
        </button>
    </div>

    <!-- Table -->
    <div class="table-container">
        <div class="table-wrapper">
            <table class="table">
                <thead>
                <tr>
                    <th class="sticky-col">Сотрудник</th>
                    @for ($day = 1; $day <= $daysInMonth; $day++)
                        @php
                            $currentDate = new DateTime("$currentYear-$currentMonth-$day");
                            $weekDayNumber = $currentDate->format('N');
                            $weekDayShort = $weekDaysShort[$weekDayNumber - 1];
                            $isToday = ($day == $currentDay);
                            $isWeekend = ($weekDayNumber == 6 || $weekDayNumber == 7);
                            $dayClass = '';
                            if ($isToday) $dayClass .= ' today';
                            if ($isWeekend) $dayClass .= ' weekend';
                        @endphp
                        <th class="day-header{{ $dayClass }}" title="{{ $weekDaysFull[$weekDayNumber - 1] }}" colspan="2">
                            <div class="day-number">{{ $day }}
                                <div class="week-day">{{ $weekDayShort }}</div>
                            </div>
                        </th>
                    @endfor
                    <th>Дни/м</th>
                    <th>Общ.часы/м</th>
                    <th>Действия</th>
                </tr>
                </thead>
                <tbody>
                @forelse($employees as $employee)
                    <tr data-employee-id="{{ $employee->id }}">
                        <td class="sticky-col">
                            <div class="employee-info">
                                <span class="employee-name">
                                    {{ $employee->last_name }} {{ $employee->first_name }} {{ $employee->middle_name }}
                                </span>
                                <div class="employee-details">
                                    <span class="employee-id">№{{ $employee->tab_number }}</span>
                                    <span class="employee-department">{{ $employee->department->name ?? 'Без отдела' }}</span>
                                </div>
                            </div>
                        </td>

                        @for ($day = 1; $day <= $daysInMonth; $day++)
                            @php
                                $currentDate = new DateTime("$currentYear-$currentMonth-$day");
                                $weekDayNumber = $currentDate->format('N');
                                $isWeekend = ($weekDayNumber == 6 || $weekDayNumber == 7);
                                $isToday = ($day == $currentDay);
                                $cellClass = '';
                                if ($isToday) $cellClass .= ' today';
                                if ($isWeekend) $cellClass .= ' weekend';
                                $defaultValue = $isWeekend ? '0' : '8';
                            @endphp
                            <td class="day-cell reason-cell small-cell{{ $cellClass }}">
                                <select class="reason-select" data-day="{{ $day }}" data-employee="{{ $employee->id }}">
                                    @foreach($reasons as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="day-cell hours-cell{{ $cellClass }}">
                                <input type="text" class="hours-input" value="{{ $defaultValue }}"
                                       data-day="{{ $day }}" data-employee="{{ $employee->id }}"
                                       data-weekday="{{ $weekDaysShort[$weekDayNumber - 1] }}"
                                       title="День {{ $day }}, {{ $weekDaysFull[$weekDayNumber - 1] }}">
                            </td>
                        @endfor

                        <td><strong class="total-days">0</strong></td>
                        <td><strong class="total-hours">0.0</strong></td>
                        <td>
                            <button class="action-btn edit-btn" title="Редактировать">
                                <svg width="14" height="14" viewBox="0 0 23 23" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M16.5 2.5L20.5 6.5L7.5 19.5H3.5V15.5L16.5 2.5Z" stroke="#FF6B00" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M13.5 5.5L17.5 9.5" stroke="#FF6B00" stroke-width="1.5" stroke-linecap="round"/>
                                </svg>
                            </button>

                            <form action="/employees/{{ $employee->id }}" method="POST"
                                  onsubmit="return confirm('Вы уверены, что хотите удалить сотрудника?')" style="display: inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="action-btn-danger" title="Удалить">
                                    <svg width="11" height="14" viewBox="0 0 11 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M1.86643 2.38419e-06L5.24143 5.44603H5.34371L8.71871 2.38419e-06H10.5852L6.46871 6.54546L10.5852 13.0909H8.71871L5.34371 7.74716H5.24143L1.86643 13.0909H-4.43831e-05L4.21871 6.54546L-4.43831e-05 2.38419e-06H1.86643Z" fill="#F44359"/>
                                    </svg>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $daysInMonth * 2 + 4 }}" class="text-center py-5">
                            <p class="text-muted mb-2">Нет сотрудников</p>
                            <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#employeeModal">
                                Добавить первого сотрудника
                            </button>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</main>

<footer>
    © 2026 Табель - Система учета рабочего времени. Все права защищены.
</footer>

<!-- MODAL: Добавить сотрудника -->
<div class="modal fade" id="employeeModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom-0 pb-0">
                <div class="d-flex align-items-center gap-2">

                    <h5 class="modal-title fw-bold fs-5">Добавить сотрудника</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-1">
                <form method="POST" action="{{ route('admin.employees.store') }}" id="employeeForm">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label fw-medium text-dark d-block mb-1">Фамилия <span class="text-danger">*</span></label>
                        <input type="text" name="last_name" class="form-control form-control-sm @error('last_name') is-invalid @enderror"
                               placeholder="Введите фамилию" value="{{ old('last_name') }}" required>
                        @error('last_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium text-dark d-block mb-1">Имя <span class="text-danger">*</span></label>
                        <input type="text" name="first_name" class="form-control form-control-sm @error('first_name') is-invalid @enderror"
                               placeholder="Введите имя" value="{{ old('first_name') }}" required>
                        @error('first_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium text-dark d-block mb-1">Отчество</label>
                        <input type="text" name="middle_name" class="form-control form-control-sm @error('middle_name') is-invalid @enderror"
                               placeholder="Введите отчество" value="{{ old('middle_name') }}">
                        @error('middle_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium text-dark d-block mb-1">Табельный номер <span class="text-danger">*</span></label>
                        <input type="text" name="tab_number" class="form-control form-control-sm @error('tab_number') is-invalid @enderror"
                               placeholder="Например: 12345" value="{{ old('tab_number') }}" required>
                        @error('tab_number')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-medium text-dark d-block mb-1">Отдел <span class="text-danger">*</span></label>
                        @if($admin && $admin->role === 'super_admin')
                            <select name="department_id" class="form-select form-select-sm @error('department_id') is-invalid @enderror" required>
                                <option value="" disabled {{ old('department_id') ? '' : 'selected' }}>Выберите отдел</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>
                                        {{ $dept->name }}
                                    </option>
                                @endforeach
                            </select>
                        @else
                            @php
                                $adminDepartmentId = $admin && $admin->employee ? $admin->employee->department_id : null;
                                $adminDepartment = $departments->firstWhere('id', $adminDepartmentId);
                            @endphp
                            <input type="text" class="form-control form-control-sm bg-light"
                                   value="{{ $adminDepartment->name ?? 'Отдел не назначен' }}"
                                   readonly disabled>
                            <input type="hidden" name="department_id" value="{{ $adminDepartmentId }}">
                            <small class="text-muted d-block mt-1">Вы можете добавлять сотрудников только в свой отдел</small>
                        @endif
                        @error('department_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-outline-secondary btn-sm px-3 py-1" data-bs-dismiss="modal">Отмена</button>
                <button type="submit" form="employeeForm" class="btn btn-primary btn-sm px-3 py-1">Применить</button>
            </div>
        </div>
    </div>
</div>
<!-- MODAL: Добавить отдел -->
<div class="modal fade" id="departmentModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold fs-5">Добавить отдел</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-1">
                <form method="POST" action="{{ route('admin.departments.store') }}" id="departmentForm">
                    @csrf
                    <div class="form-field mb-3">
                        <label class="form-label fw-medium text-dark d-block mb-1">Название отдела</label>
                        <input type="text" name="name" class="form-control form-control-sm @error('name') is-invalid @enderror"
                               placeholder="Например, Отдел продаж" value="{{ old('name') }}" required>
                        @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-field mb-4">
                        <label class="form-label fw-medium text-dark d-block mb-1">Руководитель отдела</label>
                        <select name="manager_id" class="form-select form-select-sm @error('manager_id') is-invalid @enderror">
                            <option value="" {{ old('manager_id') ? '' : 'selected' }}>Не выбран</option>
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}" {{ old('manager_id') == $emp->id ? 'selected' : '' }}>
                                    {{ $emp->last_name }} {{ $emp->first_name }} {{ $emp->middle_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('manager_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-outline-secondary btn-sm px-3 py-1" data-bs-dismiss="modal">Отмена</button>
                <button type="submit" form="departmentForm" class="btn btn-primary btn-sm px-3 py-1">Применить</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: Редактировать сотрудника -->
<div class="modal fade" id="editEmployeeModal" tabindex="-1"
     data-bs-backdrop="static"
     data-bs-keyboard="false"
     aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold fs-5">Редактировать сотрудника</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-1">
                <form method="POST" action="" id="editEmployeeForm">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label fw-medium text-dark d-block mb-1">Фамилия <span class="text-danger">*</span></label>
                        <input type="text" name="last_name" id="edit_last_name"
                               class="form-control form-control-sm @error('last_name') is-invalid @enderror"
                               placeholder="Введите фамилию" required>
                        <div class="invalid-feedback">Пожалуйста, укажите фамилию</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium text-dark d-block mb-1">Имя <span class="text-danger">*</span></label>
                        <input type="text" name="first_name" id="edit_first_name"
                               class="form-control form-control-sm @error('first_name') is-invalid @enderror"
                               placeholder="Введите имя" required>
                        <div class="invalid-feedback">Пожалуйста, укажите имя</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium text-dark d-block mb-1">Отчество</label>
                        <input type="text" name="middle_name" id="edit_middle_name"
                               class="form-control form-control-sm @error('middle_name') is-invalid @enderror"
                               placeholder="Введите отчество">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium text-dark d-block mb-1">Табельный номер <span class="text-danger">*</span></label>
                        <input type="text" name="tab_number" id="edit_tab_number"
                               class="form-control form-control-sm @error('tab_number') is-invalid @enderror"
                               placeholder="Например: 12345" required>
                        <div class="invalid-feedback">Пожалуйста, укажите табельный номер</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-medium text-dark d-block mb-1">Отдел <span class="text-danger">*</span></label>
                        <select name="department_id" id="edit_department_id"
                                class="form-select form-select-sm @error('department_id') is-invalid @enderror" required>
                            <option value="">Выберите отдел</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback">Пожалуйста, выберите отдел</div>
                    </div>

                    <div class="alert alert-info py-1 px-2 small" role="alert">
                        <i class="fas fa-info-circle me-1"></i>
                        ID сотрудника: <strong id="edit_employee_id_display"></strong>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-outline-secondary btn-sm px-3 py-1" data-bs-dismiss="modal">Отмена</button>
                <button type="submit" form="editEmployeeForm" class="btn btn-primary btn-sm px-3 py-1">Сохранить</button>
            </div>
        </div>
    </div>
</div>

</body>
</html>
