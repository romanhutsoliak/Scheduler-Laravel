<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\TaskHistory;
use \Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory,
        SoftDeletes;

    public $fillable = [
        'name',
        'description',
        'startDateTime',
        'stopDateTime',
        'userId',
        'mustBeCompleted',
        'hasEvent',
        'periodType',
        'periodTypeTime',
        'periodTypeWeekDays',
        'periodTypeMonthDays',
        'periodTypeMonths',
        'isActive',
    ];

    public $casts = [
        'periodTypeWeekDays' => 'array',
        'periodTypeMonthDays' => 'array',
        'periodTypeMonths' => 'array',
        'mustBeCompleted' => 'boolean',
        'isActive' => 'boolean',
    ];

    public $periodTypes = [
        '1' => 'Daily',
        '2' => 'Weekly',
        '3' => 'Monthly',
        '4' => 'Yearly',
        '5' => 'Once',
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

    public function userDevices()
    {
        return $this->hasMany(UserDevice::class, 'userId', 'userId');
    }

    /**
     * Undocumented function
     *
     * @param boolean $fill
     * @param boolean $forceMoveToNextPeriod - force move event to next time if there is not long time to next event
     * @return $nextRunDateTime - time when to run next time
     */
    public function calculateNextRunDateTime($fill = true, $forceMoveToNextPeriod = false)
    {
        $currentTimestamp = time() - $this->user->timezoneOffset * 60;
        $nextRunDateTime = null;
        $scheduledTimestamp = strtotime(date('Y-m-d ' . $this->periodTypeTime . ':00', $currentTimestamp));
        // daily
        if ($this->periodType == 1) {
            if ($currentTimestamp < $scheduledTimestamp) {
                if ($forceMoveToNextPeriod && $scheduledTimestamp - $currentTimestamp < (3 * 3600))
                    $scheduledTimestamp += 86400;
                $nextRunDateTime = date('Y-m-d ' . $this->periodTypeTime . ':00', $scheduledTimestamp);
            } else {
                $nextRunDateTime = date('Y-m-d ' . $this->periodTypeTime . ':00', $scheduledTimestamp + 86400);
            }
        }
        // weekly
        else if ($this->periodType == 2) {
            $i = 0;
            while (!$nextRunDateTime && $i < 100) {
                if ($currentTimestamp < $scheduledTimestamp) {
                    if (in_array(date('N', $scheduledTimestamp), $this->periodTypeWeekDays)) {
                        $nextRunDateTime = date('Y-m-d ' . $this->periodTypeTime . ':00', $scheduledTimestamp);
                        if ($forceMoveToNextPeriod && $scheduledTimestamp - $currentTimestamp < 86400)
                            $nextRunDateTime = null;
                    }
                }
                $scheduledTimestamp +=  86400;
                $i++;
            }
        }
        // monthly
        else if ($this->periodType == 3) {
            $i = 0;
            while (!$nextRunDateTime && $i < 100) {
                if ($currentTimestamp < $scheduledTimestamp) {
                    if (in_array(date('j', $scheduledTimestamp), $this->periodTypeMonthDays)) {
                        $nextRunDateTime = date('Y-m-d ' . $this->periodTypeTime . ':00', $scheduledTimestamp);
                        if ($forceMoveToNextPeriod && $scheduledTimestamp - $currentTimestamp < (2 * 86400))
                            $nextRunDateTime = null;
                    }
                }
                $scheduledTimestamp +=  86400;
                $i++;
            }
        }
        // yearly
        else if ($this->periodType == 4) {
            // make available dates array
            $dates = [];
            foreach ($this->periodTypeMonthDays as $day) {
                foreach ($this->periodTypeMonths as $month) {
                    $scheduledTimestamp = strtotime(date('Y-' . $month . '-' . $day . ' ' . $this->periodTypeTime . ':00'));
                    if ($currentTimestamp < $scheduledTimestamp) {
                        $dates[] = $scheduledTimestamp;
                        if ($forceMoveToNextPeriod && $scheduledTimestamp - $currentTimestamp < (3 * 86400))
                            $dates = array_pop($dates);
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
        // once
        else if ($this->periodType == 5) {
            // do nothing
        }
        if ($fill) {
            $this->nextRunDateTime = $nextRunDateTime;
            $this->nextRunDateTimeUtc = date('Y-m-d H:i:00', (strtotime($nextRunDateTime) + $this->user->timezoneOffset * 60));
        }
        return $nextRunDateTime;
    }
}
