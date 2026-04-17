# API Документация - Управление Табелем Рабочего Времени

## Обзор

Это API для управления данными о рабочем времени сотрудников. Система поддерживает два режима сохранения:
1. **Массовое сохранение** - сохранение нескольких записей за раз
2. **Единичное обновление** - обновление одной записи о времени

---

## 1. Массовое сохранение TimeRecords

### Endpoint
```
POST /main/save-time-records
```

### Описание
Сохраняет множество записей о рабочем времени за один запрос. Использует upsert для оптимизации БД.

### Headers
```
Content-Type: application/json
X-CSRF-TOKEN: {csrf_token}
```

### Request Body (JSON)
```json
[
  {
    "employee_id": 1,
    "day": 15,
    "status": "present",
    "hours": 8.5,
    "month": 4,
    "year": 2026
  },
  {
    "employee_id": 2,
    "day": 15,
    "status": "sick_leave",
    "hours": 0,
    "month": 4,
    "year": 2026
  }
]
```

### Параметры

| Параметр | Тип | Обязательный | Описание |
|----------|-----|-------------|---------|
| `employee_id` | integer | ✅ | ID сотрудника |
| `day` | integer | ✅ | День месяца (1-31) |
| `status` | string | ❌ | Статус работника (по умолчанию: `present`) |
| `hours` | float | ❌ | Рабочие часы (0-24, по умолчанию: 0) |
| `month` | integer | ❌ | Месяц (1-12, по умолчанию: текущий) |
| `year` | integer | ❌ | Год (по умолчанию: текущий) |

### Допустимые Статусы
- `present` - Присутствует (—)
- `absent` - Отсутствовал (ОТ)
- `late` - Опоздал (ОП)
- `early_leave` - Ранний уход (РУ)
- `vacation` - Отпуск (ОТП)
- `sick_leave` - Больничный (БО)
- `day_off` - Выходной (ВЫХ)

### Response - Success (200)
```json
{
  "success": true,
  "message": "✅ Сохранено 2 записей",
  "saved": 2,
  "skipped": 0,
  "access_denied": 0
}
```

### Response - Validation Error (422)
```json
{
  "success": false,
  "message": "Множество ошибок в данных. Проверьте входные данные.",
  "errors": [
    "Запись 0: часы должны быть от 0 до 24 (получено: 25)",
    "Запись 1: некорректный день (0)"
  ]
}
```

### Response - Error (500)
```json
{
  "success": false,
  "message": "Ошибка сервера при сохранении данных",
  "error_details": "..."
}
```

### Примеры использования

#### JavaScript/Fetch
```javascript
const data = [
  {
    employee_id: 1,
    day: 15,
    status: 'present',
    hours: 8.5,
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
  body: JSON.stringify(data)
})
.then(response => response.json())
.then(result => console.log(result));
```

#### cURL
```bash
curl -X POST http://localhost/main/save-time-records \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: your_csrf_token" \
  -d '[
    {
      "employee_id": 1,
      "day": 15,
      "status": "present",
      "hours": 8.5,
      "month": 4,
      "year": 2026
    }
  ]'
```

---

## 2. Единичное Обновление TimeRecord

### Endpoint
```
PATCH /time-records/{employeeId}/{date}
```

### Описание
Обновляет или создает одну запись о рабочем времени для конкретного сотрудника на конкретную дату.

### Headers
```
Content-Type: application/json
X-CSRF-TOKEN: {csrf_token}
```

### URL Parameters

| Параметр | Тип | Описание |
|----------|-----|---------|
| `employeeId` | integer | ID сотрудника |
| `date` | string | Дата в формате YYYY-MM-DD |

### Request Body (JSON)
```json
{
  "status": "present",
  "hours": 8.5
}
```

### Параметры

| Параметр | Тип | Обязательный | Описание |
|----------|-----|-------------|---------|
| `status` | string | ❌ | Статус работника (по умолчанию: `present`) |
| `hours` | float | ❌ | Рабочие часы 0-24 (по умолчанию: 0) |

### Response - Success (200)
```json
{
  "success": true,
  "message": "Запись успешно сохранена",
  "record": {
    "id": 42,
    "employee_id": 1,
    "date": "2026-04-15",
    "status": "present",
    "hours": 8.5
  }
}
```

