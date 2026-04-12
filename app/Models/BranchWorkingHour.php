<?php

namespace App\Models;

use App\Models\Admin\Branch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BranchWorkingHour extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'branch_id'   => 'integer',
        'day_of_week' => 'integer',
        'is_enabled'  => 'boolean',
    ];

    /**
     * Saudi week order: Saturday(6) → Friday(5)
     */
    const SAUDI_DAY_ORDER = [6, 0, 1, 2, 3, 4, 5];

    const DAY_NAMES_AR = [
        0 => 'الأحد',
        1 => 'الإثنين',
        2 => 'الثلاثاء',
        3 => 'الأربعاء',
        4 => 'الخميس',
        5 => 'الجمعة',
        6 => 'السبت',
    ];

    const DAY_NAMES_EN = [
        0 => 'Sunday',
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    public function scopeForDay($query, int $dayOfWeek)
    {
        return $query->where('day_of_week', $dayOfWeek);
    }

    public function getDayNameAttribute(): string
    {
        return self::DAY_NAMES_AR[$this->day_of_week] ?? '';
    }

    public function getDayNameEnAttribute(): string
    {
        return self::DAY_NAMES_EN[$this->day_of_week] ?? '';
    }
}
