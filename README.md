# Thesis

> **Smart Misting Monitoring & AI-Based Decision Support System for Vivarium**

![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?logo=php&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-11-FF2D20?logo=laravel&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?logo=mysql&logoColor=white)
![Python](https://img.shields.io/badge/Python-3.x-3776AB?logo=python&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-green)
![Platform](https://img.shields.io/badge/IoT-ESP32-blue)

---

## About This Project

**RAP Enclosure DSS** is a Laravel-based web dashboard for monitoring and configuring automated misting control in animal enclosures (vivariums). The system is designed for dart frog vivariums and integrates with **ESP32 microcontrollers** as the primary rule-based misting executor.

The web application serves as:
- **Monitoring Dashboard** — real-time telemetry, humidity & temperature trends
- **Configuration Panel** — misting threshold & duration parameters
- **AI-Based DSS** — Decision Support System with actionable recommendations

> **Key Design Principle:** The web/Laravel layer is **not** the misting decision-maker. All ON/OFF misting logic is executed locally by the ESP32. Laravel receives, stores, analyzes, and suggests.

---

## System Architecture

```text
┌─────────────────────────────────────────────────┐
│              ESP32 / Hardware Device             │
│  Reads humidity & temperature sensors            │
│  ↓                                              │
│  Fetches control config from Laravel API         │
│    GET /api/enclosures/{id}/control-config       │
│  ↓                                              │
│  Executes rule-based misting logic LOCALLY       │
│  (misting ON if humidity < bottom_threshold)     │
│  ↓                                              │
│  Sends actual telemetry + misting status         │
│    POST /api/telemetry                           │
└─────────────────────────────────────────────────┘
         ↓                        ↑
┌─────────────────────────────────────────────────┐
│              Laravel Web Application             │
│  • Stores sensor logs in MySQL                  │
│  • Renders real-time monitoring dashboard        │
│  • Computes stability scores                    │
│  • Runs AI DSS analysis engine                  │
│  • Displays AI insights & recommendations       │
│  ↓                                              │
│  User reviews recommendation on dashboard        │
│  → Clicks "Apply" or "Reject"                   │
│  → If applied: parameters updated               │
│  → ESP32 fetches new config on next poll        │
└─────────────────────────────────────────────────┘
```

---

## Features

| Feature | Description |
|---|---|
| **Authentication** | User login, registration, and session-based access |
| **Enclosure Selection** | Multi-enclosure support; user picks active vivarium on login |
| **Real-Time Dashboard** | Live temperature, humidity, misting status, and connection state |
| **Misting Parameters** | Configurable bottom/top humidity threshold and misting duration |
| **Analytics** | Historical charts, misting cycles, humidity distribution, trend graphs |
| **Stability Score** | Computed from range compliance, variability, duration, and fluctuation penalty |
| **AI Insight Engine** | Auto-generated contextual insights based on sensor data patterns |
| **AI Recommendations (DSS)** | Actionable parameter change suggestions with Human-in-the-Loop approval |
| **Apply / Reject Workflow** | AI recommendations only take effect after explicit user approval |
| **Parameter History** | Full audit trail of every manual or AI-driven parameter change |
| **ESP32 Simulator** | Python script that mimics real ESP32 device for local development |
| **Device Key Auth** | Optional `X-DEVICE-KEY` header for securing ESP32-to-API communication |

---

## Tech Stack

| Layer | Technology |
|---|---|
| **Backend Framework** | Laravel 11 |
| **Language** | PHP 8.2+ |
| **Database** | MySQL / MariaDB |
| **Frontend** | Blade + Vanilla JavaScript |
| **Charts** | Chart.js |
| **Build Tool** | Vite |
| **IoT Device** | ESP32 (or `telemetry_simulator.py` for local testing) |
| **Simulator Language** | Python 3.x |

---

## Prerequisites

Before you begin, ensure you have the following installed:

- **PHP** `>= 8.2` with extensions: `pdo_mysql`, `mbstring`, `openssl`, `bcmath`, `json`
- **Composer** `>= 2.x`
- **Node.js** `>= 18.x` and **npm**
- **MySQL** `>= 8.0` or **MariaDB** `>= 10.6`
- **Python** `>= 3.8` (only if using the ESP32 simulator)
- **Git**

---

## Quick Start (Local Installation)

### 1. Clone the Repository

```bash
git clone https://github.com/[YOUR_USERNAME]/rap-enclosure-dss.git
cd rap-enclosure-dss
```

### 2. Install PHP Dependencies

```bash
composer install
```

### 3. Install Node.js Dependencies

```bash
npm install
```

### 4. Configure Environment

```bash
cp .env.example .env
php artisan key:generate
```

### 5. Set Up Database

Create a MySQL database named `skripsi`, then open `.env` and set:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=skripsi
DB_USERNAME=root
DB_PASSWORD=
```

### 6. Run Migrations and Seeders

```bash
php artisan migrate --seed
```

This creates all tables and seeds demo data: 1 user account, 2 dart frog vivariums, and 30 days of realistic telemetry data.

### 7. Start the Development Server

Open two terminals and run both simultaneously:

**Terminal 1 — Laravel Backend:**

```bash
php artisan serve
```

**Terminal 2 — Vite Asset Builder:**

```bash
npm run dev
```

The application will be available at: **http://localhost:8000**

---

## Running the ESP32 Simulator

If you don't have physical ESP32 hardware, use the included Python simulator to generate realistic telemetry:

### Install Python Dependencies

```bash
pip install requests
```

### Run Simulator for Enclosure A

```bash
python telemetry_simulator.py
```

### Run Simulator for Enclosure B

```bash
python telemetry_simulator_2.py
```

The simulator mimics full ESP32 behavior:
1. Fetches the latest `control-config` from Laravel
2. Applies rule-based misting logic locally (ON when humidity `< bottom_threshold`)
3. Sends actual telemetry + misting status to `POST /api/telemetry`
4. Simulates a 12-hour dry-out period (08:00–20:00) with humidity dropping from ~85% to ~60%

---

## Default Credentials

After running `php artisan migrate --seed`, use these credentials to log in:

| Role | Email | Password |
|---|---|---|
| Researcher | `researcher@smart-enclosure.test` | `password` |

> You can also register a new account via the `/register` page.

### Demo Enclosures (Seeded)

| Enclosure | Species | Biological Range | Misting Thresholds | Duration |
|---|---|---|---|---|
| Dart Frog Vivarium A | *Dendrobates tinctorius* | 80%–95% RH | ON @ 82% / OFF @ 92% | 10 seconds |
| Dart Frog Vivarium B | *Ranitomeya imitator* | 75%–90% RH | ON @ 78% / OFF @ 88% | 8 seconds |

---

## API Endpoints

All API routes are prefixed with `/api`. For local development, no authentication token is required. Device identity is optionally verified via the `X-DEVICE-KEY` header.

### IoT / ESP32 Endpoints

| Method | Endpoint | Description | Auth |
|---|---|---|---|
| `POST` | `/api/telemetry` | Receive sensor data from ESP32 | `X-DEVICE-KEY` (optional) |
| `GET` | `/api/enclosures/{id}/control-config` | ESP32 fetches misting parameters | `X-DEVICE-KEY` (optional) |
| `POST` | `/api/enclosures/{id}/mist/trigger` | Trigger manual mist from web dashboard | — |

### Dashboard Data Endpoints

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/enclosures/{id}/latest` | Latest telemetry for real-time cards |
| `GET` | `/api/enclosures/{id}/dashboard` | Combined snapshot: telemetry + stability + AI data |
| `GET` | `/api/enclosures/{id}/history?period=24h\|7d\|30d\|90d` | Historical sensor logs for charts |
| `GET` | `/api/enclosures/{id}/analytics?period=24h\|7d\|30d\|90d` | Stats, humidity distribution, misting activity |
| `GET` | `/api/enclosures/{id}/stability?period=4w\|12w` | Stability score with component breakdown |

### Configuration & DSS Endpoints

| Method | Endpoint | Description |
|---|---|---|
| `PUT` | `/api/enclosures/{id}/parameters` | Update misting thresholds and duration |
| `PUT` | `/api/enclosures/{id}` | Update enclosure identity (name, species, etc.) |
| `POST` | `/api/enclosures/{id}/analyze` | Trigger AI DSS analysis engine |
| `POST` | `/api/recommendations/{id}/apply` | Apply AI recommendation → updates ESP32 config |
| `POST` | `/api/recommendations/{id}/reject` | Reject AI recommendation |

### Payload Examples

**POST `/api/telemetry`** — ESP32 sends a sensor reading:

```json
{
  "enclosure_id": 1,
  "temperature": 25.4,
  "humidity": 84.7,
  "top_humidity": 86.1,
  "bottom_humidity": 83.5,
  "misting_status": true,
  "misting_duration_executed": 10,
  "device_timestamp": "2026-05-20T15:20:00+07:00"
}
```

Response includes `control_config` for passive parameter sync:

```json
{
  "success": true,
  "message": "Telemetry received.",
  "data": {
    "sensor_log_id": 1234,
    "humidity": 86.1,
    "temperature": 25.4,
    "misting_status": true,
    "control_config": {
      "enclosure_id": 1,
      "mode": "auto",
      "bottom_humidity": 82,
      "top_humidity": 92,
      "misting_duration_seconds": 10
    }
  }
}
```

**GET `/api/enclosures/{id}/control-config`** — ESP32 fetches current config:

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

**PUT `/api/enclosures/{id}/parameters`** — Update misting parameters:

```json
{
  "misting_bottom_threshold": 82,
  "misting_top_threshold": 92,
  "misting_duration_seconds": 10,
  "source": "manual"
}
```

Valid `source` values: `manual`, `ai_recommendation`, `system_default`

---

## Security: Device Key Authentication

ESP32 devices can be authenticated using a device key. This is optional for local demo but recommended for production deployments:

```http
X-DEVICE-KEY: your-secret-device-key
```

- If `device_key` in the `enclosures` table is **empty** → API accepts all requests (demo mode)
- If `device_key` is **set** → ESP32 must include the matching header or the request is rejected

---

## Project Structure

```text
rap-enclosure-dss/
│
├── app/
│   ├── Actions/Enclosure/
│   │   └── UpdateParametersAction.php          # Parameter update + history logging
│   ├── Http/Controllers/Api/
│   │   ├── TelemetryController.php             # POST /api/telemetry
│   │   ├── DashboardController.php             # Dashboard, history, analytics, stability
│   │   ├── EnclosureController.php             # control-config, update parameters, manual mist
│   │   ├── RecommendationController.php        # apply / reject AI recommendation
│   │   └── DssController.php                   # AI DSS trigger endpoint
│   ├── Models/
│   │   ├── Enclosure.php                       # + device_key, parameterHistories()
│   │   ├── EnclosureParameter.php              # + misting_duration_seconds
│   │   ├── SensorLog.php                       # + misting_duration_executed, device_timestamp
│   │   └── ParameterHistory.php                # Audit trail for every parameter change
│   └── Services/
│       ├── DssService.php                      # AI analysis engine
│       └── StabilityComputeService.php         # Stability score computation
│
├── database/migrations/
│   ├── 2024_05_15_*                            # Core tables (enclosures, sensor_logs, etc.)
│   ├── 2026_05_20_000001_add_control_config_columns.php
│   └── 2026_05_20_000002_create_parameter_histories_table.php
│
├── resources/views/dashboard/
│   └── index.blade.php                         # Main dashboard with misting control form
│
├── public/assets/js/
│   ├── api.js                                  # Frontend API helper functions
│   └── app.js                                  # Dashboard real-time logic
│
├── routes/
│   ├── api.php                                 # All API routes
│   └── web.php                                 # Auth, dashboard, enclosure selection
│
├── telemetry_simulator.py                      # ESP32 simulator — Enclosure A
├── telemetry_simulator_2.py                    # ESP32 simulator — Enclosure B
└── .env.example                                # Environment configuration template
```

---

## AI DSS — How It Works

The Decision Support System follows a **Human-in-the-Loop** design, ensuring AI suggestions never auto-apply without user confirmation:

```text
1. Trigger analysis
   POST /api/enclosures/{id}/analyze?hours=24

2. DssService analyzes sensor data:
   → Computes StabilityScore (4 weighted components)
   → Generates Insight  (contextual observation about current state)
   → Generates Recommendation (suggested parameter adjustment)

3. Recommendation saved with status: "pending"
   → Does NOT affect ESP32 yet

4. User reviews on dashboard:
   ✅ "Apply"  → POST /api/recommendations/{id}/apply
               → enclosure_parameters updated
               → ESP32 fetches new config on next poll
   ❌ "Reject" → POST /api/recommendations/{id}/reject
               → Recommendation dismissed, no parameter change
```

### Stability Score Components

| Component | Weight | Description |
|---|---|---|
| Range Compliance | 40% | Percentage of readings within the biological humidity range |
| Variability Score | 30% | Standard deviation of humidity readings (lower = better) |
| Stability Duration | 20% | Longest consecutive period within acceptable range |
| Fluctuation Penalty | 10% | Deduction for rapid humidity swings |

---

## Contributing

1. Fork this repository
2. Create a feature branch: `git checkout -b feature/your-feature`
3. Commit your changes: `git commit -m "feat: add your feature description"`
4. Push to the branch: `git push origin feature/your-feature`
5. Open a Pull Request

If you modify the database schema, please include the appropriate migration files and update the seeder if needed.

---

## License

This project is licensed under the [MIT License](https://opensource.org/licenses/MIT).

---

</div>
