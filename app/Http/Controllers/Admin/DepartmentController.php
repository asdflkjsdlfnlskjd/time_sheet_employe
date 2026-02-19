<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DepartmentController extends Controller
{
    /**
     * Сохранить новый отдел
     */
    public function store(Request $request)
    {
        // Валидация
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:departments,name|max:255',
            'manager_id' => 'nullable|exists:employees,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('modal', 'department');
        }

        // Создание отдела
        Department::create([
            'name' => $request->name,
            'manager_id' => $request->manager_id,
        ]);

        return redirect()->back()->with('success', 'Отдел успешно добавлен');
    }

    /**
     * Удалить отдел
     */
    public function destroy($id)
    {
        $department = Department::findOrFail($id);

        // Проверяем, есть ли сотрудники в отделе
        if ($department->employees()->count() > 0) {
            return redirect()->back()->with('error', 'Нельзя удалить отдел, в котором есть сотрудники');
        }

        $department->delete();

        return redirect()->back()->with('success', 'Отдел удален');
    }
}
