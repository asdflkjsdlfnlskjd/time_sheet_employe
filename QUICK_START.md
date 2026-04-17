# 🚀 Quick Start - Быстрый Старт

## ⚡ 5 Минут Для Начала Работы

---

## 1️⃣ Фиксирование Легенды (Legend Items)

### Проблема была:
Легенда (условные обозначения) скроллилась вместе с таблицей по горизонтали

### Теперь:
✅ Легенда остается видима внизу экрана при скролле

### Нужно сделать:
Просто обновите файлы:
- ✏️ `resources/views/admin/Main/index.blade.php`
- ✏️ `public/css/main.css`

### Готово! ✨
Легенда теперь "прилипнет" к низу таблицы

---

## 2️⃣ Сохранение Данных (TimeRecords)

### Способ 1: Массовое сохранение (как было, но лучше)

```javascript
// Сохраняем несколько записей за раз
const records = [
  {
    employee_id: 1,
    day: 15,
    status: 'present',
    hours: 8.5,
    month: 4,
    year: 2026
  },
  {
    employee_id: 2,
    day: 15,
    status: 'sick_leave',
    hours: 0,
    month: 4,
    year: 2026
  }
];

fetch('/main/save-time-records', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
  },
  body: JSON.stringify(records)
})
.then(res => res.json())
.then(result => {
  if (result.success) {
    console.log(`✅ Сохранено ${result.saved} записей`);
  } else {
    console.error(`❌ ${result.message}`);
  }
});
```

### Способ 2: Единичное обновление (НОВОЕ! 🎉)

```javascript
// Обновляем одну запись
fetch('/time-records/1/2026-04-15', {
  method: 'PATCH',
  headers: {
    'Content-Type': 'application/json',
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
  },
  body: JSON.stringify({
    status: 'present',
    hours: 8.5
  })
})
.then(res => res.json())
.then(result => {
  if (result.success) {
    console.log('✅ Запись обновлена');
  }
});
```

---

## 3️⃣ Обработка Ошибок

### На клиенте (Frontend):
```javascript
// ✅ Валидация часов: 0-24
if (hours < 0 || hours > 24) {
  showError('Часы должны быть от 0 до 24');
  return;
}

// ✅ Визуальное выделение ошибок
input.classList.add('is-invalid'); // Красная граница

// ✅ Красивые уведомления
showNotification('error', 'Ошибка: ' + message);
```

### На сервере (Backend):
```php
// ✅ Валидация структуры
if (empty($record['employee_id'])) {
  // Ошибка 422: Unprocessable Entity
  return response()->json(['message' => 'Отсутствует employee_id'], 422);
}

// ✅ Валидация значений
if ($hours < 0 || $hours > 24) {
  return response()->json(['message' => 'Часы: 0-24'], 422);
}

// ✅ Проверка прав
if ($admin->role !== 'super_admin' && ...) {
  return response()->json(['message' => 'Нет прав'], 403);
}
```

---

## 📋 Допустимые Статусы

```
present     → — (Присутствует)
absent      → ОТ (Отсутствовал)
late        → ОП (Опоздал)
early_leave → РУ (Ранний уход)
vacation    → ОТП (Отпуск)
sick_leave  → БО (Больничный)
day_off     → ВЫХ (Выходной)
```

---

## ✅ Тестирование

### Локально через браузер:

1. Откройте страницу табеля: `http://localhost/main`
2. Измените значение часов в таблице
3. Нажмите кнопку "💾 Сохранить данные"
4. Проверьте:
   - ✅ Появилось ли зеленое уведомление?
   - ✅ Легенда остается видима при скролле?
   - ✅ При вводе часов > 24 появляется ошибка?

### Через API (cURL):

```bash
# Массовое сохранение
curl -X POST http://localhost/main/save-time-records \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: your_csrf_token" \
  -d '[{"employee_id": 1, "day": 15, "status": "present", "hours": 8.5, "month": 4, "year": 2026}]'

# Ответ успеха:
# {"success":true,"message":"✅ Сохранено 1 записей","saved":1,"skipped":0,"access_denied":0}

# Единичное обновление
curl -X PATCH http://localhost/time-records/1/2026-04-15 \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: your_csrf_token" \
  -d '{"status": "present", "hours": 8.5}'

# Ответ успеха:
# {"success":true,"message":"Запись успешно сохранена","record":{...}}
```

---

## 🐛 Если что-то не работает

### Ошибка 401 (Не авторизован)
```
Решение: Проверьте X-CSRF-TOKEN в headers
```

### Ошибка 422 (Ошибка валидации)
```
Решение: Проверьте значения (часы 0-24, день 1-31, месяц 1-12)
```

### Ошибка 403 (Нет прав)
```
Решение: Обычный админ может редактировать только свой отдел
```

### Ошибка 500 (Сервер)
```
Решение: Посмотрите логи в storage/logs/laravel.log
```

---

## 📚 Подробнее

| Хочу узнать... | Нужно прочитать |
|---|---|
| **Обзор всех изменений** | IMPLEMENTATION_SUMMARY.md |
| **Полную документацию API** | API_TIMERECORDS.md |
| **Как улучшить архитектуру** | ARCHITECTURE_IMPROVEMENTS.md |
| **Что изменилось в версии** | CHANGELOG.md |
| **Индекс документации** | README_DOCS.md |

---

## 🎯 Основные Команды

```bash
# Просмотр логов
tail -f storage/logs/laravel.log

# Очистка кэша (если нужна)
php artisan cache:clear

# Запуск тестов (если добавлены)
php artisan test
```

---

## 💡 Советы

1. **Используйте массовое сохранение** для большого количества записей
2. **Используйте PATCH** для обновления одной записи
3. **Проверяйте логи** если что-то не работает
4. **Валидируйте на клиенте** перед отправкой

---

## 🎉 Готово!

Теперь вы можете:
- ✅ Видеть легенду при скролле таблицы
- ✅ Сохранять данные с полной валидацией
- ✅ Получать информативные ошибки
- ✅ Использовать REST API для интеграций

**Вперед! 🚀**

---

**Версия**: v1.1.0  
**Дата**: 2026-04-16
