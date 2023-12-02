<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'label',
    ];

    public $timestamps = false;

    public function tasks()
    {
        return $this->hasMany(Task::class, 'categoryId');
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (! $model->slug) {
                $model->slug = strtolower(preg_replace('#[^\d\w_-]+#', '', $model->name));
            }
        });
        static::saving(function ($model) {
            if (! $model->slug) {
                $model->slug = strtolower(preg_replace('#[^\d\w_-]+#', '', $model->name));
            }
        });
    }

    /**
     * \@builder Returns only category which have tasks
     *
     * @param $query
     * @param $name
     * @return void
     */
    public function builderCategoriesWithTasks(Builder $builder)
    {
        return $builder->whereHas('tasks', function ($query) {
            $query->where('userId', auth()->user()->id);
        });
    }
}
