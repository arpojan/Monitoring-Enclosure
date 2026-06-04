"""
Smart Enclosure — ESP32 Telemetry Simulator
============================================

Simulator ini mengikuti flow final project skripsi:
- Web/Laravel menyimpan parameter bottom humidity, top humidity, dan durasi misting.
- ESP32 mengambil konfigurasi dari Laravel.
- ESP32 menjalankan rule-based misting secara lokal.
- ESP32 mengirim telemetry + status misting aktual ke Laravel.

Cara menjalankan:
    python telemetry_simulator.py

Pastikan Laravel server sudah berjalan:
    php artisan serve
"""

import random
import sys
import time
from datetime import datetime

import requests

# ─── Konfigurasi ──────────────────────────────────────────────
BASE_URL = "http://localhost:8000/api"
ENCLOSURE_ID = 1
INTERVAL = 10  # detik
DEVICE_KEY = None  # Isi kalau kolom enclosures.device_key dipakai

# ─── State Simulasi ESP32 ─────────────────────────────────────
current_temp = 25.0
current_humidity = 85.0
misting_active = False
misting_started_at = None
last_config = {
    "bottom_humidity": 82.0,
    "top_humidity": 92.0,
    "misting_duration_seconds": 10,
    "mode": "auto",
}


def headers():
    h = {"Accept": "application/json", "Content-Type": "application/json"}
    if DEVICE_KEY:
        h["X-DEVICE-KEY"] = DEVICE_KEY
    return h


def fetch_control_config():
    """Ambil parameter kontrol dari web Laravel."""
    global last_config

    url = f"{BASE_URL}/enclosures/{ENCLOSURE_ID}/control-config"
    try:
        response = requests.get(url, headers=headers(), timeout=5)
        data = response.json()
        if response.status_code == 200 and data.get("success"):
            config = data["data"]
            last_config = {
                "bottom_humidity": float(config["bottom_humidity"]),
                "top_humidity": float(config["top_humidity"]),
                "misting_duration_seconds": int(config["misting_duration_seconds"]),
                "mode": config.get("mode", "auto"),
            }
        return response.status_code, data
    except requests.exceptions.ConnectionError:
        return None, {"error": "Connection refused — pastikan Laravel server berjalan"}
    except Exception as exc:
        return None, {"error": str(exc)}


def evaluate_rule_based_control():
    """Rule-based misting lokal seperti yang akan berjalan di ESP32."""
    global misting_active, misting_started_at

    if last_config.get("mode") != "auto":
        misting_active = False
        misting_started_at = None
        return

    bottom = float(last_config["bottom_humidity"])
    top = float(last_config["top_humidity"])
    duration = int(last_config["misting_duration_seconds"])
    now = time.monotonic()

    if misting_active:
        duration_reached = misting_started_at is not None and (now - misting_started_at) >= duration
        if current_humidity >= top or duration_reached:
            misting_active = False
            misting_started_at = None
        return

    if current_humidity <= bottom:
        misting_active = True
        misting_started_at = now


def simulate_environment():
    """Simulasi efek fisik misting terhadap suhu dan kelembapan."""
    global current_temp, current_humidity

    current_temp = max(23.0, min(27.0, current_temp + random.uniform(-0.3, 0.3)))

    if misting_active:
        current_humidity += random.uniform(1.0, 3.0)
        current_temp -= random.uniform(0.0, 0.1)
    else:
        current_humidity -= random.uniform(0.5, 1.5)

    current_humidity = max(70.0, min(99.0, current_humidity))


def send_telemetry():
    """Kirim telemetry aktual dari ESP32 ke Laravel."""
    payload = {
        "enclosure_id": ENCLOSURE_ID,
        "temperature": round(current_temp, 2),
        "humidity": round(current_humidity, 2),
        "misting_status": misting_active,
        "misting_duration_executed": last_config["misting_duration_seconds"] if misting_active else None,
        "device_timestamp": datetime.now().isoformat(),
    }

    try:
        response = requests.post(f"{BASE_URL}/telemetry", json=payload, headers=headers(), timeout=5)
        return payload, response.status_code, response.json()
    except requests.exceptions.ConnectionError:
        return payload, None, {"error": "Connection refused — pastikan Laravel server berjalan"}
    except requests.exceptions.Timeout:
        return payload, None, {"error": "Request timeout"}
    except Exception as exc:
        return payload, None, {"error": str(exc)}


def main():
    print("=" * 72)
    print("🦎 Smart Enclosure — ESP32 Telemetry Simulator")
    print("   ESP32-Centric Rule-Based Control")
    print("=" * 72)
    print(f"  Base URL     : {BASE_URL}")
    print(f"  Enclosure ID : {ENCLOSURE_ID}")
    print(f"  Interval     : {INTERVAL}s")
    print("  Misting      : Diputuskan lokal oleh ESP32/simulator")
    print("=" * 72)
    print()

    cfg_status, cfg_response = fetch_control_config()
    if cfg_status == 200:
        print(f"✅ Config awal: {last_config}")
    else:
        print(f"⚠️ Config awal gagal, pakai default: {cfg_response.get('error') or cfg_response.get('message')}")
    print()

    tick = 0
    while True:
        tick += 1
        timestamp = datetime.now().strftime("%H:%M:%S")

        if tick == 1 or tick % 6 == 0:
            fetch_control_config()

        evaluate_rule_based_control()
        simulate_environment()
        payload, status_code, response = send_telemetry()

        misting_icon = "💧" if misting_active else "  "
        config_text = (
            f"B:{last_config['bottom_humidity']:.1f}% "
            f"T:{last_config['top_humidity']:.1f}% "
            f"D:{last_config['misting_duration_seconds']}s"
        )

        if status_code == 201:
            log_id = response["data"]["sensor_log_id"]
            print(
                f"[{timestamp}] #{tick:04d} "
                f"🌡 {payload['temperature']:5.2f}°C "
                f"💦 {payload['humidity']:5.2f}% "
                f"{misting_icon} {config_text} "
                f"→ ✅ OK (log: {log_id})"
            )
        elif status_code is not None:
            print(
                f"[{timestamp}] #{tick:04d} "
                f"🌡 {payload['temperature']:5.2f}°C "
                f"💦 {payload['humidity']:5.2f}% "
                f"→ ❌ HTTP {status_code}: {response.get('message', 'Error')}"
            )
        else:
            print(f"[{timestamp}] #{tick:04d} → ⚠️ {response.get('error', 'Unknown error')}")

        time.sleep(INTERVAL)


if __name__ == "__main__":
    try:
        main()
    except KeyboardInterrupt:
        print("\n\n🛑 Simulator dihentikan.")
        sys.exit(0)
