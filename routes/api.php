<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TelemetryController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EnclosureController;
use App\Http\Controllers\Api\RecommendationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Web/Laravel berperan sebagai monitoring dashboard, configuration panel,
| dan AI-based DSS. ESP32 tetap menjadi rule-based misting executor.
|
*/

// ─── IoT Telemetry (ESP32 / Simulator) ──────────────────────
Route::post('/telemetry', [TelemetryController::class, 'store']);

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

    // ESP32 mengambil parameter rule-based dari web
    Route::get('/control-config', [EnclosureController::class, 'controlConfig']);

    // Web mengubah parameter bottom/top/duration untuk ESP32
    Route::put('/parameters', [EnclosureController::class, 'updateParameters']);

    // Update identitas enclosure
    Route::put('/', [EnclosureController::class, 'update']);
});
