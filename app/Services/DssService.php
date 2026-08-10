<?php

namespace App\Services;

use App\Models\Enclosure;
use App\Models\Insight;
use App\Models\Recommendation;
use App\Models\StabilityScore;
use App\Services\AnimalKnowledgeBase;
use Illuminate\Support\Facades\DB;

/**
 * DssService — Decision Support System Engine
 *
 * Peran dalam arsitektur:
 *   - Laravel/Server = satu-satunya tempat analisis AI berjalan.
 *   - ESP32          = eksekutor rule-based misting (tidak menjalankan AI).
 *   - Human-in-the-Loop = AI hanya memberi SARAN. User memilih Apply/Reject.
 *
 * Alur utama:
 *   1. Ambil sensor_logs X jam terakhir.
 *   2. Hitung Stability Score (0–100) dengan formula berbobot.
 *   3. Simpan ke tabel stability_scores.
 *   4. Hasilkan teks Insight sesuai kondisi.
 *   5. Simpan ke tabel insights.
 *   6. Tentukan parameter rekomendasi (jika kondisi tidak stabil).
 *   7. Simpan ke tabel recommendations (status = 'pending').
 *   8. Kembalikan seluruh hasil sebagai array.
 */
class DssService
{
    /**
     * Jendela analisis default: 24 jam terakhir.
     */
    private const DEFAULT_ANALYSIS_HOURS = 24;

    /**
     * Batas minimum log agar analisis bermakna.
     */
    private const MIN_LOGS_REQUIRED = 5;

    /**
     * Jalankan analisis DSS untuk satu enclosure.
     *
     * @param  Enclosure  $enclosure  Enclosure yang akan dianalisis.
     * @param  int        $hours      Jendela waktu analisis (jam).
     * @return array{
     *   stability: array,
     *   insight:   array|null,
     *   recommendation: array|null,
     *   message:   string
     * }
     */
    public function analyze(Enclosure $enclosure, int $hours = self::DEFAULT_ANALYSIS_HOURS): array
    {
        $enclosure->load('parameters');
        $params = $enclosure->parameters;

        // ── 1. Ambil sensor logs ──────────────────────────────────
        $logs = $enclosure->sensorLogs()
            ->where('logged_at', '>=', now()->subHours($hours))
            ->orderBy('logged_at', 'asc')
            ->get();

        $totalLogs = $logs->count();

        if ($totalLogs < self::MIN_LOGS_REQUIRED) {
            return [
                'stability'      => null,
                'insight'        => null,
                'recommendation' => null,
                'message'        => "Data tidak cukup untuk analisis (ditemukan {$totalLogs} log, minimal " . self::MIN_LOGS_REQUIRED . ").",
            ];
        }

        // ── 2. Hitung komponen skor ───────────────────────────────
        $scoreComponents = $this->computeScoreComponents($logs, $params);

        // ── 3. Hitung skor final (berbobot) ──────────────────────
        $finalScore = $this->computeFinalScore($scoreComponents);
        $status     = $this->resolveStatus($finalScore);

        // ── 4. Simpan StabilityScore ──────────────────────────────
        $stabilityScore = $this->persistStabilityScore($enclosure, $finalScore, $status, $scoreComponents);

        // ── 5. Hasilkan & simpan Insight ──────────────────────────
        $insight = $this->generateAndPersistInsight($enclosure, $finalScore, $scoreComponents, $logs, $params);

        // ── 6. Hasilkan & simpan Recommendation (jika diperlukan) ─
        $recommendation = $this->generateAndPersistRecommendation(
            $enclosure,
            $insight,
            $finalScore,
            $scoreComponents,
            $logs,
            $params
        );

        return [
            'stability'      => $stabilityScore->toArray(),
            'insight'        => $insight?->toArray(),
            'recommendation' => $recommendation?->toArray(),
            'message'        => "Analisis selesai. Skor Stabilitas: {$finalScore}/100 ({$status}).",
        ];
    }

    // ─────────────────────────────────────────────────────────────
    //  KOMPONEN SKOR
    // ─────────────────────────────────────────────────────────────

