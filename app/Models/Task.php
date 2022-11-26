<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\TaskHistory;
use \Illuminate\Database\Eloquent\SoftDeletes;
use App\Services\CalculateNextRunDateTimeService;

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
     * Calculate Next Run Date Time
     *
     * @param boolean $fill
     * @param boolean $forceMoveToNextPeriod - force move event to next time if there is not long time to next event
     * @return array $nextRunDateTime - time when to run next time
     */
    public function calculateNextRunDateTime($fill = true, $forceMoveToNextPeriod = false)
    {
        $calculator = new CalculateNextRunDateTimeService($this);
        $calculationResult = $calculator->calculateNextRunDateTime($forceMoveToNextPeriod);

        if ($fill) {
            if (!empty($calculationResult['nextRunDateTime']))
                $this->nextRunDateTime = $calculationResult['nextRunDateTime'];
            if (!empty($calculationResult['nextRunDateTimeUtc']))
                $this->nextRunDateTimeUtc = $calculationResult['nextRunDateTimeUtc'];
        }
        return $calculationResult;
    }
}
