<?php

namespace App\Models;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\TaskHistory;

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

    public function history()
    {
        return $this->hasMany(TaskHistory::class, 'taskId');
    }

    public function calculateNextRunDateTime($fill = true)
    {
        $nextRunDateTime = null;
        if ($this->periodType == 1) {
            $currentDayTime = strtotime(date('Y-m-d ' . $this->periodTypeTime . ':00'));
            if ($currentDayTime > time()) {
                $nextRunDateTime = date('Y-m-d ' . $this->periodTypeTime . ':00', $currentDayTime);
            } else {
                $nextRunDateTime = date('Y-m-d ' . $this->periodTypeTime . ':00', $currentDayTime + 86400);
            }
        } else if ($this->periodType == 2) {
            $currentDayTime = strtotime(date('Y-m-d ' . $this->periodTypeTime . ':00'));
            $i = 0;
            while (!$nextRunDateTime && $i < 100) {
                if ($currentDayTime > time()) {
                    if (in_array(date('N', $currentDayTime), $this->periodTypeWeekDays)) {
                        $nextRunDateTime = date('Y-m-d ' . $this->periodTypeTime . ':00', $currentDayTime);
                    }
                }
                $currentDayTime +=  86400;
                $i++;
            }
        } else if ($this->periodType == 3) {
            $currentDayTime = strtotime(date('Y-m-d ' . $this->periodTypeTime . ':00'));
            $i = 0;
            while (!$nextRunDateTime && $i < 100) {
                if ($currentDayTime > time()) {
                    if (in_array(date('j', $currentDayTime), $this->periodTypeMonthDays)) {
                        $nextRunDateTime = date('Y-m-d ' . $this->periodTypeTime . ':00', $currentDayTime);
                    }
                }
                $currentDayTime +=  86400;
                $i++;
            }
        } else if ($this->periodType == 4) {
            $currentDayTime = strtotime(date('Y-m-d ' . $this->periodTypeTime . ':00'));
            // make available dates array
            $dates = [];
            foreach ($this->periodTypeMonthDays as $day) {
                foreach ($this->periodTypeMonths as $month) {
                    $currentDayTime = strtotime(date('Y-' . $month . '-' . $day . ' ' . $this->periodTypeTime . ':00'));
                    if ($currentDayTime > time()) {
                        $dates[] = $currentDayTime;
                    } else {
                        $dates[] = strtotime(date((string)((int)date('Y') + 1) . '-' . $month . '-' . $day . ' ' . $this->periodTypeTime . ':00'));
                    }
                }
            }
            $minDate = 2300000000;
            foreach ($dates as $date) {
                if ($date < $minDate)
                    $minDate = $date;
            }
            if ($minDate < 2300000000)
                $nextRunDateTime = date('Y-m-d ' . $this->periodTypeTime . ':00', $minDate);
        }
        if ($fill) $this->nextRunDateTime = $nextRunDateTime;
        return $nextRunDateTime;
    }
}
