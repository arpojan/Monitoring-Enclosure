{{-- resources/views/enclosure/partials/add-modal.blade.php --}}
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
                        @foreach(array_keys($animalKnowledgeBase ?? []) as $habitat)
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
