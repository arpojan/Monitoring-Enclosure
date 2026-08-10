<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Enclosure;
use App\Models\EnclosureParameter;
use App\Models\ParameterHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EnclosureController extends Controller
{
    /**
     * Update identitas enclosure, seperti nama dan deskripsi.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'species' => ['sometimes', 'nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $enclosure = Enclosure::findOrFail($id);
        $enclosure->fill($validated);
        $enclosure->save();

        return response()->json([
            'success' => true,
            'message' => 'Enclosure updated successfully',
            'data' => $enclosure->fresh('parameters'),
        ]);
    }

    /**
     * Mengambil parameter kontrol yang dipakai ESP32.
     *
     * Web/Laravel hanya menyimpan dan menyediakan konfigurasi.
     * ESP32 tetap menjadi eksekutor rule-based misting.
     *
     * Cache: Hasil di-cache selama 30 detik untuk mengurangi beban DB
     * saat ESP32 polling setiap beberapa detik. Cache dibatalkan otomatis
     * saat parameter diperbarui via updateParameters().
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

        if (!$this->isAuthorizedDevice($request, $enclosure)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized device. Please check X-Device-Key header.',
            ], 401);
        }

        // Cache control_config selama 30 detik — ESP32 sering polling, cukup 1 DB query per 30 detik
        $cacheKey = "control_config_enc_{$id}";
        $config   = Cache::remember($cacheKey, 30, fn () => $this->formatControlConfig($enclosure));

        // Cek apakah ada trigger misting manual
        $manualMistKey = "manual_mist_trigger_{$id}";
        if (Cache::pull($manualMistKey)) {
            // Jika ada (sekaligus menghapusnya dengan pull), tambahkan flag force_mist_now
            $config['force_mist_now'] = true;
        }

        return response()->json([
            'success' => true,
            'message' => 'Control configuration fetched.',
            'data'    => $config,
        ]);
    }

    /**
     * Trigger manual misting dari web dashboard.
     * Menyimpan status di cache agar diambil oleh ESP32 pada polling berikutnya.
     */
    public function triggerManualMist(int $id): JsonResponse
    {
        $enclosure = Enclosure::findOrFail($id);

        // Simpan flag di cache selama 120 detik.
        // Simulator polling setiap 60 detik (6 tick x 10 detik), jadi 120 detik aman agar tidak terlewat.
        Cache::put("manual_mist_trigger_{$id}", true, 120);

        return response()->json([
            'success' => true,
            'message' => 'Perintah misting manual telah dikirim ke perangkat.',
        ]);
    }

    /**
     * Update parameter kontrol misting dari web.
     *
     * Parameter ini kemudian diambil/diterima ESP32 untuk menjalankan
     * rule-based misting secara lokal di perangkat.
     */
    public function updateParameters(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'humidity_min' => ['sometimes', 'numeric', 'between:0,100'],
            'humidity_max' => ['sometimes', 'numeric', 'between:0,100'],
            'misting_bottom_threshold' => ['required', 'numeric', 'between:0,100'],
            'misting_top_threshold' => ['required', 'numeric', 'between:0,100', 'gt:misting_bottom_threshold'],
            'misting_duration_seconds' => ['required', 'integer', 'min:1', 'max:300'],
            'is_misting_auto' => ['sometimes', 'boolean'],
            'source' => ['sometimes', 'string', Rule::in(['manual', 'ai_recommendation', 'system_default'])],
            'recommendation_id' => ['sometimes', 'nullable', 'integer', 'exists:recommendations,id'],
        ]);

        $enclosure = Enclosure::with('parameters')->findOrFail($id);
        $oldParameters = $enclosure->parameters;
        $humidityMin = $validated['humidity_min'] ?? $oldParameters?->humidity_min ?? 75;
        $humidityMax = $validated['humidity_max'] ?? $oldParameters?->humidity_max ?? 95;

        if ((float) $humidityMin >= (float) $humidityMax) {
            return response()->json([
                'success' => false,
                'message' => 'humidity_min must be lower than humidity_max',
            ], 422);
        }

        $parameters = DB::transaction(function () use ($enclosure, $validated, $oldParameters, $humidityMin, $humidityMax) {
            $old = $oldParameters;

            $parameters = EnclosureParameter::updateOrCreate(
                ['enclosure_id' => $enclosure->id],
                [
                    'humidity_min'             => $humidityMin,
                    'humidity_max'             => $humidityMax,
                    'misting_bottom_threshold' => $validated['misting_bottom_threshold'],
                    'misting_top_threshold'    => $validated['misting_top_threshold'],
                    'misting_duration_seconds' => $validated['misting_duration_seconds'],
                    'is_misting_auto'          => $validated['is_misting_auto'] ?? $old?->is_misting_auto ?? true,
                ]
            );

            ParameterHistory::create([
                'enclosure_id'        => $enclosure->id,
                'recommendation_id'   => $validated['recommendation_id'] ?? null,
                'source'              => $validated['source'] ?? 'manual',
                'changed_by'          => auth()->id(),
                'old_bottom_humidity' => $old?->misting_bottom_threshold,
                'old_top_humidity'    => $old?->misting_top_threshold,
                'old_duration_seconds'=> $old?->misting_duration_seconds,
                'new_bottom_humidity' => $parameters->misting_bottom_threshold,
                'new_top_humidity'    => $parameters->misting_top_threshold,
                'new_duration_seconds'=> $parameters->misting_duration_seconds,
                'metadata'            => [
                    'humidity_min'    => $parameters->humidity_min,
                    'humidity_max'    => $parameters->humidity_max,
                    'is_misting_auto' => $parameters->is_misting_auto,
                ],
            ]);

            return $parameters;
        });

        // Hapus cache control_config agar ESP32 segera mendapat nilai baru
        Cache::forget("control_config_enc_{$id}");

        return response()->json([
            'success' => true,
            'message' => 'Control parameters updated successfully',
            'data' => [
                'parameters' => $parameters->fresh(),
                'control_config' => $this->formatControlConfig($enclosure->fresh('parameters')),
            ],
        ]);
    }

    private function isAuthorizedDevice(Request $request, Enclosure $enclosure): bool
    {
        if (blank($enclosure->device_key)) {
            return true;
        }

        return hash_equals((string) $enclosure->device_key, (string) $request->header('X-DEVICE-KEY'));
    }

    private function formatControlConfig(Enclosure $enclosure): array
    {
        $params = $enclosure->parameters;

        return [
            'enclosure_id' => $enclosure->id,
            'enclosure_name' => $enclosure->name,
            'mode' => $params?->is_misting_auto ? 'auto' : 'manual',
            'bottom_humidity' => $params ? (float) $params->misting_bottom_threshold : null,
            'top_humidity' => $params ? (float) $params->misting_top_threshold : null,
            'misting_duration_seconds' => $params ? (int) $params->misting_duration_seconds : null,
            'humidity_min' => $params ? (float) $params->humidity_min : null,
            'humidity_max' => $params ? (float) $params->humidity_max : null,
            'updated_at' => $params?->updated_at?->toIso8601String(),
        ];
    }
}
