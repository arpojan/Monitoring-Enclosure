<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
 * Autentikasi:
 *   - Gunakan header `X-Device-Key: <device_key>`.
 *   - Jika enclosure.device_key kosong, semua request diterima (dev mode).
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
     *   "temperature":               27.5,        // Suhu dalam °C
     *   "humidity":                  85.3,        // Kelembapan umum (%)
     *   "top_humidity":              87.1,        // (Opsional) Sensor kelembapan atas
     *   "bottom_humidity":           83.5,        // (Opsional) Sensor kelembapan bawah
     *   "misting_status":            true,        // Apakah misting sedang aktif
     *   "misting_duration_executed": 10,          // Berapa detik misting berjalan
     *   "device_timestamp":          "2026-08-06T01:00:00Z" // Timestamp perangkat (opsional)
     * }
     *
     * Header: X-Device-Key: <device_key>
     *
     * Response 201: Data tersimpan + control_config terbaru untuk ESP32.
     * Response 401: Device key tidak cocok.
     * Response 422: Enclosure tidak aktif atau validasi gagal.
     */
    public function store(Request $request): JsonResponse
    {
        // ── 1. Validasi payload ──────────────────────────────────
        $validated = $request->validate([
            'enclosure_id'              => ['required', 'integer', 'exists:enclosures,id'],
            'temperature'               => ['required', 'numeric', 'between:-10,60'],
            'humidity'                  => ['required', 'numeric', 'between:0,100'],
            // Sensor terpisah top/bottom (opsional; ESP32 dengan 2 sensor)
            'top_humidity'              => ['sometimes', 'nullable', 'numeric', 'between:0,100'],
            'bottom_humidity'           => ['sometimes', 'nullable', 'numeric', 'between:0,100'],
            'misting_status'            => ['sometimes', 'boolean'],
            'misting_duration_executed' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:300'],
            'device_timestamp'          => ['sometimes', 'nullable', 'date'],
        ]);

        try {
            // ── 2. Ambil enclosure ───────────────────────────────
            $enclosure = Enclosure::with('parameters')->findOrFail($validated['enclosure_id']);

            // ── 3. Cek status aktif ──────────────────────────────
            if (!$enclosure->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Enclosure is not active.',
                ], 422);
            }

            // ── 4. Autentikasi device key ────────────────────────
            if (!$this->isAuthorizedDevice($request, $enclosure)) {
                Log::warning('Telemetry: Unauthorized device attempt', [
                    'enclosure_id' => $enclosure->id,
                    'ip'           => $request->ip(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized device. Please check X-Device-Key header.',
                ], 401);
            }

            // ── 5. Simpan sensor log ─────────────────────────────
            $now             = Carbon::now();
            $deviceTimestamp = isset($validated['device_timestamp'])
                ? Carbon::parse($validated['device_timestamp'])
                : null;

            $sensorLog = SensorLog::create([
                'enclosure_id'              => $enclosure->id,
                'temperature'               => $validated['temperature'],
                // Field 'humidity' = kelembapan utama (kompatibilitas ke belakang)
                // Jika ESP32 mengirim field terpisah, nilai dari 'top_humidity' digunakan sebagai utama
                'humidity'                  => $validated['top_humidity'] ?? $validated['humidity'],
                'misting_status'            => $validated['misting_status'] ?? false,
                'misting_duration_executed' => $validated['misting_duration_executed'] ?? null,
                'logged_at'                 => $now,
                'device_timestamp'          => $deviceTimestamp,
            ]);

            // ── 6. Update last_seen_at enclosure ─────────────────
            $enclosure->update(['last_seen_at' => $now]);
            $enclosure->refresh()->load('parameters');

            // ── 7. Kembalikan respons + control_config ───────────
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
                    // Kembalikan konfigurasi terbaru agar ESP32 bisa sinkron threshold
                    'control_config'            => $this->formatControlConfig($enclosure),
                ],
            ], 201);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Enclosure not found.',
            ], 404);

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

    /**
     * Verifikasi bahwa request berasal dari perangkat yang sah.
     *
     * Jika enclosure.device_key dikosongkan (null/blank), semua request diterima.
     * Ini berguna saat development/testing sebelum device_key ditetapkan.
     *
     * Gunakan `hash_equals` untuk mencegah timing-attack.
     */
    private function isAuthorizedDevice(Request $request, Enclosure $enclosure): bool
    {
        if (blank($enclosure->device_key)) {
            return true; // Dev mode: tidak ada key yang ditetapkan
        }

        $incomingKey = (string) ($request->header('X-Device-Key') ?? $request->query('device_key', ''));

        return hash_equals((string) $enclosure->device_key, $incomingKey);
    }

    /**
     * Format payload control_config yang dikembalikan ke ESP32.
     *
     * ESP32 menggunakan nilai-nilai ini untuk menjalankan rule-based misting:
     *   - Jika humidity < bottom_humidity → aktifkan misting selama misting_duration_seconds
     *   - Jika humidity > top_humidity    → hentikan misting
     */
    private function formatControlConfig(Enclosure $enclosure): array
    {
        $params = $enclosure->parameters;

        return [
            'mode'                     => $params?->is_misting_auto ? 'auto' : 'manual',
            'target_habitat'           => $enclosure->target_habitat,
            'bottom_humidity'          => $params ? (float) $params->misting_bottom_threshold : null,
            'top_humidity'             => $params ? (float) $params->misting_top_threshold : null,
            'misting_duration_seconds' => $params ? (int) $params->misting_duration_seconds : null,
            'humidity_min'             => $params ? (float) $params->humidity_min : null,
            'humidity_max'             => $params ? (float) $params->humidity_max : null,
            'updated_at'               => $params?->updated_at?->toIso8601String(),
        ];
    }
}
