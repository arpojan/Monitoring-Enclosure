<!-- resources/views/dashboard/index.blade.php -->
@extends('layouts.app')

@section('content')
<div id="app-screen" class="view active" data-enclosure-id="{{ request()->route('id', 1) }}" style="display: flex;"> <!-- Pastikan style layout mengikuti CSS asli Anda -->
    <!-- Sidebar Navigation -->
    <nav class="sidebar glass-card">
        <div class="sidebar-header">
            <i class="ph ph-leaf text-green"></i>
            <span class="brand">RAP Enclosure</span>
            <button class="menu-toggle d-mobile"><i class="ph ph-x"></i></button>
        </div>
        <ul class="nav-links">
            <!-- Navigasi Internal di dalam Dashboard -->
            <li class="active" data-target="dashboard">
                <i class="ph ph-squares-four"></i><span>{{ $enclosureName ?? 'Dasbor' }}</span>
            </li>
            <li data-target="analytics">
                <i class="ph ph-chart-line-up"></i><span>Analitik</span>
            </li>
            <li data-target="stability">
                <i class="ph ph-scales"></i><span>Stabilitas</span>
            </li>
            <!-- Tombol Kembali ke Pilih Kandang -->
            <li style="margin-top: 20px; border-top: 1px solid var(--border-light); padding-top: 20px;">
                <a href="{{ route('enclosure.select') }}" style="display:flex; align-items:center; gap:10px; color:inherit; text-decoration:none;">
                    <i class="ph ph-arrow-u-up-left"></i><span>Pilih Kandang</span>
                </a>
            </li>
        </ul>
        <div class="sidebar-footer">
            <div class="user-profile">
                <img src="https://ui-avatars.com/api/?name=Admin&background=00b4d8&color=fff" alt="User">
                <div class="user-info">
                    <span class="user-name">Admin</span>
                    <span class="user-role">Ahli Herpetologi</span>
                </div>
            </div>
            <div class="footer-actions" style="display: flex; gap: 8px;">
                <!-- Tombol Pengaturan Akun -->
                <button type="button" id="open-settings-btn" class="btn-icon" title="Pengaturan Akun"><i class="ph ph-gear"></i></button>
                <!-- Logout Arahkan ke Route -->
                <form action="{{ route('logout') }}" method="POST" style="display: inline;">
                    @csrf
                    <button type="submit" class="btn-icon" title="Keluar"><i class="ph ph-sign-out"></i></button>
                </form>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
                    <!-- Header -->
            <header class="topbar">
                <div class="header-left">
                    <button class="menu-toggle d-mobile"><i class="ph ph-list"></i></button>
                    <h1 id="page-title">{{ $enclosureName ?? 'Dasbor' }}</h1>
                </div>
                <div class="header-right">
                    <div class="status-indicator" id="system-status-indicator">
                        <span class="dot" id="system-status-dot"></span>
                        <span class="status-text" id="system-status-text">Memuat...</span>
                    </div>
                    <button id="theme-toggle" class="btn-icon">
                        <i class="ph ph-moon"></i>
                    </button>
                    <button class="notification-btn btn-icon">
                        <i class="ph ph-bell"></i>
                        <span class="badge">2</span>
                    </button>
                </div>
            </header>

            <!-- Views Container -->
            <div class="views-container">

                <!-- View: Dashboard -->
                <div id="view-dashboard" class="page-view active">
                    <!-- 1 & 2. Summary Status & Stability Badge -->
                    <div class="metrics-grid">
                        <div class="metric-card glass-card grid-col-span-2" id="stability-metric-card" style="display: flex; align-items: center; gap: 15px;">
                            <div style="font-size: 3rem;" id="stability-icon-large">
                                🔵
                            </div>
                            <div>
                                <div class="metric-header" style="margin-bottom: 5px;">
                                    <h3 style="font-size: 1.2rem;">Status Lingkungan</h3>
                                </div>
                                <div class="metric-value" style="font-size: 1.5rem;" id="stability-status-text-large">Memuat...</div>
                                <div class="metric-trend" id="stability-score-sub">Skor: --/100</div>
                            </div>
                        </div>
                        <div class="metric-card glass-card">
                            <div class="metric-header">
                                <h3>Suhu Udara</h3>
                                <i class="ph ph-thermometer text-blue"></i>
                            </div>
                            <div class="metric-value" id="temp-value">--<span class="unit">°C</span></div>
                            <div class="metric-trend" id="temp-trend">
                                Memuat...
                            </div>
                        </div>
                        <div class="metric-card glass-card">
                            <div class="metric-header">
                                <h3>Kelembapan Udara</h3>
                                <i class="ph ph-drop text-teal"></i>
                            </div>
                            <div class="metric-value" id="humidity-value">--<span class="unit">%</span></div>
                            <div class="metric-trend" id="humidity-trend">
                                Memuat...
                            </div>
                        </div>
                    </div>

                    <!-- 3. Insight Summary -->
                    <div class="insight-summary-card glass-card" style="margin-bottom: 20px; padding: 20px; border-left: 4px solid var(--primary-color);">
                        <h3 style="margin-bottom: 10px; display: flex; align-items: center; gap: 8px;">
                            <i class="ph ph-brain"></i> Interpretasi Keseluruhan
                        </h3>
                        <p id="dashboard-insight-summary" style="color: var(--text-muted); line-height: 1.5; font-size: 1.05rem;">
                            Menganalisis kondisi lingkungan enclosure...
                        </p>
                    </div>

                    <!-- Parameter Kontrol Misting -->
                    <div class="control-config-card glass-card" style="margin-bottom: 20px; padding: 20px;">
                        <div class="card-header" style="margin-bottom: 15px;">
                            <h2><i class="ph ph-sliders-horizontal text-teal"></i> Parameter Kontrol Misting</h2>
                        </div>
                        <form id="control-parameters-form" class="control-parameters-form">
                            <div>
                                <label style="display:block; margin-bottom:6px; color:var(--text-secondary); font-size:0.9rem; font-weight:500;">Kelembapan Minimum (%)</label>
                                <div class="input-group" style="margin-bottom: 0;">
                                    <i class="ph ph-caret-double-down" style="color: var(--accent-teal);"></i>
                                    <input
                                        type="number"
                                        name="bottom_humidity"
                                        id="param-bottom-humidity"
                                        min="0"
                                        max="100"
                                        step="1"
                                        placeholder="Contoh: 40"
                                        value="{{ old('bottom_humidity', (int) round($config->bottom_humidity ?? 0)) }}"
                                    >
                                </div>
                            </div>
                            
                            <div>
                                <label style="display:block; margin-bottom:6px; color:var(--text-secondary); font-size:0.9rem; font-weight:500;">Kelembapan Maksimum (%)</label>
                                <div class="input-group" style="margin-bottom: 0;">
                                    <i class="ph ph-caret-double-up" style="color: var(--accent-teal);"></i>
                                    <input
                                        type="number"
                                        name="top_humidity"
                                        id="param-top-humidity"
                                        min="0"
                                        max="100"
                                        step="1"
                                        placeholder="Contoh: 90"
                                        value="{{ old('top_humidity', (int) round($config->top_humidity ?? 0)) }}"
                                    >
                                </div>
                            </div>
                            
                            <div>
                                <label style="display:block; margin-bottom:6px; color:var(--text-secondary); font-size:0.9rem; font-weight:500;">Durasi Misting (detik)</label>
                                <div class="input-group" style="margin-bottom: 0;">
                                    <i class="ph ph-timer" style="color: var(--accent-teal);"></i>
                                    <input
                                        type="number"
                                        min="1"
                                        max="300"
                                        step="1"
                                        id="param-misting-duration"
                                        placeholder="Contoh: 10"
                                    >
                                </div>
                            </div>
                            
                            <div>
                                <button type="submit" class="btn-primary" id="save-control-parameters-btn" style="width: 100%;">
                                    <i class="ph ph-paper-plane-tilt"></i> Simpan ke ESP32
                                </button>
                            </div>
                            <p style="grid-column: 1 / -1; color: var(--text-muted); font-size: 0.85rem; margin: 10px 0 0 0; display: flex; align-items: center; gap: 6px;">
                                <i class="ph ph-info-circle text-teal" style="font-size: 1.1rem;"></i> Web menyimpan parameter sebagai konfigurasi. ESP32 mengambil konfigurasi ini dan mengeksekusi rule-based misting secara lokal.
                            </p>
                        </form>
                    </div>

                    <div class="dashboard-grid">
                        <!-- 4. RH Chart (Separated) -->
                        <div class="chart-card glass-card grid-col-span-2">
                            <div class="card-header">
                                <h2>Kondisi Kelembapan (RH) Terkini</h2>
                                <div class="card-actions">
                                    <span id="rh-ideal-zone-label" style="font-size: 0.85rem; color: var(--text-muted); margin-right: 10px; display: inline-flex; align-items: center; gap: 4px;">
                                        <span style="display:inline-block; width:12px; height:12px; background:rgba(76, 175, 80, 0.2); border:1px solid rgba(76,175,80,1);"></span>
                                        Zona Ideal: {{ (int) round($config->bottom_humidity ?? 80) }}–{{ (int) round($config->top_humidity ?? 90) }}%
                                    </span>
                                </div>
                            </div>
                            <div class="chart-container">
                                <canvas id="rhRealtimeChart"></canvas>
                            </div>
                        </div>

                        <!-- 6. Event Timeline -->
                        <div class="event-card glass-card">
                            <div class="card-header">
                                <h2><i class="ph ph-clock-clockwise text-teal"></i> Kejadian Penting 24 Jam Terakhir</h2>
                            </div>
                            <div class="timeline" id="dashboard-timeline">
                                <div class="timeline-item">
                                    <div class="timeline-dot system"></div>
                                    <div class="timeline-time">Memuat...</div>
                                    <div class="timeline-desc">Menunggu data...</div>
                                </div>
                            </div>
                        </div>

                        <!-- 5. Temperature Chart (Separated) -->
                        <div class="chart-card glass-card grid-col-span-2">
                            <div class="card-header">
                                <h2>Kondisi Suhu Terkini</h2>
                            </div>
                            <div class="chart-container">
                                <canvas id="tempRealtimeChart"></canvas>
                            </div>
                        </div>
                        
                        <!-- 7. AI Insight Cards -->
                        <div class="insight-card glass-card">
                            <div class="card-header">
                                <h2><i class="ph ph-magic-wand text-blue"></i> Temuan Cerdas AI</h2>
                            </div>
                            <div class="insight-list" id="dashboard-ai-insights">
                                <div class="insight-item info">
                                    <div class="insight-icon"><i class="ph ph-info"></i></div>
                                    <div class="insight-content">
                                        <h4>Memuat Temuan...</h4>
                                        <p>Sedang menganalisis pola lingkungan.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 8. Recommendation System -->
                        <div class="recommendation-card glass-card grid-col-span-3" id="dashboard-recommendation">
                            <div class="card-header">
                                <h2><i class="ph ph-lightbulb text-warning"></i> Saran Tindakan</h2>
                            </div>
                            <div class="recommendation-content">
                                <div class="recommendation-text">
                                    <p>Sistem sedang mencari rekomendasi terbaik untuk menjaga stabilitas enclosure Anda...</p>
                                </div>
                                <div class="recommendation-actions">
                                    <button type="button" id="apply-dashboard-recommendation-btn" class="btn-primary" disabled><i class="ph ph-check"></i> Menunggu Saran</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- View: Analytics -->
                <div id="view-analytics" class="page-view">
                    <!-- Filter Bar -->
                    <div class="analytics-header glass-card">
                        <div class="filter-controls-group">
                            <!-- Filter Item: Rentang Waktu -->
                            <div class="filter-item">
                                <label for="date-start">
                                    <i class="ph ph-calendar"></i> Rentang Waktu
                                </label>
                                <div class="date-range-inputs">
                                    <div class="date-input-wrapper">
                                        <i class="ph ph-calendar-blank date-icon"></i>
                                        <input type="date" id="date-start" class="input-modern" value="{{ \Carbon\Carbon::now()->subDays(7)->format('Y-m-d') }}">
                                    </div>
                                    <span class="range-separator"><i class="ph ph-arrow-right"></i></span>
                                    <div class="date-input-wrapper">
                                        <i class="ph ph-calendar-blank date-icon"></i>
                                        <input type="date" id="date-end" class="input-modern" value="{{ \Carbon\Carbon::now()->format('Y-m-d') }}">
                                    </div>
                                </div>
                            </div>

                            <!-- Filter Item: Pilih Metrik -->
                            <div class="filter-item">
                                <label for="metric-select">
                                    <i class="ph ph-funnel"></i> Pilih Metrik
                                </label>
                                <div class="select-wrapper">
                                    <select id="metric-select" class="input-modern">
                                        <option value="all">Semua Metrik</option>
                                        <option value="humidity">Kelembapan (RH %)</option>
                                        <option value="temperature">Suhu (°C)</option>
                                        <option value="stability">Skor Stabilitas</option>
                                    </select>
                                    <i class="ph ph-caret-down select-caret"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Action Button: Ekspor Laporan -->
                        <div class="action-wrapper">
                            <button class="btn-export" id="export-report-btn">
                                <i class="ph ph-download-simple"></i>
                                <span>Ekspor Laporan</span>
                            </button>
                        </div>
                    </div>

                    <!-- Ringkasan Statistik -->
                    <div class="metrics-grid">
                        <div class="metric-card glass-card">
                            <div class="metric-header">
                                <h3>Rata-rata RH</h3>
                                <i class="ph ph-drop text-blue"></i>
                            </div>
                            <div class="metric-value" id="analytics-avg-rh">--<span class="unit">%</span></div>
                            <div class="metric-trend text-neutral" id="analytics-avg-rh-sub">Memuat...</div>
                        </div>
                        <div class="metric-card glass-card">
                            <div class="metric-header">
                                <h3>Rata-rata Suhu</h3>
                                <i class="ph ph-thermometer text-teal"></i>
                            </div>
                            <div class="metric-value" id="analytics-avg-temp">--<span class="unit">°C</span></div>
                            <div class="metric-trend text-neutral" id="analytics-avg-temp-sub">Memuat...</div>
                        </div>
                        <div class="metric-card glass-card">
                            <div class="metric-header">
                                <h3>Siklus Pengabutan</h3>
                                <i class="ph ph-cloud-rain text-blue"></i>
                            </div>
                            <div class="metric-value" id="analytics-misting-cycles">--<span class="unit">x</span></div>
                            <div class="metric-trend text-neutral" id="analytics-misting-sub">Memuat...</div>
                        </div>
                        <div class="metric-card glass-card">
                            <div class="metric-header">
                                <h3>Waktu di Range</h3>
                                <i class="ph ph-clock text-green"></i>
                            </div>
                            <div class="metric-value" id="analytics-time-range">--<span class="unit">%</span></div>
                            <div class="metric-trend text-neutral" id="analytics-range-sub">Memuat...</div>
                        </div>
                    </div>

                    <!-- Status Ringkas Historis -->
                    <div class="historical-status-bar glass-card" id="historical-status-bar">
                        <div class="status-item">
                            <i class="ph ph-drop text-teal"></i>
                            <span>Kelembapan: <strong id="hist-current-rh">--</strong><small>%</small></span>
                        </div>
                        <div class="status-item">
                            <i class="ph ph-thermometer text-blue"></i>
                            <span>Suhu: <strong id="hist-current-temp">--</strong><small>°C</small></span>
                        </div>
                        <div class="status-item" id="hist-status-humidity">
                            <i class="ph ph-minus-circle"></i>
                            <span>Status: <strong>Memuat...</strong></span>
                        </div>
                        <div class="status-item" id="hist-status-misting">
                            <i class="ph ph-cloud-rain"></i>
                            <span>Misting: <strong>--</strong></span>
                        </div>
                    </div>

                    <!-- Grafik Historis Kelembapan -->
                    <div class="chart-card glass-card mb-2">
                        <div class="card-header">
                            <h2><i class="ph ph-drop text-teal"></i> Historis Kelembapan</h2>
                            <div class="card-actions" id="historical-period-filters">
                                <button class="btn-small active" data-period="7d" data-range="7">7 Hari</button>
                                <button class="btn-small" data-period="30d" data-range="30">30 Hari</button>
                                <button class="btn-small" data-period="90d" data-range="90">90 Hari</button>
                            </div>
                        </div>
                        <span id="hist-ideal-zone-label" style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 8px; display: inline-flex; align-items: center; gap: 4px;">
                            <span style="display:inline-block; width:12px; height:12px; background:rgba(76, 175, 80, 0.2); border:1px solid rgba(76,175,80,1);"></span>
                            Zona Ideal: {{ (int) round($config->bottom_humidity ?? 80) }}–{{ (int) round($config->top_humidity ?? 90) }}%
                        </span>
                        <div class="chart-container large">
                            <canvas id="historicalHumidityChart"></canvas>
                        </div>
                    </div>

                    <!-- Grafik Historis Suhu -->
                    <div class="chart-card glass-card mb-2">
                        <div class="card-header">
                            <h2><i class="ph ph-thermometer text-blue"></i> Historis Suhu</h2>
                        </div>
                        <div class="chart-container">
                            <canvas id="historicalTempChart"></canvas>
                        </div>
                    </div>

                    <div class="dashboard-grid">
                        <!-- Distribusi Kelembapan -->
                        <div class="chart-card glass-card grid-col-span-2">
                            <div class="card-header">
                                <h2><i class="ph ph-chart-bar text-teal"></i> Distribusi Kelembapan</h2>
                            </div>
                            <div class="chart-container">
                                <canvas id="humidityDistChart"></canvas>
                            </div>
                        </div>

                        <!-- Aktivitas Pengabutan -->
                        <div class="chart-card glass-card">
                            <div class="card-header">
                                <h2><i class="ph ph-cloud-rain text-blue"></i> Aktivitas Misting</h2>
                            </div>
                            <div class="chart-container">
                                <canvas id="mistingChart"></canvas>
                            </div>
                        </div>

                        <!-- Tren Stabilitas -->
                        <div class="chart-card glass-card grid-col-span-2">
                            <div class="card-header">
                                <h2><i class="ph ph-chart-line-up text-warning"></i> Tren Skor Stabilitas</h2>
                            </div>
                            <div class="chart-container">
                                <canvas id="stabilityTrendChart"></canvas>
                            </div>
                        </div>

                        <!-- Linimasa Kejadian -->
                        <div class="event-card glass-card">
                            <div class="card-header">
                                <h2><i class="ph ph-clock-clockwise text-teal"></i> Linimasa Kejadian</h2>
                            </div>
                            <div class="timeline" id="analytics-timeline">
                                <div class="timeline-item">
                                    <div class="timeline-dot system"></div>
                                    <div class="timeline-time">Memuat...</div>
                                    <div class="timeline-desc">Menunggu data dari server</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- View: Stability Analysis -->
                <div id="view-stability" class="page-view">
                    <div class="dashboard-grid">
                        <!-- Hero Gauge -->
                        <div class="stability-hero glass-card grid-col-span-3">
                            <div class="hero-content">
                                <div class="score-gauge-container">
                                    <canvas id="scoreGaugeChart"></canvas>
                                    <div class="gauge-center">
                                        <span class="score-value" id="stab-gauge-value">--</span>
                                        <span class="score-label" id="stab-gauge-label">Memuat...</span>
                                    </div>
                                </div>
                                <div class="hero-details">
                                    <h2>Stabilitas Lingkungan</h2>
                                    <p>Sistem ini mengevaluasi kestabilan lingkungan berdasarkan kesesuaian range (Range
                                        Compliance), tingkat fluktuasi (Variability), dan durasi kondisi ideal
                                        (Stability Duration).</p>
                                    <div class="status-badge" id="stab-status-badge">
                                        <i class="ph ph-minus-circle"></i> Status: Memuat...
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Metric Cards -->
                        <div class="metric-card glass-card">
                            <div class="metric-header">
                                <h3>Kepatuhan Range (RC)</h3>
                                <i class="ph ph-target text-teal"></i>
                            </div>
                            <div class="metric-value" id="stab-rc-value">--<span class="unit">%</span></div>
                            <div class="metric-trend text-neutral" id="stab-rc-sub">Memuat...</div>
                            <div class="progress-bar-container mt-1">
                                <div class="progress-bar bg-teal" id="stab-rc-bar" style="width: 0%"></div>
                            </div>
                        </div>

                        <div class="metric-card glass-card">
                            <div class="metric-header">
                                <h3>Variabilitas</h3>
                                <i class="ph ph-wave-sine text-warning"></i>
                            </div>
                            <div class="metric-value" id="stab-var-value">--</div>
                            <div class="metric-trend text-neutral" id="stab-var-sub">Memuat...</div>
                            <div class="progress-bar-container mt-1">
                                <div class="progress-bar bg-warning" id="stab-var-bar" style="width: 0%"></div>
                            </div>
                        </div>

                        <div class="metric-card glass-card">
                            <div class="metric-header">
                                <h3>Durasi Stabilitas</h3>
                                <i class="ph ph-hourglass text-blue"></i>
                            </div>
                            <div class="metric-value" id="stab-dur-value">--<span class="unit">j</span></div>
                            <div class="metric-trend text-neutral" id="stab-dur-sub">Memuat...</div>
                            <div class="progress-bar-container mt-1">
                                <div class="progress-bar bg-blue" id="stab-dur-bar" style="width: 0%"></div>
                            </div>
                        </div>

                        <!-- Radar Chart -->
                        <div class="chart-card glass-card grid-col-span-2">
                            <div class="card-header">
                                <h2><i class="ph ph-radar text-teal"></i> Analisis Komponen Stabilitas</h2>
                            </div>
                            <div class="chart-container large">
                                <canvas id="stabilityRadarChart"></canvas>
                            </div>
                        </div>

                        <!-- Penalti & Info -->
                        <div class="metric-card glass-card">
                            <div class="metric-header">
                                <h3>Penalti Fluktuasi</h3>
                                <i class="ph ph-trend-down text-red"></i>
                            </div>
                            <div class="metric-value" id="stab-penalty-value">--<span class="unit">pts</span></div>
                            <div class="metric-trend text-neutral" id="stab-penalty-sub">Memuat...</div>
                            <div class="progress-bar-container mt-1">
                                <div class="progress-bar bg-red" id="stab-penalty-bar" style="width: 0%"></div>
                            </div>
                        </div>

                        <!-- Stability History Chart -->
                        <div class="chart-card glass-card grid-col-span-3">
                            <div class="card-header">
                                <h2><i class="ph ph-chart-line text-green"></i> Riwayat Skor Stabilitas</h2>
                                <div class="card-actions" id="stability-period-filters">
                                    <button class="btn-small active" data-period="4w">4 Minggu</button>
                                    <button class="btn-small" data-period="12w">12 Minggu</button>
                                </div>
                            </div>
                            <div class="chart-container">
                                <canvas id="stabilityHistoryChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- User Settings Modal -->
    <div id="user-settings-modal" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center; backdrop-filter: blur(5px);">
        <div class="modal-content glass-card" style="width: 90%; max-width: 450px; padding: 2.5rem; position: relative;">
            <button id="close-settings-btn" class="btn-icon" style="position: absolute; top: 1.5rem; right: 1.5rem;"><i class="ph ph-x"></i></button>
            <h2 style="margin-bottom: 1.5rem; font-size: 1.5rem;"><i class="ph ph-user-gear text-teal"></i> Pengaturan Akun</h2>
            <form id="user-settings-form">
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; color: var(--text-secondary); font-size: 0.9rem;">Nama Pengguna</label>
                    <div class="input-group" style="margin-bottom: 0;">
                        <i class="ph ph-user"></i>
                        <input type="text" value="Admin">
                    </div>
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; color: var(--text-secondary); font-size: 0.9rem;">Email</label>
                    <div class="input-group" style="margin-bottom: 0;">
                        <i class="ph ph-envelope"></i>
                        <input type="email" value="admin@herpetology.com">
                    </div>
                </div>
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; color: var(--text-secondary); font-size: 0.9rem;">Kata Sandi Baru</label>
                    <div class="input-group" style="margin-bottom: 0;">
                        <i class="ph ph-lock"></i>
                        <input type="password" placeholder="Kosongkan jika tidak diubah">
                    </div>
                </div>
                <button type="submit" class="btn-primary" id="save-user-settings-btn">
                    <i class="ph ph-check"></i> <span>Simpan Perubahan</span>
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const settingsBtn = document.getElementById('open-settings-btn');
        const settingsModal = document.getElementById('user-settings-modal');
        const closeSettingsBtn = document.getElementById('close-settings-btn');

        if(settingsBtn && settingsModal && closeSettingsBtn) {
            settingsBtn.addEventListener('click', (e) => {
                e.preventDefault();
                settingsModal.style.display = 'flex';
            });

            closeSettingsBtn.addEventListener('click', () => {
                settingsModal.style.display = 'none';
            });

            settingsModal.addEventListener('click', (e) => {
                if (e.target === settingsModal) {
                    settingsModal.style.display = 'none';
                }
            });
        }
    });
</script>
<script src="{{ asset('assets/js/api.js') }}?v={{ time() }}"></script>
<script src="{{ asset('assets/js/app.js') }}?v={{ time() }}"></script>
@endpush