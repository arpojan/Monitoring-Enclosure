<!-- resources/views/auth/login.blade.php -->
@extends('layouts.app')

@section('content')
<div class="view active" style="flex-direction: column; align-items: center; justify-content: center;">
    <!-- Theme Toggle -->
    <div class="enclosure-topbar" style="position: absolute; top: 0; right: 0;">
        <button id="login-theme-toggle" class="btn-icon-pill" title="Ganti Tema">
            <i class="ph ph-moon"></i>
            <span>Tema</span>
        </button>
    </div>

    <div class="login-container">
        <div class="login-card glass-card">
            <div class="login-logo">
                <i class="ph ph-leaf text-green"></i>
            </div>
            <h2>Sistem Kandang Pintar</h2>
            <p>Sistem Pendukung Keputusan</p>
            
            @if ($errors->any())
                <div class="alert-error">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Ubah action mengarah ke route login -->
            <form action="{{ route('login.post') }}" method="POST">
                @csrf
                <div class="input-group">
                    <i class="ph ph-envelope"></i>
                    <input type="email" name="email" placeholder="Email" required value="{{ old('email') }}">
                </div>
                <div class="input-group">
                    <i class="ph ph-lock"></i>
                    <input type="password" name="password" placeholder="Kata Sandi" required>
                </div>
                <button type="submit" class="btn-primary">Masuk</button>
            </form>
            <div class="login-footer">
                <a href="#">Lupa kata sandi?</a>
                <div class="login-register-link">
                    <span>Belum punya akun?</span>
                    <a href="{{ route('register') }}" class="register-link">Buat Akun</a>
                </div>
            </div>
        </div>
        <div class="login-bg-decoration"></div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const themeToggleBtn = document.getElementById('login-theme-toggle');
    const themeIcon = themeToggleBtn ? themeToggleBtn.querySelector('i') : null;

    function setTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('theme', theme);
        if (themeIcon) {
            themeIcon.className = theme === 'dark' ? 'ph ph-sun' : 'ph ph-moon';
        }
        const metaThemeColor = document.querySelector('meta[name="theme-color"]');
        if (metaThemeColor) {
            metaThemeColor.setAttribute('content', theme === 'dark' ? '#0c1411' : '#f0f4f1');
        }
    }

    const savedTheme = localStorage.getItem('theme') || 'light';
    setTheme(savedTheme);

    if (themeToggleBtn) {
        themeToggleBtn.addEventListener('click', () => {
            const current = document.documentElement.getAttribute('data-theme');
            setTheme(current === 'dark' ? 'light' : 'dark');
        });
    }
});
</script>
@endpush