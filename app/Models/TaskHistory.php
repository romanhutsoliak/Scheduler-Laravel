<?php

namespace App\Models;

use App\Models\Task;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TaskHistory extends Model
{
    use HasFactory;

    public $fillable = [
        'taskId',
        'notes',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class, 'userId');
    }
}
