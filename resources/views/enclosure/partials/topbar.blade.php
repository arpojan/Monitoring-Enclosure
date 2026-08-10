{{-- resources/views/enclosure/partials/topbar.blade.php --}}
<!-- Top Action Bar -->
<div class="enclosure-topbar">
    <button id="enclosure-theme-toggle" class="btn-icon-pill" title="Ganti Tema">
        <i class="ph ph-moon"></i>
        <span>Tema</span>
    </button>
    <form action="{{ route('logout') }}" method="POST" style="margin: 0;">
        @csrf
        <button type="submit" class="btn-icon-pill btn-logout" title="Keluar">
            <i class="ph ph-sign-out"></i>
            <span>Keluar</span>
        </button>
    </form>
</div>
