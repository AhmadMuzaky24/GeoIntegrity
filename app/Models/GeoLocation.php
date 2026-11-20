<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GeoLocation extends Model
{
    protected $fillable = [
        'name',
        'description',
        'latitude',
        'longitude',
        'data_hash',
        'created_by',
        'updated_by',
    ];

    public function blocks(): HasMany
    {
        return $this->hasMany(Block::class);
    }
}