    /**
     * Hitung seluruh komponen yang menyusun skor stabilitas.
     *
     * @param  \Illuminate\Support\Collection  $logs
     * @param  \App\Models\EnclosureParameter|null  $params
     * @return array
     */
    private function computeScoreComponents($logs, $params): array
    {
        $totalLogs   = $logs->count();
        $humidityValues = $logs->pluck('humidity')->map(fn($v) => (float) $v);

        // ── Range Compliance (RC) — bobot 40% ─────────────────────
        // Persentase pembacaan yang berada dalam range humidity_min–humidity_max.
        $rcScore = 0.0;
        if ($params) {
            $humMin  = (float) $params->humidity_min;
            $humMax  = (float) $params->humidity_max;
            $inRange = $logs->filter(
                fn($l) => (float) $l->humidity >= $humMin && (float) $l->humidity <= $humMax
            )->count();
            $rcScore = round(($inRange / $totalLogs) * 100, 2);
        }

        // ── Variability Score — bobot 30% ─────────────────────────
        // Berdasarkan standar deviasi nilai humidity.
        // stdDev 0 = skor 100; stdDev ≥ 10 = skor 0.
        $variabilityScore = 0.0;
        $stdDev = 0.0;
        if ($totalLogs > 1) {
            $mean     = $humidityValues->avg();
            $variance = $humidityValues->map(fn($v) => pow($v - $mean, 2))->avg();
            $stdDev   = sqrt($variance);
            $variabilityScore = round(max(0.0, 100.0 - ($stdDev * 10)), 2);
        }

        // ── Stability Duration Ratio — bobot 20% ──────────────────
        // Berapa lama berturut-turut (dari belakang) humidity berada dalam range.
        $stableReadings = 0;
        $stableHours    = 0.0;
        if ($params && $totalLogs > 0) {
            $humMin  = (float) $params->humidity_min;
            $humMax  = (float) $params->humidity_max;
            foreach ($logs->reverse() as $log) {
                $h = (float) $log->humidity;
                if ($h >= $humMin && $h <= $humMax) {
                    $stableReadings++;
                } else {
                    break;
                }
            }
            if ($stableReadings > 0 && $totalLogs > 1) {
                $totalMinutes     = $logs->first()->logged_at->diffInMinutes($logs->last()->logged_at);
                $minutesPerReading = $totalMinutes / ($totalLogs - 1);
                $stableHours       = round(($stableReadings * $minutesPerReading) / 60, 2);
            }
        }
        // Rasio dari 24 jam (semakin lama stabil = skor lebih tinggi, maks 100)
        $durationRatio = min(100.0, round(($stableHours / 24) * 100, 2));

        // ── Fluctuation Penalty — bobot 10% ───────────────────────
        // Hitung lonjakan cepat (delta > 5% antar pembacaan berturutan).
        $fluctuationCount = 0;
        $prevHum = null;
        foreach ($logs as $log) {
            if ($prevHum !== null && abs((float) $log->humidity - $prevHum) > 5) {
                $fluctuationCount++;
            }
            $prevHum = (float) $log->humidity;
        }
        // Maksimal penalti 20 poin
        $penaltyPoints = min(20.0, round($fluctuationCount * 2, 2));

        // ── Statistik tambahan ─────────────────────────────────────
        $avgHumidity    = round($humidityValues->avg(), 2);
        $avgTemperature = round($logs->pluck('temperature')->map(fn($v) => (float) $v)->avg(), 2);

        return compact(
            'rcScore',
            'variabilityScore',
            'stdDev',
            'durationRatio',
            'stableHours',
            'fluctuationCount',
            'penaltyPoints',
            'avgHumidity',
            'avgTemperature',
            'totalLogs'
        );
    }

    /**
     * Hitung skor final dari komponen berbobot.
     *
     * Formula:
     *   final = (RC × 0.40) + (Variability × 0.30) + (Duration × 0.20) - PenaltyPoints
     *   diclamp ke [0, 100]
     */
    private function computeFinalScore(array $c): float
    {
        $raw = ($c['rcScore'] * 0.40)
             + ($c['variabilityScore'] * 0.30)
             + ($c['durationRatio'] * 0.20)
             - $c['penaltyPoints'];

        return (float) round(max(0.0, min(100.0, $raw)), 2);
    }

    /**
     * Konversi skor numerik ke label status tesis.
     *
     * Sesuai spesifikasi: Stabil 80–100, Moderate 50–79, Tidak Stabil <50.
     */
    private function resolveStatus(float $score): string
    {
        return match (true) {
            $score >= 80 => 'Stabil',
            $score >= 50 => 'Moderate',
            default      => 'Tidak Stabil',
        };
    }

