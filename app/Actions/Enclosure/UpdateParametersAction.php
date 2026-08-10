<?php

namespace App\Actions\Enclosure;

use App\Models\Enclosure;
use App\Models\EnclosureParameter;
use App\Models\ParameterHistory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class UpdateParametersAction
{
    /**
     * Update parameter misting kandang dan catat riwayatnya.
     *
     * Menangani: updateOrCreate EnclosureParameter, insert ParameterHistory,
     * dan bust cache control_config agar ESP32 segera dapat nilai baru.
     */
    public function execute(Enclosure $enclosure, array $data): EnclosureParameter
    {
        return DB::transaction(function () use ($enclosure, $data) {
            $old = $enclosure->parameters;

            $parameters = EnclosureParameter::updateOrCreate(
                ['enclosure_id' => $enclosure->id],
                [
                    'humidity_min'             => $data['humidity_min']             ?? $old?->humidity_min             ?? 75,
                    'humidity_max'             => $data['humidity_max']             ?? $old?->humidity_max             ?? 95,
                    'misting_bottom_threshold' => $data['misting_bottom_threshold'],
                    'misting_top_threshold'    => $data['misting_top_threshold'],
                    'misting_duration_seconds' => $data['misting_duration_seconds'],
                    'is_misting_auto'          => $data['is_misting_auto']          ?? $old?->is_misting_auto          ?? true,
                ]
            );

            ParameterHistory::create([
                'enclosure_id'         => $enclosure->id,
                'recommendation_id'    => $data['recommendation_id'] ?? null,
                'source'               => $data['source']            ?? 'manual',
                'changed_by'           => auth()->id(),
                'old_bottom_humidity'  => $old?->misting_bottom_threshold,
                'old_top_humidity'     => $old?->misting_top_threshold,
                'old_duration_seconds' => $old?->misting_duration_seconds,
                'new_bottom_humidity'  => $parameters->misting_bottom_threshold,
                'new_top_humidity'     => $parameters->misting_top_threshold,
                'new_duration_seconds' => $parameters->misting_duration_seconds,
                'metadata'             => [
                    'humidity_min'    => $parameters->humidity_min,
                    'humidity_max'    => $parameters->humidity_max,
                    'is_misting_auto' => $parameters->is_misting_auto,
                ],
            ]);

            // Bust cache agar ESP32 segera dapat konfigurasi terbaru
            Cache::forget("control_config_enc_{$enclosure->id}");

            return $parameters->fresh();
        });
    }
}
