<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Block extends Model
{
    protected $fillable = [
        'height',
        'geo_location_id',
        'actor',
        'data_hash',
        'prev_hash',
        'nonce',
        'hash',
        'timestamp',
    ];

    protected $casts = [
        'height' => 'integer',
        'nonce' => 'integer',
        'timestamp' => 'integer',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(GeoLocation::class, 'geo_location_id');
    }
}