    // ─────────────────────────────────────────────────────────────
    //  PERSISTENSI
    // ─────────────────────────────────────────────────────────────

    /**
     * Simpan atau perbarui StabilityScore untuk hari ini.
     * Jika sudah ada record untuk tanggal yang sama, timpa nilainya.
     */
    private function persistStabilityScore(
        Enclosure $enclosure,
        float $finalScore,
        string $status,
        array $c
    ): StabilityScore {
        return StabilityScore::updateOrCreate(
            [
                'enclosure_id'  => $enclosure->id,
                'analyzed_date' => now()->toDateString(),
            ],
            [
                'range_compliance_score'   => $c['rcScore'],
                'variability_score'        => $c['variabilityScore'],
                'stability_duration_ratio' => $c['durationRatio'],
                'fluctuation_penalty'      => $c['penaltyPoints'],
                'final_stability_score'    => $finalScore,
                'status'                   => $status,
            ]
        );
    }

    /**
     * Hasilkan teks Insight berbasis aturan dan simpan ke DB.
     *
     * Severity mapping:
     *   - Stabil (>=80)    → info
     *   - Moderate (50-79) → warning
     *   - Tidak Stabil <50 → critical
     */
    private function generateAndPersistInsight(
        Enclosure $enclosure,
        float $finalScore,
        array $c,
        $logs,
        $params
    ): Insight {
        $avgHum  = $c['avgHumidity'];
        $avgTemp = $c['avgTemperature'];
        $stdDev  = round($c['stdDev'], 2);

        // Tentukan severity
        $severity = match (true) {
            $finalScore >= 80 => 'info',
            $finalScore >= 50 => 'warning',
            default           => 'critical',
        };

        // Referensi parameter biologis
        $humMin = $params ? (float) $params->humidity_min : '?';
        $humMax = $params ? (float) $params->humidity_max : '?';

        $animalInsight = "";
        if ($enclosure->jenis_hewan) {
            $animalEval = AnimalKnowledgeBase::evaluateHumidity($enclosure, $avgHum);
            if ($animalEval) {
                $animalInsight = " " . $animalEval['ai_insight'];
            }
        }

        // Bangun teks insight sesuai kondisi dominan
        if ($finalScore >= 80) {
            $description = "Kondisi enclosure stabil dalam 24 jam terakhir. "
                . "Rata-rata kelembapan {$avgHum}% berada dalam zona biologis ({$humMin}–{$humMax}%). "
                . "Variabilitas rendah (σ={$stdDev}%). "
                . "Sistem pengabutan bekerja efisien. Tidak diperlukan tindakan.";
        } elseif ($finalScore >= 50) {
            if ($c['rcScore'] < 60) {
                $description = "Kepatuhan range kelembapan rendah ({$c['rcScore']}%). "
                    . "Rata-rata kelembapan {$avgHum}% dengan fluktuasi sedang (σ={$stdDev}%). "
                    . "Pertimbangkan untuk menyesuaikan ambang batas misting.";
            } elseif ($c['variabilityScore'] < 60) {
                $description = "Fluktuasi kelembapan cukup tinggi (σ={$stdDev}%). "
                    . "Meski rata-rata {$avgHum}% masih mendekati range ideal, "
                    . "lonjakan tiba-tiba ({$c['fluctuationCount']}x terdeteksi) dapat stres pada hewan. "
                    . "Periksa sistem ventilasi dan posisi sensor.";
            } else {
                $description = "Kondisi enclosure dalam status perhatian. "
                    . "Skor stabilitas {$finalScore}/100. "
                    . "Rata-rata kelembapan {$avgHum}% (target {$humMin}–{$humMax}%), suhu rata-rata {$avgTemp}°C. "
                    . "Pantau lebih sering dalam 6 jam ke depan.";
            }
        } else {
            if ($c['penaltyPoints'] >= 15) {
                $description = "Peringatan Kritis! Terdeteksi {$c['fluctuationCount']} lonjakan kelembapan ekstrem (>5% per pembacaan). "
                    . "Rata-rata {$avgHum}% dengan deviasi tinggi (σ={$stdDev}%). "
                    . "Segera periksa nozzle misting, sambungan selang, atau posisi sensor apakah basah/kotor.";
            } elseif ($c['rcScore'] < 40) {
                $description = "Peringatan Kritis! Hanya {$c['rcScore']}% waktu kelembapan berada dalam zona aman ({$humMin}–{$humMax}%). "
                    . "Rata-rata berada di {$avgHum}%. Segera kalibrasi sensor atau ubah durasi misting agar tidak membahayakan sistem biologis.";
            } else {
                $description = "Kondisi tidak stabil. Skor {$finalScore}/100. "
                    . "Parameter kelembapan dan fluktuasi berada di luar batas toleransi (Rata-rata: {$avgHum}%). "
                    . "Perlu tinjauan manual secepatnya.";
            }
        }

        // Append animal-specific insight if available
        $description .= $animalInsight;

        return Insight::create([
            'enclosure_id' => $enclosure->id,
            'type'         => 'dss_analysis',
            'description'  => $description,
            'severity'     => $severity,
            'generated_at' => now(),
        ]);
    }

