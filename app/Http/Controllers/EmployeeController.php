<?php
// app/Http/Controllers/EmployeeController.php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmployeeController extends Controller
{
    public function store(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['success' => false, 'message' => 'Не авторизован'], 401);
        }

        $admin = Auth::user();

        $rules = [
            'last_name' => 'required|string|max:255',
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'tab_number' => 'required|string|min:2|max:6|unique:employees',
            'department_id' => 'required|exists:departments,id',
        ];

        $messages = [
            'tab_number.unique' => 'Такой табельный номер уже существует',
            'tab_number.min' => 'Табельный номер должен содержать от 2 до 6 символов',
            'tab_number.max' => 'Табельный номер должен содержать от 2 до 6 символов',
        ];

        // Если не супер-админ, проверяем, что добавляет в свой отдел
        if ($admin->role !== 'super_admin') {
            $adminDepartmentId = $admin->employee->department_id ?? null;

            if (!$adminDepartmentId) {
                return response()->json(['success' => false, 'message' => 'У вас не назначен отдел']);
            }

            if ($request->department_id != $adminDepartmentId) {
                return response()->json(['success' => false, 'message' => 'Вы можете добавлять сотрудников только в свой отдел']);
            }
        }

        $validated = $request->validate($rules, $messages);

        Employee::create($validated);

        return response()->json(['success' => true, 'message' => 'Сотрудник успешно добавлен']);
    }

    public function destroy($id)
    {
        if (!Auth::check()) {
            return redirect('/login');
        }

        $admin = Auth::user();
        $employee = Employee::findOrFail($id);

        if ($admin->role !== 'super_admin') {
            $adminDepartmentId = $admin->employee->department_id ?? null;

            if (!$adminDepartmentId || $employee->department_id != $adminDepartmentId) {
                return redirect()->back()
                    ->with('error', 'Вы не можете удалить этого сотрудника');
            }
        }

        $employee->delete();

        return redirect('/main') // Вместо route('admin.main.index')
        ->with('success', 'Сотрудник успешно удален');
    }

    public function update(Request $request, $id)
    {
        if (!Auth::check()) {
            return response()->json(['success' => false, 'message' => 'Не авторизован'], 401);
        }

        $admin = Auth::user();
        $employee = Employee::findOrFail($id);

        // Проверка доступа
        if ($admin->role !== 'super_admin') {
            $adminDepartmentId = $admin->employee->department_id ?? null;

            if (!$adminDepartmentId || $employee->department_id != $adminDepartmentId) {
                return response()->json(['success' => false, 'message' => 'Вы не можете редактировать этого сотрудника']);
            }

            if ($request->department_id && $request->department_id != $adminDepartmentId) {
                return response()->json(['success' => false, 'message' => 'Вы можете редактировать сотрудников только в своем отделе']);
            }
        }

        $rules = [
            'last_name' => 'required|string|max:255',
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'tab_number' => 'required|string|min:2|max:6|unique:employees,tab_number,' . $id,
            'department_id' => 'required|exists:departments,id',
        ];

        $validated = $request->validate($rules);

        $employee->update($validated);

        return response()->json(['success' => true, 'message' => 'Сотрудник успешно обновлен']);
    }
}
