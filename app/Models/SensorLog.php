<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SensorLog extends Model
{
    use HasFactory;

    // Disabling updated_at since sensor logs are append-only
    const UPDATED_AT = null;

    protected $fillable = [
        'enclosure_id',
        'temperature',
        'humidity',
        'misting_status',
        'misting_duration_executed',
        'logged_at',
        'device_timestamp',
    ];

    protected $casts = [
        'temperature' => 'decimal:2',
        'humidity' => 'decimal:2',
        'misting_status' => 'boolean',
        'misting_duration_executed' => 'integer',
        'logged_at' => 'datetime',
        'device_timestamp' => 'datetime',
    ];

    public function enclosure(): BelongsTo
    {
        return $this->belongsTo(Enclosure::class);
    }
}
