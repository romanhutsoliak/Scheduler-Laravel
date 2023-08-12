<?php

namespace App\Models;

use App\Enums\TaskPeriodTypesEnum;
use App\Managers\TaskManager;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
        'hasEvent',
        'periodTypeId',
        'periodTypeTime',
        'periodTypeWeekDays',
        'periodTypeMonthDays',
        'periodTypeMonths',
        'isActive',
    ];

    protected $appends = ['periodType'];
    public $casts = [
        'periodTypeWeekDays' => 'array',
        'periodTypeMonthDays' => 'array',
        'periodTypeMonths' => 'array',
        'isActive' => 'boolean',
        'periodTypeId' => TaskPeriodTypesEnum::class,
    ];

    protected function periodType(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->periodTypeId->name ?? '',
        );
    }

    public function fill(array $attributes): self
    {
        if (!empty($attributes['periodType'])) {
            $attributes['periodTypeId'] = collect(TaskPeriodTypesEnum::cases())->where('name', $attributes['periodType'])->first() ?? null;
            unset($attributes['periodType']);
        }
        return parent::fill($attributes);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->userId) {
                $model->userId = auth()->user()->id ?? null;
            }
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

    public function manager(): TaskManager
    {
        return new TaskManager($this);
    }

    /**
     * Calculate Next Run Date Time
     *
     * @param boolean $forceMoveToNextPeriod - force move event to next time if there is not long time to next event
     * @return void
     */
    public function calculateAndFillNextRunDateTime(bool $forceMoveToNextPeriod = false): void
    {
        $calculationResult = $this->manager()->calculateNextRunDateTime($forceMoveToNextPeriod);

        $this->nextRunDateTime = $calculationResult['nextRunDateTime'];
        $this->nextRunDateTimeUtc = $calculationResult['nextRunDateTimeUtc'];
    }
}
