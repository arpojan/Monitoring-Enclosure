# RAP Enclosure DSS — Smart Misting Monitoring & Configuration Dashboard

Aplikasi ini adalah dashboard monitoring dan konfigurasi kontrol misting berbasis Laravel untuk enclosure/kandang. Sistem digunakan untuk membaca data suhu dan kelembapan dari ESP32, menyimpan telemetry, menampilkan analitik, serta memberikan AI insight dan AI recommendation sebagai Decision Support System (DSS).

## Flow Sistem

```text
Sensor + ESP32
   ↓
ESP32 membaca suhu dan kelembapan
   ↓
ESP32 mengambil parameter dari web:
- bottom humidity
- top humidity
- misting duration
   ↓
ESP32 menjalankan rule-based misting secara lokal
   ↓
ESP32 mengirim telemetry + status misting aktual ke Laravel
   ↓
Laravel menyimpan data, menampilkan dashboard, analytics, stability score
   ↓
AI insight + AI recommendation membantu user menentukan parameter kontrol
   ↓
User menerapkan parameter manual / menerapkan rekomendasi AI
   ↓
ESP32 mengambil konfigurasi terbaru
```

> Catatan penting: Laravel/web bukan pengambil keputusan ON/OFF misting utama. Web berperan sebagai monitoring, configuration panel, dan AI-based DSS. Eksekusi rule-based misting dilakukan oleh ESP32.

## Fitur Utama

- Login dan pemilihan enclosure/kandang.
- Realtime dashboard untuk suhu, kelembapan, status misting, dan status koneksi perangkat.
- Parameter kontrol misting:
  - bottom humidity
  - top humidity
  - misting duration
- API telemetry untuk menerima data aktual dari ESP32.
- API control config agar ESP32 dapat mengambil konfigurasi terbaru.
- Analitik data historis: rata-rata RH, suhu, siklus misting, distribusi kelembapan, dan grafik historis.
- Stability score berdasarkan range compliance, variability, stability duration, dan fluctuation penalty.
- AI insight dan AI recommendation sebagai DSS.
- Apply/reject recommendation agar rekomendasi AI menjadi actionable.
- Parameter history untuk mencatat perubahan parameter manual maupun dari rekomendasi AI.

## Tech Stack

- Laravel 11
- PHP 8.2+
- MySQL/MariaDB
- Blade + JavaScript
- Chart.js
- ESP32 / telemetry simulator

## Endpoint Penting

### Telemetry ESP32

```http
POST /api/telemetry
```

Contoh payload:

```json
{
  "enclosure_id": 1,
  "temperature": 25.4,
  "humidity": 84.7,
  "misting_status": true,
  "misting_duration_executed": 10,
  "device_timestamp": "2026-05-20T15:20:00+07:00"
}
```

### ESP32 Mengambil Konfigurasi

```http
GET /api/enclosures/{id}/control-config
```

Contoh response:

```json
{
  "success": true,
  "data": {
    "enclosure_id": 1,
    "mode": "auto",
    "bottom_humidity": 82,
    "top_humidity": 92,
    "misting_duration_seconds": 10,
    "humidity_min": 80,
    "humidity_max": 95
  }
}
```

### Update Parameter dari Web

```http
PUT /api/enclosures/{id}/parameters
```

Contoh payload:

```json
{
  "misting_bottom_threshold": 82,
  "misting_top_threshold": 92,
  "misting_duration_seconds": 10,
  "source": "manual"
}
```

### Apply AI Recommendation

```http
POST /api/recommendations/{id}/apply
```

### Reject AI Recommendation

```http
POST /api/recommendations/{id}/reject
```

## Instalasi Lokal

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run dev
php artisan serve
```

Konfigurasi database di `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=skripsi
DB_USERNAME=root
DB_PASSWORD=
```

## Menjalankan Simulator ESP32

```bash
python telemetry_simulator.py
```

Simulator mengikuti flow final: simulator/ESP32 mengambil konfigurasi dari web, menjalankan rule-based misting lokal, lalu mengirim telemetry dan status misting aktual ke backend.

## Struktur File Penting

```text
app/Http/Controllers/Api/TelemetryController.php
app/Http/Controllers/Api/EnclosureController.php
app/Http/Controllers/Api/RecommendationController.php
app/Models/EnclosureParameter.php
app/Models/ParameterHistory.php
database/migrations/2026_05_20_000001_add_control_config_columns.php
database/migrations/2026_05_20_000002_create_parameter_histories_table.php
resources/views/dashboard/index.blade.php
public/assets/js/api.js
public/assets/js/app.js
telemetry_simulator.py
```

## Catatan Keamanan

Endpoint ESP32 mendukung header opsional:

```http
X-DEVICE-KEY: your-device-key
```

Jika `device_key` pada tabel `enclosures` kosong, API tetap menerima request untuk kemudahan demo lokal. Jika `device_key` diisi, ESP32 wajib mengirim header tersebut.
