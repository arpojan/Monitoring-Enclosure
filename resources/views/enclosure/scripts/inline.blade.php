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
    const animalKB = @json($animalKnowledgeBase ?? []);

    function updateHewanDropdown(habitatValue, selectedHewan, selectHewanEl, hintEl, hintTextEl) {
        selectHewanEl.innerHTML = '<option value="">-- Pilih Hewan --</option>';
        if (hintEl) hintEl.style.display = 'none';
        
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
        if (hintEl && hintTextEl) {
            if (habitat && hewan && animalKB[habitat] && animalKB[habitat][hewan]) {
                const config = animalKB[habitat][hewan];
                hintTextEl.textContent = `Batas Ideal RH: ${config.min}% - ${config.max}%`;
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

            if (inputId) inputId.value = id;
            if (inputName) inputName.value = name;
            
            // Set dropdowns
            if (selectHabitat) {
                selectHabitat.value = habitat || '';
                updateHewanDropdown(habitat || '', hewan || '', selectHewan, animalHint, animalHintText);
            }

            if (editModal) editModal.style.display = 'flex';
        });
    });

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
