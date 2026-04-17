<?php
/**
 * Простой тест API сохранения данных табеля
 * Использование: php test_api_save.php
 */

$curl = curl_init();

// Подготавливаем данные для отправки
$data = [
    [
        'employee_id' => 1,
        'day' => 16,
        'status' => 'present',
        'hours' => 8.5,
        'month' => 4,
        'year' => 2026
    ]
];

// Получаем CSRF токен (для этого нужно сначала открыть страницу)
// Для теста используем пустой токен - сервер может требовать его
$headers = [
    'Content-Type: application/json',
    'X-CSRF-TOKEN: ' // Пустой токен для теста
];

echo "=== ТЕСТИРОВАНИЕ API ===\n\n";
echo "📤 Отправляем запрос на http://127.0.0.1:8000/main/save-time-records\n";
echo "Данные: " . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n\n";

curl_setopt_array($curl, [
    CURLOPT_URL => 'http://127.0.0.1:8000/main/save-time-records',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => json_encode($data),
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_VERBOSE => true,
    CURLOPT_FOLLOWLOCATION => true,
]);

$response = curl_exec($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$error = curl_error($curl);

curl_close($curl);

echo "📊 Результат:\n";
echo "HTTP код: {$httpCode}\n";
echo "Ответ:\n";
echo $response . "\n\n";

if ($error) {
    echo "❌ Ошибка: {$error}\n";
} else {
    echo "✅ Запрос отправлен\n";
}
