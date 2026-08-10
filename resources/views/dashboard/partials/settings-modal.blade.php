{{-- resources/views/dashboard/partials/settings-modal.blade.php --}}
<div id="user-settings-modal" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center; backdrop-filter: blur(5px);">
    <div class="modal-content glass-card" style="width: 90%; max-width: 450px; padding: 2.5rem; position: relative;">
        <button id="close-settings-btn" class="btn-icon" style="position: absolute; top: 1.5rem; right: 1.5rem;"><i class="ph ph-x"></i></button>
        <h2 style="margin-bottom: 1.5rem; font-size: 1.5rem;"><i class="ph ph-user-gear text-teal"></i> Pengaturan Akun</h2>
        <form id="user-settings-form-web" action="{{ route('user.profile.update') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            @php
                $avatarPath = 'avatars/user_' . auth()->id() . '.jpg';
                $avatarUrl = file_exists(public_path($avatarPath)) ? asset($avatarPath) . '?v=' . time() : 'https://ui-avatars.com/api/?name=' . urlencode(auth()->user()->name) . '&background=00b4d8&color=fff';
            @endphp
            <div style="margin-bottom: 1rem; text-align: center;">
                <label for="avatar-input" style="cursor: pointer; display: inline-block; position: relative;">
                    <img src="{{ $avatarUrl }}" alt="Avatar Preview" id="avatar-preview" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 2px solid var(--teal);">
                    <div style="position: absolute; bottom: 0; right: 0; background: var(--teal); color: white; border-radius: 50%; padding: 4px; font-size: 12px;"><i class="ph ph-camera"></i></div>
                </label>
                <input type="file" id="avatar-input" name="avatar" accept="image/*" style="display: none;" onchange="document.getElementById('avatar-preview').src = window.URL.createObjectURL(this.files[0])">
                <div style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 5px;">Klik gambar untuk mengubah foto profil</div>
            </div>
            <div style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.5rem; color: var(--text-secondary); font-size: 0.9rem;">Nama Pengguna</label>
                <div class="input-group" style="margin-bottom: 0;">
                    <i class="ph ph-user"></i>
                    <input type="text" id="user-name-input" name="name" value="{{ auth()->user()->name }}" required>
                </div>
            </div>
            <div style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.5rem; color: var(--text-secondary); font-size: 0.9rem;">Email</label>
                <div class="input-group" style="margin-bottom: 0;">
                    <i class="ph ph-envelope"></i>
                    <input type="email" id="user-email-input" name="email" value="{{ auth()->user()->email }}" required>
                </div>
            </div>
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; color: var(--text-secondary); font-size: 0.9rem;">Kata Sandi Baru</label>
                <div class="input-group" style="margin-bottom: 0;">
                    <i class="ph ph-lock"></i>
                    <input type="password" name="password" placeholder="Kosongkan jika tidak diubah">
                </div>
            </div>
            <button type="submit" class="btn-primary" id="save-user-settings-btn">
                <i class="ph ph-check"></i> <span>Simpan Perubahan</span>
            </button>
        </form>
    </div>
</div>
