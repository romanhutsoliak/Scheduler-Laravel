<?php

namespace App\Models;

class MissedLanguage extends BaseModel
{
    public $timestamps = false;

    protected $fillable = [
        'language',
        'text',
        'url',
        'created_at',
        'updated_at',
    ];
}
