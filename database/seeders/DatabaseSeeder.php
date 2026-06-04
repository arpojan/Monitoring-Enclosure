<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Enclosure;
use App\Models\EnclosureParameter;
use App\Models\NotificationPreference;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Creates a complete demo environment:
     * - 1 user (researcher)
     * - 2 enclosures (dart frog vivariums)
     * - 2 enclosure parameters (biological range + misting thresholds)
     * - 1 notification preference
     */
    public function run(): void
    {
        // ─── User ───────────────────────────────────────────
        $user = User::create([
            'name'     => 'Researcher',
            'email'    => 'researcher@smart-enclosure.test',
            'password' => bcrypt('password'),
        ]);

        // ─── Notification Preference ────────────────────────
        NotificationPreference::create([
            'user_id'                  => $user->id,
            'low_humidity_alert'       => true,
            'unstable_enclosure_alert' => true,
        ]);

        // ─── Enclosure A: Dart Frog Vivarium ────────────────
        $enclosureA = Enclosure::create([
            'user_id'     => $user->id,
            'name'        => 'Dart Frog Vivarium A',
            'description' => 'Vivarium utama untuk Dendrobates tinctorius. Berisi tanaman bromeliad dan moss substrate.',
            'species'     => 'Dendrobates tinctorius',
            'is_active'   => true,
        ]);

        EnclosureParameter::create([
            'enclosure_id'             => $enclosureA->id,
            'humidity_min'             => 80.00,  // Biological range: batas bawah
            'humidity_max'             => 95.00,  // Biological range: batas atas
            'misting_bottom_threshold' => 82.00,  // Misting ON ketika RH turun ke sini
            'misting_top_threshold'    => 92.00,  // Misting OFF ketika RH naik ke sini
            'misting_duration_seconds' => 10,     // Durasi misting yang dieksekusi ESP32
            'is_misting_auto'          => true,
        ]);

        // ─── Enclosure B: Dart Frog Vivarium ────────────────
        $enclosureB = Enclosure::create([
            'user_id'     => $user->id,
            'name'        => 'Dart Frog Vivarium B',
            'description' => 'Vivarium sekunder untuk Ranitomeya imitator. Setup bioactive dengan springtails.',
            'species'     => 'Ranitomeya imitator',
            'is_active'   => true,
        ]);

        EnclosureParameter::create([
            'enclosure_id'             => $enclosureB->id,
            'humidity_min'             => 75.00,
            'humidity_max'             => 90.00,
            'misting_bottom_threshold' => 78.00,
            'misting_top_threshold'    => 88.00,
            'misting_duration_seconds' => 8,
            'is_misting_auto'          => true,
        ]);
        // ─── Generate Dummy Telemetry (Optional) ────────────
        // Uncomment the line below to generate 30 days of realistic telemetry data
        $this->call(DummyTelemetrySeeder::class);
    }
}
