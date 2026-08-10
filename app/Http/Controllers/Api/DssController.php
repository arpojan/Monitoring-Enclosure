<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Enclosure;
use App\Services\DssService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * DssController — REST interface untuk Decision Support System Engine.
 *
 * Endpoint:
 *   POST /api/enclosures/{id}/analyze
 *
 * Alur Human-in-the-Loop:
 *   1. Endpoint ini dipanggil (manual via tombol dashboard ATAU terjadwal via artisan).
 *   2. DssService menganalisis data, menyimpan StabilityScore, Insight, dan Recommendation.
 *   3. Recommendation tersimpan dengan status 'pending' — belum memengaruhi ESP32.
 *   4. User melihat saran di dashboard → klik "Terapkan" (POST /recommendations/{id}/apply)
 *      atau "Tolak" (POST /recommendations/{id}/reject).
 *   5. Hanya setelah "Terapkan" diklik, enclosure_parameters diperbarui dan ESP32 mengambil
 *      config baru via GET /api/enclosures/{id}/control-config.
 */
class DssController extends Controller
{
    public function __construct(private readonly DssService $dssService)
    {
    }

    /**
     * POST /api/enclosures/{id}/analyze
     *
     * Picu analisis DSS untuk enclosure tertentu.
     *
     * Query params:
     *   ?hours=24  — Jendela analisis dalam jam (default: 24, maks: 168 / 7 hari).
     *
     * Response:
     *   {
     *     "success": true,
     *     "message": "...",
     *     "data": {
     *       "stability":      { ... stability_score record ... },
     *       "insight":        { ... insight record ... } | null,
     *       "recommendation": { ... recommendation record ... } | null
     *     }
     *   }
     */
    public function analyze(Request $request, int $id): JsonResponse
    {
        // Validasi parameter request
        $validated = $request->validate([
            'hours' => ['sometimes', 'integer', 'min:1', 'max:168'],
        ]);

        $hours = (int) ($validated['hours'] ?? 24);

        // Ambil enclosure — 404 jika tidak ditemukan
        $enclosure = Enclosure::with('parameters')->findOrFail($id);

        try {
            $result = $this->dssService->analyze($enclosure, $hours);

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data'    => [
                    'stability'      => $result['stability'],
                    'insight'        => $result['insight'],
                    'recommendation' => $result['recommendation'],
                    'analysis_window_hours' => $hours,
                    'enclosure_id'   => $enclosure->id,
                    'enclosure_name' => $enclosure->name,
                    'analyzed_at'    => now()->toIso8601String(),
                ],
            ]);
        } catch (\Throwable $e) {
            // Log lengkap untuk debugging, kembalikan pesan ramah ke client
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menjalankan analisis DSS.',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
