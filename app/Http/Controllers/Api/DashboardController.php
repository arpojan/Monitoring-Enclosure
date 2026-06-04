<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Enclosure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    /**
     * GET /api/enclosures/{id}/latest
     *
     * Telemetry terbaru untuk realtime dashboard cards.
     */
    public function latest(int $id): JsonResponse
    {
        $enclosure = Enclosure::with('parameters')->findOrFail($id);

        $latestLog = $enclosure->sensorLogs()
            ->latest('logged_at')
            ->first();

        // Ambil log 1 jam lalu untuk trend comparison
        $oneHourAgo = $enclosure->sensorLogs()
            ->where('logged_at', '<=', now()->subHour())
            ->latest('logged_at')
            ->first();

        $tempTrend = null;
        $humTrend = null;

        if ($latestLog && $oneHourAgo) {
            $tempTrend = round((float) $latestLog->temperature - (float) $oneHourAgo->temperature, 2);
            $humTrend  = round((float) $latestLog->humidity - (float) $oneHourAgo->humidity, 2);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'enclosure' => [
                    'id'           => $enclosure->id,
                    'name'         => $enclosure->name,
                    'species'      => $enclosure->species,
                    'is_active'    => $enclosure->is_active,
                    'system_status'=> $enclosure->isOnline() ? 'online' : 'offline',
                    'last_seen_at' => $enclosure->last_seen_at?->toIso8601String(),
                ],
                'telemetry' => $latestLog ? [
                    'temperature'    => $latestLog->temperature,
                    'humidity'       => $latestLog->humidity,
                    'misting_status' => $latestLog->misting_status,
                    'misting_duration_executed' => $latestLog->misting_duration_executed,
                    'logged_at'      => $latestLog->logged_at->toIso8601String(),
                ] : null,
                'trend' => [
                    'temperature' => $tempTrend,
                    'humidity'    => $humTrend,
                ],
                'parameters' => $enclosure->parameters ? [
                    'humidity_min'             => $enclosure->parameters->humidity_min,
                    'humidity_max'             => $enclosure->parameters->humidity_max,
                    'misting_bottom_threshold' => $enclosure->parameters->misting_bottom_threshold,
                    'misting_top_threshold'    => $enclosure->parameters->misting_top_threshold,
                    'misting_duration_seconds' => $enclosure->parameters->misting_duration_seconds,
                    'is_misting_auto'          => $enclosure->parameters->is_misting_auto,
                ] : null,
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

        $period = $request->query('period', '24h');

        // Tentukan time range dan sampling strategy
        $config = match ($period) {
            '7d'    => ['since' => now()->subDays(7),  'label' => '7 hari'],
            '30d'   => ['since' => now()->subDays(30), 'label' => '30 hari'],
            '90d'   => ['since' => now()->subDays(90), 'label' => '90 hari'],
            default => ['since' => now()->subHours(24),'label' => '24 jam'],
        };

        $logs = $enclosure->sensorLogs()
            ->where('logged_at', '>=', $config['since'])
            ->orderBy('logged_at', 'asc')
            ->get(['temperature', 'humidity', 'misting_status', 'misting_duration_executed', 'logged_at']);

        // Format untuk chart — array of objects dengan timestamp clean
        $chartData = $logs->map(fn ($log) => [
            'time'           => $log->logged_at->format('H:i'),
            'datetime'       => $log->logged_at->toIso8601String(),
            'temperature'    => (float) $log->temperature,
            'humidity'       => (float) $log->humidity,
            'misting_status' => (bool)  $log->misting_status,
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
     * Single endpoint gabungan untuk dashboard frontend.
     * Menggabungkan: latest telemetry, stability score, insight, recommendation, status.
     */
    public function dashboard(int $id): JsonResponse
    {
        $enclosure = Enclosure::with(['parameters'])->findOrFail($id);

        // Latest telemetry
        $latestLog = $enclosure->sensorLogs()
            ->latest('logged_at')
            ->first();

        // Latest stability score (from analytics engine)
        $latestStability = $enclosure->stabilityScores()
            ->latest('analyzed_date')
            ->first();

        // Fallback: compute realtime jika tabel kosong
        $stabilityData = null;
        if ($latestStability) {
            $stabilityData = [
                'final_score'   => (float) $latestStability->final_stability_score,
                'status'        => $latestStability->status,
                'analyzed_date' => $latestStability->analyzed_date->format('Y-m-d'),
            ];
        } else {
            // Compute dari sensor_logs (logika sama dengan stability())
            $computed = $this->computeRealtimeStability($enclosure);
            if ($computed) {
                $stabilityData = $computed;
            }
        }

        // Latest insight (warning/critical first)
        $latestInsight = $enclosure->insights()
            ->latest('generated_at')
            ->first();

        // Latest pending recommendation
        $pendingRecommendation = $enclosure->recommendations()
            ->where('decision_status', 'pending')
            ->latest('created_at')
            ->first();

        // Trend (last hour)
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

        // Recent chart data (last 1 hour for realtime chart)
        $recentLogs = $enclosure->sensorLogs()
            ->where('logged_at', '>=', now()->subHour())
            ->orderBy('logged_at', 'asc')
            ->get(['temperature', 'humidity', 'misting_status', 'misting_duration_executed', 'logged_at']);

        $chartData = $recentLogs->map(fn ($log) => [
            'time'        => $log->logged_at->format('H:i'),
            'temperature' => (float) $log->temperature,
            'humidity'    => (float) $log->humidity,
            'misting'     => (bool) $log->misting_status,
            'misting_duration_executed' => $log->misting_duration_executed,
        ]);

        $events = $enclosure->eventTimelines()
            ->latest('created_at')
            ->take(5)
            ->get()
            ->map(fn ($e) => [
                'type'        => $e->event_type,
                'description' => $e->description,
                'triggered_by'=> $e->triggered_by,
                'time'        => $e->created_at->diffForHumans(),
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
                    'temperature'    => $latestLog->temperature,
                    'humidity'       => $latestLog->humidity,
                    'misting_status' => $latestLog->misting_status,
                    'misting_duration_executed' => $latestLog->misting_duration_executed,
                    'logged_at'      => $latestLog->logged_at->toIso8601String(),
                ] : null,
                'trend' => [
                    'temperature' => $tempTrend,
                    'humidity'    => $humTrend,
                ],
                'stability' => $stabilityData,
                'insight' => $latestInsight ? [
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
                'parameters' => $enclosure->parameters ? [
                    'humidity_min'             => $enclosure->parameters->humidity_min,
                    'humidity_max'             => $enclosure->parameters->humidity_max,
                    'misting_bottom_threshold' => $enclosure->parameters->misting_bottom_threshold,
                    'misting_top_threshold'    => $enclosure->parameters->misting_top_threshold,
                    'misting_duration_seconds' => $enclosure->parameters->misting_duration_seconds,
                    'is_misting_auto'          => $enclosure->parameters->is_misting_auto,
                ] : null,
                'chart' => $chartData->values(),
                'events'=> $events->values(),
            ],
        ]);
    }

    /**
     * GET /api/enclosures/{id}/analytics?period=24h|7d|30d
     *
     * Data untuk halaman Analitik.
     * Menghitung: rata-rata, distribusi, siklus misting, waktu dalam range, event timeline.
     */
    public function analytics(Request $request, int $id): JsonResponse
    {
        $enclosure = Enclosure::with('parameters')->findOrFail($id);
        $period    = $request->query('period', '24h');

        $since = match ($period) {
            '7d'    => now()->subDays(7),
            '30d'   => now()->subDays(30),
            '90d'   => now()->subDays(90),
            default => now()->subHours(24),
        };

        $logs = $enclosure->sensorLogs()
            ->where('logged_at', '>=', $since)
            ->orderBy('logged_at', 'asc')
            ->get();

        $params = $enclosure->parameters;
        $totalLogs = $logs->count();

        // ── Ringkasan Statistik ──
        $avgHumidity    = $totalLogs > 0 ? round($logs->avg('humidity'), 1) : 0;
        $avgTemperature = $totalLogs > 0 ? round($logs->avg('temperature'), 1) : 0;

        // Siklus misting: hitung transisi OFF → ON
        $mistingCycles = 0;
        $prevMisting   = false;
        foreach ($logs as $log) {
            if ($log->misting_status && !$prevMisting) {
                $mistingCycles++;
            }
            $prevMisting = (bool) $log->misting_status;
        }

        // Waktu di Range (Range Compliance sederhana)
        $inRangeCount = 0;
        if ($params && $totalLogs > 0) {
            $humMin = (float) $params->humidity_min;
            $humMax = (float) $params->humidity_max;
            $inRangeCount = $logs->filter(function ($log) use ($humMin, $humMax) {
                $h = (float) $log->humidity;
                return $h >= $humMin && $h <= $humMax;
            })->count();
        }
        $timeInRange = $totalLogs > 0 ? round(($inRangeCount / $totalLogs) * 100, 1) : 0;

        // ── Historical Chart Data ──
        $chartData = $logs->map(fn ($log) => [
            'time'        => $log->logged_at->format($period === '24h' ? 'H:i' : 'd/m H:i'),
            'datetime'    => $log->logged_at->toIso8601String(),
            'temperature' => (float) $log->temperature,
            'humidity'    => (float) $log->humidity,
            'misting'     => (bool) $log->misting_status,
            'misting_duration_executed' => $log->misting_duration_executed,
        ]);

        // ── Distribusi Kelembapan (7 bins) ──
        $bins = ['<70' => 0, '70-75' => 0, '75-80' => 0, '80-85' => 0, '85-90' => 0, '90-95' => 0, '>95' => 0];
        foreach ($logs as $log) {
            $h = (float) $log->humidity;
            if      ($h < 70)  $bins['<70']++;
            elseif  ($h < 75)  $bins['70-75']++;
            elseif  ($h < 80)  $bins['75-80']++;
            elseif  ($h < 85)  $bins['80-85']++;
            elseif  ($h < 90)  $bins['85-90']++;
            elseif  ($h < 95)  $bins['90-95']++;
            else               $bins['>95']++;
        }

        // ── Misting Activity per Day ──
        $mistingPerDay = [];
        $grouped = $logs->groupBy(fn ($log) => $log->logged_at->format('Y-m-d'));
        foreach ($grouped as $date => $dayLogs) {
            $cycles = 0;
            $prev   = false;
            $onCount = 0;
            foreach ($dayLogs as $log) {
                if ($log->misting_status && !$prev) $cycles++;
                if ($log->misting_status) $onCount++;
                $prev = (bool) $log->misting_status;
            }
            $mistingPerDay[] = [
                'date'      => Carbon::parse($date)->format('d/m'),
                'cycles'    => $cycles,
                'on_count'  => $onCount,
            ];
        }

        // ── Stability Trend ── (dari tabel stability_scores)
        $stabilityTrend = $enclosure->stabilityScores()
            ->where('analyzed_date', '>=', $since->format('Y-m-d'))
            ->orderBy('analyzed_date', 'asc')
            ->get()
            ->map(fn ($s) => [
                'date'  => $s->analyzed_date->format('d/m'),
                'score' => (float) $s->final_stability_score,
            ]);

        // ── Event Timeline ── (dari tabel event_timelines)
        $events = $enclosure->eventTimelines()
            ->where('created_at', '>=', $since)
            ->latest('created_at')
            ->take(10)
            ->get()
            ->map(fn ($e) => [
                'type'        => $e->event_type,
                'description' => $e->description,
                'triggered_by'=> $e->triggered_by,
                'time'        => $e->created_at->diffForHumans(),
                'datetime'    => $e->created_at->toIso8601String(),
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
                'chart'              => $chartData->values(),
                'humidity_distribution' => $bins,
                'misting_activity'     => $mistingPerDay,
                'stability_trend'      => $stabilityTrend->values(),
                'events'               => $events->values(),
            ],
        ]);
    }

    /**
     * GET /api/enclosures/{id}/stability
     *
     * Data untuk halaman Stabilitas.
     * Menampilkan: skor terbaru, komponen, riwayat, dan computed stats.
     */
    public function stability(Request $request, int $id): JsonResponse
    {
        $enclosure = Enclosure::with('parameters')->findOrFail($id);
        $params    = $enclosure->parameters;

        // Latest stability score (dari analytics engine)
        $latest = $enclosure->stabilityScores()
            ->latest('analyzed_date')
            ->first();

        // Jika belum ada stability score, hitung dari sensor_logs langsung
        $logs = $enclosure->sensorLogs()
            ->where('logged_at', '>=', now()->subHours(24))
            ->orderBy('logged_at', 'asc')
            ->get();

        $totalLogs = $logs->count();

        // ── Compute Range Compliance ──
        $rcScore = 0;
        if ($params && $totalLogs > 0) {
            $humMin = (float) $params->humidity_min;
            $humMax = (float) $params->humidity_max;
            $inRange = $logs->filter(fn ($l) => (float) $l->humidity >= $humMin && (float) $l->humidity <= $humMax)->count();
            $rcScore = round(($inRange / $totalLogs) * 100, 1);
        }

        // ── Compute Variability (std deviation based) ──
        $variabilityScore = 0;
        $variabilityLabel = 'N/A';
        if ($totalLogs > 1) {
            $humValues = $logs->pluck('humidity')->map(fn ($v) => (float) $v);
            $mean = $humValues->avg();
            $variance = $humValues->map(fn ($v) => pow($v - $mean, 2))->avg();
            $stdDev = sqrt($variance);

            // Skor: semakin rendah stdDev, semakin baik (max 100)
            // Skala: stdDev 0 = 100, stdDev >= 10 = 0
            $variabilityScore = round(max(0, 100 - ($stdDev * 10)), 1);
            $variabilityLabel = $stdDev < 2 ? 'Rendah' : ($stdDev < 5 ? 'Sedang' : 'Tinggi');
        }

        // ── Compute Stability Duration ──
        // Berapa lama berturut-turut humidity dalam range (dari akhir)
        $stableHours = 0;
        if ($params && $totalLogs > 0) {
            $humMin = (float) $params->humidity_min;
            $humMax = (float) $params->humidity_max;
            $reversed = $logs->reverse();
            foreach ($reversed as $log) {
                $h = (float) $log->humidity;
                if ($h >= $humMin && $h <= $humMax) {
                    $stableHours++;
                } else {
                    break;
                }
            }
            // Convert reading count to approximate hours (assuming ~6 readings/hour at 10s interval)
            // More accurately: count the time span
            if ($stableHours > 0 && $totalLogs > 1) {
                $firstLog = $logs->first();
                $lastLog  = $logs->last();
                $totalMinutes = $firstLog->logged_at->diffInMinutes($lastLog->logged_at);
                $minutesPerReading = $totalLogs > 1 ? $totalMinutes / ($totalLogs - 1) : 10;
                $stableHours = round(($stableHours * $minutesPerReading) / 60, 1);
            }
        }

        // ── Compute Fluctuation Penalty ──
        // Hitung rapid changes (delta > 5% antar reading berturutan)
        $fluctuationPenalty = 0;
        $prevHum = null;
        foreach ($logs as $log) {
            if ($prevHum !== null) {
                $delta = abs((float) $log->humidity - $prevHum);
                if ($delta > 5) {
                    $fluctuationPenalty++;
                }
            }
            $prevHum = (float) $log->humidity;
        }
        // Convert to penalty points (max -20)
        $penaltyPoints = min(20, $fluctuationPenalty * 2);

        // ── Final Computed Score (jika belum ada dari analytics engine) ──
        $computedScore = null;
        $computedStatus = null;
        if (!$latest && $totalLogs > 0) {
            // Weighted: RC 40%, Variability 30%, Stability Duration 20%, Penalty 10%
            $durationRatio = $params && $stableHours > 0 ? min(100, ($stableHours / 24) * 100) : 0;
            $computedScore = round(
                ($rcScore * 0.4) +
                ($variabilityScore * 0.3) +
                ($durationRatio * 0.2) -
                $penaltyPoints
            , 1);
            $computedScore = max(0, min(100, $computedScore));

            if ($computedScore >= 85) $computedStatus = 'Optimal';
            elseif ($computedScore >= 70) $computedStatus = 'Stabil';
            elseif ($computedScore >= 50) $computedStatus = 'Perhatian';
            else $computedStatus = 'Kritis';
        }

        $period = $request->query('period', '4w'); // '4w' or '12w'
        $days = $period === '12w' ? 84 : 30; // 12 weeks = 84 days, 4 weeks = 30 days

        // ── Stability History ──
        $history = $enclosure->stabilityScores()
            ->where('analyzed_date', '>=', now()->subDays($days))
            ->orderBy('analyzed_date', 'asc')
            ->get()
            ->map(fn ($s) => [
                'date'  => $s->analyzed_date->format('d/m'),
                'score' => (float) $s->final_stability_score,
                'status'=> $s->status,
            ]);

        return response()->json([
            'success' => true,
            'data'    => [
                'score' => $latest ? [
                    'final_score'   => (float) $latest->final_stability_score,
                    'status'        => $latest->status,
                    'analyzed_date' => $latest->analyzed_date->format('Y-m-d'),
                    'source'        => 'analytics_engine',
                ] : ($computedScore !== null ? [
                    'final_score'   => $computedScore,
                    'status'        => $computedStatus,
                    'analyzed_date' => now()->format('Y-m-d'),
                    'source'        => 'realtime_computed',
                ] : null),
                'components' => [
                    'range_compliance' => [
                        'score' => $latest ? (float) $latest->range_compliance_score : $rcScore,
                        'label' => $rcScore . '% dalam range',
                    ],
                    'variability' => [
                        'score' => $latest ? (float) $latest->variability_score : $variabilityScore,
                        'label' => $variabilityLabel,
                    ],
                    'stability_duration' => [
                        'score' => $latest ? (float) $latest->stability_duration_ratio : min(100, ($stableHours / 24) * 100),
                        'hours' => $stableHours,
                    ],
                    'fluctuation_penalty' => [
                        'score'  => $latest ? (float) $latest->fluctuation_penalty : $penaltyPoints,
                        'events' => $fluctuationPenalty,
                    ],
                ],
                'history' => $history->values(),
            ],
        ]);
    }

    /**
     * Compute stability score realtime dari sensor_logs.
     * Digunakan sebagai fallback ketika tabel stability_scores belum terisi.
     * Shared antara dashboard() dan stability().
     */
    private function computeRealtimeStability(Enclosure $enclosure): ?array
    {
        $params = $enclosure->parameters;
        $logs = $enclosure->sensorLogs()
            ->where('logged_at', '>=', now()->subHours(24))
            ->orderBy('logged_at', 'asc')
            ->get();

        $totalLogs = $logs->count();
        if ($totalLogs === 0) return null;

        // Range Compliance
        $rcScore = 0;
        if ($params) {
            $humMin = (float) $params->humidity_min;
            $humMax = (float) $params->humidity_max;
            $inRange = $logs->filter(fn ($l) => (float) $l->humidity >= $humMin && (float) $l->humidity <= $humMax)->count();
            $rcScore = round(($inRange / $totalLogs) * 100, 1);
        }

        // Variability
        $variabilityScore = 0;
        if ($totalLogs > 1) {
            $humValues = $logs->pluck('humidity')->map(fn ($v) => (float) $v);
            $mean = $humValues->avg();
            $variance = $humValues->map(fn ($v) => pow($v - $mean, 2))->avg();
            $stdDev = sqrt($variance);
            $variabilityScore = round(max(0, 100 - ($stdDev * 10)), 1);
        }

        // Stability Duration
        $stableHours = 0;
        if ($params) {
            $humMin = (float) $params->humidity_min;
            $humMax = (float) $params->humidity_max;
            $reversed = $logs->reverse();
            foreach ($reversed as $log) {
                if ((float) $log->humidity >= $humMin && (float) $log->humidity <= $humMax) {
                    $stableHours++;
                } else {
                    break;
                }
            }
            if ($stableHours > 0 && $totalLogs > 1) {
                $totalMinutes = $logs->first()->logged_at->diffInMinutes($logs->last()->logged_at);
                $minutesPerReading = $totalMinutes / ($totalLogs - 1);
                $stableHours = round(($stableHours * $minutesPerReading) / 60, 1);
            }
        }

        // Fluctuation Penalty
        $fluctuationPenalty = 0;
        $prevHum = null;
        foreach ($logs as $log) {
            if ($prevHum !== null && abs((float) $log->humidity - $prevHum) > 5) {
                $fluctuationPenalty++;
            }
            $prevHum = (float) $log->humidity;
        }
        $penaltyPoints = min(20, $fluctuationPenalty * 2);

        // Final Score
        $durationRatio = $stableHours > 0 ? min(100, ($stableHours / 24) * 100) : 0;
        $computedScore = round(
            ($rcScore * 0.4) + ($variabilityScore * 0.3) + ($durationRatio * 0.2) - $penaltyPoints
        , 1);
        $computedScore = max(0, min(100, $computedScore));

        $status = match (true) {
            $computedScore >= 85 => 'Optimal',
            $computedScore >= 70 => 'Stabil',
            $computedScore >= 50 => 'Perhatian',
            default              => 'Kritis',
        };

        return [
            'final_score'   => $computedScore,
            'status'        => $status,
            'analyzed_date' => now()->format('Y-m-d'),
        ];
    }
}
