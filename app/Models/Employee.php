<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $fillable = [
        'first_name', 'last_name', 'middle_name',
        'tab_number', 'department_id',
        'email', 'phone', 'hire_date', 'is_active'
    ];

    protected $casts = [
        'hire_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function getFullNameAttribute()
    {
        return trim($this->last_name . ' ' . $this->first_name . ' ' . $this->middle_name);
    }

    public function getShortNameAttribute()
    {
        $initials = '';
        if ($this->first_name) {
            $initials .= mb_substr($this->first_name, 0, 1) . '.';
        }
        if ($this->middle_name) {
            $initials .= mb_substr($this->middle_name, 0, 1) . '.';
        }
        return trim($this->last_name . ' ' . $initials);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function managedDepartment()
    {
        return $this->hasOne(Department::class, 'manager_id');
    }

    public function timeRecords()
    {
        return $this->hasMany(TimeRecord::class);
    }
}
