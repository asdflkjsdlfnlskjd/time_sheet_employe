<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function edit()
    {
        if (!Auth::check()) {
            return redirect('/login');
        }

        $admin = Auth::user()->load(['employee.department', 'employee.managedDepartment']);
        if (!$this->canAccessProfile($admin)) {
            abort(403, 'Доступ к профилю ограничен');
        }

        return view('admin.profile.index', compact('admin'));
    }

    public function update(Request $request)
    {
        if (!Auth::check()) {
            return redirect('/login');
        }

        $admin = Auth::user()->load(['employee.department', 'employee.managedDepartment']);
        if (!$this->canAccessProfile($admin)) {
            abort(403, 'Доступ к профилю ограничен');
        }

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('admins', 'name')->ignore($admin->id),
            ],
            'first_name' => 'nullable|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('employees', 'email')->ignore(optional($admin->employee)->id),
            ],
            'phone' => 'nullable|string|max:25',
        ]);

        $admin->name = $validated['name'];
        $admin->save();
        session(['admin_name' => $admin->name]);

        if ($admin->employee) {
            $admin->employee->update([
                'first_name' => $validated['first_name'] ?? $admin->employee->first_name,
                'last_name' => $validated['last_name'] ?? $admin->employee->last_name,
                'middle_name' => $validated['middle_name'] ?? $admin->employee->middle_name,
                'email' => $validated['email'] ?? null,
                'phone' => $validated['phone'] ?? null,
            ]);
        }

        return back()->with('success', 'Профиль обновлен');
    }

    public function updatePassword(Request $request)
    {
        if (!Auth::check()) {
            return redirect('/login');
        }

        $admin = Auth::user()->load(['employee.managedDepartment']);
        if (!$this->canAccessProfile($admin)) {
            abort(403, 'Доступ к профилю ограничен');
        }

        $validated = $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:3|confirmed|different:current_password',
        ]);

        if (!Hash::check($validated['current_password'], $admin->password)) {
            return back()->withErrors([
                'current_password' => 'Текущий пароль указан неверно',
            ])->withInput();
        }

        $admin->password = Hash::make($validated['password']);
        $admin->save();

        return back()->with('success', 'Пароль успешно изменен');
    }

    private function canAccessProfile($admin): bool
    {
        if (!$admin) {
            return false;
        }

        if ($admin->role === 'super_admin') {
            return true;
        }

        return (bool) optional($admin->employee)->managedDepartment;
    }
}
