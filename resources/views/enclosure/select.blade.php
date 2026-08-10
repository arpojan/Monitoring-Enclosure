<!-- resources/views/enclosure/select.blade.php -->
@extends('layouts.app')

@section('content')
<div class="view active">
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
    </div>
    <div class="login-bg-decoration"></div>

    <!-- Add Enclosure Modal -->
    <div id="add-enclosure-modal" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center; backdrop-filter: blur(5px);">
        <div class="modal-content glass-card" style="width: 90%; max-width: 400px; padding: 2rem; position: relative;">
            <button type="button" id="close-add-modal" class="btn-icon" style="position: absolute; top: 1.5rem; right: 1.5rem;"><i class="ph ph-x"></i></button>
            <h2 style="margin-bottom: 1.5rem; font-size: 1.5rem;"><i class="ph ph-plus-circle text-teal"></i> Tambah Kandang</h2>
            <form action="{{ route('enclosure.select.create') }}" method="POST" id="add-enclosure-form" enctype="multipart/form-data">
                @csrf
                
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; color: var(--text-secondary); font-size: 0.9rem;">Nama Kandang</label>
                    <div class="input-group" style="margin-bottom: 0;">
                        <i class="ph ph-tag"></i>
                        <input type="text" name="name" id="add-enclosure-name" style="width: 100%;" required>
                    </div>
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; color: var(--text-secondary); font-size: 0.9rem;">Kategori Habitat</label>
                    <div class="input-group" style="margin-bottom: 0; padding: 0;">
                        <i class="ph ph-mountains" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--accent-teal); font-size: 1.2rem; z-index: 10;"></i>
                        <select name="target_habitat" id="add-enclosure-habitat" style="width: 100%; background: transparent; border: none; padding: 0.8rem 1rem 0.8rem 2.8rem; font-size: 1rem; color: var(--text-primary); cursor: pointer; appearance: none; outline: none; box-shadow: none;">
                            <option value="">-- Pilih Habitat --</option>
                            @foreach(array_keys($animalKnowledgeBase) as $habitat)
                                <option value="{{ $habitat }}">{{ $habitat }}</option>
                            @endforeach
                        </select>
                        <i class="ph ph-caret-down" style="position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); pointer-events: none;"></i>
                    </div>
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; color: var(--text-secondary); font-size: 0.9rem;">Jenis Hewan</label>
                    <div class="input-group" style="margin-bottom: 0; padding: 0;">
                        <i class="ph ph-bug" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--accent-teal); font-size: 1.2rem; z-index: 10;"></i>
                        <select name="jenis_hewan" id="add-enclosure-hewan" style="width: 100%; background: transparent; border: none; padding: 0.8rem 1rem 0.8rem 2.8rem; font-size: 1rem; color: var(--text-primary); cursor: pointer; appearance: none; outline: none; box-shadow: none;" disabled>
                            <option value="">-- Pilih Hewan --</option>
                        </select>
                        <i class="ph ph-caret-down" style="position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); pointer-events: none;"></i>
                    </div>
                    <small id="add-animal-hint" style="display: none; margin-top: 0.5rem; color: var(--info); font-size: 0.8rem; font-weight: 500;"><i class="ph ph-info-circle"></i> <span></span></small>
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; color: var(--text-secondary); font-size: 0.9rem;">Foto/Gambar Kandang</label>
                    <div class="file-upload-wrapper" style="position: relative; width: 100%;">
                        <div class="input-group" style="margin-bottom: 0;">
                            <i class="ph ph-image"></i>
                            <input type="text" id="add-file-name-display" placeholder="Pilih file gambar..." readonly style="width: 100%; cursor: pointer; text-overflow: ellipsis;">
                        </div>
                        <input type="file" name="image" id="add-enclosure-image-input" accept="image/*" style="font-size: 100px; position: absolute; left: 0; top: 0; opacity: 0; cursor: pointer; height: 100%; width: 100%;">
                    </div>
                    <small style="display: block; margin-top: 0.5rem; color: var(--text-muted); font-size: 0.8rem;">Format: JPG, PNG, WEBP. Maks 4MB.</small>
                </div>
                <button type="submit" class="btn-primary" style="width: 100%;" id="create-enclosure-btn">
                    <i class="ph ph-plus"></i> <span>Buat Kandang</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Edit Enclosure Modal -->
    <div id="edit-enclosure-modal" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center; backdrop-filter: blur(5px);">
        <div class="modal-content glass-card" style="width: 90%; max-width: 400px; padding: 2rem; position: relative;">
            <button type="button" id="close-edit-modal" class="btn-icon" style="position: absolute; top: 1.5rem; right: 1.5rem;"><i class="ph ph-x"></i></button>
            <h2 style="margin-bottom: 1.5rem; font-size: 1.5rem;"><i class="ph ph-pencil text-teal"></i> Pengaturan Kandang</h2>
            <form action="{{ route('enclosure.select.post') }}" method="POST" id="edit-enclosure-form" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="enclosure_id" id="edit-enclosure-id" value="">
                
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; color: var(--text-secondary); font-size: 0.9rem;">Nama Kandang</label>
                    <div class="input-group" style="margin-bottom: 0;">
                        <i class="ph ph-tag"></i>
                        <input type="text" name="name" id="edit-enclosure-name" style="width: 100%;" required>
                    </div>
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; color: var(--text-secondary); font-size: 0.9rem;">Kategori Habitat</label>
                    <div class="input-group" style="margin-bottom: 0; padding: 0;">
                        <i class="ph ph-mountains" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--accent-teal); font-size: 1.2rem; z-index: 10;"></i>
                        <select name="target_habitat" id="edit-enclosure-habitat" style="width: 100%; background: transparent; border: none; padding: 0.8rem 1rem 0.8rem 2.8rem; font-size: 1rem; color: var(--text-primary); cursor: pointer; appearance: none; outline: none; box-shadow: none;">
                            <option value="">-- Pilih Habitat --</option>
                            @foreach(array_keys($animalKnowledgeBase) as $habitat)
                                <option value="{{ $habitat }}">{{ $habitat }}</option>
                            @endforeach
                        </select>
                        <i class="ph ph-caret-down" style="position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); pointer-events: none;"></i>
                    </div>
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; color: var(--text-secondary); font-size: 0.9rem;">Jenis Hewan</label>
                    <div class="input-group" style="margin-bottom: 0; padding: 0;">
                        <i class="ph ph-bug" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--accent-teal); font-size: 1.2rem; z-index: 10;"></i>
                        <select name="jenis_hewan" id="edit-enclosure-hewan" style="width: 100%; background: transparent; border: none; padding: 0.8rem 1rem 0.8rem 2.8rem; font-size: 1rem; color: var(--text-primary); cursor: pointer; appearance: none; outline: none; box-shadow: none;" disabled>
                            <option value="">-- Pilih Hewan --</option>
                        </select>
                        <i class="ph ph-caret-down" style="position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); pointer-events: none;"></i>
                    </div>
                    <small id="animal-hint" style="display: none; margin-top: 0.5rem; color: var(--info); font-size: 0.8rem; font-weight: 500;"><i class="ph ph-info-circle"></i> <span></span></small>
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; color: var(--text-secondary); font-size: 0.9rem;">Foto/Gambar Kandang</label>
                    <div class="file-upload-wrapper" style="position: relative; width: 100%;">
                        <div class="input-group" style="margin-bottom: 0;">
                            <i class="ph ph-image"></i>
                            <input type="text" id="file-name-display" placeholder="Pilih file gambar..." readonly style="width: 100%; cursor: pointer; text-overflow: ellipsis;">
                        </div>
                        <input type="file" name="image" id="enclosure-image-input" accept="image/*" style="font-size: 100px; position: absolute; left: 0; top: 0; opacity: 0; cursor: pointer; height: 100%; width: 100%;">
                    </div>
                    <small style="display: block; margin-top: 0.5rem; color: var(--text-muted); font-size: 0.8rem;">Format: JPG, PNG, WEBP. Maks 4MB.</small>
                </div>
                <button type="submit" class="btn-primary" style="width: 100%;" id="save-enclosure-btn">
                    <i class="ph ph-check"></i> <span>Simpan Perubahan</span>
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {

    // ==============================
    // Theme Toggle
    // ==============================
    const themeToggleBtn = document.getElementById('enclosure-theme-toggle');
    const themeIcon = themeToggleBtn?.querySelector('i');

    function setTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('theme', theme);

        if (themeIcon) {
            themeIcon.className = theme === 'dark'
                ? 'ph ph-sun'
                : 'ph ph-moon';
        }
    }

    const savedTheme = localStorage.getItem('theme') || 'light';
    setTheme(savedTheme);

    themeToggleBtn?.addEventListener('click', () => {
        const current = document.documentElement.getAttribute('data-theme');

        setTheme(current === 'dark' ? 'light' : 'dark');
    });



    // ==============================
    // Modal Edit Enclosure
    // ==============================
    const editBtns = document.querySelectorAll('.edit-enclosure-btn');
    const editModal = document.getElementById('edit-enclosure-modal');
    const closeEditBtn = document.getElementById('close-edit-modal');

    const inputName = document.getElementById('edit-enclosure-name');
    const inputId = document.getElementById('edit-enclosure-id');
    const selectHabitat = document.getElementById('edit-enclosure-habitat');
    const selectHewan = document.getElementById('edit-enclosure-hewan');
    const animalHint = document.getElementById('animal-hint');
    const animalHintText = animalHint.querySelector('span');

    // Parse animal KB from PHP to JS
    const animalKB = @json($animalKnowledgeBase ?? []);

    function updateHewanDropdown(habitatValue, selectedHewan, selectHewanEl, hintEl, hintTextEl) {
        selectHewanEl.innerHTML = '<option value="">-- Pilih Hewan --</option>';
        hintEl.style.display = 'none';
        
        if (habitatValue && animalKB[habitatValue]) {
            selectHewanEl.disabled = false;
            Object.keys(animalKB[habitatValue]).forEach(species => {
                const option = document.createElement('option');
                option.value = species;
                option.textContent = species;
                if (species === selectedHewan) {
                    option.selected = true;
                }
                selectHewanEl.appendChild(option);
            });
            updateAnimalHint(habitatValue, selectedHewan, hintEl, hintTextEl);
        } else {
            selectHewanEl.disabled = true;
        }
    }

    function updateAnimalHint(habitat, hewan, hintEl, hintTextEl) {
        if (habitat && hewan && animalKB[habitat] && animalKB[habitat][hewan]) {
            const config = animalKB[habitat][hewan];
            hintTextEl.textContent = `Batas Ideal RH: ${config.min}% - ${config.max}%`;
            hintEl.style.display = 'block';
        } else {
            hintEl.style.display = 'none';
        }
    }

    // Modal Edit Listeners
    selectHabitat.addEventListener('change', (e) => {
        updateHewanDropdown(e.target.value, '', selectHewan, animalHint, animalHintText);
    });

    selectHewan.addEventListener('change', (e) => {
        updateAnimalHint(selectHabitat.value, e.target.value, animalHint, animalHintText);
    });

    editBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();

            const id = btn.dataset.id;
            const name = btn.dataset.name;
            const habitat = btn.dataset.habitat;
            const hewan = btn.dataset.hewan;

            inputId.value = id;
            inputName.value = name;
            
            // Set dropdowns
            selectHabitat.value = habitat || '';
            updateHewanDropdown(habitat || '', hewan || '', selectHewan, animalHint, animalHintText);

            editModal.style.display = 'flex';
        });
    });

    closeEditBtn?.addEventListener('click', () => {
        editModal.style.display = 'none';
    });

    editModal?.addEventListener('click', (e) => {
        if (e.target === editModal) {
            editModal.style.display = 'none';
        }
    });

    // ==============================
    // Modal Add Enclosure
    // ==============================
    const btnAddEnclosure = document.getElementById('btn-add-enclosure');
    const addModal = document.getElementById('add-enclosure-modal');
    const closeAddBtn = document.getElementById('close-add-modal');
    const addHabitat = document.getElementById('add-enclosure-habitat');
    const addHewan = document.getElementById('add-enclosure-hewan');
    const addHint = document.getElementById('add-animal-hint');
    const addHintText = addHint?.querySelector('span');

    btnAddEnclosure?.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        
        // Reset form
        document.getElementById('add-enclosure-form').reset();
        document.getElementById('add-file-name-display').value = '';
        updateHewanDropdown('', '', addHewan, addHint, addHintText);

        addModal.style.display = 'flex';
    });

    closeAddBtn?.addEventListener('click', () => {
        addModal.style.display = 'none';
    });

    addModal?.addEventListener('click', (e) => {
        if (e.target === addModal) {
            addModal.style.display = 'none';
        }
    });

    addHabitat?.addEventListener('change', (e) => {
        updateHewanDropdown(e.target.value, '', addHewan, addHint, addHintText);
    });

    addHewan?.addEventListener('change', (e) => {
        updateAnimalHint(addHabitat.value, e.target.value, addHint, addHintText);
    });

    // ==============================
    // File Input Display Logic
    // ==============================
    const imageInput = document.getElementById('enclosure-image-input');
    const fileNameDisplay = document.getElementById('file-name-display');
    const addImageInput = document.getElementById('add-enclosure-image-input');
    const addFileNameDisplay = document.getElementById('add-file-name-display');

    if (imageInput && fileNameDisplay) {
        imageInput.addEventListener('change', function(e) {
            if (this.files && this.files.length > 0) {
                fileNameDisplay.value = this.files[0].name;
            } else {
                fileNameDisplay.value = '';
            }
        });
    }

    if (addImageInput && addFileNameDisplay) {
        addImageInput.addEventListener('change', function(e) {
            if (this.files && this.files.length > 0) {
                addFileNameDisplay.value = this.files[0].name;
            } else {
                addFileNameDisplay.value = '';
            }
        });
    }

});
</script>
@endpush
