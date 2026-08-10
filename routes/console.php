<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

/*
|--------------------------------------------------------------------------
| DSS Scheduler — Analisis Otomatis
|--------------------------------------------------------------------------
|
| Perintah `dss:analyze` dijalankan otomatis setiap jam untuk semua
| enclosure aktif. Hasilnya disimpan ke:
|   - stability_scores (skor harian, upsert per hari)
|   - insights         (teks interpretasi kondisi)
|   - recommendations  (parameter saran, status = 'pending')
|
| User tetap harus klik "Terapkan" di dashboard agar parameter ESP32 berubah.
| Aktifkan Laravel Scheduler dengan menambahkan entry ini ke crontab server:
|   * * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
|
*/
Schedule::command('dss:analyze --hours=24')
    ->hourly()
    ->withoutOverlapping()           // Cegah tumpang tindih jika command berjalan lama
    ->runInBackground()              // Jalankan di background agar tidak blokir scheduler
    ->appendOutputTo(storage_path('logs/dss_analysis.log'));
