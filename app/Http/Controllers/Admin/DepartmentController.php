<?php
// app/Http/Controllers/Admin/DepartmentController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DepartmentController extends Controller
{
    public function store(Request $request)
    {
        if (!Auth::check()) {
            return redirect('/login');
        }

        $admin = Auth::user();

        if ($admin->role !== 'super_admin') {
            return redirect()->back()
                ->with('error', 'Только супер-администратор может создавать отделы');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:departments',
            'manager_id' => 'nullable|exists:employees,id',
        ]);

        Department::create($validated);

        return redirect('/main') // Вместо route('admin.main.index')
        ->with('success', 'Отдел успешно создан');
    }

    public function destroy($id)
    {
        if (!Auth::check()) {
            return redirect('/login');
        }

        $admin = Auth::user();

        if ($admin->role !== 'super_admin') {
            return redirect()->back()
                ->with('error', 'Только супер-администратор может удалять отделы');
        }

        $department = Department::findOrFail($id);

        if ($department->employees()->count() > 0) {
            return redirect()->back()
                ->with('error', 'Нельзя удалить отдел, в котором есть сотрудники');
        }

        $department->delete();

        return redirect('/main') // Вместо route('admin.main.index')
        ->with('success', 'Отдел успешно удален');
    }
}
