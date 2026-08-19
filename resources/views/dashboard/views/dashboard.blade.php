{{-- resources/views/dashboard/views/dashboard.blade.php --}}
<!-- View: Dashboard -->
<div id="view-dashboard" class="page-view active">

    {{-- Banner Onboarding: muncul jika device belum pernah konek --}}
    <div id="provisioning-banner" style="display: none; background: linear-gradient(135deg, rgba(38,198,218,0.12), rgba(38,198,218,0.05)); border: 1px solid var(--accent-teal); border-radius: 12px; padding: 14px 18px; margin-bottom: 1.2rem; display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
        <div style="font-size: 2rem; flex-shrink: 0;">📡</div>
        <div style="flex: 1; min-width: 200px;">
            <p style="margin: 0 0 2px 0; font-weight: 700; color: var(--text-primary); font-size: 0.9rem;">Perangkat ESP32 belum terhubung</p>
            <p style="margin: 0; font-size: 0.78rem; color: var(--text-secondary);">Hubungkan ESP32 ke kandang ini lewat WiFi Provisioning untuk mulai memantau suhu &amp; kelembapan secara real-time.</p>
        </div>
        <a href="{{ route('enclosure.select') }}" style="flex-shrink: 0; display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; background: var(--accent-teal); color: #fff; border-radius: 8px; font-size: 0.82rem; font-weight: 600; text-decoration: none; white-space: nowrap;">
            <i class="ph ph-wifi-high"></i> Setup Perangkat
        </a>
    </div>

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
    <div class="insight-summary-card glass-card">
        <h3 style="margin-bottom: 6px; display: flex; align-items: center; gap: 6px; font-size: 0.82rem; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted);">
            <i class="ph ph-brain text-teal"></i> Interpretasi Keseluruhan
        </h3>
        <p id="dashboard-insight-summary" style="color: var(--text-secondary); line-height: 1.5; font-size: 0.88rem; margin: 0;">
            Menganalisis kondisi lingkungan enclosure...
        </p>
    </div>

    <!-- Parameter Kontrol Misting -->
    <div class="control-config-card glass-card">
        <div class="card-header">
            <h2><i class="ph ph-sliders-horizontal text-teal"></i> Parameter Kontrol Misting</h2>
            {{-- Indicator: muncul saat user sedang mengedit field (dirty state) --}}
            <span
                id="form-dirty-indicator"
                title="Form sedang diedit. Polling data ditangguhkan sementara."
                style="display: none; align-items: center; gap: 4px; font-size: 0.72rem; font-weight: 600; color: var(--warning); background: rgba(233,196,106,0.15); border: 1px solid var(--warning); border-radius: 20px; padding: 2px 8px; white-space: nowrap;"
            >
                <i class="ph ph-pencil-simple" style="font-size: 0.85rem;"></i> Mengedit
            </span>
        </div>
        <form id="control-parameters-form" class="control-parameters-form">
            <div>
                <label style="display:block; margin-bottom:4px; color:var(--text-secondary); font-size:0.78rem; font-weight:600; text-transform:uppercase; letter-spacing:0.4px;">RH Min (%)</label>
                <div class="input-group" style="margin-bottom: 0;">
                    <i class="ph ph-caret-double-down" style="color: var(--accent-teal);"></i>
                    <input
                        type="number"
                        name="bottom_humidity"
                        id="param-bottom-humidity"
                        min="0"
                        max="100"
                        step="1"
                        placeholder="mis. 80"
                        value="{{ old('bottom_humidity', (int) round($config->bottom_humidity ?? 0)) }}"
                    >
                </div>
            </div>
            
            <div>
                <label style="display:block; margin-bottom:4px; color:var(--text-secondary); font-size:0.78rem; font-weight:600; text-transform:uppercase; letter-spacing:0.4px;">RH Max (%)</label>
                <div class="input-group" style="margin-bottom: 0;">
                    <i class="ph ph-caret-double-up" style="color: var(--accent-teal);"></i>
                    <input
                        type="number"
                        name="top_humidity"
                        id="param-top-humidity"
                        min="0"
                        max="100"
                        step="1"
                        placeholder="mis. 90"
                        value="{{ old('top_humidity', (int) round($config->top_humidity ?? 0)) }}"
                    >
                </div>
            </div>
            
            <div>
                <label style="display:block; margin-bottom:4px; color:var(--text-secondary); font-size:0.78rem; font-weight:600; text-transform:uppercase; letter-spacing:0.4px;">Durasi (detik)</label>
                <div class="input-group" style="margin-bottom: 0;">
                    <i class="ph ph-timer" style="color: var(--accent-teal);"></i>
                    <input
                        type="number"
                        min="1"
                        max="300"
                        step="1"
                        id="param-misting-duration"
                        placeholder="mis. 10"
                    >
                </div>
            </div>
            
            <div style="display: flex; gap: 10px; align-items: center;">
                <button type="submit" class="btn-primary" id="save-control-parameters-btn" style="flex: 1;">
                    <i class="ph ph-paper-plane-tilt"></i> Simpan ke ESP32
                </button>
                <button type="button" class="btn-primary" id="trigger-manual-mist-btn" style="flex: 1; background-color: var(--accent-blue); border-color: var(--accent-blue); color: #ffffff;" title="Paksa pompa misting menyala sekarang">
                    <i class="ph ph-drop"></i> Semprot Manual
                </button>
            </div>
            <p style="grid-column: 1 / -1; color: var(--text-muted); font-size: 0.75rem; margin: 4px 0 0 0; display: flex; align-items: flex-start; gap: 5px;">
                <i class="ph ph-info-circle text-teal" style="font-size: 0.95rem; flex-shrink: 0; margin-top: 1px;"></i>
                Web menyimpan konfigurasi. ESP32 mengambil dan mengeksekusi misting secara lokal.
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
        
        <!-- 7. DSS Insight Cards -->
        <div class="metric-card glass-card" style="display: flex; flex-direction: column;">
            <div class="section-header" style="margin-bottom: 1rem;">
                <h2><i class="ph ph-magic-wand text-blue"></i> Temuan Analisis DSS</h2>
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
            <div class="section-header" style="margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center;">
                <h2><i class="ph ph-lightbulb text-warning"></i> Saran Tindakan DSS (Human-in-the-Loop)</h2>
                {{-- Tombol untuk memicu analisis DSS secara manual --}}
                <button
                    type="button"
                    id="run-dss-analysis-btn"
                    class="btn-small"
                    title="Jalankan analisis DSS sekarang untuk mendapatkan skor stabilitas dan rekomendasi terbaru."
                    style="display: inline-flex; align-items: center; gap: 5px;"
                >
                    <i class="ph ph-brain"></i> Analisis Sekarang
                </button>
            </div>
            <div class="recommendation-content">
                <div class="recommendation-text">
                    <p id="dashboard-recommendation-text">Sistem sedang mencari rekomendasi terbaik untuk menjaga stabilitas enclosure Anda...</p>
                </div>
                <div class="recommendation-actions" style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <button type="button" id="apply-dashboard-recommendation-btn" class="btn-primary" disabled>
                        <i class="ph ph-check"></i> Terapkan
                    </button>
                    <button type="button" id="reject-dashboard-recommendation-btn" class="btn-secondary" disabled
                        style="background: transparent; border: 1px solid var(--danger); color: var(--danger); padding: 8px 16px; border-radius: 8px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; font-weight: 500;">
                        <i class="ph ph-x"></i> Tolak
                    </button>
                </div>
                <p style="margin:0; font-size: 0.75rem; color: var(--text-muted);">
                    DSS hanya memberi saran. Parameter ESP32 hanya berubah setelah Anda klik <strong>Terapkan</strong>.
                </p>
            </div>
        </div>
    </div>
</div>
