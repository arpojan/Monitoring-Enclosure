<?php

namespace App\Actions\Enclosure;

use App\Models\EnclosureParameter;
use App\Models\ParameterHistory;
use App\Models\Recommendation;
use Illuminate\Support\Facades\DB;

class ApplyRecommendationAction
{
    /**
     * Terapkan rekomendasi AI ke parameter kandang.
     *
     * Mengupdate EnclosureParameter, mencatat ParameterHistory,
     * dan mengubah status rekomendasi menjadi 'accepted'.
     *
     * @return array{ recommendation: Recommendation, parameters: EnclosureParameter }
     */
    public function execute(Recommendation $recommendation): array
    {
        return DB::transaction(function () use ($recommendation) {
            $enclosure = $recommendation->enclosure;
            $old       = $enclosure->parameters;

            $parameters = EnclosureParameter::updateOrCreate(
                ['enclosure_id' => $enclosure->id],
                [
                    'humidity_min'             => $old?->humidity_min             ?? 75,
                    'humidity_max'             => $old?->humidity_max             ?? 95,
                    'misting_bottom_threshold' => $recommendation->recommended_bottom_rh,
                    'misting_top_threshold'    => $recommendation->recommended_top_rh,
                    'misting_duration_seconds' => $recommendation->recommended_duration,
                    'is_misting_auto'          => $old?->is_misting_auto          ?? true,
                ]
            );

            ParameterHistory::create([
                'enclosure_id'         => $enclosure->id,
                'recommendation_id'    => $recommendation->id,
                'source'               => 'ai_recommendation',
                'changed_by'           => auth()->id(),
                'old_bottom_humidity'  => $old?->misting_bottom_threshold,
                'old_top_humidity'     => $old?->misting_top_threshold,
                'old_duration_seconds' => $old?->misting_duration_seconds,
                'new_bottom_humidity'  => $parameters->misting_bottom_threshold,
                'new_top_humidity'     => $parameters->misting_top_threshold,
                'new_duration_seconds' => $parameters->misting_duration_seconds,
                'metadata'             => [
                    'recommendation_title'       => $recommendation->title,
                    'recommendation_description' => $recommendation->description,
                ],
            ]);

            $recommendation->update([
                'decision_status' => 'accepted',
                'implemented_at'  => now(),
            ]);

            return [
                'recommendation' => $recommendation->fresh(),
                'parameters'     => $parameters->fresh(),
            ];
        });
    }
}
