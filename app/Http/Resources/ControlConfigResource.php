<?php

namespace App\Http\Resources;

use App\Models\Enclosure;
use App\Services\AnimalKnowledgeBase;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ControlConfigResource extends JsonResource
{
    /**
     * Transform enclosure into control config payload for ESP32.
     *
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Enclosure $this->resource */
        $params = $this->resource->parameters;

        $speciesKey = $this->resource->species_key ?? $this->resource->jenis_hewan;
        $species = $speciesKey ? AnimalKnowledgeBase::getSpeciesByKey($speciesKey) : null;

        $tMax = $species ? (float) $species['temperature']['temp_max'] : 31.0;
        $rhMin = $species ? (float) $species['humidity']['humid_min'] : ($params ? (float) $params->humidity_min : 55.0);
        $rhMax = $species ? (float) $species['humidity']['humid_max'] : ($params ? (float) $params->humidity_max : null);

        return [
            'enclosure_id'             => $this->resource->id,
            'enclosure_name'           => $this->resource->name,
            'mode'                     => $params?->is_misting_auto ? 'auto' : 'manual',
            'target_habitat'           => $this->resource->target_habitat,
            'bottom_humidity'          => $params ? (float) $params->misting_bottom_threshold : null,
            'top_humidity'             => $params ? (float) $params->misting_top_threshold : null,
            'misting_duration_seconds' => $params ? (int) $params->misting_duration_seconds : null,
            'humidity_min'             => $rhMin,
            'humidity_max'             => $rhMax,
            't_max'                    => $tMax,
            'temp_max'                 => $tMax,
            'temperature_max'          => $tMax,
            'updated_at'               => $params?->updated_at?->toIso8601String(),
            
            // ── Species thresholds dari AnimalKnowledgeBase ──────────────
            'species_key'              => $speciesKey,
            'species_name'             => $species['name'] ?? null,
            'temp_min'                 => $species ? (float) $species['temperature']['temp_min'] : null,
            'temp_ideal_min'           => $species ? (float) $species['temperature']['temp_ideal_min'] : null,
            'temp_ideal_max'           => $species ? (float) $species['temperature']['temp_ideal_max'] : null,
            'humid_min_critical'       => $species ? (float) $species['humidity']['humid_min'] : null,
            'humid_ideal_min'          => $species ? (float) $species['humidity']['humid_ideal_min'] : null,
            'humid_ideal_max'          => $species ? (float) $species['humidity']['humid_ideal_max'] : null,
            'humid_max_critical'       => $species ? (float) $species['humidity']['humid_max'] : null,
        ];
    }
}