{{-- resources/views/dashboard/views/stability.blade.php --}}
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
