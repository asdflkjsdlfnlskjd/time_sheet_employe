<?php
// Быстрая проверка синтаксиса PHP-кода
$file = file_get_contents(__DIR__ . '/resources/views/admin/Main/index.blade.php');

// Проверяем баланс скобок в последнем <script> блоке
if (preg_match('/<script>(.*?)<\/script>\s*$/s', $file, $m)) {
    $script = $m[1];
    
    $open = substr_count($script, '{');
    $close = substr_count($script, '}');
    
    echo "✅ Проверка синтаксиса JavaScript\n";
    echo "Открывающие {: $open\n";
    echo "Закрывающие }: $close\n";
    
    if ($open === $close) {
        echo "✅ Скобки сбалансированы\n";
    } else {
        echo "❌ ОШИБКА: неправильное количество скобок!\n";
    }
} else {
    echo "⚠️ Не найден последний блок <script>\n";
}

// Проверяем, есть ли function showNotificationSave
if (strpos($file, 'function showNotificationSave') !== false) {
    echo "✅ Функция showNotificationSave найдена\n";
} else {
    echo "❌ Функция showNotificationSave НЕ найдена\n";
}

// Проверяем, есть ли слушатель кнопки сохранения
if (strpos($file, 'saveTimeRecordsBtn') !== false && strpos($file, 'addEventListener') !== false) {
    echo "✅ Слушатель кнопки сохранения найден\n";
} else {
    echo "❌ Слушатель кнопки НЕ найден\n";
}
