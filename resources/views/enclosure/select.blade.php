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

{{-- Scripts --}}
@include('enclosure.scripts.inline')
