<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model
{
    protected $casts_common = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
        'deleted_at' => 'datetime:Y-m-d H:i:s',
    ];

    protected $hidden_common = [
        'media',
    ];

    public function __construct(array $attributes = []) {
        $this->casts = array_merge($this->casts_common, $this->casts);
        $this->hidden = array_merge($this->hidden_common, $this->hidden);
        parent::__construct($attributes);
    }
}
