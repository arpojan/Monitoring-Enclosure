<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Enclosure;
use App\Models\SensorLog;
use App\Models\StabilityScore;
use App\Models\Insight;
use App\Models\Recommendation;
use App\Models\EventTimeline;
use Carbon\Carbon;

class DummyTelemetrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $enclosures = Enclosure::with('parameters')->get();
        
        if ($enclosures->isEmpty()) {
            $this->command->info('No enclosures found. Generating default enclosures via DatabaseSeeder...');
            $this->call(DatabaseSeeder::class);
            $enclosures = Enclosure::with('parameters')->get();
        }

        $this->command->info('Cleaning up existing telemetry data...');
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        SensorLog::truncate();
        StabilityScore::truncate();
        Insight::truncate();
        Recommendation::truncate();
        EventTimeline::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $days = 30;
        $endDate = Carbon::now();
        $startDate = $endDate->copy()->subDays($days);

        foreach ($enclosures as $enclosure) {
            $this->command->info("Generating {$days} days of realistic telemetry for {$enclosure->name}...");
            $this->generateForEnclosure($enclosure, $startDate, $endDate);
        }
        
        $this->command->info('Telemetry generation completed successfully!');
    }

    private function generateForEnclosure($enclosure, Carbon $startDate, Carbon $endDate)
    {
        $currentDate = $startDate->copy();
        
        $temp = 25.0; // Base Temp
        $hum = 85.0;  // Base Humidity
        $mistingState = false;

        $dayIndex = 1;
        $totalDays = $startDate->diffInDays($endDate) + 1;

        while ($currentDate <= $endDate) {
            $scenario = $this->determineScenario($enclosure, $dayIndex, $totalDays);
            
            $sensorLogs = [];
            $events = [];
            
            // 24 hours * 6 (10 min intervals) = 144 logs per day
            $logsPerDay = 144;
            
            // Daily aggregations
            $dayTotalHum = 0;
            $dayTotalTemp = 0;
            $mistingCount = 0;
            $fluctuations = 0;
            $inRangeCount = 0;
            
            $params = $enclosure->parameters;
            
            for ($i = 0; $i < $logsPerDay; $i++) {
                $isOffline = false;
                
                // Scenario specific logic
                switch ($scenario) {
                    case 'Ideal Stable':
                        $targetHum = 86;
                        $targetTemp = 25.0;
                        $volatility = 0.5;
                        break;
                    case 'Dry Enclosure':
                        $targetHum = 72;
                        $targetTemp = 26.5;
                        $volatility = 1.0;
                        break;
                    case 'High Fluctuation':
                        $targetHum = 85;
                        $targetTemp = 25.5;
                        $volatility = 5.0; // High random jumps
                        break;
                    case 'Over-Misting':
                        $targetHum = 98;
                        $targetTemp = 24.0;
                        $volatility = 0.5;
                        break;
                    case 'Device Offline':
                        $targetHum = 85;
                        $targetTemp = 25.0;
                        $volatility = 1.0;
                        // Simulate offline for 4 hours (24 logs) in the middle of the day
                        if ($i > 50 && $i < 74) {
                            $isOffline = true;
                        }
                        break;
                    default:
                        $targetHum = 85;
                        $targetTemp = 25.0;
                        $volatility = 0.5;
                }

                if (!$isOffline) {
                    // Misting Logic
                    if ($hum <= ($params->misting_bottom_threshold ?? 80) && !$mistingState) {
                        $mistingState = true;
                        $mistingCount++;
                        $events[] = [
                            'enclosure_id' => $enclosure->id,
                            'event_type' => 'misting_on',
                            'description' => 'Auto-misting triggered due to low humidity.',
                            'triggered_by' => 'system',
                            'metadata' => json_encode(['humidity' => round($hum, 2)]),
                            'created_at' => $currentDate->copy()->toDateTimeString(),
                            'updated_at' => $currentDate->copy()->toDateTimeString(),
                        ];
                    } elseif ($hum >= ($params->misting_top_threshold ?? 90) && $mistingState) {
                        $mistingState = false;
                        $events[] = [
                            'enclosure_id' => $enclosure->id,
                            'event_type' => 'misting_off',
                            'description' => 'Auto-misting stopped. Target reached.',
                            'triggered_by' => 'system',
                            'metadata' => json_encode(['humidity' => round($hum, 2)]),
                            'created_at' => $currentDate->copy()->toDateTimeString(),
                            'updated_at' => $currentDate->copy()->toDateTimeString(),
                        ];
                    }

                    // Value adjustments
                    if ($mistingState) {
                        $hum += 2.0; // humidity goes up fast when misting
                        $temp -= 0.1; // temp drops slightly
                    } else {
                        // pull towards target with some random volatility
                        $hum += ($targetHum - $hum) * 0.1 + (mt_rand(-10, 10) / 10 * $volatility);
                        $temp += ($targetTemp - $temp) * 0.1 + (mt_rand(-5, 5) / 10 * ($volatility * 0.5));
                    }

                    // Bounds
                    $hum = min(100, max(0, $hum));
                    $temp = min(40, max(15, $temp));

                    // Track metrics for daily score
                    $dayTotalHum += $hum;
                    $dayTotalTemp += $temp;
                    if ($hum >= ($params->humidity_min ?? 80) && $hum <= ($params->humidity_max ?? 95)) {
                        $inRangeCount++;
                    }
                    if ($volatility > 2) {
                        $fluctuations += abs($hum - $targetHum);
                    }

                    $sensorLogs[] = [
                        'enclosure_id' => $enclosure->id,
                        'temperature' => round($temp, 2),
                        'humidity' => round($hum, 2),
                        'misting_status' => $mistingState,
                        'misting_duration_executed' => $mistingState ? ($params->misting_duration_seconds ?? 10) : null,
                        'logged_at' => $currentDate->copy()->toDateTimeString(),
                        'created_at' => clone $currentDate,
                    ];
                } else {
                    if ($i == 51) {
                         $events[] = [
                            'enclosure_id' => $enclosure->id,
                            'event_type' => 'device_offline',
                            'description' => 'System missed telemetry heartbeats for 10 minutes.',
                            'triggered_by' => 'system',
                            'metadata' => json_encode(['status' => 'offline']),
                            'created_at' => $currentDate->copy()->toDateTimeString(),
                            'updated_at' => $currentDate->copy()->toDateTimeString(),
                        ];
                    }
                    if ($i == 73) {
                        $events[] = [
                            'enclosure_id' => $enclosure->id,
                            'event_type' => 'device_online',
                            'description' => 'Device reconnected to the system.',
                            'triggered_by' => 'system',
                            'metadata' => json_encode(['status' => 'online']),
                            'created_at' => $currentDate->copy()->toDateTimeString(),
                            'updated_at' => $currentDate->copy()->toDateTimeString(),
                        ];
                    }
                }

                $currentDate->addMinutes(10);
            }
            
            // Insert daily batch of logs & events
            SensorLog::insert($sensorLogs);
            if (!empty($events)) EventTimeline::insert($events);

            // === Generate Stability Score ===
            // Range Compliance (RC)
            $rc = ($inRangeCount / $logsPerDay) * 100;
            
            // Variability
            $varScore = max(0, 100 - ($fluctuations / 10)); // Arbitrary formula for dummy
            
            // Stability Duration Ratio
            $sdRatio = $rc * 0.9; // Just a dummy correlation
            
            // Fluctuation Penalty
            $penalty = ($scenario == 'High Fluctuation') ? mt_rand(15, 30) : mt_rand(0, 5);
            
            // Force Enclosure B to have below average scores
            if (str_contains($enclosure->name, 'B') || $enclosure->id == 2) {
                $rc = min($rc, mt_rand(20, 45));
                $varScore = min($varScore, mt_rand(25, 45));
                $sdRatio = min($sdRatio, mt_rand(15, 35));
                $penalty += mt_rand(10, 20);
            }

            $finalScore = (($rc * 0.5) + ($varScore * 0.3) + ($sdRatio * 0.2)) - $penalty;
            $finalScore = min(100, max(0, $finalScore));
            
            $status = 'Kritis';
            if ($finalScore >= 85) $status = 'Optimal';
            elseif ($finalScore >= 70) $status = 'Stabil';
            elseif ($finalScore >= 50) $status = 'Perhatian';

            StabilityScore::create([
                'enclosure_id' => $enclosure->id,
                'analyzed_date' => clone $currentDate->copy()->subDay(),
                'range_compliance_score' => round($rc, 2),
                'variability_score' => round($varScore, 2),
                'stability_duration_ratio' => round($sdRatio, 2),
                'fluctuation_penalty' => round($penalty, 2),
                'final_stability_score' => round($finalScore, 2),
                'status' => $status,
                'created_at' => clone $currentDate,
                'updated_at' => clone $currentDate,
            ]);

            // === Generate Insights & Recommendations ===
            $this->generateInsightAndRecommendation($enclosure, $scenario, $currentDate->copy()->subHours(12));

            $dayIndex++;
        }
    }

    private function determineScenario($enclosure, $dayIndex, $totalDays)
    {
        // Enclosure 1 (A) is strictly stable
        if (str_contains($enclosure->name, 'A') || $enclosure->id == 1) {
            return 'Ideal Stable';
        }

        // Enclosure 2 (B) goes through unstable scenarios
        $block = ceil($totalDays / 4); 
        
        if ($dayIndex <= $block) return 'High Fluctuation';
        if ($dayIndex <= $block * 2) return 'Dry Enclosure';
        if ($dayIndex <= $block * 3) return 'Over-Misting';
        return 'Device Offline';
    }

    private function generateInsightAndRecommendation($enclosure, $scenario, $date)
    {
        // Only generate occasionally, not every single day for some scenarios, to look organic
        if (mt_rand(1, 100) > 40) return;

        $insight = null;
        $params = $enclosure->parameters;

        switch ($scenario) {
            case 'Dry Enclosure':
                $insight = Insight::create([
                    'enclosure_id' => $enclosure->id,
                    'type' => 'low_humidity_trend',
                    'description' => 'Kelembaban konsisten di bawah ambang batas optimal (80%) selama 12 jam terakhir.',
                    'severity' => 'warning',
                    'generated_at' => clone $date,
                ]);
                
                Recommendation::create([
                    'enclosure_id' => $enclosure->id,
                    'insight_id' => $insight->id,
                    'title' => 'Naikkan Ambang Batas Misting',
                    'description' => 'Tingkatkan batas bawah misting agar trigger menyala lebih cepat mencegah kekeringan.',
                    'action_type' => 'adjust_threshold',
                    'current_bottom_rh' => $params->misting_bottom_threshold ?? 80,
                    'current_top_rh' => $params->misting_top_threshold ?? 90,
                    'current_duration' => $params->misting_duration_seconds ?? 10,
                    'recommended_bottom_rh' => ($params->misting_bottom_threshold ?? 80) + 2,
                    'recommended_top_rh' => ($params->misting_top_threshold ?? 90) + 2,
                    'recommended_duration' => min(300, ($params->misting_duration_seconds ?? 10) + 2),
                    'decision_status' => 'pending',
                    'created_at' => clone $date,
                    'updated_at' => clone $date,
                ]);
                break;

            case 'High Fluctuation':
                $insight = Insight::create([
                    'enclosure_id' => $enclosure->id,
                    'type' => 'stability_drop',
                    'description' => 'Kelembaban terlalu fluktuatif, drop dan spike terjadi secara ekstrem yang dapat menyebabkan stres pada spesies.',
                    'severity' => 'critical',
                    'generated_at' => clone $date,
                ]);
                break;

            case 'Over-Misting':
                $insight = Insight::create([
                    'enclosure_id' => $enclosure->id,
                    'type' => 'misting_pattern',
                    'description' => 'Misting terlalu sering aktif, menyebabkan kelembaban selalu menyentuh angka 95-100%. Risiko jamur meningkat.',
                    'severity' => 'warning',
                    'generated_at' => clone $date,
                ]);

                Recommendation::create([
                    'enclosure_id' => $enclosure->id,
                    'insight_id' => $insight->id,
                    'title' => 'Turunkan Durasi/Frekuensi Misting',
                    'description' => 'Turunkan batas atas misting agar pompa berhenti lebih awal.',
                    'action_type' => 'adjust_threshold',
                    'current_bottom_rh' => $params->misting_bottom_threshold ?? 80,
                    'current_top_rh' => $params->misting_top_threshold ?? 90,
                    'current_duration' => $params->misting_duration_seconds ?? 10,
                    'recommended_bottom_rh' => ($params->misting_bottom_threshold ?? 80),
                    'recommended_top_rh' => ($params->misting_top_threshold ?? 90) - 3,
                    'recommended_duration' => max(1, ($params->misting_duration_seconds ?? 10) - 2),
                    'decision_status' => 'pending',
                    'created_at' => clone $date,
                    'updated_at' => clone $date,
                ]);
                break;
                
            case 'Ideal Stable':
                // Occasionally positive insight
                if (mt_rand(1, 100) > 70) {
                    Insight::create([
                        'enclosure_id' => $enclosure->id,
                        'type' => 'optimal_condition',
                        'description' => 'Kondisi enclosure sangat stabil dan optimal dalam 48 jam terakhir.',
                        'severity' => 'info',
                        'generated_at' => clone $date,
                    ]);
                }
                break;
        }
    }
}
