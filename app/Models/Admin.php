<?php
// app/Models/Admin.php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Admin extends Authenticatable
{
    protected $table = 'admins';

    protected $fillable = [
        'name', 'password', 'employee_id',
        'role', 'is_active'
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Поле для аутентификации (используем name вместо username)
     */
    public function getAuthIdentifierName()
    {
        return 'name';
    }

    /**
     * Связь с сотрудником
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Проверка, является ли админ супер-админом
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    /**
     * Получить отдел, которым управляет админ
     */
    public function getManagedDepartmentAttribute()
    {
        if ($this->isSuperAdmin()) {
            return null; // супер-админ управляет всеми отделами
        }

        return $this->employee->department ?? null;
    }

    /**
     * Получить ID отдела, которым управляет админ
     */
    public function getManagedDepartmentIdAttribute()
    {
        $department = $this->managed_department;
        return $department ? $department->id : null;
    }

    /**
     * Проверка, может ли админ управлять сотрудником
     */
    public function canManageEmployee($employee): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->employee &&
            $this->employee->department_id === $employee->department_id;
    }

    /**
     * Проверка, может ли админ управлять отделом
     */
    public function canManageDepartment($department): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->employee &&
            $this->employee->department_id === $department->id;
    }

    /**
     * Получить список ID сотрудников, доступных админу
     */
    public function getAccessibleEmployeeIdsAttribute(): array
    {
        if ($this->isSuperAdmin()) {
            return Employee::pluck('id')->toArray();
        }

        if ($this->employee && $this->employee->department) {
            return Employee::where('department_id', $this->employee->department_id)
                ->pluck('id')
                ->toArray();
        }

        return [];
    }

    /**
     * Получить запрос на доступных сотрудников
     */
    public function accessibleEmployees()
    {
        if ($this->isSuperAdmin()) {
            return Employee::query();
        }

        if ($this->employee && $this->employee->department) {
            return Employee::where('department_id', $this->employee->department_id);
        }

        return Employee::whereRaw('1 = 0'); // пустой результат
    }
}
