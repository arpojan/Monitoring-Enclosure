<?php

namespace App\Services;

use App\Models\Enclosure;

/**
 * MistingService — Simulation Helper Only
 *
 * Flow utama project:
 * - Laravel/Web menyimpan parameter bottom humidity, top humidity, dan durasi misting.
 * - ESP32 mengambil parameter tersebut lalu menjalankan rule-based misting secara lokal.
 * - Laravel menerima status aktual misting dari ESP32 melalui /api/telemetry.
 *
 * Service ini tidak dipakai sebagai pengambil keputusan utama pada telemetry production.
 * Ia boleh dipakai untuk simulator/dummy data agar perilaku historis tetap realistis.
 */
class MistingService
{
    /**
     * Simulasi rule-based misting.
     *
     * @param  Enclosure  $enclosure  Enclosure dengan parameters loaded
     * @param  float      $humidity   Humidity saat ini dari sensor/simulator
     * @return bool       true = misting ON, false = misting OFF
     */
    public function evaluate(Enclosure $enclosure, float $humidity): bool
    {
        $params = $enclosure->parameters;

        if (!$params || !$params->is_misting_auto) {
            return false;
        }

        $bottomThreshold = (float) $params->misting_bottom_threshold;
        $topThreshold = (float) $params->misting_top_threshold;
        $lastMistingStatus = $this->getLastMistingStatus($enclosure);

        if ($humidity <= $bottomThreshold) {
            return true;
        }

        if ($humidity >= $topThreshold) {
            return false;
        }

        return $lastMistingStatus;
    }

    private function getLastMistingStatus(Enclosure $enclosure): bool
    {
        $lastLog = $enclosure->sensorLogs()
            ->latest('logged_at')
            ->first();

        return $lastLog ? (bool) $lastLog->misting_status : false;
    }
}
