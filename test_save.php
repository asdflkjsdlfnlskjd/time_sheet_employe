<?php
require_once __DIR__ . '/vendor/autoload.php';

// Инициализируем Laravel приложение
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Создаем request с POST данными
$request = Illuminate\Http\Request::create('/main/save-time-records', 'POST', [], [], [], [
    'HTTP_X_CSRF_TOKEN' => csrf_token(),
    'CONTENT_TYPE' => 'application/json'
], json_encode([
    [
        'employee_id' => 1,
        'day' => 16,
        'status' => 'present',
        'hours' => 8.5,
        'month' => 4,
        'year' => 2026
    ],
    [
        'employee_id' => 2,
        'day' => 16,
        'status' => 'absent',
        'hours' => 0,
        'month' => 4,
        'year' => 2026
    ]
]));

// Фиксируем текущего пользователя (админа) для теста
Auth::loginUsingId(1);

echo "=== ТЕСТИРОВАНИЕ СОХРАНЕНИЯ ДАННЫХ ===\n\n";
echo "📤 Отправляем 2 записи...\n";

// Запускаем приложение
$response = $kernel->handle($request);

echo "\n✅ Результат:\n";
echo "Статус: " . $response->status() . "\n";
echo "Ответ: " . $response->getContent() . "\n\n";

// Проверяем БД
echo "📊 Проверяем БД:\n";
$records = DB::table('time_records')
    ->whereIn('employee_id', [1, 2])
    ->where('date', '2026-04-16')
    ->get();

foreach ($records as $record) {
    echo "- Сотр: {$record->employee_id}, Часы: {$record->hours}, Статус: {$record->status}\n";
}

if (count($records) == 0) {
    echo "❌ Записи не найдены в БД!\n";
} else {
    echo "✅ Найдено " . count($records) . " записей\n";
}
