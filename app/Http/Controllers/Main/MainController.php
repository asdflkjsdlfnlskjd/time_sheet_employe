<?php
// app/Http/Controllers/Main/MainController.php

namespace App\Http\Controllers\Main;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Department;
use App\Models\TimeRecord;
use App\Exports\TimeRecordExport;
use App\Imports\TimeRecordImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MainController extends Controller
{
    public function index(Request $request)
    {
        // ПРОВЕРЯЕМ АВТОРИЗАЦИЮ
        if (!Auth::check()) {
            return redirect('/login'); // Явный редирект на страницу входа
        }

        // Получаем текущего админа
        $admin = Auth::user();

        // Если админ не найден (редкий случай)
        if (!$admin) {
            Auth::logout();
            return redirect('/login');
        }

        // Данные для фильтров
        $months = [
            1 => 'Январь', 2 => 'Февраль', 3 => 'Март', 4 => 'Апрель',
            5 => 'Май', 6 => 'Июнь', 7 => 'Июль', 8 => 'Август',
            9 => 'Сентябрь', 10 => 'Октябрь', 11 => 'Ноябрь', 12 => 'Декабрь'
        ];

        $currentMonth = now()->month;
        $currentYear = now()->year;
        $daysInMonth = Carbon::createFromDate($currentYear, $currentMonth, 1)->daysInMonth;
        $currentDay = now()->day;

        $weekDaysShort = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'];
        $weekDaysFull = ['Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота', 'Воскресенье'];

        // Используем статусы из модели TimeRecord
        $statusMap = TimeRecord::getStatusMap();
        $reasons = [];
        foreach ($statusMap as $key => $data) {
            $reasons[$key] = $data['short'];
        }
        $reasons = ['present' => '—'] + $reasons;

        $departments = Department::orderBy('name')->get();
        $departmentId = $request->get('department', 'all');
        $search = $request->get('search', '');

        // Получаем сотрудников с учетом прав доступа
        $query = Employee::with('department');

        // Если не супер-админ - показываем только сотрудников своего отдела
        if ($admin->role !== 'super_admin') {
            $adminDepartmentId = $admin->employee->department_id ?? null;

            if ($adminDepartmentId) {
                $query->where('department_id', $adminDepartmentId);
            } else {
                // Если у админа нет отдела - показываем пустой результат
                $query->whereRaw('1 = 0');
            }
        } else {
            // Супер-админ может фильтровать по отделам
            if ($request->filled('department') && $request->department !== 'all') {
                $query->where('department_id', $request->department);
            }
        }

        // Поиск
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('middle_name', 'like', "%{$search}%")
                    ->orWhere('tab_number', 'like', "%{$search}%");
            });
        }

        // Сортировка и пагинация
        $employees = $query->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(10);

        // Получаем список руководителей отделов
        $managerIds = Department::whereNotNull('manager_id')->pluck('manager_id')->toArray();
        $monthStart = Carbon::createFromDate($currentYear, $currentMonth, 1);
        $monthEnd = $monthStart->copy()->endOfMonth();

        $employeeIds = $employees->pluck('id')->toArray();
        \Log::info('Loading TimeRecords', [
            'month_start' => $monthStart->format('Y-m-d'),
            'month_end' => $monthEnd->format('Y-m-d'),
            'employee_count' => count($employeeIds),
            'employee_ids' => $employeeIds
        ]);

        $timeRecords = TimeRecord::whereIn('employee_id', $employeeIds)
            ->whereDate('date', '>=', $monthStart->format('Y-m-d'))
            ->whereDate('date', '<=', $monthEnd->format('Y-m-d'))
            ->get()
            ->groupBy('employee_id')
            ->map(function($records) use ($daysInMonth) {
    // Сортируем по дню (1-30) правильно, чтобы день 1 был в начале
    $daysData = $records->keyBy(fn($r) => $r->date->day);
    // Переупорядочиваем: день 1 должен быть первым, а не последним
    return collect(range(1, $daysInMonth))
        ->mapWithKeys(fn($day) => [$day => $daysData[$day] ?? null]);
});

        \Log::info('TimeRecords loaded', [
            'total_records' => count(TimeRecord::whereDate('date', '>=', $monthStart->format('Y-m-d'))->whereDate('date', '<=', $monthEnd->format('Y-m-d'))->get()),
            'grouped_employees' => count($timeRecords),
            'sample_data' => $timeRecords->take(1)->toArray()
        ]);

        return view('admin.main.index', compact(
            'admin',
            'employees',
            'months',
            'currentMonth',
            'currentYear',
            'daysInMonth',
            'weekDaysShort',
            'weekDaysFull',
            'currentDay',
            'reasons',
            'departments',
            'departmentId',
            'search',
            'statusMap',
            'timeRecords',
            'managerIds'
        ));
    }

    /**
     * Сохранить данные табеля (AJAX)
     * 
     * Структура запроса:
     * [
     *     {
     *         "employee_id": 1,
     *         "day": 15,
     *         "status": "present",
     *         "hours": 8.5,
     *         "month": 4,
     *         "year": 2026
     *     }
     * ]
     */
    public function saveTimeRecords(Request $request)
    {
        try {
            $admin = Auth::user();
            if (!$admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Не авторизован'
                ], 401);
            }

            $data = $request->json()->all();
            
            // Логируем ВСЕ приходящие данные для отладки
            // Отдельно логируем данные для дня 1
            $day1Data = array_filter($data, fn($r) => ($r['day'] ?? null) == 1);
            \Log::info('SaveTimeRecords called', [
                'admin_id' => $admin->id ?? null,
                'request_content_type' => $request->header('content-type'),
                'raw_input' => $request->getContent(),
                'parsed_data' => $data,
                'data_count' => count($data ?? []),
                'day_1_records' => $day1Data,
                'day_1_hours' => array_map(fn($r) => $r['hours'] ?? 'MISSING', $day1Data)
            ]);
            
            if (empty($data)) {
                \Log::warning('SaveTimeRecords: empty data', ['raw_content' => $request->getContent()]);
                return response()->json([
                    'success' => false,
                    'message' => 'Нет данных для сохранения'
                ], 422);
            }

            // Валидация: проверяем структуру данных
            $validationErrors = [];
            $recordCount = 0;
            
            foreach ($data as $index => $record) {
                if (!isset($record['employee_id']) || !isset($record['day']) || !isset($record['month']) || !isset($record['year'])) {
                    $validationErrors[] = "Запись $index: отсутствуют обязательные поля";
                    continue;
                }
                
                // Валидация дня
                $day = (int)$record['day'];
                if ($day < 1 || $day > 31) {
                    $validationErrors[] = "Запись $index: некорректный день ($day)";
                    continue;
                }
                
                // Валидация месяца
                $month = (int)$record['month'];
                if ($month < 1 || $month > 12) {
                    $validationErrors[] = "Запись $index: некорректный месяц ($month)";
                    continue;
                }
                
                // Валидация года
                $year = (int)$record['year'];
                if ($year < 2000 || $year > 2100) {
                    $validationErrors[] = "Запись $index: некорректный год ($year)";
                    continue;
                }
                
                // Валидация часов
                $hours = (float)($record['hours'] ?? 0);
                if ($hours < 0 || $hours > 24) {
                    $validationErrors[] = "Запись $index: часы должны быть от 0 до 24 (получено: $hours)";
                    continue;
                }
                
                $recordCount++;
            }
            
            // Если много ошибок валидации, возвращаем ошибку
            if (count($validationErrors) > 5) {
                return response()->json([
                    'success' => false,
                    'message' => 'Множество ошибок в данных. Проверьте входные данные.',
                    'errors' => array_slice($validationErrors, 0, 5)
                ], 422);
            }

            // Кэшируем всех сотрудников один раз
            $employeeIds = collect($data)->pluck('employee_id')->unique()->values();
            $employees = Employee::whereIn('id', $employeeIds)->get()->keyBy('id');
            
            $recordsToUpsert = [];
            $now = now();
            $allowedStatuses = array_keys(TimeRecord::getStatusMap());
            $skippedCount = 0;
            $accessDeniedCount = 0;

            foreach ($data as $record) {
                $employeeId = $record['employee_id'] ?? null;
                $day = $record['day'] ?? null;
                $status = $record['reason'] ?? $record['status'] ?? 'present';
                
                // Валидация статуса
                if (!in_array($status, $allowedStatuses, true)) {
                    $status = 'present';
                }

                $hours = max(0, floatval($record['hours'] ?? 0));
                $currentMonth = $record['month'] ?? now()->month;
                $currentYear = $record['year'] ?? now()->year;

                // Базовая валидация
                if (!$employeeId || !$day || $day < 1 || $day > 31) {
                    $skippedCount++;
                    continue;
                }

                // Валидация даты
                if (!checkdate((int)$currentMonth, (int)$day, (int)$currentYear)) {
                    $skippedCount++;
                    continue;
                }

                // Используем кэшированного сотрудника вместо DB запроса
                $employee = $employees->get($employeeId);
                if (!$employee) {
                    $skippedCount++;
                    continue;
                }

                // Проверка прав доступа: обычный админ может только свой отдел
                if ($admin->role !== 'super_admin') {
                    $adminDeptId = $admin->employee->department_id ?? null;
                    if ($adminDeptId && $employee->department_id !== $adminDeptId) {
                        $accessDeniedCount++;
                        continue;
                    }
                }

                // Форматируем дату как DATE (YYYY-MM-DD)
                $dateStr = sprintf('%04d-%02d-%02d', $currentYear, $currentMonth, $day);

                $recordsToUpsert[] = [
                    'employee_id' => $employeeId,
                    'date' => $dateStr,
                    'status' => $status,
                    'hours' => $hours,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if (empty($recordsToUpsert) && $recordCount > 0) {
                $errorMsg = '';
                if ($skippedCount > 0) {
                    $errorMsg .= "Пропущено $skippedCount записей (ошибки в данных). ";
                }
                if ($accessDeniedCount > 0) {
                    $errorMsg .= "Не имеете доступа к $accessDeniedCount записям. ";
                }
                
                return response()->json([
                    'success' => false,
                    'message' => $errorMsg ?: 'Нет валидных данных для сохранения',
                    'validation_errors' => $validationErrors
                ], 422);
            }

            // Сохраняем данные в БД
            if (!empty($recordsToUpsert)) {
                DB::table('time_records')->upsert(
                    $recordsToUpsert,
                    ['employee_id', 'date'],  // Уникальный ключ
                    ['status', 'hours', 'updated_at']  // Поля для обновления
                );
                
                \Log::info('TimeRecords saved', [
                    'admin_id' => $admin->id,
                    'records_saved' => count($recordsToUpsert),
                    'skipped' => $skippedCount,
                    'access_denied' => $accessDeniedCount
                ]);
            }

            $successMessage = "✅ Сохранено " . count($recordsToUpsert) . " записей";
            if ($skippedCount > 0) {
                $successMessage .= " (пропущено $skippedCount)";
            }
            if ($accessDeniedCount > 0) {
                $successMessage .= " (без доступа $accessDeniedCount)";
            }

            return response()->json([
                'success' => true,
                'message' => $successMessage,
                'saved' => count($recordsToUpsert),
                'skipped' => $skippedCount,
                'access_denied' => $accessDeniedCount
            ]);
            
        } catch (\Exception $e) {
            \Log::error('TimeRecord save error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка сервера при сохранении данных',
                'error_details' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Обновить отдельную запись о времени (PATCH)
     * 
     * Использование: PATCH /time-records/{employeeId}/{date}
     * Параметры:
     * - employeeId: ID сотрудника
     * - date: дата в формате YYYY-MM-DD
     * 
     * Тело запроса JSON:
     * {
     *     "status": "present",
     *     "hours": 8.5
     * }
     */
    public function updateTimeRecord(Request $request, $employeeId, $date)
    {
        try {
            $admin = Auth::user();
            if (!$admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Не авторизован'
                ], 401);
            }

            // Валидация параметров
            if (!$employeeId || !$date) {
                return response()->json([
                    'success' => false,
                    'message' => 'Некорректные параметры'
                ], 422);
            }

            // Валидация формата даты
            try {
                $dateObj = Carbon::createFromFormat('Y-m-d', $date);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Некорректный формат даты (используйте YYYY-MM-DD)'
                ], 422);
            }

            // Получаем сотрудника
            $employee = Employee::findOrFail($employeeId);

            // Проверка прав доступа
            if ($admin->role !== 'super_admin') {
                $adminDeptId = $admin->employee->department_id ?? null;
                if (!$adminDeptId || $employee->department_id !== $adminDeptId) {
                    return response()->json([
                        'success' => false,
                        'message' => 'У вас нет прав на изменение этого сотрудника'
                    ], 403);
                }
            }

            // Валидация входных данных
            $validated = $request->validate([
                'status' => 'nullable|string|in:' . implode(',', array_keys(TimeRecord::getStatusMap())),
                'hours' => 'nullable|numeric|min:0|max:24',
            ]);

            // Получаем или создаем запись
            $timeRecord = TimeRecord::updateOrCreate(
                [
                    'employee_id' => $employeeId,
                    'date' => $date
                ],
                [
                    'status' => $validated['status'] ?? 'present',
                    'hours' => $validated['hours'] ?? 0,
                ]
            );

            \Log::info('TimeRecord updated', [
                'admin_id' => $admin->id,
                'employee_id' => $employeeId,
                'date' => $date,
                'status' => $validated['status'] ?? 'present',
                'hours' => $validated['hours'] ?? 0
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Запись успешно сохранена',
                'record' => [
                    'id' => $timeRecord->id,
                    'employee_id' => $timeRecord->employee_id,
                    'date' => $timeRecord->date->format('Y-m-d'),
                    'status' => $timeRecord->status,
                    'hours' => $timeRecord->hours
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('TimeRecord update error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка сервера при обновлении записи',
                'error_details' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $employee = Employee::findOrFail($id);

            $validated = $request->validate([
                'last_name' => 'required|string|max:255',
                'first_name' => 'required|string|max:255',
                'middle_name' => 'nullable|string|max:255',
                'tab_number' => 'required|string|unique:employees,tab_number,' . $id,
                'department_id' => 'required|exists:departments,id',
            ]);

            $employee->update([
                'last_name' => $validated['last_name'],
                'first_name' => $validated['first_name'],
                'middle_name' => $validated['middle_name'],
                'tab_number' => $validated['tab_number'],
                'department_id' => $validated['department_id'],
            ]);

            // Загружаем отдел для возврата названия
            $employee->load('department');

            return response()->json([
                'success' => true,
                'employee' => [
                    'id' => $employee->id,
                    'last_name' => $employee->last_name,
                    'first_name' => $employee->first_name,
                    'middle_name' => $employee->middle_name,
                    'tab_number' => $employee->tab_number,
                    'department_name' => $employee->department->name ?? 'Без отдела'
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка сервера: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Экспорт табеля в Excel (CSV формат)
     */
    /**
     * Экспортирует табеля в CSV/XLSX формат
     */
    public function exportExcel(Request $request)
    {
        try {
            $admin = Auth::user();
            if (!$admin) {
                return redirect('/login');
            }

            $currentMonth = $request->get('month', now()->month);
            $currentYear = $request->get('year', now()->year);
            $departmentId = $request->get('department', 'all');

            // Получаем сотрудников с фильтрацией по правам доступа
            $query = Employee::with('department');

            if ($admin->role !== 'super_admin') {
                $adminDepartmentId = $admin->employee->department_id ?? null;
                if ($adminDepartmentId) {
                    $query->where('department_id', $adminDepartmentId);
                }
            } else {
                if ($request->filled('department') && $request->department !== 'all') {
                    $query->where('department_id', $request->department);
                }
            }

            $employees = $query->orderBy('last_name')->orderBy('first_name')->get();

            if ($employees->isEmpty()) {
                return redirect()->back()->with('error', 'Нет сотрудников для экспорта');
            }

            // Используем Export класс с CSV форматом
            $exporter = new TimeRecordExport($currentMonth, $currentYear, $departmentId, $employees);
            $exporter->toCsv();

        } catch (\Exception $e) {
            \Log::error('Export error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'Ошибка при экспорте: ' . $e->getMessage());
        }
    }

    /**
     * Импортирует табеля из CSV/XLSX файла
     */
    public function importExcel(Request $request)
    {
        try {
            $admin = Auth::user();
            if (!$admin) {
                return redirect('/login');
            }

            // Валидация входных данных
            $request->validate([
                'csv_file' => 'required|file|mimes:csv,txt,xlsx',
                'month' => 'required|integer|min:1|max:12',
                'year' => 'required|integer|min:2000|max:2100'
            ]);

            $month = $request->get('month');
            $year = $request->get('year');
            $file = $request->file('csv_file');
            
            // Сохраняем файл временно
            $path = $file->store('imports');
            $filepath = storage_path('app/' . $path);
            
            // Создаем директорию если её нет
            @mkdir(dirname($filepath), 0755, true);

            // Используем Import класс
            $importer = new TimeRecordImport($filepath, $month, $year);
            $result = $importer->import();

            // Удаляем загруженный файл
            @unlink($filepath);

            // Формируем сообщение об результате
            $message = "✅ Импорт завершен. Загружено: {$result['imported']} записей.";
            
            if ($result['skipped'] > 0) {
                $message .= " Пропущено: {$result['skipped']}.";
            }

            $successType = $result['errors'] ? 'warning' : 'success';

            return redirect()->back()
                ->with($successType, $message)
                ->with('import_errors', $result['errors']);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->errors())
                ->with('error', 'Ошибка валидации');

        } catch (\Exception $e) {
            \Log::error('Import error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'Ошибка при импорте: ' . $e->getMessage());
        }
    }

    /**
     * Генерировать 500 сотрудников и создать их в базе данных
     */
    public function generateEmployeesExcel()
    {
        try {
            // Русские имена и фамилии
            $firstNames = [
                'Иван', 'Петр', 'Николай', 'Василий', 'Дмитрий',
                'Андрей', 'Алексей', 'Сергей', 'Михаил', 'Владимир',
                'Максим', 'Игорь', 'Константин', 'Александр', 'Борис',
                'Виктор', 'Геннадий', 'Евгений', 'Жан', 'Зинаида',
                'Инна', 'Юлия', 'Екатерина', 'Ольга', 'Анна',
                'Елена', 'Дарья', 'Светлана', 'Марина', 'Ирина',
                'Людмила', 'Виктория', 'Кристина', 'Маргарита', 'Беатриса',
                'Валентина', 'Наталья', 'Татьяна', 'Павел', 'Роман'
            ];

            $lastNames = [
                'Иванов', 'Петров', 'Сидоров', 'Кузнецов', 'Орлов',
                'Соколов', 'Морозов', 'Волков', 'Новиков', 'Федоров',
                'Павлов', 'Воронов', 'Сафонов', 'Казаков', 'Лебедев',
                'Титов', 'Карпов', 'Успенский', 'Никифоров', 'Сысоев',
                'Власов', 'Бурцев', 'Громов', 'Гавриков', 'Данилов',
                'Ермолин', 'Журавлев', 'Зенин', 'Зуев', 'Иванцов',
                'Калугин', 'Киселев', 'Коваленко', 'Красильников', 'Лапин',
                'Лосев', 'Малахов', 'Мещеряков', 'Миллер', 'Молчанов'
            ];

            $departments = Department::all();
            if ($departments->isEmpty()) {
                return redirect()->back()->with('error', 'В системе нет отделов. Создайте отделы перед импортом сотрудников.');
            }

            $departmentIds = $departments->pluck('id')->toArray();
            $now = now();
            $batch = [];
            $batchSize = 50; // Вставляем порциями по 50 записей
            $created = 0;

            // Генерируем 500 сотрудников
            for ($i = 1; $i <= 500; $i++) {
                $firstName = $firstNames[array_rand($firstNames)];
                $lastName = $lastNames[array_rand($lastNames)];
                $tabNumber = 'EMP' . str_pad($i, 6, '0', STR_PAD_LEFT);
                
                $batch[] = [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'middle_name' => '',
                    'tab_number' => $tabNumber,
                    'department_id' => $departmentIds[array_rand($departmentIds)],
                    'created_at' => $now,
                    'updated_at' => $now
                ];

                // Вставляем партиями по 50
                if (count($batch) >= $batchSize) {
                    DB::table('employees')->insertOrIgnore($batch);
                    $created += count($batch);
                    $batch = [];
                }
            }

            // Вставляем оставшиеся записи
            if (!empty($batch)) {
                DB::table('employees')->insertOrIgnore($batch);
                $created += count($batch);
            }

            $message = "✅ Создано $created сотрудников";

            return redirect()->route('admin.main.index')->with('success', $message);
        } catch (\Exception $e) {
            \Log::error('Generate employees error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'Ошибка при генерации сотрудников: ' . $e->getMessage());
        }
    }

}
