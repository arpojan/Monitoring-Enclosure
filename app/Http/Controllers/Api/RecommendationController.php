<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EnclosureParameter;
use App\Models\ParameterHistory;
use App\Models\Recommendation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RecommendationController extends Controller
{
    /**
     * Terapkan rekomendasi AI ke parameter kontrol enclosure.
     *
     * AI berperan sebagai DSS: menghasilkan saran. User tetap memutuskan
     * apakah saran tersebut diterapkan atau ditolak.
     */
    public function apply(Request $request, int $id): JsonResponse
    {
        $recommendation = Recommendation::with('enclosure.parameters')->findOrFail($id);

        if ($recommendation->decision_status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Recommendation is not pending',
            ], 422);
        }

        if (
            $recommendation->recommended_bottom_rh === null ||
            $recommendation->recommended_top_rh === null ||
            $recommendation->recommended_duration === null
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Recommendation does not contain complete control parameters',
            ], 422);
        }

        if ((float) $recommendation->recommended_bottom_rh >= (float) $recommendation->recommended_top_rh) {
            return response()->json([
                'success' => false,
                'message' => 'Recommended bottom humidity must be lower than top humidity',
            ], 422);
        }

        $result = DB::transaction(function () use ($recommendation) {
            $enclosure = $recommendation->enclosure;
            $old = $enclosure->parameters;

            $parameters = EnclosureParameter::updateOrCreate(
                ['enclosure_id' => $enclosure->id],
                [
                    'humidity_min' => $old?->humidity_min ?? 75,
                    'humidity_max' => $old?->humidity_max ?? 95,
                    'misting_bottom_threshold' => $recommendation->recommended_bottom_rh,
                    'misting_top_threshold' => $recommendation->recommended_top_rh,
                    'misting_duration_seconds' => $recommendation->recommended_duration,
                    'is_misting_auto' => $old?->is_misting_auto ?? true,
                ]
            );

            ParameterHistory::create([
                'enclosure_id' => $enclosure->id,
                'recommendation_id' => $recommendation->id,
                'source' => 'ai_recommendation',
                'changed_by' => auth()->id(),
                'old_bottom_humidity' => $old?->misting_bottom_threshold,
                'old_top_humidity' => $old?->misting_top_threshold,
                'old_duration_seconds' => $old?->misting_duration_seconds,
                'new_bottom_humidity' => $parameters->misting_bottom_threshold,
                'new_top_humidity' => $parameters->misting_top_threshold,
                'new_duration_seconds' => $parameters->misting_duration_seconds,
                'metadata' => [
                    'recommendation_title' => $recommendation->title,
                    'recommendation_description' => $recommendation->description,
                ],
            ]);

            $recommendation->update([
                'decision_status' => 'accepted',
                'implemented_at' => now(),
            ]);

            return [
                'recommendation' => $recommendation->fresh(),
                'parameters' => $parameters->fresh(),
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'AI recommendation applied to control parameters',
            'data' => $result,
        ]);
    }

    /**
     * Tolak rekomendasi AI agar tidak muncul lagi sebagai pending action.
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $recommendation = Recommendation::findOrFail($id);

        if ($recommendation->decision_status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Recommendation is not pending',
            ], 422);
        }

        $recommendation->update([
            'decision_status' => 'rejected',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'AI recommendation rejected',
            'data' => $recommendation->fresh(),
        ]);
    }
}
