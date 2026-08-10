{{-- resources/views/dashboard/views/analytics.blade.php --}}
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
        <div class="action-wrapper" style="position: relative;">
            <button class="btn-export" id="export-report-btn" onclick="toggleExportMenu(event)">
                <i class="ph ph-download-simple"></i>
                <span>Ekspor Laporan</span>
                <i class="ph ph-caret-down" style="margin-left: 4px;"></i>
            </button>
            <div id="export-dropdown" style="display: none; position: absolute; right: 0; top: calc(100% + 8px); padding: 0.5rem; z-index: 100; min-width: 160px; flex-direction: column; gap: 4px; box-shadow: var(--shadow-soft); background-color: var(--bg-main); border: 1px solid var(--border-light); border-radius: var(--border-radius-sm);">
                <button class="btn-secondary" style="width: 100%; text-align: left; padding: 0.6rem 1rem; justify-content: flex-start; border: none; background: transparent; color: var(--text-primary); transition: background var(--transition-fast); border-radius: 6px;" onmouseover="this.style.background='var(--bg-transparent-hover)'" onmouseout="this.style.background='transparent'" onclick="triggerExport('pdf')">
                    <i class="ph ph-file-pdf text-danger" style="margin-right: 8px;"></i> Format PDF
                </button>
                <button class="btn-secondary" style="width: 100%; text-align: left; padding: 0.6rem 1rem; justify-content: flex-start; border: none; background: transparent; color: var(--text-primary); transition: background var(--transition-fast); border-radius: 6px;" onmouseover="this.style.background='var(--bg-transparent-hover)'" onmouseout="this.style.background='transparent'" onclick="triggerExport('csv')">
                    <i class="ph ph-file-csv text-green" style="margin-right: 8px;"></i> Format CSV
                </button>
            </div>
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