### Response - Validation Error (422)
```json
{
  "success": false,
  "message": "Ошибка валидации",
  "errors": {
    "hours": [
      "The hours must be a number."
    ],
    "status": [
      "The selected status is invalid."
    ]
  }
}
```

### Response - Access Denied (403)
```json
{
  "success": false,
  "message": "У вас нет прав на изменение этого сотрудника"
}
```

### Response - Not Found (404)
```json
{
  "success": false,
  "message": "Сотрудник не найден"
}
```

### Примеры использования

#### JavaScript/Fetch
```javascript
const employeeId = 1;
const date = '2026-04-15';
const data = {
  status: 'present',
  hours: 8.5
};

fetch(`/time-records/${employeeId}/${date}`, {
  method: 'PATCH',
  headers: {
    'Content-Type': 'application/json',
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
  },
  body: JSON.stringify(data)
})
.then(response => response.json())
.then(result => console.log(result));
```

#### cURL
```bash
curl -X PATCH http://localhost/time-records/1/2026-04-15 \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: your_csrf_token" \
  -d '{
    "status": "present",
    "hours": 8.5
  }'
```

---

## Особенности и Ограничения

### Валидация Данных

1. **День месяца**: 1-31
2. **Месяц**: 1-12
3. **Год**: 2000-2100
4. **Часы**: 0-24 часа
5. **Дата должна быть валидной** (например, февраль не может иметь 30 дней)

### Права Доступа

- **Super Admin**: может редактировать данные всех сотрудников
- **Обычный Admin**: может редактировать только сотрудников своего отдела

### Обработка Ошибок

При массовом сохранении:
- Невалидные записи **пропускаются** (скипаются)
- Остальные валидные записи **сохраняются**
- Возвращается статистика (сохранено, пропущено, без доступа)

При единичном обновлении:
- При ошибке **вся операция отменяется**
- Возвращается конкретное описание ошибки

### Логирование

Все операции сохранения логируются в `storage/logs/laravel.log`:
```
[2026-04-16 12:30:45] TimeRecords saved admin_id=1 records_saved=5 skipped=2 access_denied=0
[2026-04-16 12:31:22] TimeRecord updated admin_id=1 employee_id=1 date=2026-04-15
[2026-04-16 12:32:10] TimeRecord save error message="..." trace="..."
```

---

## Лучшие Практики

### 1. Используйте Массовое Сохранение для Больших Наборов Данных
```javascript
// ✅ ХОРОШО - один запрос на 100 записей
const records = generateManyRecords(100);
fetch('/main/save-time-records', { ... body: JSON.stringify(records) });

// ❌ ПЛОХО - 100 отдельных запросов
records.forEach(r => {
  fetch(`/time-records/${r.employee_id}/${r.date}`, { ... });
});
```

### 2. Валидируйте Данные на Клиенте Перед Отправкой
```javascript
function validateTimeRecord(record) {
  if (record.hours < 0 || record.hours > 24) {
    throw new Error(`Некорректные часы: ${record.hours}`);
  }
  if (record.day < 1 || record.day > 31) {
    throw new Error(`Некорректный день: ${record.day}`);
  }
  return true;
}
```

### 3. Обрабатывайте Ошибки Корректно
```javascript
fetch(url, options)
  .then(res => {
    if (!res.ok) {
      return res.json().then(err => {
        throw new Error(err.message || 'Неизвестная ошибка');
      });
    }
    return res.json();
  })
  .catch(err => {
    console.error('API Error:', err.message);
    // Показать пользователю ошибку
  });
```

### 4. Используйте CSRF Token из Meta Tag
```html
<!-- В HTML -->
<meta name="csrf-token" content="{{ csrf_token() }}">

<!-- В JavaScript -->
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
```

---

## Статус Кодов

| Код | Описание |
|-----|---------|
| 200 | ✅ Успешно |
| 401 | ❌ Не авторизован |
| 403 | ❌ Нет прав доступа |
| 404 | ❌ Не найден |
| 422 | ❌ Ошибка валидации |
| 500 | ❌ Внутренняя ошибка сервера |

---

## Версия API
**v1.0** - Апрель 2026

## Контакт
По вопросам: admin@timesheet.local
