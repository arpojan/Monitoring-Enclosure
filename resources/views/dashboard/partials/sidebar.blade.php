{{-- resources/views/dashboard/partials/sidebar.blade.php --}}
<nav class="sidebar glass-card">
    <div class="sidebar-header">
        <i class="ph ph-leaf text-green"></i>
        <span class="brand">RAP Enclosure</span>
        <button class="menu-toggle d-mobile"><i class="ph ph-x"></i></button>
    </div>
    <ul class="nav-links">
        <!-- Navigasi Internal di dalam Dashboard -->
        <li class="active" data-target="dashboard">
            <i class="ph ph-squares-four"></i><span>Dashboard</span>
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
            @php
                $avatarPath = 'avatars/user_' . auth()->id() . '.jpg';
                $avatarUrl = file_exists(public_path($avatarPath)) ? asset($avatarPath) . '?v=' . time() : 'https://ui-avatars.com/api/?name=' . urlencode(auth()->user()->name) . '&background=00b4d8&color=fff';
            @endphp
            <img src="{{ $avatarUrl }}" alt="User" style="object-fit: cover;">
            <div class="user-info">
                <span class="user-name">{{ auth()->user()->name }}</span>
                <span class="user-role">{{ auth()->user()->email }}</span>
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
