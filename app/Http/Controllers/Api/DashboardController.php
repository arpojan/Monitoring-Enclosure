<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Enclosure;
use App\Services\StabilityComputeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function __construct(private readonly StabilityComputeService $stabilityService)
    {
    }

    /**
     * GET /api/enclosures/{id}/latest
     *
     * Telemetry terbaru untuk realtime dashboard cards.
     */
    public function latest(int $id): JsonResponse
    {
        $enclosure = Enclosure::with('parameters')->findOrFail($id);

        $latestLog  = $enclosure->sensorLogs()->latest('logged_at')->first();
        $oneHourAgo = $enclosure->sensorLogs()
            ->where('logged_at', '<=', now()->subHour())
            ->latest('logged_at')
            ->first();

        $tempTrend = null;
        $humTrend  = null;
        if ($latestLog && $oneHourAgo) {
            $tempTrend = round((float) $latestLog->temperature - (float) $oneHourAgo->temperature, 2);
            $humTrend  = round((float) $latestLog->humidity - (float) $oneHourAgo->humidity, 2);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'enclosure' => [
                    'id'            => $enclosure->id,
                    'name'          => $enclosure->name,
                    'species'       => $enclosure->species,
                    'is_active'     => $enclosure->is_active,
                    'system_status' => $enclosure->isOnline() ? 'online' : 'offline',
                    'last_seen_at'  => $enclosure->last_seen_at?->toIso8601String(),
                ],
                'telemetry' => $latestLog ? [
                    'temperature'               => $latestLog->temperature,
                    'humidity'                  => $latestLog->humidity,
                    'misting_status'            => $latestLog->misting_status,
                    'misting_duration_executed' => $latestLog->misting_duration_executed,
                    'logged_at'                 => $latestLog->logged_at->toIso8601String(),
                ] : null,
                'trend'      => ['temperature' => $tempTrend, 'humidity' => $humTrend],
                'parameters' => $this->formatParameters($enclosure),
            ],
        ]);
    }

    /**
     * GET /api/enclosures/{id}/history?period=24h|7d|30d
     *
     * Historical telemetry untuk chart frontend.
     */
    public function history(Request $request, int $id): JsonResponse
    {
        $enclosure = Enclosure::findOrFail($id);
        $period    = $request->query('period', '24h');

        $config = match ($period) {
            '7d'    => ['since' => now()->subDays(7),  'label' => '7 hari'],
            '30d'   => ['since' => now()->subDays(30), 'label' => '30 hari'],
            '90d'   => ['since' => now()->subDays(90), 'label' => '90 hari'],
            default => ['since' => now()->subHours(24),'label' => '24 jam'],
        };

        $logs = $enclosure->sensorLogs()
            ->where('logged_at', '>=', $config['since'])
            ->orderBy('logged_at', 'asc')
            ->get(['temperature', 'humidity', 'misting_status', 'misting_duration_executed', 'logged_at', 'device_timestamp']);

        $chartData = $logs->map(fn($log) => [
            'time'                      => ($log->device_timestamp ?? $log->logged_at)->format('H:i'),
            'datetime'                  => ($log->device_timestamp ?? $log->logged_at)->toIso8601String(),
            'temperature'               => (float) $log->temperature,
            'humidity'                  => (float) $log->humidity,
            'misting_status'            => (bool)  $log->misting_status,
            'misting_duration_executed' => $log->misting_duration_executed,
        ]);

        return response()->json([
            'success' => true,
            'period'  => $period,
            'label'   => $config['label'],
            'count'   => $chartData->count(),
            'data'    => $chartData->values(),
        ]);
    }

    /**
     * GET /api/enclosures/{id}/dashboard
     *
     * Single endpoint gabungan: latest telemetry, stability, insight, recommendation, chart, events.
     */
    public function dashboard(int $id): JsonResponse
    {
        $enclosure = Enclosure::with(['parameters'])->findOrFail($id);

        $latestLog      = $enclosure->sensorLogs()->latest('logged_at')->first();
        $latestStability= $enclosure->stabilityScores()->latest('analyzed_date')->first();

        // Gunakan StabilityComputeService sebagai fallback jika tabel stability_scores kosong
        $stabilityData = null;
        if ($latestStability) {
            $stabilityData = [
                'final_score'   => (float) $latestStability->final_stability_score,
                'status'        => $latestStability->status,
                'analyzed_date' => $latestStability->analyzed_date->format('Y-m-d'),
            ];
        } else {
            $computed = $this->stabilityService->computeFromLogs($enclosure);
            if ($computed) {
                $stabilityData = [
                    'final_score'   => $computed['final_score'],
                    'status'        => $computed['status'],
                    'analyzed_date' => $computed['analyzed_date'],
                ];
            }
        }

        $latestInsight        = $enclosure->insights()->latest('generated_at')->first();
        $pendingRecommendation= $enclosure->recommendations()->where('decision_status', 'pending')->latest()->first();

        // Trend (perbandingan 1 jam lalu)
        $oneHourAgo = $enclosure->sensorLogs()
            ->where('logged_at', '<=', now()->subHour())
            ->latest('logged_at')
            ->first();

        $tempTrend = null;
        $humTrend  = null;
        if ($latestLog && $oneHourAgo) {
            $tempTrend = round((float) $latestLog->temperature - (float) $oneHourAgo->temperature, 2);
            $humTrend  = round((float) $latestLog->humidity - (float) $oneHourAgo->humidity, 2);
        }

        // Chart 1 jam terakhir
        $recentLogs = $enclosure->sensorLogs()
            ->where('logged_at', '>=', now()->subHour())
            ->orderBy('logged_at', 'asc')
            ->get(['temperature', 'humidity', 'misting_status', 'misting_duration_executed', 'logged_at', 'device_timestamp']);

        $chartData = $recentLogs->map(fn($log) => [
            'time'                      => ($log->device_timestamp ?? $log->logged_at)->format('H:i'),
            'temperature'               => (float) $log->temperature,
            'humidity'                  => (float) $log->humidity,
            'misting'                   => (bool)  $log->misting_status,
            'misting_duration_executed' => $log->misting_duration_executed,
        ]);

        $events = $enclosure->eventTimelines()
            ->where('created_at', '>=', now()->subHours(24))
            ->latest('created_at')
            ->take(5)
            ->get()
            ->map(fn($e) => [
                'type'         => $e->event_type,
                'description'  => $e->description,
                'triggered_by' => $e->triggered_by,
                'time'         => $e->created_at->diffForHumans(),
            ]);

        return response()->json([
            'success' => true,
            'data'    => [
                'enclosure' => [
                    'id'            => $enclosure->id,
                    'name'          => $enclosure->name,
                    'species'       => $enclosure->species,
                    'system_status' => $enclosure->isOnline() ? 'online' : 'offline',
                    'last_seen_at'  => $enclosure->last_seen_at?->toIso8601String(),
                ],
                'telemetry' => $latestLog ? [
                    'temperature'               => $latestLog->temperature,
                    'humidity'                  => $latestLog->humidity,
                    'misting_status'            => $latestLog->misting_status,
                    'misting_duration_executed' => $latestLog->misting_duration_executed,
                    'logged_at'                 => $latestLog->logged_at->toIso8601String(),
                ] : null,
                'trend'          => ['temperature' => $tempTrend, 'humidity' => $humTrend],
                'stability'      => $stabilityData,
                'insight'        => $latestInsight ? [
                    'type'        => $latestInsight->type,
                    'description' => $latestInsight->description,
                    'severity'    => $latestInsight->severity,
                ] : null,
                'recommendation' => $pendingRecommendation ? [
                    'id'                    => $pendingRecommendation->id,
                    'title'                 => $pendingRecommendation->title,
                    'description'           => $pendingRecommendation->description,
                    'action_type'           => $pendingRecommendation->action_type,
                    'current_bottom_rh'     => $pendingRecommendation->current_bottom_rh,
                    'current_top_rh'        => $pendingRecommendation->current_top_rh,
                    'current_duration'      => $pendingRecommendation->current_duration,
                    'recommended_bottom_rh' => $pendingRecommendation->recommended_bottom_rh,
                    'recommended_top_rh'    => $pendingRecommendation->recommended_top_rh,
                    'recommended_duration'  => $pendingRecommendation->recommended_duration,
                ] : null,
                'parameters'     => $this->formatParameters($enclosure),
                'chart'          => $chartData->values(),
                'events'         => $events->values(),
            ],
        ]);
    }

    /**
     * GET /api/enclosures/{id}/analytics?period=24h|7d|30d
     *
     * Data untuk halaman Analitik: statistik, distribusi, misting activity, stability trend, events.
     */
    public function analytics(Request $request, int $id): JsonResponse
    {
        $enclosure = Enclosure::with('parameters')->findOrFail($id);
        $period    = $request->query('period', '24h');
        $params    = $enclosure->parameters;

        $since = match ($period) {
            '7d'    => now()->subDays(7),
            '30d'   => now()->subDays(30),
            '90d'   => now()->subDays(90),
            default => now()->subHours(24),
        };

        $logs      = $enclosure->sensorLogs()->where('logged_at', '>=', $since)->orderBy('logged_at', 'asc')->get();
        $totalLogs = $logs->count();

        // ── Ringkasan Statistik ──
        $avgHumidity    = $totalLogs > 0 ? round($logs->avg('humidity'), 1) : 0;
        $avgTemperature = $totalLogs > 0 ? round($logs->avg('temperature'), 1) : 0;

        $mistingCycles = 0;
        $prevMisting   = false;
        foreach ($logs as $log) {
            if ($log->misting_status && !$prevMisting) {
                $mistingCycles++;
            }
            $prevMisting = (bool) $log->misting_status;
        }

        $inRangeCount = 0;
        if ($params && $totalLogs > 0) {
            $humMin       = (float) $params->humidity_min;
            $humMax       = (float) $params->humidity_max;
            $inRangeCount = $logs->filter(fn($l) => (float) $l->humidity >= $humMin && (float) $l->humidity <= $humMax)->count();
        }
        $timeInRange = $totalLogs > 0 ? round(($inRangeCount / $totalLogs) * 100, 1) : 0;

        // ── Chart Data ──
        $chartData = $logs->map(fn($log) => [
            'time'                      => ($log->device_timestamp ?? $log->logged_at)->format($period === '24h' ? 'H:i' : 'd/m H:i'),
            'datetime'                  => ($log->device_timestamp ?? $log->logged_at)->toIso8601String(),
            'temperature'               => (float) $log->temperature,
            'humidity'                  => (float) $log->humidity,
            'misting'                   => (bool)  $log->misting_status,
            'misting_duration_executed' => $log->misting_duration_executed,
        ]);

        // ── Distribusi Kelembapan (7 bins) ──
        $bins = ['<70' => 0, '70-75' => 0, '75-80' => 0, '80-85' => 0, '85-90' => 0, '90-95' => 0, '>95' => 0];
        foreach ($logs as $log) {
            $h = (float) $log->humidity;
            if      ($h < 70) $bins['<70']++;
            elseif  ($h < 75) $bins['70-75']++;
            elseif  ($h < 80) $bins['75-80']++;
            elseif  ($h < 85) $bins['80-85']++;
            elseif  ($h < 90) $bins['85-90']++;
            elseif  ($h < 95) $bins['90-95']++;
            else               $bins['>95']++;
        }

        // ── Misting Activity per Day ──
        $mistingPerDay = [];
        foreach ($logs->groupBy(fn($l) => $l->logged_at->format('Y-m-d')) as $date => $dayLogs) {
            $cycles  = 0;
            $prev    = false;
            $onCount = 0;
            foreach ($dayLogs as $log) {
                if ($log->misting_status && !$prev) $cycles++;
                if ($log->misting_status) $onCount++;
                $prev = (bool) $log->misting_status;
            }
            $mistingPerDay[] = [
                'date'     => Carbon::parse($date)->format('d/m'),
                'cycles'   => $cycles,
                'on_count' => $onCount,
            ];
        }

        // ── Stability Trend ──
        $stabilityTrend = $enclosure->stabilityScores()
            ->where('analyzed_date', '>=', $since->format('Y-m-d'))
            ->orderBy('analyzed_date', 'asc')
            ->get()
            ->map(fn($s) => ['date' => $s->analyzed_date->format('d/m'), 'score' => (float) $s->final_stability_score]);

        // ── Event Timeline ──
        $events = $enclosure->eventTimelines()
            ->where('created_at', '>=', $since)
            ->latest('created_at')
            ->take(10)
            ->get()
            ->map(fn($e) => [
                'type'         => $e->event_type,
                'description'  => $e->description,
                'triggered_by' => $e->triggered_by,
                'time'         => $e->created_at->diffForHumans(),
                'datetime'     => $e->created_at->toIso8601String(),
            ]);

        return response()->json([
            'success' => true,
            'period'  => $period,
            'data'    => [
                'summary' => [
                    'avg_humidity'    => $avgHumidity,
                    'avg_temperature' => $avgTemperature,
                    'misting_cycles'  => $mistingCycles,
                    'time_in_range'   => $timeInRange,
                    'total_readings'  => $totalLogs,
                ],
                'chart'                 => $chartData->values(),
                'humidity_distribution' => $bins,
                'misting_activity'      => $mistingPerDay,
                'stability_trend'       => $stabilityTrend->values(),
                'events'                => $events->values(),
            ],
        ]);
    }

    /**
     * GET /api/enclosures/{id}/stability?period=4w|12w
     *
     * Data untuk halaman Stabilitas: skor, komponen, riwayat.
     * Gunakan StabilityComputeService sebagai fallback jika tabel stability_scores kosong.
     */
    public function stability(Request $request, int $id): JsonResponse
    {
        $enclosure = Enclosure::with('parameters')->findOrFail($id);

        $latest = $enclosure->stabilityScores()->latest('analyzed_date')->first();

        // Hitung realtime dari logs sebagai fallback atau untuk komponen detail
        $computed = $this->stabilityService->computeFromLogs($enclosure);
        $c        = $computed['components'] ?? [];

        $period = $request->query('period', '4w');
        $days   = $period === '12w' ? 84 : 30;

        $history = $enclosure->stabilityScores()
            ->where('analyzed_date', '>=', now()->subDays($days))
            ->orderBy('analyzed_date', 'asc')
            ->get()
            ->map(fn($s) => [
                'date'   => $s->analyzed_date->format('d/m'),
                'score'  => (float) $s->final_stability_score,
                'status' => $s->status,
            ]);

        return response()->json([
            'success' => true,
            'data'    => [
                'score' => $latest ? [
                    'final_score'   => (float) $latest->final_stability_score,
                    'status'        => $latest->status,
                    'analyzed_date' => $latest->analyzed_date->format('Y-m-d'),
                    'source'        => 'analytics_engine',
                ] : ($computed ? [
                    'final_score'   => $computed['final_score'],
                    'status'        => $computed['status'],
                    'analyzed_date' => $computed['analyzed_date'],
                    'source'        => 'realtime_computed',
                ] : null),
                'components' => [
                    'range_compliance' => [
                        'score' => $latest ? (float) $latest->range_compliance_score : ($c['rcScore'] ?? 0),
                        'label' => ($c['rcScore'] ?? 0) . '% dalam range',
                    ],
                    'variability' => [
                        'score' => $latest ? (float) $latest->variability_score : ($c['variabilityScore'] ?? 0),
                        'label' => $c['variabilityLabel'] ?? 'N/A',
                    ],
                    'stability_duration' => [
                        'score' => $latest
                            ? (float) $latest->stability_duration_ratio
                            : min(100, (($c['stableHours'] ?? 0) / 24) * 100),
                        'hours' => $c['stableHours'] ?? 0,
                    ],
                    'fluctuation_penalty' => [
                        'score'  => $latest ? (float) $latest->fluctuation_penalty : ($c['penaltyPoints'] ?? 0),
                        'events' => $c['fluctuationCount'] ?? 0,
                    ],
                ],
                'history' => $history->values(),
            ],
        ]);
    }

    /**
     * Format parameter enclosure untuk response.
     */
    private function formatParameters(Enclosure $enclosure): ?array
    {
        $params = $enclosure->parameters;
        if (!$params) return null;

        return [
            'humidity_min'             => $params->humidity_min,
            'humidity_max'             => $params->humidity_max,
            'misting_bottom_threshold' => $params->misting_bottom_threshold,
            'misting_top_threshold'    => $params->misting_top_threshold,
            'misting_duration_seconds' => $params->misting_duration_seconds,
            'is_misting_auto'          => $params->is_misting_auto,
        ];
    }
}
