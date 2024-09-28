<?php

namespace App\Models;

use App\Enums\TaskPeriodTypesEnum;
use App\Managers\TaskManager;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property Collection $userDevices
 */
class Task extends Model
{
    use HasFactory,
        SoftDeletes;

    public $fillable = [
        'name',
        'categoryId',
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

    protected function getPeriodTypeAttribute(): string
    {
        return $this->periodTypeId->name ?? '';
    }

    public function fill(array $attributes): self
    {
        if (! empty($attributes['periodType'])) {
            $attributes['periodTypeId'] = collect(TaskPeriodTypesEnum::cases())->where('name', $attributes['periodType'])->first() ?? null;
            unset($attributes['periodType']);
        }

        return parent::fill($attributes);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (! $model->userId) {
                $model->userId = auth()->user()->id ?? null;
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'userId');
    }

    public function history(): HasMany
    {
        return $this->hasMany(TaskHistory::class, 'taskId');
    }

    public function userDevices(): HasMany
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
     * @param  bool  $forceMoveToNextPeriod  - force move event to next time if there is not long time to next event
     */
    public function calculateAndFillNextRunDateTime(bool $forceMoveToNextPeriod = false): void
    {
        $calculationResult = $this->manager()->calculateNextRunDateTime($forceMoveToNextPeriod);
        if (! is_null($calculationResult)) {
            $this->nextRunDateTime = $calculationResult['nextRunDateTime'];
            $this->nextRunDateTimeUtc = $calculationResult['nextRunDateTimeUtc'];
        }
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TaskCategory::class, 'categoryId');
    }

    /**
     * \@scope Search on tasks list
     */
    public function scopeSearchOnList($query, $search): void
    {
        $query->when(! is_null($search), function ($query) use ($search) {
            $query->where('name', 'like', '%'.$search.'%');
        });
    }

    /**
     * \@scope Filter on tasks list
     */
    public function scopeFiltersOnList($query, $filter): void
    {
        $query->when(! empty($filter['category']), function ($query) use ($filter) {
            $query->whereHas('category', function ($query) use ($filter) {
                $query->where('slug', $filter['category']);
            });
        });
    }
}
