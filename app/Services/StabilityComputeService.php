<?php

namespace App\Services;

use App\Models\Enclosure;
use App\Models\EnclosureParameter;
use Illuminate\Support\Collection;

/**
 * StabilityComputeService
 *
 * Memusatkan kalkulasi skor stabilitas yang sebelumnya tersebar (duplikat) di:
 *   - DashboardController::computeRealtimeStability()
 *   - DashboardController::stability() (inline)
 *   - DashboardController::dashboard() (inline via private method)
 *
 * Dipakai sebagai fallback ketika tabel stability_scores belum terisi oleh DssService.
 */
class StabilityComputeService
{
    /**
     * Compute stability score langsung dari sensor_logs.
     * Mengembalikan null jika tidak ada log.
     */
    public function computeFromLogs(Enclosure $enclosure, int $hours = 24): ?array
    {
        $params = $enclosure->parameters;

        $logs = $enclosure->sensorLogs()
            ->where('logged_at', '>=', now()->subHours($hours))
            ->orderBy('logged_at', 'asc')
            ->get();

        $totalLogs = $logs->count();
        if ($totalLogs === 0) {
            return null;
        }

        $components = $this->computeComponents($logs, $params, $totalLogs);
        $score      = $this->computeFinalScore($components);
        $status     = $this->resolveStatus($score);

        return [
            'final_score'   => $score,
            'status'        => $status,
            'analyzed_date' => now()->format('Y-m-d'),
            'source'        => 'realtime_computed',
            'components'    => $components,
        ];
    }

    /**
     * Hitung semua komponen skor dari koleksi sensor logs.
     */
    public function computeComponents(Collection $logs, ?EnclosureParameter $params, int $totalLogs): array
    {
        $humidityValues = $logs->pluck('humidity')->map(fn($v) => (float) $v);

        // ── Range Compliance (RC) — bobot 40% ──
        $rcScore = 0.0;
        if ($params) {
            $humMin  = (float) $params->humidity_min;
            $humMax  = (float) $params->humidity_max;
            $inRange = $logs->filter(fn($l) => (float) $l->humidity >= $humMin && (float) $l->humidity <= $humMax)->count();
            $rcScore = round(($inRange / $totalLogs) * 100, 1);
        }

        // ── Variability — bobot 30% ──
        $variabilityScore = 0.0;
        $stdDev           = 0.0;
        $variabilityLabel = 'N/A';
        if ($totalLogs > 1) {
            $mean             = $humidityValues->avg();
            $variance         = $humidityValues->map(fn($v) => pow($v - $mean, 2))->avg();
            $stdDev           = sqrt($variance);
            $variabilityScore = round(max(0.0, 100.0 - ($stdDev * 10)), 1);
            $variabilityLabel = match (true) {
                $stdDev < 2  => 'Rendah',
                $stdDev < 5  => 'Sedang',
                default      => 'Tinggi',
            };
        }

        // ── Stability Duration — bobot 20% ──
        $stableCount = 0;
        $stableHours = 0.0;
        if ($params && $totalLogs > 0) {
            $humMin = (float) $params->humidity_min;
            $humMax = (float) $params->humidity_max;
            foreach ($logs->reverse() as $log) {
                if ((float) $log->humidity >= $humMin && (float) $log->humidity <= $humMax) {
                    $stableCount++;
                } else {
                    break;
                }
            }
            if ($stableCount > 0 && $totalLogs > 1) {
                $totalMinutes      = $logs->first()->logged_at->diffInMinutes($logs->last()->logged_at);
                $minutesPerReading = $totalMinutes / ($totalLogs - 1);
                $stableHours       = round(($stableCount * $minutesPerReading) / 60, 1);
            }
        }

        // ── Fluctuation Penalty ──
        $fluctuationCount = 0;
        $prevHum          = null;
        foreach ($logs as $log) {
            if ($prevHum !== null && abs((float) $log->humidity - $prevHum) > 5) {
                $fluctuationCount++;
            }
            $prevHum = (float) $log->humidity;
        }
        $penaltyPoints = min(20.0, round($fluctuationCount * 2, 1));

        return compact(
            'rcScore', 'variabilityScore', 'stdDev', 'variabilityLabel',
            'stableHours', 'fluctuationCount', 'penaltyPoints'
        );
    }

    /**
     * Hitung skor final berbobot.
     * Formula: (RC×40%) + (Variability×30%) + (Duration×20%) − Penalty
     */
    public function computeFinalScore(array $c): float
    {
        $durationRatio = min(100.0, round(($c['stableHours'] / 24) * 100, 1));

        $raw = ($c['rcScore'] * 0.40)
             + ($c['variabilityScore'] * 0.30)
             + ($durationRatio * 0.20)
             - $c['penaltyPoints'];

        return (float) round(max(0.0, min(100.0, $raw)), 1);
    }

    /**
     * Konversi skor ke label status.
     */
    public function resolveStatus(float $score): string
    {
        return match (true) {
            $score >= 85 => 'Optimal',
            $score >= 70 => 'Stabil',
            $score >= 50 => 'Perhatian',
            default      => 'Kritis',
        };
    }
}
