<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Статистика</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/main.css') }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <style>
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 8px 12px rgba(0, 0, 0, 0.2); }
        .stat-card.success { background: linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%); }
        .stat-card.danger { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }
        .stat-card.warning { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }
        .stat-card.info { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .stat-card.purple { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .stat-card.orange { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .stat-number { font-size: 2.5rem; font-weight: bold; margin-bottom: 10px; }
        .stat-label { font-size: 0.95rem; opacity: 0.9; }
        .chart-card { background: white; border-radius: 10px; padding: 25px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); margin-bottom: 30px; }
        .chart-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px; }
        .chart-title { font-size: 1.5rem; font-weight: 600; color: #333; }
        .period-buttons { display: flex; gap: 10px; flex-wrap: wrap; }
        .period-btn { padding: 8px 16px; border: 2px solid #ddd; background: white; border-radius: 5px; cursor: pointer; transition: all 0.3s ease; font-weight: 500; text-decoration: none; color: #333; }
        .period-btn:hover { border-color: #667eea; color: #667eea; }
        .period-btn.active { background: #667eea; color: white; border-color: #667eea; }
        .activity-card { background: white; border-radius: 10px; padding: 25px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); }
        .activity-item { display: flex; gap: 15px; padding: 15px 0; border-bottom: 1px solid #eee; align-items: flex-start; }
        .activity-item:last-child { border-bottom: none; }
        .activity-icon { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; color: white; font-size: 1rem; }
        .activity-icon.primary { background: #667eea; }
        .activity-icon.success { background: #84fab0; color: #333; }
        .activity-icon.danger { background: #fa709a; }
        .activity-icon.warning { background: #ffc107; color: #333; }
        .activity-content { flex: 1; }
        .activity-message { font-weight: 600; color: #333; margin-bottom: 5px; }
        .activity-time { font-size: 0.85rem; color: #999; }
        .activity-details { font-size: 0.9rem; color: #666; margin-top: 5px; }
        .empty-state { text-align: center; padding: 40px 20px; color: #999; }
        .filter-section { background: white; border-radius: 10px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .canvas-container { position: relative; height: 300px; margin-bottom: 20px; }
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
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit" class="dropdown-item-custom">
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
    <div class="mb-4">
        <h1 class="page-title">📊 Детальная Статистика</h1>
        <p class="page-subtitle">Полный анализ рабочего времени и производительности</p>
    </div>
    <div class="filter-section">
        <h6 class="mb-3">📅 Выберите период анализа:</h6>
        <div class="period-buttons">
            <a href="?period=day" class="period-btn {{ $period === 'day' ? 'active' : '' }}">
                <i class="fas fa-calendar-day me-2"></i>За день
            </a>
            <a href="?period=week" class="period-btn {{ $period === 'week' ? 'active' : '' }}">
                <i class="fas fa-calendar-week me-2"></i>За неделю
            </a>
            <a href="?period=month" class="period-btn {{ $period === 'month' ? 'active' : '' }}">
                <i class="fas fa-calendar-alt me-2"></i>За месяц
            </a>
            <a href="?period=year" class="period-btn {{ $period === 'year' ? 'active' : '' }}">
                <i class="fas fa-calendar me-2"></i>За год
            </a>
        </div>
    </div>
    <div class="stats-grid">
        <div class="stat-card success">
            <div class="stat-number">{{ $stats['working'] }}</div>
            <div class="stat-label">
                <i class="fas fa-check-circle me-2"></i>Отработано дней
            </div>
        </div>
        <div class="stat-card danger">
            <div class="stat-number">{{ $stats['absent'] }}</div>
            <div class="stat-label">
                <i class="fas fa-times-circle me-2"></i>Отсутствовали
            </div>
        </div>
        <div class="stat-card warning">
            <div class="stat-number">{{ $stats['late'] }}</div>
            <div class="stat-label">
                <i class="fas fa-clock me-2"></i>Опоздали
            </div>
        </div>
        <div class="stat-card info">
            <div class="stat-number">{{ $stats['vacation'] }}</div>
            <div class="stat-label">
                <i class="fas fa-umbrella-beach me-2"></i>В отпуске
            </div>
        </div>
        <div class="stat-card orange">
            <div class="stat-number">{{ $stats['totalHours'] }}ч</div>
            <div class="stat-label">
                <i class="fas fa-hourglass-end me-2"></i>Всего часов
            </div>
        </div>
        <div class="stat-card purple">
            <div class="stat-number">{{ $stats['overtimeHours'] }}ч</div>
            <div class="stat-label">
                <i class="fas fa-bolt me-2"></i>Переработано
            </div>
        </div>
    </div>
    <div class="chart-card">
        <div class="chart-header">
            <h3 class="chart-title">
                <i class="fas fa-chart-line me-2"></i>Аналитика Рабочего Времени
            </h3>
            <span class="badge bg-info">
                @if($period === 'day') За день
                @elseif($period === 'week') За неделю
                @elseif($period === 'year') За год
                @else За месяц @endif
            </span>
        </div>
        <div class="canvas-container">
            <canvas id="analyticsChart"></canvas>
        </div>
        <div class="row mt-4">
            <div class="col-md-3 text-center">
                <h5 class="text-muted">Среднее в день</h5>
                <h3 class="text-primary">{{ $stats['avgHoursPerDay'] }}ч</h3>
            </div>
            <div class="col-md-3 text-center">
                <h5 class="text-muted">Дней работы</h5>
                <h3 class="text-success">{{ $stats['working'] }}</h3>
            </div>
            <div class="col-md-3 text-center">
                <h5 class="text-muted">Дней отсутствия</h5>
                <h3 class="text-danger">{{ ($stats['absent'] + $stats['vacation'] + $stats['sickLeave']) }}</h3>
            </div>
            <div class="col-md-3 text-center">
                <h5 class="text-muted">Всего сотрудников</h5>
                <h3 class="text-info">{{ $stats['totalEmployees'] }}</h3>
            </div>
        </div>
    </div>
    <div class="activity-card">
        <h3 class="chart-title mb-4">
            <i class="fas fa-history me-2"></i>Последние Действия
        </h3>
        @if(count($recentActivities) > 0)
            @foreach($recentActivities as $activity)
                <div class="activity-item">
                    <div class="activity-icon {{ $activity['color'] }}">
                        <i class="fas {{ $activity['icon'] }}"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-message">{{ $activity['message'] }}</div>
                        <div class="activity-time">
                            <i class="far fa-clock me-2"></i>{{ $activity['date']->diffForHumans() }}
                        </div>
                        <div class="activity-details">{{ $activity['details'] }}</div>
                    </div>
                </div>
            @endforeach
        @else
            <div class="empty-state">
                <i class="fas fa-inbox" style="font-size: 3rem; opacity: 0.3;"></i>
                <p class="mt-3">Нет действий</p>
            </div>
        @endif
    </div>
</main>
<footer>
    © 2026 Табель - Система учета рабочего времени. Все права защищены.
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const ctx = document.getElementById('analyticsChart').getContext('2d');
    const analyticsData = @json($analyticsData);
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: analyticsData.labels,
            datasets: [{
                label: 'Часы работы',
                data: analyticsData.data,
                borderColor: '#667eea',
                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#667eea',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 5,
                pointHoverRadius: 7
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: true, labels: { font: { size: 12 }, padding: 20 } } },
            scales: {
                y: { beginAtZero: true, grid: { drawBorder: false, color: 'rgba(0, 0, 0, 0.05)' } },
                x: { grid: { display: false, drawBorder: false } }
            }
        }
    });
</script>
</body>
</html>
