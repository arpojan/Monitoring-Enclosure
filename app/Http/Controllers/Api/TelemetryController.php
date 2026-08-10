<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ControlConfigResource;
use App\Models\Enclosure;
use App\Models\SensorLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * TelemetryController — Menerima data sensor dari ESP32.
 *
 * Arsitektur:
 *   - ESP32 membaca sensor, menjalankan rule-based misting secara lokal,
 *     lalu mengirim hasilnya ke endpoint ini.
 *   - Laravel menyimpan data untuk monitoring, analytics, dan AI DSS.
 *   - Laravel mengembalikan `control_config` terbaru agar ESP32 bisa
 *     menyinkronkan parameter threshold secara pasif.
 *
 * Autentikasi device ditangani oleh middleware AuthorizeDevice.
 */
class TelemetryController extends Controller
{
    /**
     * POST /api/telemetry
     *
     * Terima data telemetry dari ESP32 dan simpan ke sensor_logs.
     *
     * Payload JSON yang diharapkan:
     * {
     *   "enclosure_id":              1,
     *   "temperature":               27.5,
     *   "humidity":                  85.3,
     *   "top_humidity":              87.1,        // (Opsional) Sensor atas
     *   "bottom_humidity":           83.5,        // (Opsional) Sensor bawah
     *   "misting_status":            true,
     *   "misting_duration_executed": 10,
     *   "device_timestamp":          "2026-08-06T01:00:00Z"
     * }
     *
     * Header: X-Device-Key: <device_key>
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'enclosure_id'              => ['required', 'integer', 'exists:enclosures,id'],
            'temperature'               => ['required', 'numeric', 'between:-10,60'],
            'humidity'                  => ['required', 'numeric', 'between:0,100'],
            'top_humidity'              => ['sometimes', 'nullable', 'numeric', 'between:0,100'],
            'bottom_humidity'           => ['sometimes', 'nullable', 'numeric', 'between:0,100'],
            'misting_status'            => ['sometimes', 'boolean'],
            'misting_duration_executed' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:300'],
            'device_timestamp'          => ['sometimes', 'nullable', 'date'],
        ]);

        try {
            $enclosure = Enclosure::with('parameters')->findOrFail($validated['enclosure_id']);

            if (!$enclosure->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Enclosure is not active.',
                ], 422);
            }

            $now             = Carbon::now();
            $deviceTimestamp = isset($validated['device_timestamp'])
                ? Carbon::parse($validated['device_timestamp'])
                : null;

            // field 'humidity' = kelembapan utama; jika ada sensor terpisah, pakai 'top_humidity'
            $sensorLog = SensorLog::create([
                'enclosure_id'              => $enclosure->id,
                'temperature'               => $validated['temperature'],
                'humidity'                  => $validated['top_humidity'] ?? $validated['humidity'],
                'misting_status'            => $validated['misting_status'] ?? false,
                'misting_duration_executed' => $validated['misting_duration_executed'] ?? null,
                'logged_at'                 => $now,
                'device_timestamp'          => $deviceTimestamp,
            ]);

            $enclosure->update(['last_seen_at' => $now]);
            $enclosure->refresh()->load('parameters');

            return response()->json([
                'success' => true,
                'message' => 'Telemetry received.',
                'data'    => [
                    'sensor_log_id'             => $sensorLog->id,
                    'enclosure_id'              => $enclosure->id,
                    'enclosure_name'            => $enclosure->name,
                    'temperature'               => (float) $sensorLog->temperature,
                    'humidity'                  => (float) $sensorLog->humidity,
                    'misting_status'            => (bool)  $sensorLog->misting_status,
                    'misting_duration_executed' => $sensorLog->misting_duration_executed,
                    'logged_at'                 => $sensorLog->logged_at->toIso8601String(),
                    'device_timestamp'          => $sensorLog->device_timestamp?->toIso8601String(),
                    'system_status'             => 'online',
                    'control_config'            => (new ControlConfigResource($enclosure))->toArray($request),
                ],
            ], 201);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Enclosure not found.'], 404);

        } catch (\Throwable $e) {
            Log::error('Telemetry store failed', [
                'error'   => $e->getMessage(),
                'payload' => $request->except(['device_key']),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error. Telemetry could not be saved.',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
