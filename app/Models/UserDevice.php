<?php

namespace App\Models;

class UserDevice extends BaseModel
{
    protected $fillable = [
        'userId',
        'deviceId',
        'platform',
        'manufacturer',
        'model',
        'appVersion',
        'notificationToken',
    ];

    protected $casts = [
        'lastLoginTime' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (! $model->userId) {
                $model->userId = auth()->user()->id ?? null;
            }
        });
    }
}
