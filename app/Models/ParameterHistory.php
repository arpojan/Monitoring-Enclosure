<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParameterHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'enclosure_id',
        'recommendation_id',
        'source',
        'changed_by',
        'old_bottom_humidity',
        'old_top_humidity',
        'old_duration_seconds',
        'new_bottom_humidity',
        'new_top_humidity',
        'new_duration_seconds',
        'metadata',
    ];

    protected $casts = [
        'old_bottom_humidity' => 'decimal:2',
        'old_top_humidity' => 'decimal:2',
        'old_duration_seconds' => 'integer',
        'new_bottom_humidity' => 'decimal:2',
        'new_top_humidity' => 'decimal:2',
        'new_duration_seconds' => 'integer',
        'metadata' => 'array',
    ];

    public function enclosure(): BelongsTo
    {
        return $this->belongsTo(Enclosure::class);
    }

    public function recommendation(): BelongsTo
    {
        return $this->belongsTo(Recommendation::class);
    }
}
