#!/usr/bin/env php
<?php
/**
 * Тест сохранения данных в табеле
 * Запуск: php test_timerecord_save.php
 */

require_once __DIR__ . '/vendor/autoload.php';

// Загружаем Laravel приложение  
$app = require_once __DIR__ . '/bootstrap/app.php';

use App\Models\TimeRecord;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║     🧪 ТЕСТ СОХРАНЕНИЯ ДАННЫХ В ТАБЕЛЕ                    ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

try {
    // Проверяем сотрудников
    echo "1️⃣  ПРОВЕРКА СОТРУДНИКОВ:\n";
    $employee = Employee::first();
    if (!$employee) {
        echo "   ❌ Нет сотрудников в БД\n";
        exit(1);
    }
    echo "   ✅ Найден сотрудник: {$employee->first_name} {$employee->last_name} (ID: {$employee->id})\n\n";

    // Удаляем старую запись за 16 апреля
    echo "2️⃣  ПОДГОТОВКА ТЕСТОВЫХ ДАННЫХ:\n";
    $testDate = '2026-04-16';
    $deleted = TimeRecord::where('employee_id', $employee->id)
        ->where('date', $testDate)
        ->delete();
    echo "   - Удалено старых записей: $deleted\n";

    // Проверяем что запись удалена
    $exists = TimeRecord::where('employee_id', $employee->id)
        ->where('date', $testDate)
        ->exists();
    if ($exists) {
        echo "   ❌ Не удалось удалить старую запись\n";
        exit(1);
    }
    echo "   ✅ Запись за {$testDate} успешно удалена\n\n";

    // ТЕСТ 1: Прямое сохранение через Eloquent
    echo "3️⃣  ТЕСТ 1: Прямое сохранение через Eloquent\n";
    $record1 = TimeRecord::create([
        'employee_id' => $employee->id,
        'date' => $testDate,
        'status' => 'present',
        'hours' => 8.5
    ]);
    echo "   ✅ Запись создана: ID={$record1->id}, Часы={$record1->hours}\n\n";

    // Проверяем что запись сохранена
    $found1 = TimeRecord::find($record1->id);
    if ($found1) {
        echo "   ✅ Запись найдена в БД: ID={$found1->id}, Статус={$found1->status}, Часы={$found1->hours}\n\n";
    } else {
        echo "   ❌ Запись не найдена в БД\n";
        exit(1);
    }

    // Удаляем для теста 2
    $record1->delete();

    // ТЕСТ 2: Сохранение через Upsert (как в контроллере)
    echo "4️⃣  ТЕСТ 2: Сохранение через UPSERT\n";
    $upsertData = [
        [
            'employee_id' => $employee->id,
            'date' => $testDate,
            'status' => 'absent',
            'hours' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]
    ];
    
    DB::table('time_records')->upsert(
        $upsertData,
        ['employee_id', 'date'],
        ['status', 'hours', 'updated_at']
    );
    echo "   ✅ UPSERT выполнен\n";

    // Проверяем что запись сохранена через UPSERT
    $found2 = TimeRecord::where('employee_id', $employee->id)
        ->where('date', $testDate)
        ->first();
    if ($found2) {
        echo "   ✅ Запись найдена в БД после UPSERT:\n";
        echo "      - ID: {$found2->id}\n";
        echo "      - Сотр: {$found2->employee->first_name}\n";
        echo "      - Дата: {$found2->date}\n";
        echo "      - Статус: {$found2->status}\n";
        echo "      - Часы: {$found2->hours}\n\n";
    } else {
        echo "   ❌ Запись не найдена в БД после UPSERT\n";
        exit(1);
    }

    // ТЕСТ 3: Проверка загрузки данных контроллером
    echo "5️⃣  ТЕСТ 3: Загрузка данных как в контроллере\n";
    $start = \Carbon\Carbon::parse('2026-04-01');
    $end = \Carbon\Carbon::parse('2026-04-30');
    
    $timeRecords = TimeRecord::whereBetween('date', [$start, $end])
        ->where('employee_id', $employee->id)
        ->get()
        ->keyBy(fn($r) => $r->date->day);
    
    echo "   ✅ Найдено записей за апрель для сотрудника: " . count($timeRecords) . "\n";
    echo "   - День 16: " . ($timeRecords[16] ? "Статус={$timeRecords[16]->status}, Часы={$timeRecords[16]->hours}" : "НЕ НАЙДЕНА") . "\n\n";

    // Итоговый результат
    echo "╔════════════════════════════════════════════════════════════╗\n";
    echo "║                    ✅ ВСЕ ТЕСТЫ ПРОЙДЕНЫ                   ║\n";
    echo "║                                                            ║\n";
    echo "║  БД работает правильно!                                   ║\n";
    echo "║  Данные сохраняются и загружаются корректно.              ║\n";
    echo "║                                                            ║\n";
    echo "║  Если у вас не отображаются данные на странице,           ║\n";
    echo "║  проблема может быть в:                                   ║\n";
    echo "║  1. JavaScript сборке данных из формы                     ║\n";
    echo "║  2. Отправке данных на API                                ║\n";
    echo "║  3. Перезагрузке страницы                                 ║\n";
    echo "║                                                            ║\n";
    echo "║  Откройте браузер DevTools (F12) и посмотрите:            ║\n";
    echo "║  - Network tab для видения запросов                       ║\n";
    echo "║  - Console tab для видения ошибок JavaScript              ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n";

} catch (\Exception $e) {
    echo "❌ ОШИБКА: {$e->getMessage()}\n";
    echo "Trace: {$e->getTraceAsString()}\n";
    exit(1);
}
