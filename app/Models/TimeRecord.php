<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimeRecord extends Model
{
    protected $fillable = [
        'employee_id', 'date', 'hours', 'status', 'check_in', 'check_out', 'notes'
    ];

    protected $casts = [
        'date' => 'date',
        'hours' => 'decimal:1',
    ];

    // Статусы с сокращениями и цветами
    public static function getStatusMap()
    {
        return [
            'present' => ['label' => 'Присутствует', 'short' => '—', 'color' => '#28a745', 'bg' => '#d4edda'],
            'absent' => ['label' => 'Отсутствовал', 'short' => 'ОТ', 'color' => '#dc3545', 'bg' => '#f8d7da'],
            'late' => ['label' => 'Опоздал', 'short' => 'ОП', 'color' => '#ffc107', 'bg' => '#fff3cd'],
            'early_leave' => ['label' => 'Ранний уход', 'short' => 'РУ', 'color' => '#fd7e14', 'bg' => '#ffe5cc'],
            'vacation' => ['label' => 'Отпуск', 'short' => 'ОТП', 'color' => '#0dcaf0', 'bg' => '#cfe2ff'],
            'sick_leave' => ['label' => 'Больничный', 'short' => 'БО', 'color' => '#d63384', 'bg' => '#f8f0fc'],
            'day_off' => ['label' => 'Выходной', 'short' => 'ВЫХ', 'color' => '#6c757d', 'bg' => '#f0f0f0'],
        ];
    }

    public static function getStatusShort($status)
    {
        $map = self::getStatusMap();
        return $map[$status]['short'] ?? '—';
    }

    public static function getStatusColor($status)
    {
        $map = self::getStatusMap();
        return $map[$status]['color'] ?? '#000000';
    }

    public static function getStatusBg($status)
    {
        $map = self::getStatusMap();
        return $map[$status]['bg'] ?? '#ffffff';
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
