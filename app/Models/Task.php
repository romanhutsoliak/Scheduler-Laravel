<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Task extends Model
{
    use HasFactory;

    public $fillable = [
        'name',
        'description',
        'startDateTime',
        'stopDateTime',
        'nextRunDateTime',
        'userId',
        'hasEvent',
        'periodType',
        'periodTypeTime',
        'periodTypeWeekDays',
        'periodTypeMonthDays',
        'periodTypeMonths',
    ];

    public $casts = [
        'periodTypeWeekDays' => 'array',
        'periodTypeMonthDays' => 'array',
        'periodTypeMonths' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->userId) $model->userId = auth()->user()->id ?? null;
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'userId');
    }
}
