<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnclosureParameter extends Model
{
    use HasFactory;

    protected $fillable = [
        'enclosure_id',
        'humidity_min',
        'humidity_max',
        'misting_bottom_threshold',
        'misting_top_threshold',
        'misting_duration_seconds',
        'is_misting_auto',
    ];

    protected $casts = [
        'humidity_min' => 'decimal:2',
        'humidity_max' => 'decimal:2',
        'misting_bottom_threshold' => 'decimal:2',
        'misting_top_threshold' => 'decimal:2',
        'misting_duration_seconds' => 'integer',
        'is_misting_auto' => 'boolean',
    ];

    public function enclosure(): BelongsTo
    {
        return $this->belongsTo(Enclosure::class);
    }
}
