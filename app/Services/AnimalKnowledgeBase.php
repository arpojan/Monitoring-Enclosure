<?php

namespace App\Services;

use App\Models\Enclosure;

class AnimalKnowledgeBase
{
    /**
     * Mengembalikan daftar spesies hewan beserta batas kelembaban idealnya.
     * Dikelompokkan berdasarkan kategori habitat.
     */
    public static function getAnimals(): array
    {
        return [
            'Kategori Kering (Arid / Low Humidity)' => [
                'Kadal BTM (Bearded Dragon)' => ['min' => 35, 'max' => 45],
                'Kura-kura Sulcata (Sulcata Tortoise)' => ['min' => 45, 'max' => 60],
            ],
            'Kategori Menengah (Tropical Moderate)' => [
                'Ular Jagung (Corn Snake)' => ['min' => 40, 'max' => 60],
                'Ular Sanca Bola (Ball Python)' => ['min' => 60, 'max' => 80],
                'Iguana Hijau (Green Iguana)' => ['min' => 65, 'max' => 75],
                'Tokek Puncak (Crested Gecko)' => ['min' => 70, 'max' => 80],
                'Bunglon Yaman (Veiled Chameleon)' => ['min' => 50, 'max' => 80],
            ],
            'Kategori Basah (Tropical High / Amphibian)' => [
                'Ular Piton Hijau (Green Tree Python)' => ['min' => 80, 'max' => 90],
                'Naga Air Asia (Asian Water Dragon)' => ['min' => 70, 'max' => 80],
                'Katak Pesek (White\'s Tree Frog)' => ['min' => 50, 'max' => 70],
                'Katak Tanduk (Ornate Horned Frog / Pacman)' => ['min' => 60, 'max' => 80],
                'Katak Pohon Bermata Merah (Red-eyed Tree Frog)' => ['min' => 60, 'max' => 80],
                'Katak Beracun (Poison Dart Frog)' => ['min' => 80, 'max' => 100],
                'Kadal Air Perut Api (Fire-bellied Newt)' => ['min' => 70, 'max' => 80],
                'Salamander Harimau (Tiger Salamander)' => ['min' => 70, 'max' => 80],
            ],
        ];
    }

    /**
     * Mendapatkan rentang kelembaban untuk spesies tertentu.
     */
    public static function getSpeciesConfig(string $species): ?array
    {
        foreach (self::getAnimals() as $habitat => $speciesList) {
            if (isset($speciesList[$species])) {
                return array_merge(['habitat' => $habitat], $speciesList[$species]);
            }
        }
        return null;
    }

    /**
     * Mengevaluasi kondisi kelembaban saat ini berdasarkan jenis hewan di kandang.
     * Mengembalikan status, durasi misting yang disarankan, dan insight AI.
     */
    public static function evaluateHumidity(Enclosure $enclosure, float $currentHumidity): ?array
    {
        if (empty($enclosure->jenis_hewan)) {
            return null; // Tidak ada jenis hewan, abaikan evaluasi KB
        }

        $config = self::getSpeciesConfig($enclosure->jenis_hewan);
        if (!$config) {
            return null; // Hewan tidak dikenali di KB
        }

        $min = $config['min'];
        $max = $config['max'];
        $habitat = $config['habitat'];

        $status = 'Stable';
        $mistingDuration = null;
        $insight = "Kelembaban {$currentHumidity}% sangat ideal untuk {$enclosure->jenis_hewan}.";

        if ($currentHumidity < $min) {
            // Terlalu Kering
            $diff = $min - $currentHumidity;
            if ($diff <= 5) {
                $status = 'Warning';
                $insight = "Kelembaban {$currentHumidity}% mendekati batas bawah ideal ({$min}%) untuk {$enclosure->jenis_hewan}.";
                $mistingDuration = 10;
            } else {
                $status = 'Danger';
                $insight = "Kelembaban {$currentHumidity}% terlalu kering untuk {$enclosure->jenis_hewan}, berisiko dehidrasi ekstrem.";
                $mistingDuration = ($habitat === 'Amphibian' || $habitat === 'Tropical') ? 20 : 10;
            }
        } elseif ($currentHumidity > $max) {
            // Terlalu Lembab
            $diff = $currentHumidity - $max;
            if ($diff <= 5) {
                $status = 'Warning';
                $insight = "Kelembaban {$currentHumidity}% mendekati batas atas ideal ({$max}%) untuk {$enclosure->jenis_hewan}.";
                $mistingDuration = 0; // Matikan misting
            } else {
                $status = 'Danger';
                $insight = "Kelembaban {$currentHumidity}% terlalu tinggi untuk {$enclosure->jenis_hewan}, berisiko infeksi pernapasan. Misting dimatikan.";
                $mistingDuration = 0; // Matikan misting secara paksa
            }
        }

        return [
            'status' => $status,
            'recommendation_misting_duration' => $mistingDuration,
            'ai_insight' => $insight,
        ];
    }
}
