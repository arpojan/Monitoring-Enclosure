<?php

namespace App\Http\Resources;

use App\Models\Enclosure;
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

        return [
            'enclosure_id'             => $this->resource->id,
            'enclosure_name'           => $this->resource->name,
            'mode'                     => $params?->is_misting_auto ? 'auto' : 'manual',
            'target_habitat'           => $this->resource->target_habitat,
            'bottom_humidity'          => $params ? (float) $params->misting_bottom_threshold : null,
            'top_humidity'             => $params ? (float) $params->misting_top_threshold : null,
            'misting_duration_seconds' => $params ? (int) $params->misting_duration_seconds : null,
            'humidity_min'             => $params ? (float) $params->humidity_min : null,
            'humidity_max'             => $params ? (float) $params->humidity_max : null,
            'updated_at'               => $params?->updated_at?->toIso8601String(),
        ];
    }
}
