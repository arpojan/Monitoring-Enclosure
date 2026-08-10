<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Enclosure extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'image_path',
        'description',
        'species',
        'is_active',
        'target_habitat',
        'jenis_hewan',
        'last_seen_at',
        'device_key',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_seen_at' => 'datetime',
    ];

    /**
     * Determine if the enclosure's ESP32 is online.
     * Considers online if last_seen_at is within the last 1 minute.
     */
    public function isOnline(): bool
    {
        if (is_null($this->last_seen_at)) {
            return false;
        }

        return $this->last_seen_at->greaterThan(now()->subMinute());
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parameters(): HasOne
    {
        return $this->hasOne(EnclosureParameter::class);
    }

    public function sensorLogs(): HasMany
    {
        return $this->hasMany(SensorLog::class);
    }

    public function stabilityScores(): HasMany
    {
        return $this->hasMany(StabilityScore::class);
    }

    public function insights(): HasMany
    {
        return $this->hasMany(Insight::class);
    }

    public function recommendations(): HasMany
    {
        return $this->hasMany(Recommendation::class);
    }

    public function eventTimelines(): HasMany
    {
        return $this->hasMany(EventTimeline::class);
    }

    public function parameterHistories(): HasMany
    {
        return $this->hasMany(ParameterHistory::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }
}
