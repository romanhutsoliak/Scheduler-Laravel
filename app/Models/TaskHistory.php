<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaskHistory extends Model
{
    use HasFactory,
        SoftDeletes;

    public $fillable = [
        'taskId',
        'notes',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class, 'userId');
    }
}
