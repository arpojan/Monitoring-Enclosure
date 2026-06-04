<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Enclosure;
use App\Models\SensorLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class TelemetryController extends Controller
{
    /**
     * Menerima data telemetry aktual dari ESP32.
     *
     * Flow final project:
     * 1. ESP32 membaca sensor dan menjalankan rule-based misting secara lokal.
     * 2. ESP32 mengirim temperature, humidity, dan status misting aktual ke web.
     * 3. Laravel menyimpan telemetry untuk monitoring, analytics, AI insight, dan DSS.
     * 4. Laravel mengembalikan control_config terbaru agar ESP32 bisa sinkron parameter.
     *
     * Catatan penting:
     * Laravel tidak lagi menjadi pengambil keputusan ON/OFF misting utama.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'enclosure_id' => ['required', 'integer', 'exists:enclosures,id'],
            'temperature' => ['required', 'numeric', 'between:-10,60'],
            'humidity' => ['required', 'numeric', 'between:0,100'],
            'misting_status' => ['sometimes', 'boolean'],
            'misting_duration_executed' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:300'],
            'device_timestamp' => ['sometimes', 'nullable', 'date'],
        ]);

        $enclosure = Enclosure::with('parameters')->findOrFail($validated['enclosure_id']);

        if (!$enclosure->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Enclosure is not active',
            ], 422);
        }

        if (!$this->isAuthorizedDevice($request, $enclosure)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized device',
            ], 401);
        }

        $now = Carbon::now();
        $deviceTimestamp = isset($validated['device_timestamp'])
            ? Carbon::parse($validated['device_timestamp'])
            : null;

        $sensorLog = SensorLog::create([
            'enclosure_id' => $enclosure->id,
            'temperature' => $validated['temperature'],
            'humidity' => $validated['humidity'],
            'misting_status' => $validated['misting_status'] ?? false,
            'misting_duration_executed' => $validated['misting_duration_executed'] ?? null,
            'logged_at' => $now,
            'device_timestamp' => $deviceTimestamp,
        ]);

        $enclosure->update(['last_seen_at' => $now]);
        $enclosure->refresh()->load('parameters');

        return response()->json([
            'success' => true,
            'message' => 'Telemetry received',
            'data' => [
                'sensor_log_id' => $sensorLog->id,
                'enclosure_id' => $enclosure->id,
                'enclosure_name' => $enclosure->name,
                'temperature' => (float) $sensorLog->temperature,
                'humidity' => (float) $sensorLog->humidity,
                'misting_status' => (bool) $sensorLog->misting_status,
                'misting_duration_executed' => $sensorLog->misting_duration_executed,
                'logged_at' => $sensorLog->logged_at->toIso8601String(),
                'device_timestamp' => $sensorLog->device_timestamp?->toIso8601String(),
                'system_status' => 'online',
                'control_config' => $this->formatControlConfig($enclosure),
            ],
        ], 201);
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
