<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EmployeeController extends Controller
{
    /**
     * Сохранить нового сотрудника
     */
    public function store(Request $request)
    {
        // Валидация
        $validator = Validator::make($request->all(), [
            'last_name' => 'required|string|max:255',
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'tab_number' => 'required|string|unique:employees,tab_number|min:2|max:6',
            'department_id' => 'required|exists:departments,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('modal', 'employee'); // указываем, какое модальное окно показать
        }

        // Создание сотрудника
        Employee::create([
            'last_name' => $request->last_name,
            'first_name' => $request->first_name,
            'middle_name' => $request->middle_name,
            'tab_number' => $request->tab_number,
            'department_id' => $request->department_id,
        ]);

        return redirect()->back()->with('success', 'Сотрудник успешно добавлен');
    }

    /**
     * Удалить сотрудника
     */
    public function destroy($id)
    {
        $employee = Employee::findOrFail($id);
        $employee->delete();

        return redirect()->back()->with('success', 'Сотрудник удален');
    }
}
