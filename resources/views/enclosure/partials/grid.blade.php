{{-- resources/views/enclosure/partials/grid.blade.php --}}
<div class="enclosure-grid">   
    @foreach ($enclosures as $enclosure)
        @php
            // Ambil data sensor terbaru
            $latestSensor = $enclosure->sensorLogs->first();
            $temperature = $latestSensor ? number_format($latestSensor->temperature, 1) : '--';
            $humidity = $latestSensor ? number_format($latestSensor->humidity, 0) : '--';
            
            // Ambil skor stabilitas terbaru
            $latestStability = $enclosure->stabilityScores->first();
            $stabilityStatus = $latestStability->status ?? null;
            
            // Tentukan badge status
            if ($stabilityStatus === 'Optimal' || $stabilityStatus === 'stable') {
                $badgeClass = 'stable';
                $badgeIcon = 'ph-check-circle';
                $badgeText = 'Sangat Stabil';
            } elseif ($stabilityStatus === 'Moderate' || $stabilityStatus === 'Tidak Stabil' || $stabilityStatus === 'warning') {
                $badgeClass = 'warning';
                $badgeIcon = 'ph-warning';
                $badgeText = $stabilityStatus; // Tampilkan status aslinya
            } elseif ($stabilityStatus === 'Kritis' || $stabilityStatus === 'critical') {
                $badgeClass = 'critical';
                $badgeIcon = 'ph-warning-octagon';
                $badgeText = 'Kritis';
            } else {
                $badgeClass = 'no-data';
                $badgeIcon = 'ph-minus-circle';
                $badgeText = 'Belum ada data';
            }

            // Online status
            $isOnline = $enclosure->isOnline();
        @endphp

        <a href="{{ route('dashboard', $enclosure->id) }}" class="text-decoration-none">
            <div class="enclosure-card glass-card position-relative">

                <button
                    type="button"
                    class="btn-icon edit-enclosure-btn"
                    data-id="{{ $enclosure->id }}"
                    data-name="{{ $enclosure->name }}"
                    data-habitat="{{ $enclosure->target_habitat }}"
                    data-hewan="{{ $enclosure->jenis_hewan }}"
                    style="position: absolute; top: 15px; right: 15px; z-index: 10;"
                    title="Pengaturan Kandang"
                >
                    <i class="ph ph-gear"></i>
                </button>

                <div class="enclosure-icon" style="overflow: hidden; display: flex; align-items: center; justify-content: center;">
                    @if($enclosure->image_path)
                        <img src="{{ asset('storage/' . $enclosure->image_path) }}" alt="{{ $enclosure->name }}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                    @else
                        <i class="ph ph-drop text-teal"></i>
                    @endif
                </div>

                <div class="enclosure-info">
                    <h3>{{ $enclosure->name }}</h3>

                    <div class="quick-stats">
                        <span class="{{ $latestSensor ? '' : 'text-muted' }}">
                            <i class="ph ph-thermometer text-blue"></i> {{ $temperature }}{{ $latestSensor ? '°C' : '' }}
                        </span>
                        <span class="{{ $latestSensor ? '' : 'text-muted' }}">
                            <i class="ph ph-drop text-teal"></i> {{ $humidity }}{{ $latestSensor ? '%' : '' }}
                        </span>
                    </div>

                    <div class="enclosure-meta">
                        <span class="online-indicator {{ $isOnline ? 'online' : 'offline' }}">
                            <span class="dot-sm {{ $isOnline ? 'pulse-green' : '' }}"></span>
                            {{ $isOnline ? 'Online' : 'Offline' }}
                        </span>
                    </div>

                    <span class="status-badge {{ $badgeClass }}">
                        <i class="ph {{ $badgeIcon }}"></i> {{ $badgeText }}
                    </span>
                </div>

            </div>
        </a>
    @endforeach

    <div class="enclosure-card glass-card add-new" id="btn-add-enclosure" style="cursor: pointer;">
        <div class="enclosure-icon">
            <i class="ph ph-plus text-neutral"></i>
        </div>
        <div class="enclosure-info">
            <h3>Tambah Kandang Baru</h3>
        </div>
    </div>
</div>
