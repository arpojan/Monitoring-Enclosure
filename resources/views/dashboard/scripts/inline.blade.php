{{-- resources/views/dashboard/scripts/inline.blade.php --}}
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const settingsBtn = document.getElementById('open-settings-btn');
        const settingsModal = document.getElementById('user-settings-modal');
        const closeSettingsBtn = document.getElementById('close-settings-btn');

        if(settingsBtn && settingsModal && closeSettingsBtn) {
            settingsBtn.addEventListener('click', (e) => {
                e.preventDefault();
                settingsModal.style.display = 'flex';
            });

            closeSettingsBtn.addEventListener('click', () => {
                settingsModal.style.display = 'none';
            });

            settingsModal.addEventListener('click', (e) => {
                if (e.target === settingsModal) {
                    settingsModal.style.display = 'none';
                }
            });
        }
    });
</script>

@if(session('success'))
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Jika ada flash message, kita tambahkan ke notification queue
        if(typeof NotificationManager !== 'undefined') {
            NotificationManager.add('Pembaruan Berhasil', '{{ session('success') }}', 'info');
        } else {
            // Fallback jika class belum terload
            setTimeout(() => {
                if(typeof NotificationManager !== 'undefined') {
                    NotificationManager.add('Pembaruan Berhasil', '{{ session('success') }}', 'info');
                }
            }, 1000);
        }
    });
</script>
@endif

<script src="{{ asset('assets/js/api.js') }}?v={{ time() }}"></script>
<script src="{{ asset('assets/js/app.js') }}?v={{ time() }}"></script>

<script>
    // --- Export Dropdown Logic ---
    window.toggleExportMenu = function(e) {
        e.stopPropagation();
        const dropdown = document.getElementById('export-dropdown');
        if (dropdown.style.display === 'none' || !dropdown.style.display) {
            dropdown.style.display = 'flex';
        } else {
            dropdown.style.display = 'none';
        }
    };

    window.triggerExport = function(format) {
        const dropdown = document.getElementById('export-dropdown');
        dropdown.style.display = 'none';
        
        const exportBtn = document.getElementById('export-report-btn');
        const originalHtml = exportBtn.innerHTML;
        exportBtn.innerHTML = '<i class="ph ph-spinner ph-spin"></i> <span>Mengekspor...</span>';
        exportBtn.disabled = true;

        setTimeout(() => {
            exportBtn.innerHTML = originalHtml;
            exportBtn.disabled = false;
            
            let message = 'Laporan analitik enclosure berhasil diunduh sebagai PDF.';
            if (format === 'csv') {
                message = 'Data mentah enclosure berhasil diunduh sebagai file CSV/Excel.';
            }
            
            if (typeof showNotificationToast === 'function') {
                showNotificationToast('Ekspor Berhasil', message);
            } else {
                alert('Ekspor Berhasil! ' + message);
            }
        }, 1800);
    };

    // Close export dropdown when clicking outside
    document.addEventListener('click', function(e) {
        const dropdown = document.getElementById('export-dropdown');
        const btn = document.getElementById('export-report-btn');
        if (dropdown && btn && !dropdown.contains(e.target) && !btn.contains(e.target)) {
            dropdown.style.display = 'none';
        }
    });
</script>
@endpush
