<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TelemetryController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DssController;
use App\Http\Controllers\Api\EnclosureController;
use App\Http\Controllers\Api\RecommendationController;
use App\Http\Controllers\Api\DeviceConfigController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Web/Laravel berperan sebagai monitoring dashboard, configuration panel,
| dan AI-based DSS. ESP32 tetap menjadi rule-based misting executor.
|
*/

// ─── IoT Telemetry (ESP32) ───────────────────────────────────
// Dilindungi oleh AuthorizeDevice: wajib kirim X-Device-Key yang sesuai enclosure.
Route::post('/telemetry', [TelemetryController::class, 'store'])
    ->middleware('authorize.device');

Route::get('/device/{device}/config', [DeviceConfigController::class, 'show'])
    ->middleware('auth.device')
    ->name('api.device.config');

// ─── AI Recommendation Decision Actions ─────────────────────
Route::post('/recommendations/{id}/apply', [RecommendationController::class, 'apply']);
Route::post('/recommendations/{id}/reject', [RecommendationController::class, 'reject']);

// ─── Dashboard Data + Enclosure Control Config ──────────────
Route::prefix('enclosures/{id}')->group(function () {
    Route::get('/latest', [DashboardController::class, 'latest']);
    Route::get('/history', [DashboardController::class, 'history']);
    Route::get('/dashboard', [DashboardController::class, 'dashboard']);
    Route::get('/analytics', [DashboardController::class, 'analytics']);
    Route::get('/stability', [DashboardController::class, 'stability']);

    // ─── AI DSS Engine ──────────────────────────────────────
    // POST: Picu analisis DSS (Human-in-the-Loop: hasil disimpan sebagai 'pending').
    // Dapat dipanggil manual via tombol dashboard ATAU terjadwal via artisan dss:analyze.
    Route::post('/analyze', [DssController::class, 'analyze']);

    // ESP32 mengambil parameter rule-based dari web (dilindungi device key)
    Route::get('/control-config', [EnclosureController::class, 'controlConfig'])
        ->middleware('authorize.device');

    // Web mengubah parameter bottom/top/duration untuk ESP32
    Route::put('/parameters', [EnclosureController::class, 'updateParameters']);

    // Trigger misting manual
    Route::post('/mist/trigger', [EnclosureController::class, 'triggerManualMist']);

    // Update identitas enclosure
    Route::put('/', [EnclosureController::class, 'update']);
});

