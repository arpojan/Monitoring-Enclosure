{{-- resources/views/enclosure/scripts/inline.blade.php --}}
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
    const animalHintText = animalHint?.querySelector('span');

    // Parse animal KB from PHP to JS
    const animalSpecies = @json($animalSpecies ?? []);

    function updateHewanDropdown(categoryValue, selectedHewan, selectHewanEl, hintEl, hintTextEl) {
        selectHewanEl.innerHTML = '<option value="">-- Pilih Hewan --</option>';
        if (hintEl) hintEl.style.display = 'none';
        
        if (categoryValue) {
            selectHewanEl.disabled = false;
            let found = false;
            Object.entries(animalSpecies).forEach(([key, config]) => {
                if (config.category === categoryValue) {
                    found = true;
                    const option = document.createElement('option');
                    option.value = key;
                    option.textContent = config.name;
                    if (key === selectedHewan) {
                        option.selected = true;
                    }
                    selectHewanEl.appendChild(option);
                }
            });
            if (found) {
                updateAnimalHint(selectedHewan, hintEl, hintTextEl);
            }
        } else {
            selectHewanEl.disabled = true;
        }
    }

    function updateAnimalHint(hewanKey, hintEl, hintTextEl) {
        if (hintEl && hintTextEl) {
            if (hewanKey && animalSpecies[hewanKey]) {
                const config = animalSpecies[hewanKey];
                hintTextEl.textContent = `Batas Ideal RH: ${config.humidity.humid_ideal_min}% - ${config.humidity.humid_ideal_max}%`;
                hintEl.style.display = 'block';
            } else {
                hintEl.style.display = 'none';
            }
        }
    }

    // Modal Edit Listeners
    selectHabitat?.addEventListener('change', (e) => {
        updateHewanDropdown(e.target.value, '', selectHewan, animalHint, animalHintText);
    });

    selectHewan?.addEventListener('change', (e) => {
        updateAnimalHint(e.target.value, animalHint, animalHintText);
    });

    editBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();

            const id       = btn.dataset.id;
            const name     = btn.dataset.name;
            const habitat  = btn.dataset.habitat;
            const hewan    = btn.dataset.hewan;
            const devKey   = btn.dataset.deviceKey || '';

            if (inputId) inputId.value = id;
            if (inputName) inputName.value = name;
            
            // Set dropdowns
            if (selectHabitat) {
                selectHabitat.value = habitat || '';
                updateHewanDropdown(habitat || '', hewan || '', selectHewan, animalHint, animalHintText);
            }

            // ── Isi section Device Pairing ──
            const displayEncId  = document.getElementById('display-enclosure-id');
            const displayKey    = document.getElementById('display-device-key');
            const regenForm     = document.getElementById('regenerate-key-form');
            const neverConnected = btn.dataset.neverConnected === 'true';

            // Simpan key asli sebagai atribut tersembunyi untuk toggle
            if (displayKey) {
                displayKey.dataset.fullKey = devKey;
                displayKey.textContent = devKey ? '•'.repeat(Math.min(devKey.length, 24)) : '(belum di-generate)';
            }
            if (displayEncId) displayEncId.textContent = id;

            // Set action form regenerate key
            if (regenForm) {
                regenForm.action = `/select-enclosure/${id}/regenerate-key`;
            }

            // Reset toggle state (key tersembunyi saat modal dibuka)
            const toggleBtn = document.getElementById('toggle-device-key-btn');
            if (toggleBtn) {
                toggleBtn.querySelector('i').className = 'ph ph-eye';
                toggleBtn.dataset.visible = '0';
            }

            if (editModal) editModal.style.display = 'flex';

            // Jika kandang belum pernah konek → auto-buka petunjuk pairing
            if (neverConnected) {
                const guide = document.getElementById('pairing-guide-details');
                if (guide) {
                    guide.open = true;
                    setTimeout(() => guide.scrollIntoView({ behavior: 'smooth', block: 'center' }), 200);
                }
            }
        });
    });

    // ── Copy Enclosure ID ──
    document.getElementById('copy-enclosure-id-btn')?.addEventListener('click', () => {
        const val = document.getElementById('display-enclosure-id')?.textContent;
        if (!val || val === '—') return;
        navigator.clipboard.writeText(val).then(() => showCopyFeedback('copy-enclosure-id-btn'));
    });

    // ── Copy Server URL ──
    document.getElementById('copy-server-url-btn')?.addEventListener('click', () => {
        const val = document.getElementById('display-server-url')?.textContent?.trim();
        if (!val) return;
        navigator.clipboard.writeText(val).then(() => showCopyFeedback('copy-server-url-btn'));
    });

    // ── Copy Device Key ──
    document.getElementById('copy-device-key-btn')?.addEventListener('click', () => {
        const el = document.getElementById('display-device-key');
        const key = el?.dataset.fullKey;
        if (!key) return;
        navigator.clipboard.writeText(key).then(() => showCopyFeedback('copy-device-key-btn'));
    });

    // ── Toggle Show/Hide Device Key ──
    document.getElementById('toggle-device-key-btn')?.addEventListener('click', function() {
        const el = document.getElementById('display-device-key');
        const icon = this.querySelector('i');
        if (!el) return;

        if (this.dataset.visible === '1') {
            el.textContent = '•'.repeat(Math.min((el.dataset.fullKey || '').length, 24));
            icon.className = 'ph ph-eye';
            this.dataset.visible = '0';
        } else {
            el.textContent = el.dataset.fullKey || '(belum ada)';
            icon.className = 'ph ph-eye-slash';
            this.dataset.visible = '1';
        }
    });

    // ── Regenerate Key — konfirmasi dulu ──
    document.getElementById('regenerate-key-btn')?.addEventListener('click', function() {
        const confirmed = confirm(
            '⚠️ Regenerate Device Key?\n\n' +
            'ESP32 yang menggunakan key lama akan langsung ditolak (401).\n' +
            'Anda harus memperbarui firmware setelah ini.\n\n' +
            'Lanjutkan?'
        );
        if (confirmed) {
            document.getElementById('regenerate-key-form')?.submit();
        }
    });

    // Helper: feedback visual saat copy berhasil
    function showCopyFeedback(btnId) {
        const btn = document.getElementById(btnId);
        if (!btn) return;
        const icon = btn.querySelector('i');
        const orig = icon.className;
        icon.className = 'ph ph-check';
        btn.style.color = 'var(--accent-teal)';
        setTimeout(() => {
            icon.className = orig;
            btn.style.color = '';
        }, 1500);
    }


    closeEditBtn?.addEventListener('click', () => {
        if (editModal) editModal.style.display = 'none';
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
        const addForm = document.getElementById('add-enclosure-form');
        if (addForm) addForm.reset();
        const display = document.getElementById('add-file-name-display');
        if (display) display.value = '';
        updateHewanDropdown('', '', addHewan, addHint, addHintText);

        if (addModal) addModal.style.display = 'flex';
    });

    closeAddBtn?.addEventListener('click', () => {
        if (addModal) addModal.style.display = 'none';
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
        updateAnimalHint(e.target.value, addHint, addHintText);
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
