{{-- resources/views/dashboard/partials/topbar.blade.php --}}
<header class="topbar">
    <div class="header-left">
        <button class="menu-toggle d-mobile"><i class="ph ph-list"></i></button>
        <h1 id="page-title">{{ $enclosureName ?? 'Dashboard' }}</h1>
    </div>
    <div class="header-right">
        <div class="status-indicator" id="system-status-indicator">
            <span class="dot" id="system-status-dot"></span>
            <span class="status-text" id="system-status-text">Memuat...</span>
        </div>
        <button id="portrait-toggle" class="btn-icon" title="Mode Potret">
            <i class="ph ph-device-mobile"></i>
        </button>
        <button id="theme-toggle" class="btn-icon" title="Ganti Tema">
            <i class="ph ph-moon"></i>
        </button>
        <div style="position: relative;" id="notification-wrapper">
            <button class="notification-btn btn-icon" id="bell-icon-btn">
                <i class="ph ph-bell"></i>
                <span class="badge" id="notification-badge" style="display: none;">0</span>
            </button>
            <div id="notification-panel" style="display: none; position: absolute; right: 0; top: 120%; width: 320px; z-index: 1000; padding: 1rem; max-height: 400px; overflow-y: auto; box-shadow: var(--shadow-soft); background-color: var(--bg-main); border: 1px solid var(--border-light); border-radius: var(--border-radius-lg);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 0.5rem;">
                    <h3 style="font-size: 1.1rem; margin: 0;"><i class="ph ph-bell-ringing"></i> Notifikasi</h3>
                    <button id="clear-notifications-btn" style="background: none; border: none; color: var(--teal); cursor: pointer; font-size: 0.8rem; font-weight: bold;">Tandai Dibaca</button>
                </div>
                <div id="notification-list" style="display: flex; flex-direction: column; gap: 0.8rem;">
                    <!-- Notifikasi masuk via JS -->
                </div>
            </div>
        </div>
    </div>
</header>
