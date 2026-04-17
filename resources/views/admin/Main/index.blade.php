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
    <h1 class="page-title"><i class="fas fa-table me-2"></i>Табель учета рабочего времени</h1>
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

    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            {{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('import_errors') && count(session('import_errors')) > 0)
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <strong>📋 Ошибки импорта:</strong>
            <ul class="mb-0 small mt-2">
                @foreach(array_slice(session('import_errors'), 0, 10) as $error)
                    <li>{{ $error }}</li>
                @endforeach
                @if(count(session('import_errors')) > 10)
                    <li><em>... и еще {{ count(session('import_errors')) - 10 }} ошибок</em></li>
                @endif
            </ul>
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
        <button class="btn btn-secondary btn-sm" id="importBtn" data-bs-toggle="modal" data-bs-target="#importModal">
            <i class="fas fa-file-import me-1"></i> Импорт
        </button>
        <a href="{{ route('admin.main.export', ['month' => $currentMonth, 'year' => $currentYear]) }}" class="btn btn-secondary btn-sm">
            <i class="fas fa-file-export me-1"></i> Экспорт
        </a>
        <button class="btn btn-primary btn-sm" id="saveTimeRecordsBtn">
            <i class="fas fa-save me-1"></i>Сохранить данные
        </button>
    </div>

    <!-- ЛЕГЕНДА -->
  

    <!-- Filters -->
    <div class="filters-card">
        <form method="GET" action="{{ route('admin.main.index') }}" class="filters-row" id="filter-form">
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

            <div class="filter-group d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm apply-btn">Применить</button>
                <a href="{{ route('admin.main.index') }}" class="btn btn-secondary btn-sm reset-btn">
                   Сбросить
                </a>
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
            
            <table class="table" id="timeSheetTable">
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
                    <tr data-employee-id="{{ $employee->id }}" class="@if(in_array($employee->id, $managerIds)) manager-row @endif">
                        <td class="sticky-col">
                            <div class="employee-info @if(in_array($employee->id, $managerIds)) manager-info @endif">
                                <span class="employee-name">
                                    {{ $employee->last_name }} {{ $employee->first_name }} {{ $employee->middle_name }}
                                    @if(in_array($employee->id, $managerIds))
                                        <span class="manager-label">(рук)</span>
                                    @endif
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
                                
                                // Получаем запись из БД если она существует
                                $dayRecord = $timeRecords[$employee->id][$day] ?? null;
                                $status = $dayRecord ? $dayRecord->status : 'present';
                                $hours = $dayRecord ? $dayRecord->hours : 0;
                                
                                $statusInfo = $statusMap[$status] ?? $statusMap['present'];
                            @endphp
                            
                            <td class="day-cell reason-cell small-cell{{ $cellClass }}"
                                style="background-color: {{ $statusInfo['bg'] }};">
                                <select class="reason-select" 
                                        data-day="{{ $day }}" 
                                        data-employee="{{ $employee->id }}"
                                        style="border-left: 4px solid {{ $statusInfo['color'] }};">
                                    @foreach($statusMap as $statusKey => $statusData)
                                        <option value="{{ $statusKey }}" {{ $status === $statusKey ? 'selected' : '' }}>
                                            {{ $statusData['short'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            
                            <td class="day-cell hours-cell{{ $cellClass }}">
                                <input type="number" 
                                       class="hours-input" 
                                       value="{{ $hours }}"
                                       min="0"
                                       step="0.5"
                                       data-day="{{ $day }}" 
                                       data-employee="{{ $employee->id }}"
                                       data-weekday="{{ $weekDaysShort[$weekDayNumber - 1] }}"
                                       title="День {{ $day }}, {{ $weekDaysFull[$weekDayNumber - 1] }}">
                            </td>
                        @endfor

                        <td><strong class="total-days">0</strong></td>
                        <td><strong class="total-hours">0.0</strong></td>
                        <td>
                            <button class="action-btn edit-btn" title="Редактировать"
                                    data-bs-toggle="modal" 
                                    data-bs-target="#employeeModal"
                                    data-employee-id="{{ $employee->id }}"
                                    data-first-name="{{ $employee->first_name }}"
                                    data-last-name="{{ $employee->last_name }}"
                                    data-middle-name="{{ $employee->middle_name }}"
                                    data-tab-number="{{ $employee->tab_number }}"
                                    data-department-id="{{ $employee->department_id }}">
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
        
        <!-- ЛЕГЕНДА (вне зоны скролла) -->
        <div class="legend-card legend-fixed">
            <div class="legend-title"></div>
            <div class="legend-items">
                @foreach($statusMap as $status => $info)
                    <div class="legend-item">
                        <div class="legend-badge" style="background-color: {{ $info['bg'] }}; color: {{ $info['color'] }};">
                            {{ $info['short'] }}
                        </div>
                        <span class="legend-label">{{ $info['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
        
        <!-- Пагинация -->
        <nav aria-label="Page navigation" class="d-flex justify-content-center mt-4">
            {{ $employees->appends(request()->query())->links('pagination::bootstrap-5') }}
        </nav>
    
    </div>
</main>

<footer>
    © 2026 Табель - Система учета рабочего времени. Все права защищены.
</footer>

<script>
// ========== ОТЛАДКА СТРУКТУРЫ ТАБЛИЦЫ ==========
document.addEventListener('DOMContentLoaded', function() {
    const table = document.getElementById('timeSheetTable');
    if (table) {
        const row1 = table.querySelector('tbody tr');
        if (row1) {
            const emp_id = row1.getAttribute('data-employee-id');
            console.log(`%c📊 ПРОВЕРКА СТРУКТУРЫ ТАБЛИЦЫ`, 'font-weight: bold; font-size: 14px; color: blue;');
            console.log(`Первый сотрудник: ${emp_id}`);
            
            // Проверяем все селекты/инпуты в первой строке
            const allInputs = row1.querySelectorAll('[data-day]');
            console.log(`Всего элементов [data-day] в строке: ${allInputs.length}`);
            
            // Группируем по дням
            const dayGroups = {};
            allInputs.forEach(el => {
                const day = el.dataset.day;
                if (!dayGroups[day]) dayGroups[day] = [];
                dayGroups[day].push(el.tagName);
            });
            console.log('Группы по дням:', dayGroups);
            
            // Проверяем дни 1 и 2 подробно
            [1, 2].forEach(day => {
                const selects = row1.querySelectorAll(`.reason-select[data-day="${day}"]`);
                const inputs = row1.querySelectorAll(`.hours-input[data-day="${day}"]`);
                console.log(`День ${day}: ${selects.length} селектов, ${inputs.length} инпутов`);
            });
        }
    }
});

// ========== СОХРАНЕНИЕ ДАННЫХ ТАБЕЛЯ ==========
document.getElementById('saveTimeRecordsBtn').addEventListener('click', function() {
    const table = document.getElementById('timeSheetTable');
    const data = [];
    const btn = this;
    
    // Сборка и валидация данных
    const rows = table.querySelectorAll('tbody tr');
    let hasErrors = false;
    
    // ОТЛАДКА: проверяем дни 1 и 2
    console.log('=== ОТЛАДКА СОХРАНЕНИЯ ===');
    const day1Selects = table.querySelectorAll('[data-day="1"]');
    const day2Selects = table.querySelectorAll('[data-day="2"]');
    console.log(`День 1: ${day1Selects.length} элементов (ожидается 20 = 10 сотр × 2)`);
    console.log(`День 2: ${day2Selects.length} элементов (ожидается 20 = 10 сотр × 2)`);
    
    // Если день 1 пуст, может быть проблема с форматом data-day
    if (day1Selects.length === 0) {
        console.warn('⚠️ День 1 не найден с [data-day="1"]');
        // Проверяем, может быть это "01"?
        const day01Selects = table.querySelectorAll('[data-day="01"]');
        console.log(`День 01 (с нулём): ${day01Selects.length} элементов`);
    }
    
    rows.forEach((row, rowIdx) => {
        const employeeId = row.getAttribute('data-employee-id');
        if (!employeeId) return;
        
        const reasonSelects = row.querySelectorAll('.reason-select');
        
        // Логируем первую строку
        if (rowIdx === 0) {
            console.log(`Сотр ${employeeId}: найдено селектов ${reasonSelects.length} (ожидается 30)`);
            // Логируем первые 3 дня
            for (let i = 0; i < 3 && i < reasonSelects.length; i++) {
                const select = reasonSelects[i];
                console.log(` Селект ${i}: data-day="${select.dataset.day}", value="${select.value}"`);
            }
        }
        
        reasonSelects.forEach((select, selIdx) => {
            const day = select.dataset.day;
            const hoursInput = row.querySelector(`.hours-input[data-day="${day}"]`);
            
            // Логируем день 1 первой строки
            if (rowIdx === 0 && (day === '1' || day === 1)) {
                const dVal = hoursInput ? hoursInput.value : 'НЕ НАЙДЕН';
                console.log(` ✅ День 1: select.value=${select.value}, hours=${dVal}`);
            }
            
            if (hoursInput) {
                const hoursValue = parseFloat(hoursInput.value) || 0;
                
                // Валидация часов
                if (isNaN(hoursValue) || hoursValue < 0 || hoursValue > 24) {
                    hasErrors = true;
                    hoursInput.classList.add('is-invalid');
                    console.warn(`Некорректные часы для сотрудника ${employeeId}, день ${day}`);
                    return;
                } else {
                    hoursInput.classList.remove('is-invalid');
                }
                
                data.push({
                    employee_id: employeeId,
                    day: day,
                    reason: select.value,
                    status: select.value,
                    hours: hoursValue,
                    month: {{ $currentMonth }},
                    year: {{ $currentYear }}
                });
            }
        });
    });
    
    console.log(`Итого собрано ${data.length} записей`);
    console.log(`День 1 в собранных данных: ${data.filter(r => r.day === '1' || r.day === 1).length} записей`);
    
    // Если есть ошибки валидации, показываем сообщение
    if (hasErrors) {
        showNotificationSave('error', '⚠️ Проверьте данные: часы должны быть от 0 до 24');
        return;
    }
    
    if (data.length === 0) {
        showNotificationSave('error', '⚠️ Нет данных для сохранения');
        return;
    }
    
    // Отключаем кнопку
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Сохранение...';
    
    console.log(`Отправляем ${data.length} записей на сервер...`);
    
    // Отправляем данные на сервер
    fetch('{{ route("admin.main.save") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(err => {
                throw {
                    status: response.status,
                    data: err
                };
            });
        }
        return response.json();
    })
    .then(result => {
        console.log('Ответ сервера:', result);
        
        if (result.success) {
            showNotificationSave('success', '✅ ' + result.message);
            // Очищаем классы ошибок после успешного сохранения
            document.querySelectorAll('.is-invalid').forEach(el => {
                el.classList.remove('is-invalid');
            });
        } else {
            showNotificationSave('error', '❌ ' + (result.message || 'Ошибка при сохранении'));
        }
    })
    .catch(error => {
        console.error('Ошибка запроса:', error);
        
        let errorMessage = '❌ Ошибка при сохранении данных';
        
        if (error.status === 422) {
            errorMessage = '❌ Ошибка валидации: ' + (error.data?.message || 'Некорректные данные');
        } else if (error.status === 401) {
            errorMessage = '❌ Сессия истекла. Обновите страницу';
        } else if (error.status === 403) {
            errorMessage = '❌ Нет прав доступа для сохранения';
        } else if (error.status === 500) {
            errorMessage = '❌ Внутренняя ошибка сервера';
        } else if (error instanceof TypeError) {
            errorMessage = '❌ Ошибка сетевого соединения';
        }
        
        showNotificationSave('error', errorMessage);
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save me-2"></i>Сохранить данные';
    });
});

// Функция для показа уведомлений при сохранении
function showNotificationSave(type, message) {
    // Удаляем предыдущие уведомления
    document.querySelectorAll('.save-notification').forEach(el => el.remove());
    
    const notification = document.createElement('div');
    notification.className = `save-notification position-fixed alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show`;
    notification.style.cssText = `
        top: 20px;
        right: 20px;
        z-index: 10000;
        min-width: 350px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        border: none;
    `;
    
    const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
    
    notification.innerHTML = `
        <div style="display: flex; align-items: center; gap: 10px;">
            <i class="fas ${icon}" style="font-size: 1.2rem; flex-shrink: 0;"></i>
            <span>${message}</span>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    document.body.appendChild(notification);
    
    // Инициализируем alert Bootstrap
    new bootstrap.Alert(notification);
    
    // Автоматически скрываем через 4 секунды
    setTimeout(() => {
        const alert = bootstrap.Alert.getOrCreateInstance(notification);
        alert.close();
    }, 4000);
}

// Изменение цвета при выборе статуса
const statusMap = @json($statusMap);
document.querySelectorAll('.reason-select').forEach(select => {
    select.addEventListener('change', function() {
        const status = this.value;
        const info = statusMap[status];
        if (info) {
            this.parentElement.style.backgroundColor = info.bg;
            this.style.borderLeftColor = info.color;
        }
    });
});

// Валидация часов в реальном времени
document.querySelectorAll('.hours-input').forEach(input => {
    input.addEventListener('blur', function() {
        const value = parseFloat(this.value) || 0;
        
        if (value < 0 || value > 24) {
            this.classList.add('is-invalid');
            this.title = 'Часы должны быть от 0 до 24';
        } else {
            this.classList.remove('is-invalid');
            this.title = this.dataset.title || '';
        }
        
        // Обновляем итоги для этого сотрудника
        updateEmployeeTotals(this.closest('tr'));
    });
});

// Обновление селектора также вызывает пересчет итогов
document.querySelectorAll('.reason-select').forEach(select => {
    select.addEventListener('change', function() {
        const status = this.value;
        const day = this.dataset.day;
        const row = this.closest('tr');
        
        // Статусы, при которых часов не должно быть
        const absenceStatuses = ['vacation', 'sick', 'unpaid'];
        
        // Если выбран статус отсутствия, автоматически ставим 0 часов
        if (absenceStatuses.includes(status)) {
            const hoursInput = row.querySelector(`.hours-input[data-day="${day}"]`);
            if (hoursInput) {
                hoursInput.value = 0;
                hoursInput.classList.remove('is-invalid');
            }
        }
        
        // Обновляем цвет ячейки
        const info = statusMap[status];
        if (info) {
            this.parentElement.style.backgroundColor = info.bg;
            this.style.borderLeftColor = info.color;
        }
        
        // Пересчитываем итоги
        updateEmployeeTotals(row);
    });
});

// Функция для пересчета итогов сотрудника
function updateEmployeeTotals(row) {
    if (!row || !row.dataset.employeeId) return;
    
    // Получаем все часы-инпуты для этой строки
    const hoursInputs = row.querySelectorAll('.hours-input');
    
    let totalHours = 0;
    let workingDays = 0;
    
    // Проходим по каждому дню
    hoursInputs.forEach((input) => {
        const day = input.dataset.day;
        const hours = parseFloat(input.value) || 0;
        const reasonSelect = row.querySelector(`.reason-select[data-day="${day}"]`);
        const status = reasonSelect ? reasonSelect.value : 'present';
        
        // Добавляем часы к итогу
        totalHours += hours;
        
        // Считаем рабочие дни (если часы > 0)
        // Независимо от статуса - если есть часы, это рабочий день
        if (hours > 0) {
            workingDays += 1;
        }
    });
    
    // Обновляем элементы с итогами
    const totalDaysCell = row.querySelector('.total-days');
    const totalHoursCell = row.querySelector('.total-hours');
    
    if (totalDaysCell) {
        totalDaysCell.textContent = workingDays;
    }
    
    if (totalHoursCell) {
        totalHoursCell.textContent = totalHours.toFixed(1);
    }
}

// Инициализируем расчеты при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    const table = document.getElementById('timeSheetTable');
    if (table) {
        // Пересчитываем итоги для всех сотрудников
        const rows = table.querySelectorAll('tbody tr');
        rows.forEach(row => {
            if (row.dataset.employeeId) {
                updateEmployeeTotals(row);
            }
        });
    }
});
</script>

<!-- МОДАЛЬ ИМПОРТА ТАБЕЛЯ -->
<div class="modal fade" id="importModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Импорт табеля из файла</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="importForm" method="POST" action="{{ route('admin.main.import') }}" enctype="multipart/form-data">
                    @csrf
                    
                    <div class="alert alert-info mb-3">
                        <strong>ℹ️ Формат файла:</strong><br>
                        CSV-файл с красиво оформленным табелем, экспортированным из этой системы. 
                        Excel автоматически отформатирует его. Легенда статусов включена в файл.
                    </div>

                    <div class="mb-3">
                        <label for="importMonth" class="form-label">Месяц *</label>
                        <select class="form-select" id="importMonth" name="month" required>
                            <option value="">Выберите месяц</option>
                            <option value="1" {{ now()->month == 1 ? 'selected' : '' }}>Январь</option>
                            <option value="2" {{ now()->month == 2 ? 'selected' : '' }}>Февраль</option>
                            <option value="3" {{ now()->month == 3 ? 'selected' : '' }}>Март</option>
                            <option value="4" {{ now()->month == 4 ? 'selected' : '' }}>Апрель</option>
                            <option value="5" {{ now()->month == 5 ? 'selected' : '' }}>Май</option>
                            <option value="6" {{ now()->month == 6 ? 'selected' : '' }}>Июнь</option>
                            <option value="7" {{ now()->month == 7 ? 'selected' : '' }}>Июль</option>
                            <option value="8" {{ now()->month == 8 ? 'selected' : '' }}>Август</option>
                            <option value="9" {{ now()->month == 9 ? 'selected' : '' }}>Сентябрь</option>
                            <option value="10" {{ now()->month == 10 ? 'selected' : '' }}>Октябрь</option>
                            <option value="11" {{ now()->month == 11 ? 'selected' : '' }}>Ноябрь</option>
                            <option value="12" {{ now()->month == 12 ? 'selected' : '' }}>Декабрь</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="importYear" class="form-label">Год *</label>
                        <input type="number" class="form-control" id="importYear" name="year" 
                            min="2000" max="2100" value="{{ now()->year }}" required>
                    </div>

                    <div class="mb-3">
                        <label for="importFile" class="form-label">Выберите файл *</label>
                        <input type="file" class="form-control" id="importFile" name="csv_file" 
                            accept=".csv,.txt,.xlsx" required>
                        <small class="text-muted">
                            Допустимые форматы: CSV, TXT, XLSX
                        </small>
                    </div>
                </form>

                <div id="importHelp" class="alert alert-secondary small mt-3">
                    <strong>Подсказка:</strong><br>
                    1. Экспортируйте табель из системы<br>
                    2. Отредактируйте данные в Excel<br>
                    3. Сохраните как CSV или XLSX<br>
                    4. Загрузите файл обратно<br>
                    <br>
                    ⚠️ Существующие данные за выбранный месяц будут перезаписаны!
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="submit" form="importForm" class="btn btn-primary">
                    <i class="fas fa-upload me-2"></i>Импортировать
                </button>
            </div>
        </div>
    </div>
</div>

<!-- МОДАЛЬ ДОБАВЛЕНИЯ/РЕДАКТИРОВАНИЯ СОТРУДНИКА -->
<div class="modal fade" id="employeeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="employeeModalLabel">Добавить сотрудника</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="employeeForm">
                    <input type="hidden" id="employeeId" value="">
                    <div class="mb-3">
                        <label for="employeeFirstName" class="form-label">Имя</label>
                        <input type="text" class="form-control" id="employeeFirstName" required>
                    </div>
                    <div class="mb-3">
                        <label for="employeeLastName" class="form-label">Фамилия</label>
                        <input type="text" class="form-control" id="employeeLastName" required>
                    </div>
                    <div class="mb-3">
                        <label for="employeeMiddleName" class="form-label">Отчество</label>
                        <input type="text" class="form-control" id="employeeMiddleName">
                    </div>
                    <div class="mb-3">
                        <label for="employeeTabNumber" class="form-label">Табельный номер</label>
                        <input type="text" class="form-control" id="employeeTabNumber" required>
                    </div>
                    <div class="mb-3">
                        <label for="employeeDepartment" class="form-label">Отдел</label>
                        <select class="form-select" id="employeeDepartment" required>
                            <option value="">Выберите отдел</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-primary" id="saveEmployeeBtn">Сохранить</button>
            </div>
        </div>
    </div>
</div>

<!-- МОДАЛЬ ДОБАВЛЕНИЯ ОТДЕЛА -->
<div class="modal fade" id="departmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Добавить отдел</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="departmentForm">
                    <div class="mb-3">
                        <label for="departmentName" class="form-label">Название отдела</label>
                        <input type="text" class="form-control" id="departmentName" required>
                    </div>
                    <div class="mb-3">
                        <label for="departmentManager" class="form-label">Руководитель</label>
                        <select class="form-select" id="departmentManager">
                            <option value="">Не выбран</option>
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}">{{ $emp->last_name }} {{ $emp->first_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-primary" id="saveDepartmentBtn">Сохранить</button>
            </div>
        </div>
    </div>
</div>

<script>
// Обработка открытия модального окна сотрудника
document.getElementById('employeeModal').addEventListener('show.bs.modal', function(e) {
    const button = e.relatedTarget;
    
    if (!button) return; // Если нет button, это не редактирование
    
    const employeeId = button.getAttribute('data-employee-id');
    
    if (employeeId) {
        // Редактирование - загружаем данные
        document.getElementById('employeeId').value = employeeId;
        document.getElementById('employeeFirstName').value = button.getAttribute('data-first-name') || '';
        document.getElementById('employeeLastName').value = button.getAttribute('data-last-name') || '';
        document.getElementById('employeeMiddleName').value = button.getAttribute('data-middle-name') || '';
        document.getElementById('employeeTabNumber').value = button.getAttribute('data-tab-number') || '';
        document.getElementById('employeeDepartment').value = button.getAttribute('data-department-id') || '';
        document.getElementById('employeeModalLabel').textContent = 'Редактировать сотрудника';
    } else {
        // Создание - очищаем форму
        document.getElementById('employeeId').value = '';
        document.getElementById('employeeForm').reset();
        document.getElementById('employeeModalLabel').textContent = 'Добавить сотрудника';
    }
});

// Сохранение сотрудника
document.getElementById('saveEmployeeBtn').addEventListener('click', function() {
    const employeeId = document.getElementById('employeeId').value;
    const formData = {
        first_name: document.getElementById('employeeFirstName').value,
        last_name: document.getElementById('employeeLastName').value,
        middle_name: document.getElementById('employeeMiddleName').value,
        tab_number: document.getElementById('employeeTabNumber').value,
        department_id: document.getElementById('employeeDepartment').value
    };
    
    let url = '{{ route("admin.employees.store") }}';
    let method = 'POST';
    
    // Если редактирование
    if (employeeId) {
        url = `/employees/${employeeId}`;
        method = 'PUT';
        formData._method = 'PUT';
    }
    
    fetch(url, {
        method: method,
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            alert('✅ ' + result.message);
            document.getElementById('employeeForm').reset();
            bootstrap.Modal.getInstance(document.getElementById('employeeModal')).hide();
            location.reload();
        } else {
            alert('❌ ' + result.message);
        }
    })
    .catch(error => {
        console.error('Ошибка:', error);
        alert('❌ Ошибка при сохранении');
    });
});

// Сохранение отдела
document.getElementById('saveDepartmentBtn').addEventListener('click', function() {
    const formData = {
        name: document.getElementById('departmentName').value,
        manager_id: document.getElementById('departmentManager').value || null
    };
    
    fetch('{{ route("admin.departments.store") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            alert('✅ ' + result.message);
            document.getElementById('departmentForm').reset();
            bootstrap.Modal.getInstance(document.getElementById('departmentModal')).hide();
            location.reload();
        } else {
            alert('❌ ' + result.message);
        }
    })
    .catch(error => {
        console.error('Ошибка:', error);
        alert('❌ Ошибка при сохранении');
    });
});
</script>

</body>
</html>
