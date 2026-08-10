<?php

namespace App\Http\Controllers\Api;

use App\Actions\Enclosure\UpdateParametersAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\ControlConfigResource;
use App\Models\Enclosure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class EnclosureController extends Controller
{
    /**
     * PATCH /api/enclosures/{id}
     *
     * Update identitas kandang (nama, deskripsi, status aktif, dsb).
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name'        => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'species'     => ['sometimes', 'nullable', 'string', 'max:255'],
            'is_active'   => ['sometimes', 'boolean'],
        ]);

        $enclosure = Enclosure::findOrFail($id);
        $enclosure->fill($validated)->save();

        return response()->json([
            'success' => true,
            'message' => 'Enclosure updated successfully',
            'data'    => $enclosure->fresh('parameters'),
        ]);
    }

    /**
     * GET /api/enclosures/{id}/control-config
     *
     * Kembalikan konfigurasi kontrol untuk ESP32.
     * Autentikasi device ditangani oleh middleware AuthorizeDevice.
     *
     * Cache: di-cache 30 detik karena ESP32 sering polling.
     * Cache dibatalkan otomatis saat parameter diperbarui via updateParameters().
     */
    public function controlConfig(Request $request, int $id): JsonResponse
    {
        $enclosure = Enclosure::with('parameters')->findOrFail($id);

        if (!$enclosure->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Enclosure is not active.',
            ], 422);
        }

        $cacheKey = "control_config_enc_{$id}";
        $config   = Cache::remember(
            $cacheKey,
            30,
            fn () => (new ControlConfigResource($enclosure))->toArray($request)
        );

        // Cek trigger misting manual dari dashboard web
        if (Cache::pull("manual_mist_trigger_{$id}")) {
            $config['force_mist_now'] = true;
        }

        return response()->json([
            'success' => true,
            'message' => 'Control configuration fetched.',
            'data'    => $config,
        ]);
    }

    /**
     * POST /api/enclosures/{id}/trigger-mist
     *
     * Trigger manual misting dari web dashboard.
     * Menyimpan flag di cache agar diambil ESP32 pada polling berikutnya.
     */
    public function triggerManualMist(int $id): JsonResponse
    {
        Enclosure::findOrFail($id);

        // 120 detik = cukup aman untuk polling interval ESP32 yang ~60 detik
        Cache::put("manual_mist_trigger_{$id}", true, 120);

        return response()->json([
            'success' => true,
            'message' => 'Perintah misting manual telah dikirim ke perangkat.',
        ]);
    }

    /**
     * PUT /api/enclosures/{id}/parameters
     *
     * Update parameter kontrol misting dari web dashboard.
     * Logika update + pencatatan riwayat ditangani oleh UpdateParametersAction.
     */
    public function updateParameters(Request $request, int $id, UpdateParametersAction $action): JsonResponse
    {
        $validated = $request->validate([
            'humidity_min'             => ['sometimes', 'numeric', 'between:0,100'],
            'humidity_max'             => ['sometimes', 'numeric', 'between:0,100'],
            'misting_bottom_threshold' => ['required', 'numeric', 'between:0,100'],
            'misting_top_threshold'    => ['required', 'numeric', 'between:0,100', 'gt:misting_bottom_threshold'],
            'misting_duration_seconds' => ['required', 'integer', 'min:1', 'max:300'],
            'is_misting_auto'          => ['sometimes', 'boolean'],
            'source'                   => ['sometimes', 'string', Rule::in(['manual', 'ai_recommendation', 'system_default'])],
            'recommendation_id'        => ['sometimes', 'nullable', 'integer', 'exists:recommendations,id'],
        ]);

        $enclosure = Enclosure::with('parameters')->findOrFail($id);

        $humMin = $validated['humidity_min'] ?? $enclosure->parameters?->humidity_min ?? 75;
        $humMax = $validated['humidity_max'] ?? $enclosure->parameters?->humidity_max ?? 95;

        if ((float) $humMin >= (float) $humMax) {
            return response()->json([
                'success' => false,
                'message' => 'humidity_min must be lower than humidity_max',
            ], 422);
        }

        $parameters = $action->execute($enclosure, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Control parameters updated successfully',
            'data'    => [
                'parameters'     => $parameters,
                'control_config' => (new ControlConfigResource($enclosure->fresh('parameters')))->toArray($request),
            ],
        ]);
    }
}
