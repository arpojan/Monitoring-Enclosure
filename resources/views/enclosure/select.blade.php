{{-- resources/views/enclosure/select.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="view active">
    {{-- Top Action Bar --}}
    @include('enclosure.partials.topbar')

    <div class="selection-container">
        <div class="selection-header">
            <div class="login-logo">
                <i class="ph ph-leaf text-green"></i>
            </div>
            <h2>RAP Enclosure</h2>
            <p>Pilih kandang yang ingin Anda pantau hari ini</p>
        </div>

        {{-- Success message --}}
        @if (session('success'))
            <div class="alert-success" style="margin-bottom: 1.5rem;">
                <i class="ph ph-check-circle"></i> {{ session('success') }}
            </div>
        @endif

        {{-- Enclosure Grid --}}
        @include('enclosure.partials.grid')
    </div>
    <div class="login-bg-decoration"></div>

    {{-- Modals --}}
    @include('enclosure.partials.add-modal')
    @include('enclosure.partials.edit-modal')
</div>
@endsection

{{-- Auto-buka modal edit setelah regenerate key --}}
@if(session('regenerated_enclosure_id'))
<script>
document.addEventListener('DOMContentLoaded', () => {
    const targetId = '{{ session('regenerated_enclosure_id') }}';
    const btn = document.querySelector(`.edit-enclosure-btn[data-id="${targetId}"]`);
    if (btn) {
        // Trigger klik tombol edit kandang yang key-nya baru di-regenerate
        btn.click();
        // Scroll ke section key setelah modal terbuka
        setTimeout(() => {
            const keyEl = document.getElementById('display-device-key');
            if (keyEl) keyEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }, 350);
    }
});
</script>
@endif

{{-- Scripts --}}
@include('enclosure.scripts.inline')
