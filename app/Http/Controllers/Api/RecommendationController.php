<?php

namespace App\Http\Controllers\Api;

use App\Actions\Enclosure\ApplyRecommendationAction;
use App\Http\Controllers\Controller;
use App\Models\Recommendation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecommendationController extends Controller
{
    /**
     * POST /api/recommendations/{id}/apply
     *
     * Terapkan rekomendasi AI ke parameter kontrol kandang.
     * Hanya bisa diterapkan jika statusnya masih 'pending'.
     */
    public function apply(Request $request, int $id, ApplyRecommendationAction $action): JsonResponse
    {
        $recommendation = Recommendation::with('enclosure.parameters')->findOrFail($id);

        if ($recommendation->decision_status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Recommendation is not pending',
            ], 422);
        }

        if ($recommendation->recommended_bottom_rh === null || $recommendation->recommended_top_rh === null) {
            return response()->json([
                'success' => false,
                'message' => 'Recommendation does not contain complete control parameters',
            ], 422);
        }

        // Fallback durasi: pakai nilai saat ini jika AI tidak menyarankan durasi baru
        if ($recommendation->recommended_duration === null) {
            $recommendation->recommended_duration =
                $recommendation->enclosure->parameters?->misting_duration_seconds ?? 10;
        }

        if ((float) $recommendation->recommended_bottom_rh >= (float) $recommendation->recommended_top_rh) {
            return response()->json([
                'success' => false,
                'message' => 'Recommended bottom humidity must be lower than top humidity',
            ], 422);
        }

        $result = $action->execute($recommendation);

        return response()->json([
            'success' => true,
            'message' => 'AI recommendation applied to control parameters',
            'data'    => $result,
        ]);
    }

    /**
     * POST /api/recommendations/{id}/reject
     *
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

        $recommendation->update(['decision_status' => 'rejected']);

        return response()->json([
            'success' => true,
            'message' => 'AI recommendation rejected',
            'data'    => $recommendation->fresh(),
        ]);
    }
}
