{{-- resources/views/enclosure/partials/edit-modal.blade.php --}}
<!-- Edit Enclosure Modal -->
<div id="edit-enclosure-modal" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center; backdrop-filter: blur(5px);">
    <div class="modal-content glass-card" style="width: 90%; max-width: 480px; padding: 2rem; position: relative; max-height: 90vh; overflow-y: auto;">
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
                <label style="display: block; margin-bottom: 0.5rem; color: var(--text-secondary); font-size: 0.9rem;">Kategori Hewan</label>
                <div class="input-group" style="margin-bottom: 0; padding: 0;">
                    <i class="ph ph-paw-prints" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--accent-teal); font-size: 1.2rem; z-index: 10;"></i>
                    <select name="target_habitat" id="edit-enclosure-habitat" style="width: 100%; background: transparent; border: none; padding: 0.8rem 1rem 0.8rem 2.8rem; font-size: 1rem; color: var(--text-primary); cursor: pointer; appearance: none; outline: none; box-shadow: none;">
                        <option value="">-- Pilih Kategori --</option>
                        @foreach($animalCategories ?? [] as $key => $category)
                            <option value="{{ $key }}">{{ $category }}</option>
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

        {{-- ─── Seksi Koneksi Perangkat IoT ─── --}}
        <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid var(--border-color);">
            <h3 style="font-size: 0.9rem; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 1rem; display: flex; align-items: center; gap: 6px;">
                <i class="ph ph-wifi-high text-teal"></i> Koneksi Perangkat IoT
            </h3>

            {{-- Enclosure ID --}}
            <div style="margin-bottom: 0.75rem;">
                <label style="display: block; margin-bottom: 0.3rem; color: var(--text-muted); font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.4px;">Enclosure ID</label>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <code id="display-enclosure-id" style="flex: 1; background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 8px; padding: 8px 12px; font-family: monospace; font-size: 1rem; color: var(--accent-teal); font-weight: 700; letter-spacing: 1px;">—</code>
                    <button type="button" id="copy-enclosure-id-btn" class="btn-icon" title="Salin Enclosure ID" style="flex-shrink: 0;">
                        <i class="ph ph-copy"></i>
                    </button>
                </div>
            </div>

            {{-- Server URL --}}
            <div style="margin-bottom: 0.75rem;">
                <label style="display: block; margin-bottom: 0.3rem; color: var(--text-muted); font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.4px;">Server URL</label>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <code id="display-server-url" style="flex: 1; background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 8px; padding: 8px 12px; font-family: monospace; font-size: 0.72rem; color: var(--text-primary); word-break: break-all;">{{ rtrim(url('/'), '/') }}/api/telemetry</code>
                    <button type="button" id="copy-server-url-btn" class="btn-icon" title="Salin Server URL" style="flex-shrink: 0;">
                        <i class="ph ph-copy"></i>
                    </button>
                </div>
            </div>

            {{-- Device Key --}}
            <div style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.3rem; color: var(--text-muted); font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.4px;">Device Key (X-Device-Key header)</label>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <code id="display-device-key" style="flex: 1; background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 8px; padding: 8px 12px; font-family: monospace; font-size: 0.78rem; color: var(--text-primary); word-break: break-all; min-height: 38px; display: flex; align-items: center;">—</code>
                    <div style="display: flex; flex-direction: column; gap: 4px; flex-shrink: 0;">
                        <button type="button" id="copy-device-key-btn" class="btn-icon" title="Salin Device Key">
                            <i class="ph ph-copy"></i>
                        </button>
                        <button type="button" id="toggle-device-key-btn" class="btn-icon" title="Tampilkan/Sembunyikan Key">
                            <i class="ph ph-eye"></i>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Regenerate Key --}}
            <form id="regenerate-key-form" method="POST" action="" style="margin-bottom: 0.75rem;">
                @csrf
                <button type="button" id="regenerate-key-btn"
                    style="width: 100%; display: flex; align-items: center; justify-content: center; gap: 6px; padding: 8px 12px; background: transparent; border: 1px solid var(--danger); color: var(--danger); border-radius: 8px; cursor: pointer; font-size: 0.82rem; font-weight: 600; transition: all 0.2s;"
                    onmouseover="this.style.background='rgba(231,76,60,0.1)'" onmouseout="this.style.background='transparent'"
                >
                    <i class="ph ph-arrows-clockwise"></i> Regenerate Key
                </button>
            </form>

            {{-- Alert new key jika baru di-regenerate --}}
            @if(session('new_device_key') && session('regenerated_enclosure_id'))
            <div id="new-key-alert" style="background: rgba(76,175,80,0.1); border: 1px solid var(--success, #4caf50); border-radius: 8px; padding: 10px 12px; font-size: 0.82rem; color: var(--text-primary);">
                <p style="margin: 0 0 6px 0; font-weight: 600; color: #4caf50;"><i class="ph ph-check-circle"></i> Key baru berhasil dibuat!</p>
                <p style="margin: 0 0 4px 0; color: var(--text-muted); font-size: 0.75rem;">Segera perbarui firmware ESP32 Anda dengan key berikut:</p>
                <code style="display: block; background: var(--bg-secondary); border-radius: 6px; padding: 6px 10px; word-break: break-all; font-size: 0.78rem; margin-top: 4px;">{{ session('new_device_key') }}</code>
            </div>
            @endif

            {{-- Petunjuk WiFi Provisioning --}}
            <details style="margin-top: 0.75rem;" id="pairing-guide-details">
                <summary style="cursor: pointer; color: var(--text-muted); font-size: 0.78rem; font-weight: 600; user-select: none; display: flex; align-items: center; gap: 5px;">
                    <i class="ph ph-wifi-high text-teal"></i> Cara menghubungkan ESP32 (WiFi Provisioning)
                </summary>
                <div style="margin-top: 10px; display: flex; flex-direction: column; gap: 8px;">
                    <div style="display: flex; gap: 10px; align-items: flex-start; font-size: 0.8rem; color: var(--text-secondary);">
                        <span style="background: var(--accent-teal); color: #fff; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; font-weight: 700; flex-shrink: 0; margin-top: 1px;">1</span>
                        <span>Nyalakan ESP32 → sambungkan ke WiFi <strong style="color: var(--text-primary); font-family: monospace;">"RAP-Enclosure-Setup"</strong> dari HP/laptop</span>
                    </div>
                    <div style="display: flex; gap: 10px; align-items: flex-start; font-size: 0.8rem; color: var(--text-secondary);">
                        <span style="background: var(--accent-teal); color: #fff; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; font-weight: 700; flex-shrink: 0; margin-top: 1px;">2</span>
                        <span>Browser otomatis terbuka (atau buka <strong style="color: var(--text-primary); font-family: monospace;">192.168.4.1</strong>) → klik <strong>Configure WiFi</strong></span>
                    </div>
                    <div style="display: flex; gap: 10px; align-items: flex-start; font-size: 0.8rem; color: var(--text-secondary);">
                        <span style="background: var(--accent-teal); color: #fff; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; font-weight: 700; flex-shrink: 0; margin-top: 1px;">3</span>
                        <span>Isi WiFi rumah/lab, lalu salin nilai di atas ke kolom yang tersedia di form</span>
                    </div>
                    <div style="display: flex; gap: 10px; align-items: flex-start; font-size: 0.8rem; color: var(--text-secondary);">
                        <span style="background: var(--accent-teal); color: #fff; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; font-weight: 700; flex-shrink: 0; margin-top: 1px;">4</span>
                        <span>Klik Save → ESP32 restart → status kandang berubah jadi <strong style="color: #4caf50;">Online ●</strong></span>
                    </div>
                    <p style="margin: 4px 0 0 0; font-size: 0.72rem; color: var(--text-muted); padding: 6px 8px; background: var(--bg-secondary); border-radius: 6px;">
                        <i class="ph ph-info"></i> Untuk reset ke factory default: tekan tombol BOOT di ESP32 selama 3 detik
                    </p>
                </div>
            </details>
        </div>

    </div>
</div>

