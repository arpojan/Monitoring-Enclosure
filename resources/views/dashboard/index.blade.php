{{-- resources/views/dashboard/index.blade.php --}}
{{-- Orchestrator: Menyusun semua partial dan view menjadi satu halaman dashboard. --}}
@extends('layouts.app')

@section('content')
<div id="app-screen" class="view active" data-enclosure-id="{{ request()->route('id', 1) }}" style="display: flex;">
    {{-- Overlay gelap di belakang sidebar mobile --}}
    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    {{-- Sidebar Navigation --}}
    @include('dashboard.partials.sidebar')

    {{-- Main Content --}}
    <main class="main-content">
        {{-- Header / Topbar --}}
        @include('dashboard.partials.topbar')

        {{-- Views Container --}}
        <div class="views-container">
            @include('dashboard.views.dashboard')
            @include('dashboard.views.analytics')
            @include('dashboard.views.stability')
        </div>
    </main>

    {{-- User Settings Modal --}}
    @include('dashboard.partials.settings-modal')
</div>
@endsection

{{-- Inline Scripts --}}
@include('dashboard.scripts.inline')