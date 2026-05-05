<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Статистика</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/main.css') }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f5f7fa; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; }
        
        .main-content { padding: 30px 165px 0; }
        
        .page-header { margin-bottom: 40px; }
        .page-header h1 { font-size: 28px; font-weight: 700; color: #1a1a1a; margin-bottom: 5px; }
        .page-header p { color: #666; font-size: 14px; }
        
        .filter-section { background: white; border-radius: 12px; padding: 20px; margin-bottom: 30px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08); display: flex; flex-direction: column; gap: 12px; align-items: flex-start; }
        .filter-label { font-size: 13px; font-weight: 600; color: #333; margin-bottom: 0; display: flex; align-items: center; gap: 8px; white-space: nowrap; }
        
        .period-buttons { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
        .department-selector { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
        .filter-group-wrapper { display: flex; align-items: center; gap: 12px; padding: 0; border-radius: 0; border: none; width: 100%; }
        .filter-group-wrapper:last-child { margin-left: 0; }
        .period-btn { 
            padding: 9px 18px; 
            border: 1px solid #e0e0e0; 
            background: white; 
            border-radius: 8px; 
            cursor: pointer; 
            transition: all 0.25s ease; 
            font-weight: 500; 
            text-decoration: none; 
            color: #555;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .period-btn:hover { border-color: #667eea; background: #f8f9ff; color: #667eea; }
        .period-btn.active { background: #667eea; color: white; border-color: #667eea; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 30px; }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            transition: all 0.25s ease;
            border-left: 4px solid #667eea;
        }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12); }
        .stat-card.success { border-left-color: #10b981; }
        .stat-card.danger { border-left-color: #ef4444; }
        .stat-card.warning { border-left-color: #f59e0b; }
        .stat-card.info { border-left-color: #3b82f6; }
        .stat-card.purple { border-left-color: #8b5cf6; }
        .stat-card.orange { border-left-color: #f97316; }
        
        .stat-label { font-size: 13px; font-weight: 500; color: #666; margin-bottom: 8px; display: flex; align-items: center; gap: 6px; }
        .stat-label i { font-size: 14px; color: #667eea; }
        .stat-card.success .stat-label i { color: #10b981; }
        .stat-card.danger .stat-label i { color: #ef4444; }
        .stat-card.warning .stat-label i { color: #f59e0b; }
        .stat-card.info .stat-label i { color: #3b82f6; }
        .stat-card.purple .stat-label i { color: #8b5cf6; }
        .stat-card.orange .stat-label i { color: #f97316; }
        
        .stat-number { font-size: 28px; font-weight: 700; color: #1a1a1a; }
        .stat-unit { font-size: 12px; color: #999; margin-left: 4px; }
        
        .chart-card { 
            background: white; 
            border-radius: 12px; 
            padding: 24px; 
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08); 
            margin-bottom: 30px; 
        }
        
        .chart-header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 24px; 
            flex-wrap: wrap; 
            gap: 16px; 
        }
        
        .chart-title { 
            font-size: 16px; 
            font-weight: 600; 
            color: #1a1a1a;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .chart-title i { color: #667eea; }
        
        .chart-badge { 
            font-size: 12px; 
            padding: 6px 12px; 
            background: #f0f4ff; 
            color: #667eea; 
            border-radius: 6px; 
            font-weight: 500;
        }
        
        .chart-canvas-container { position: relative; height: 380px; margin-bottom: 24px; width: 100%; }
        .canvas-container { position: relative; height: 320px; margin-bottom: 24px; }
        
        .stats-summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 16px; padding-top: 20px; border-top: 1px solid #e5e7eb; }
        .summary-item { text-align: center; }
        .summary-label { font-size: 12px; color: #666; margin-bottom: 6px; }
        .summary-value { font-size: 24px; font-weight: 700; color: #1a1a1a; }
        
        .activity-card { 
            background: white; 
            border-radius: 12px; 
            padding: 24px; 
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08); 
        }
        
        .activity-header { font-size: 16px; font-weight: 600; color: #1a1a1a; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; }
        .activity-header i { color: #667eea; }
        
        .activity-item { 
            display: flex; 
            gap: 12px; 
            padding: 16px 0; 
            border-bottom: 1px solid #e5e7eb; 
            align-items: flex-start; 
        }
        .activity-item:last-child { border-bottom: none; }
        
        .activity-icon { 
            width: 36px; 
            height: 36px; 
            border-radius: 50%; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            flex-shrink: 0; 
            color: white; 
            font-size: 14px;
        }
        .activity-icon.primary { background: #667eea; }
        .activity-icon.success { background: #10b981; }
        .activity-icon.danger { background: #ef4444; }
        .activity-icon.warning { background: #f59e0b; }
        
        .activity-content { flex: 1; }
        .activity-message { font-weight: 600; color: #1a1a1a; margin-bottom: 4px; font-size: 14px; }
        .activity-time { font-size: 12px; color: #999; margin-bottom: 4px; }
        .activity-details { font-size: 13px; color: #666; }
        
        .empty-state { text-align: center; padding: 40px 20px; color: #999; }
        .empty-state i { font-size: 48px; opacity: 0.3; margin-bottom: 12px; }
        
        /* Плавная анимация появления модального окна */
        .modal {
            animation: modalBackdropFadeIn 0.25s ease-out;
        }
        
        .modal.fade .modal-dialog {
            animation: modalSlideIn 0.35s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards;
        }
        
        @keyframes modalBackdropFadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }
        
        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translate(0, -30px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translate(0, 0) scale(1);
            }
        }
        
        /* Плавное закрытие модального окна */
        .modal.fade.hide .modal-dialog {
            animation: modalSlideOut 0.2s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }
        
        @keyframes modalSlideOut {
            from {
                opacity: 1;
                transform: translate(0, 0) scale(1);
            }
            to {
                opacity: 0;
                transform: translate(0, -20px) scale(0.95);
            }
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
            <a href="{{ route('admin.profile.edit') }}" class="dropdown-item-custom profile-item">
                <i class="fas fa-user me-2"></i> Профиль
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
<nav class="tabs-container">
    <ul class="nav nav-tabs">
        <li class="nav-item">
            <a class="nav-link" href="{{ route('admin.main.index') }}">Табель</a>
        </li>
        <li class="nav-item">
            <a class="nav-link active" href="{{ route('admin.dashboard.index') }}">Статистика</a>
        </li>
    </ul>
</nav>
<main class="main-content">
    <div class="page-header">
        <h1><i class="fas fa-chart-bar me-2"></i>Статистика</h1>
        <p>Анализ рабочего времени и производительности команды</p>
    </div>

    <!-- ПЕРИОД ФИЛЬТРАЦИИ -->
    <div class="filter-section">
        <!-- ПЕРИОД -->
        <div class="filter-group-wrapper">
            <div class="filter-label">
                <i class="fas fa-calendar"></i> Период:
            </div>
            <div class="period-buttons">
                <a href="{{ route('admin.dashboard.index', array_merge(request()->query(), ['period' => 'day'])) }}" class="period-btn {{ $period === 'day' ? 'active' : '' }}">
                    <i class="fas fa-calendar-day"></i>День
                </a>
                <a href="{{ route('admin.dashboard.index', array_merge(request()->query(), ['period' => 'week'])) }}" class="period-btn {{ $period === 'week' ? 'active' : '' }}">
                    <i class="fas fa-calendar-week"></i>Неделя
                </a>
                <a href="{{ route('admin.dashboard.index', array_merge(request()->query(), ['period' => 'month'])) }}" class="period-btn {{ $period === 'month' ? 'active' : '' }}">
                    <i class="fas fa-calendar-alt"></i>Месяц
                </a>
                <a href="{{ route('admin.dashboard.index', array_merge(request()->query(), ['period' => 'year'])) }}" class="period-btn {{ $period === 'year' ? 'active' : '' }}">
                    <i class="fas fa-calendar"></i>Год
                </a>
            </div>
        </div>
        
        <!-- СЕЛЕКТОР ОТДЕЛОВ -->
        <div class="filter-group-wrapper">
            <div class="filter-label">
                <i class="fas fa-building"></i> Отдел:
            </div>
            <div class="department-selector">
                <a href="{{ route('admin.dashboard.index', ['period' => $period]) }}" class="period-btn {{ !$departmentId ? 'active' : '' }}">
                    <i class="fas fa-layer-group"></i>Все
                </a>
                @foreach($departments as $dept)
                    <a href="{{ route('admin.dashboard.index', ['period' => $period, 'department' => $dept->id]) }}" class="period-btn {{ $departmentId == $dept->id ? 'active' : '' }}">
                        <i class="fas fa-users"></i>{{ $dept->name }}
                    </a>
                @endforeach
            </div>
        </div>
    </div>
    <!-- КАРТОЧКИ СТАТИСТИКИ -->
    <div class="stats-grid">
        <div class="stat-card success">
            <div class="stat-label">
                <i class="fas fa-check-circle"></i> Отработано дней
            </div>
            <div class="stat-number">
                {{ $stats['working'] }}
            </div>
        </div>

        <div class="stat-card danger">
            <div class="stat-label">
                <i class="fas fa-times-circle"></i> Отсутствовали
            </div>
            <div class="stat-number">
                {{ $stats['absent'] }}
            </div>
        </div>

        <div class="stat-card warning">
            <div class="stat-label">
                <i class="fas fa-hourglass-start"></i> Опоздали
            </div>
            <div class="stat-number">
                {{ $stats['late'] }}
            </div>
        </div>

        <div class="stat-card info">
            <div class="stat-label">
                <i class="fas fa-plane"></i> В отпуске
            </div>
            <div class="stat-number">
                {{ $stats['vacation'] }}
            </div>
        </div>

        <div class="stat-card orange">
            <div class="stat-label">
                <i class="fas fa-clock"></i> Всего часов
            </div>
            <div class="stat-number">
                {{ $stats['totalHours'] }}<span class="stat-unit">ч</span>
            </div>
        </div>

        <div class="stat-card purple">
            <div class="stat-label">
                <i class="fas fa-fire"></i> Переработка
            </div>
            <div class="stat-number">
                {{ $stats['overtimeHours'] }}<span class="stat-unit">ч</span>
            </div>
        </div>
    </div>

    <!-- ГРАФИК АНАЛИТИКИ -->
    <div class="chart-card">
        <div class="chart-header">
            <div class="chart-title">
                <i class="fas fa-chart-line"></i> Анализ рабочего времени по отделам
            </div>
            <span class="chart-badge">
                @if($period === 'day') По часам
                @elseif($period === 'week') По дням недели
                @elseif($period === 'year') По месяцам
                @else По дням @endif
            </span>
        </div>
        <div class="chart-canvas-container"><canvas id="workHoursChart"></canvas></div>

        

        <div class="stats-summary">
            <div class="summary-item">
                <div class="summary-label">Среднее в день</div>
                <div class="summary-value">{{ $stats['avgHoursPerDay'] }}ч</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Дней работы</div>
                <div class="summary-value">{{ $stats['working'] }}</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Дней отсутствия</div>
                <div class="summary-value">{{ ($stats['absent'] + $stats['vacation'] + $stats['sickLeave']) }}</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Сотрудников</div>
                <div class="summary-value">{{ $stats['totalEmployees'] }}</div>
            </div>
        </div>
    </div>

    <!-- КРУГОВАЯ ДИАГРАММА РАСПРЕДЕЛЕНИЯ СТАТУСОВ -->
    <div class="chart-card">
        <div class="chart-header">
            <div class="chart-title">
                <i class="fas fa-pie-chart"></i> Распределение статусов
            </div>
            <span class="chart-badge">Процентное соотношение</span>
        </div>
        <div style="position: relative; height: 350px; margin-bottom: 24px;">
            <canvas id="statusDistributionChart"></canvas>
        </div>
        
        <!-- ЛЕГЕНДА С ПРОЦЕНТАМИ -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 16px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
            @php
                $total = $stats['working'] + $stats['absent'] + $stats['late'] + $stats['vacation'] + $stats['sickLeave'] + $stats['dayOff'];
                $total = max($total, 1); // Избегаем деления на 0
            @endphp
            
            <div style="text-align: center;">
                <div style="display: flex; align-items: center; justify-content: center; gap: 8px; margin-bottom: 8px;">
                    <div style="width: 12px; height: 12px; background: rgba(16, 185, 129, 0.8); border-radius: 2px;"></div>
                    <span style="font-size: 12px; color: #666;">Присутствие</span>
                </div>
                <div style="font-size: 18px; font-weight: 700; color: #1a1a1a;">{{ round(($stats['working'] / $total) * 100, 1) }}%</div>
            </div>

            <div style="text-align: center;">
                <div style="display: flex; align-items: center; justify-content: center; gap: 8px; margin-bottom: 8px;">
                    <div style="width: 12px; height: 12px; background: rgba(239, 68, 68, 0.8); border-radius: 2px;"></div>
                    <span style="font-size: 12px; color: #666;">Отсутствие</span>
                </div>
                <div style="font-size: 18px; font-weight: 700; color: #1a1a1a;">{{ round(($stats['absent'] / $total) * 100, 1) }}%</div>
            </div>

            <div style="text-align: center;">
                <div style="display: flex; align-items: center; justify-content: center; gap: 8px; margin-bottom: 8px;">
                    <div style="width: 12px; height: 12px; background: rgba(245, 158, 11, 0.8); border-radius: 2px;"></div>
                    <span style="font-size: 12px; color: #666;">Опоздание</span>
                </div>
                <div style="font-size: 18px; font-weight: 700; color: #1a1a1a;">{{ round(($stats['late'] / $total) * 100, 1) }}%</div>
            </div>

            <div style="text-align: center;">
                <div style="display: flex; align-items: center; justify-content: center; gap: 8px; margin-bottom: 8px;">
                    <div style="width: 12px; height: 12px; background: rgba(6, 182, 212, 0.8); border-radius: 2px;"></div>
                    <span style="font-size: 12px; color: #666;">Отпуск</span>
                </div>
                <div style="font-size: 18px; font-weight: 700; color: #1a1a1a;">{{ round(($stats['vacation'] / $total) * 100, 1) }}%</div>
            </div>

            <div style="text-align: center;">
                <div style="display: flex; align-items: center; justify-content: center; gap: 8px; margin-bottom: 8px;">
                    <div style="width: 12px; height: 12px; background: rgba(168, 85, 247, 0.8); border-radius: 2px;"></div>
                    <span style="font-size: 12px; color: #666;">Болезнь</span>
                </div>
                <div style="font-size: 18px; font-weight: 700; color: #1a1a1a;">{{ round(($stats['sickLeave'] / $total) * 100, 1) }}%</div>
            </div>

            <div style="text-align: center;">
                <div style="display: flex; align-items: center; justify-content: center; gap: 8px; margin-bottom: 8px;">
                    <div style="width: 12px; height: 12px; background: rgba(100, 116, 139, 0.8); border-radius: 2px;"></div>
                    <span style="font-size: 12px; color: #666;">Выходной</span>
                </div>
                <div style="font-size: 18px; font-weight: 700; color: #1a1a1a;">{{ round(($stats['dayOff'] / $total) * 100, 1) }}%</div>
            </div>
        </div>
    </div>

    <!-- ПОСЛЕДНИЕ ДЕЙСТВИЯ -->
    <div class="activity-card">
        <div class="activity-header">
            <i class="fas fa-history"></i> Последние действия
        </div>

        @if(count($recentActivities) > 0)
            @foreach($recentActivities as $activity)
                <div class="activity-item">
                    <div class="activity-icon {{ $activity['color'] }}">
                        <i class="fas {{ $activity['icon'] }}"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-message">{{ $activity['message'] }}</div>
                        <div class="activity-time">{{ $activity['date']->diffForHumans() }}</div>
                        <div class="activity-details">{{ $activity['details'] }}</div>
                    </div>
                </div>
            @endforeach
        @else
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <p>Нет действий</p>
            </div>
        @endif
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // ========== СТОЛБИКОВАЯ ДИАГРАММА ==========
    const ctx = document.getElementById('workHoursChart').getContext('2d');
    const analyticsData = @json($analyticsData);
    console.log('Analytics Data:', analyticsData);
    
    // Преобразуем данные датасетов для столбиковой диаграммы
    const datasets = analyticsData.datasets.map(dataset => ({
        label: dataset.label,
        data: dataset.data,
        backgroundColor: dataset.backgroundColor.replace(/rgba\((.*?), 0\.1\)/g, 'rgba($1, 0.8)'),
        borderColor: dataset.borderColor,
        borderWidth: 1
    }));
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: analyticsData.labels,
            datasets: datasets
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { 
                legend: { 
                    display: true, 
                    labels: { 
                        font: { size: 12 }, 
                        padding: 20
                    },
                    position: 'top'
                } 
            },
            scales: {
                y: { 
                    beginAtZero: true, 
                    ticks: {
                        callback: function(value) {
                            return value.toFixed(1) + 'ч';
                        }
                    },
                    grid: { drawBorder: false, color: 'rgba(0, 0, 0, 0.05)' }
                },
                x: { 
                    grid: { display: false, drawBorder: false } 
                }
            }
        }
    });

    // ========== КРУГОВАЯ ДИАГРАММА ==========
    const pieCtx = document.getElementById('statusDistributionChart').getContext('2d');
    const statusData = @json($statusDistribution);
    
    // Фильтруем только ненулевые значения для более чистого внешнего вида
    const filteredLabels = [];
    const filteredData = [];
    const filteredColors = [];
    
    statusData.labels.forEach((label, index) => {
        if (statusData.data[index] > 0) {
            filteredLabels.push(label);
            filteredData.push(statusData.data[index]);
            filteredColors.push(statusData.backgroundColor[index]);
        }
    });
    
    new Chart(pieCtx, {
        type: 'doughnut',
        data: {
            labels: filteredLabels,
            datasets: [{
                data: filteredData,
                backgroundColor: filteredColors,
                borderColor: '#ffffff',
                borderWidth: 2,
                hoverOffset: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'right',
                    labels: {
                        font: { size: 13 },
                        padding: 16,
                        usePointStyle: true,
                        pointStyle: 'circle',
                        generateLabels: function(chart) {
                            const data = chart.data;
                            return data.labels.map((label, i) => ({
                                text: label,
                                fillStyle: data.datasets[0].backgroundColor[i],
                                hidden: false,
                                index: i
                            }));
                        }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    titleFont: { size: 14 },
                    bodyFont: { size: 13 },
                    borderColor: 'rgba(255, 255, 255, 0.2)',
                    borderWidth: 1,
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((context.parsed / total) * 100).toFixed(1);
                            return context.label + ': ' + context.parsed + ' дней (' + percentage + '%)';
                        }
                    }
                }
            }
        }
    });
</script>

<footer style="margin-top: 40px;">
    © 2026 Табель - Система учета рабочего времени. Все права защищены.
</footer>

</body>
</html>