    /**
     * Tentukan parameter rekomendasi dan simpan ke DB (jika kondisi tidak stabil).
     *
     * Logika heuristik:
     *   - Avg humidity < bottom_threshold → kelembapan terlalu rendah → naikkan durasi misting, turunkan bottom threshold.
     *   - Avg humidity > top_threshold    → kelembapan terlalu tinggi → turunkan durasi misting, naikkan bottom threshold.
     *   - Fluktuasi tinggi (stdDev > 5)   → range terlalu lebar → sempitkan 3%.
     *   - Skor Stabil (>=80)              → tidak perlu rekomendasi.
     *
     * Rekomendasi selalu disimpan dengan decision_status = 'pending'.
     * User yang menentukan Apply atau Reject.
     */
    private function generateAndPersistRecommendation(
        Enclosure $enclosure,
        Insight $insight,
        float $finalScore,
        array $c,
        $logs,
        $params
    ): ?Recommendation {
        // Jika kondisi stabil, tidak perlu rekomendasi
        if ($finalScore >= 80) {
            return null;
        }

        // Jika tidak ada parameter aktif, tidak bisa memberi saran spesifik
        if (!$params) {
            return null;
        }

        $currentBottom   = (float) $params->misting_bottom_threshold;
        $currentTop      = (float) $params->misting_top_threshold;
        $currentDuration = (int)   ($params->misting_duration_seconds ?: 10); // fallback 10s jika null/0
        $avgHum          = $c['avgHumidity'];

        // Salin nilai saat ini sebagai titik awal
        $recBottom   = $currentBottom;
        $recTop      = $currentTop;
        $recDuration = $currentDuration;

        $actionType  = 'adjust_threshold';
        $reasoning   = [];

        // ── Aturan 1: Kelembapan rata-rata terlalu rendah ───────────
        if ($avgHum < $currentBottom) {
            // Tingkatkan durasi misting 25% (max 300 detik)
            $recDuration = min(300, (int) round($currentDuration * 1.25));
            // Turunkan threshold bawah 5% agar misting lebih sering aktif
            $recBottom   = max(0.0, round($currentBottom - 5.0, 2));
            $reasoning[] = "Rata-rata kelembapan ({$avgHum}%) di bawah threshold bawah ({$currentBottom}%).";
            $reasoning[] = "Durasi misting dinaikkan {$currentDuration}s → {$recDuration}s.";
        }
        // ── Aturan 2: Kelembapan rata-rata terlalu tinggi ───────────
        elseif ($avgHum > $currentTop) {
            // Kurangi durasi misting 20% (min 1 detik)
            $recDuration = max(1, (int) round($currentDuration * 0.80));
            // Naikkan threshold bawah 5% agar misting lebih jarang aktif
            $recBottom   = min($currentTop - 1, round($currentBottom + 5.0, 2));
            $reasoning[] = "Rata-rata kelembapan ({$avgHum}%) di atas threshold atas ({$currentTop}%).";
            $reasoning[] = "Durasi misting diturunkan {$currentDuration}s → {$recDuration}s.";
        }

        // ── Aturan 3: Fluktuasi tinggi → sempitkan range ─────────────
        if ($c['stdDev'] > 5.0) {
            // Sempitkan range 3% dari masing-masing sisi
            $recBottom = min($recTop - 2, round($recBottom + 3.0, 2));
            $recTop    = max($recBottom + 2, round($recTop   - 3.0, 2));
            $reasoning[] = "Fluktuasi tinggi terdeteksi (σ=" . round($c['stdDev'], 1) . "%). Range disempitkan.";
        }

        // ── Aturan 4: Avg humidity di luar zona biologis ─────────────
        // Meski avg masih di antara bottom & top threshold misting,
        // jika di luar humidity_min–humidity_max → perlu penyesuaian.
        $humMin = $params ? (float) $params->humidity_min : null;
        $humMax = $params ? (float) $params->humidity_max : null;

        if ($humMin !== null && $avgHum < $humMin && $avgHum >= $currentBottom) {
            // Kelembapan rata-rata di bawah zona biologis minimum
            // Naikkan bottom threshold agar misting lebih cepat aktif
            $recBottom = round(max($recBottom, $humMin - 3.0), 2);
            // Naikkan durasi misting 20%
            $recDuration = min(300, (int) round($recDuration * 1.20));
            $reasoning[] = "Rata-rata kelembapan ({$avgHum}%) di bawah zona biologis minimum ({$humMin}%).";
            $reasoning[] = "Threshold bawah dinaikkan dan durasi misting ditambah untuk meningkatkan kelembapan.";
        } elseif ($humMax !== null && $avgHum > $humMax && $avgHum <= $currentTop) {
            // Kelembapan rata-rata di atas zona biologis maksimum
            // Turunkan top threshold agar misting lebih cepat berhenti
            $recTop = round(min($recTop, $humMax + 2.0), 2);
            $recDuration = max(1, (int) round($recDuration * 0.85));
            $reasoning[] = "Rata-rata kelembapan ({$avgHum}%) di atas zona biologis maksimum ({$humMax}%).";
            $reasoning[] = "Threshold atas diturunkan agar pompa berhenti lebih awal.";
        }

        // ── Aturan 5: Catch-all — skor rendah tapi tidak ada aturan lain yang memicu ─
        if (empty($reasoning) && $finalScore < 80) {
            // Coba sesuaikan top threshold mendekati humidity_max
            if ($humMax !== null && $currentTop > $humMax) {
                $recTop = round($humMax, 2);
                $reasoning[] = "Threshold atas ({$currentTop}%) melebihi batas biologis ({$humMax}%). Diturunkan.";
            }
            // Coba sesuaikan bottom threshold mendekati humidity_min
            if ($humMin !== null && $currentBottom < $humMin - 10) {
                $recBottom = round($humMin - 5, 2);
                $reasoning[] = "Threshold bawah ({$currentBottom}%) terlalu rendah dari batas biologis ({$humMin}%). Dinaikkan.";
            }
            // Jika masih belum ada perubahan, beri saran moderat
            if (empty($reasoning)) {
                $recDuration = min(300, (int) round($currentDuration * 1.15));
                $reasoning[] = "Skor stabilitas rendah ({$finalScore}/100). Durasi misting sedikit dinaikkan untuk perbaikan.";
            }
        }

        // Pastikan bottom < top setelah semua penyesuaian
        if ($recBottom >= $recTop) {
            $recTop = $recBottom + 5.0;
        }
        $recBottom = round($recBottom, 2);
        $recTop    = round($recTop, 2);

        // Jika tidak ada perubahan signifikan, tidak perlu rekomendasi
        if ($recBottom === $currentBottom && $recTop === $currentTop && $recDuration === $currentDuration) {
            return null;
        }

        $score = round($finalScore);
        $reasonStr = implode(' ', $reasoning);

        // Auto-expire rekomendasi pending lama untuk enclosure ini
        Recommendation::where('enclosure_id', $enclosure->id)
            ->where('decision_status', 'pending')
            ->update(['decision_status' => 'expired']);

        return Recommendation::create([
            'enclosure_id'          => $enclosure->id,
            'insight_id'            => $insight->id,
            'title'                 => "Penyesuaian Parameter Misting (Skor: {$score}/100)",
            'description'           => "Sistem AI mendeteksi kondisi {$this->resolveStatus($finalScore)}. "
                . $reasonStr
                . " Silakan terapkan atau tolak rekomendasi ini.",
            'action_type'           => $actionType,
            'current_bottom_rh'     => $currentBottom,
            'current_top_rh'        => $currentTop,
            'current_duration'      => $currentDuration,
            'recommended_bottom_rh' => $recBottom,
            'recommended_top_rh'    => $recTop,
            'recommended_duration'  => $recDuration,
            'decision_status'       => 'pending',
        ]);
    }
}
