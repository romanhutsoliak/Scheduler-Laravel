<?php

namespace App\Models\User;

use App\Models\BaseModel;

class UserDevice extends BaseModel
{
    protected $fillable = [
        'uuid',
        'userId',
        'hardwareUuid',
        'platform',
        'manufacturer',
        'model',
        'appVersion',
        'lastLoginTime',
    ];

    protected $casts = [
        'lastLoginTime' => 'datetime'
    ];
}
