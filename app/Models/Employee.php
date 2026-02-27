<?php
// app/Models/Employee.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Employee extends Model
{
    protected $fillable = [
        'first_name', 'last_name', 'middle_name',
        'tab_number', 'position', 'email', 'phone',
        'hire_date', 'department_id', 'is_active'
    ];

    protected $casts = [
        'hire_date' => 'date',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Полное имя сотрудника
    public function getFullNameAttribute(): string
    {
        return trim($this->last_name . ' ' . $this->first_name . ' ' . $this->middle_name);
    }

    // Отдел сотрудника
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    // Администратор (если есть)
    public function admin(): HasOne
    {
        return $this->hasOne(Admin::class);
    }

    // Записи времени
    public function timeRecords(): HasMany
    {
        return $this->hasMany(TimeRecord::class);
    }

    // Отдел, которым руководит (если руководитель)
    public function managedDepartment(): HasOne
    {
        return $this->hasOne(Department::class, 'manager_id');
    }
}
