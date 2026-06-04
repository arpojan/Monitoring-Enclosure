# Revision Notes — ESP32-Centric Misting Control + AI DSS

Revisi ini menyelaraskan project dengan flow skripsi final:

```text
Web Laravel = monitoring + konfigurasi + AI DSS
ESP32 = rule-based misting executor
```

## File yang Direvisi/Ditambahkan

### Backend API

- `routes/api.php`
  - Menambah endpoint `GET /api/enclosures/{id}/control-config`.
  - Menambah endpoint `PUT /api/enclosures/{id}/parameters`.
  - Menambah endpoint `POST /api/recommendations/{id}/apply`.
  - Menambah endpoint `POST /api/recommendations/{id}/reject`.

- `app/Http/Controllers/Api/TelemetryController.php`
  - Diubah agar telemetry menerima status misting aktual dari ESP32.
  - Tidak lagi memakai Laravel sebagai pengambil keputusan ON/OFF misting utama.
  - Response mengembalikan `control_config` terbaru untuk sinkronisasi ESP32.

- `app/Http/Controllers/Api/EnclosureController.php`
  - Menambah `controlConfig()` untuk ESP32 mengambil konfigurasi.
  - Menambah `updateParameters()` untuk update bottom humidity, top humidity, dan durasi misting.
  - Menambah validasi bottom < top dan durasi 1–300 detik.

- `app/Http/Controllers/Api/RecommendationController.php`
  - Baru.
  - Menangani apply/reject AI recommendation.
  - Apply recommendation otomatis update parameter enclosure.

### Database & Model

- `database/migrations/2026_05_20_000001_add_control_config_columns.php`
  - Menambah `enclosures.device_key`.
  - Menambah `enclosure_parameters.misting_duration_seconds`.
  - Menambah `sensor_logs.misting_duration_executed` dan `sensor_logs.device_timestamp`.

- `database/migrations/2026_05_20_000002_create_parameter_histories_table.php`
  - Baru.
  - Mencatat perubahan parameter manual maupun dari rekomendasi AI.

- `app/Models/EnclosureParameter.php`
  - Menambah field/cast `misting_duration_seconds`.

- `app/Models/SensorLog.php`
  - Menambah field/cast `misting_duration_executed` dan `device_timestamp`.

- `app/Models/ParameterHistory.php`
  - Baru.

- `app/Models/Enclosure.php`
  - Menambah `device_key` dan relasi `parameterHistories()`.

### Frontend

- `resources/views/dashboard/index.blade.php`
  - Menambah card form Parameter Kontrol Misting.
  - Form berisi bottom humidity, top humidity, dan durasi misting.
  - Tombol recommendation dashboard diberi ID agar bisa apply rekomendasi AI.

- `public/assets/js/api.js`
  - Menambah helper API untuk control config, update parameter, apply/reject recommendation.

- `public/assets/js/app.js`
  - Menampilkan durasi misting pada status.
  - Sinkronisasi form parameter dari data dashboard.
  - Submit parameter manual ke backend.
  - Apply AI recommendation dari dashboard.

### Simulator & Dokumentasi

- `telemetry_simulator.py`
  - Diubah menjadi ESP32-centric.
  - Simulator mengambil control config dari web, menjalankan rule-based lokal, lalu mengirim telemetry aktual.

- `README.md`
  - Diganti dari README default Laravel menjadi dokumentasi project.

- `.env.example`
  - Disesuaikan dengan nama aplikasi, timezone Asia/Jakarta, dan MySQL database `skripsi`.

## Setelah Copy Revisi

Jalankan:

```bash
composer dump-autoload
php artisan migrate
php artisan db:seed
php artisan serve
```

Lalu simulator:

```bash
python telemetry_simulator.py
```

## Catatan

File SQL dump dari RAR tidak direvisi karena archive RAR memerlukan extractor eksternal untuk file yang terkompresi penuh. Struktur DB diperbaiki lewat migration Laravel agar aman untuk project existing maupun fresh install.
