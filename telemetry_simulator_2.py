"""
Smart Enclosure — ESP32 Telemetry Simulator (Kandang 2)
=======================================================

Simulator ini mewakili kondisi EKSTREM (Panas Terik):
- Suhu lebih tinggi.
- Dry-Out Period: Pukul 08:00 - 16:00 (8 Jam)
- Penurunan Kelembaban: 85% -> 50%
- Aturan Darurat: Jika kelembaban <= 50%, misting aktif otomatis.
- Terdapat Cooldown antar misting.

Cara menjalankan:
    python telemetry_simulator_2.py
"""

import random
import sys
import time
from datetime import datetime

import requests

# ─── Konfigurasi ──────────────────────────────────────────────
BASE_URL = "http://localhost:8000/api"
ENCLOSURE_ID = 2
INTERVAL = 10  # detik
DEVICE_KEY = None

# PARAMETER ACUAN: ILAR Journal & Frogfather UK Standards
CRITICAL_TEMP_HIGH = 31.0  # Memicu evaporative cooling darurat jika kelembaban rendah
CRITICAL_HUMID_LOW = 50.0  # Titik kritis penguapan hari terik

# ─── State Simulasi ESP32 ─────────────────────────────────────
current_temp = 27.0
current_humidity = 85.0
misting_active = False
misting_started_at = None
last_misting_duration_run = 0

last_misting_ended_at = None
COOLDOWN_SECONDS = 14400  # Jeda wajib 4 jam (4 * 60 * 60 detik)

# State Misting Spike
spike_active = False
spike_target_humidity = 0.0
spike_end_time = None

last_config = {
    "bottom_humidity": 80.0,
    "top_humidity": 90.0,
    "misting_duration_seconds": 15,
    "mode": "auto",
}

def headers():
    h = {"Accept": "application/json", "Content-Type": "application/json"}
    if DEVICE_KEY:
        h["X-DEVICE-KEY"] = DEVICE_KEY
    return h

def restore_last_state():
    """Ambil data telemetri terakhir dari API agar simulator melanjutkan
    dari kondisi terakhir sebelum dimatikan, bukan dari nilai hardcoded."""
    global current_temp, current_humidity
    url = f"{BASE_URL}/enclosures/{ENCLOSURE_ID}/latest"
    try:
        response = requests.get(url, headers=headers(), timeout=5)
        if response.status_code == 200:
            data = response.json()
            telemetry = data.get("data", {}).get("telemetry")
            if telemetry:
                current_temp = float(telemetry["temperature"])
                current_humidity = float(telemetry["humidity"])
                print(f"  ✅ State dipulihkan dari data terakhir:")
                print(f"     Suhu: {current_temp:.2f}°C | RH: {current_humidity:.2f}%")
                return True
        print("  ⚠️  Tidak ada data terakhir di server. Menggunakan nilai default.")
        return False
    except requests.exceptions.ConnectionError:
        print("  ⚠️  Server tidak tersedia. Menggunakan nilai default.")
        return False
    except Exception as exc:
        print(f"  ⚠️  Gagal memulihkan state: {exc}. Menggunakan nilai default.")
        return False

def fetch_control_config():
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
                "force_mist_now": config.get("force_mist_now", False)
            }
        return response.status_code, data
    except requests.exceptions.ConnectionError:
        return None, {"error": "Connection refused"}
    except Exception as exc:
        return None, {"error": str(exc)}

# State Cooling
cooling_active = False
cooling_temp_offset = 0.0
cooling_end_time = None

def evaluate_rule_based_control():
    global misting_active, misting_started_at, last_misting_ended_at, last_misting_duration_run
    global spike_active, spike_target_humidity, spike_end_time
    global cooling_active, cooling_temp_offset, cooling_end_time

    bottom = float(last_config["bottom_humidity"])
    top = float(last_config["top_humidity"])
    config_duration = int(last_config["misting_duration_seconds"])
    now = time.monotonic()

    # Rule 0: Manual Misting Trigger dari Web UI (Bypass mode manual/auto)
    if last_config.get("force_mist_now") and not misting_active:
        print(f"  --> [MANUAL MISTING TRIGGERED] Menyemprot selama {config_duration}s")
        misting_active = True
        misting_started_at = now
        last_misting_duration_run = config_duration
        last_config["force_mist_now"] = False # Reset lokal
        return

    # Jika mode manual dan tidak sedang misting, abaikan semua aturan otomatis di bawah ini
    if last_config.get("mode") != "auto" and not misting_active:
        return

    # Rule Darurat Tambahan: Suhu Kritis Ekstrem (Suhu > 31°C dan Kelembaban < 55%)
    # Mengabaikan cooldown jika kondisi darurat!
    if current_temp > CRITICAL_TEMP_HIGH and current_humidity < 55.0 and not misting_active:
        misting_active = True
        misting_started_at = now
        last_misting_duration_run = 15
        return

    # Rule Darurat Asli: Kelembaban sangat kritis <= 50%
    # HANYA BERLAKU UNTUK KATEGORI BASAH / AMPHIBIAN
    habitat = last_config.get("target_habitat", "") or ""
    is_basah = "Basah" in habitat or "Tropical High" in habitat or "Amphibian" in habitat
    
    if is_basah and current_humidity <= CRITICAL_HUMID_LOW and not misting_active:
        misting_active = True
        misting_started_at = now
        last_misting_duration_run = 15 # Override konfigurasi
        return

    if misting_active:
        run_duration = last_misting_duration_run if last_misting_duration_run > 0 else config_duration
        duration_reached = misting_started_at is not None and (now - misting_started_at) >= run_duration
        
        if current_humidity >= top or duration_reached:
            misting_active = False
            misting_started_at = None
            last_misting_ended_at = now
            
            actual_dur = run_duration
            
            # 1. Hitung Evaporative Cooling
            if actual_dur >= 20:
                c_drop = random.uniform(3.0, 5.0)
                c_time_mins = random.uniform(60, 120)
            elif actual_dur >= 10:
                c_drop = random.uniform(2.0, 3.0)
                c_time_mins = random.uniform(30, 45)
            elif actual_dur >= 5:
                c_drop = random.uniform(1.0, 1.5)
                c_time_mins = random.uniform(10, 15)
            else:
                c_drop = random.uniform(0.5, 1.0)
                c_time_mins = 5.0
            
            cooling_active = True
            cooling_temp_offset = c_drop
            cooling_end_time = now + (c_time_mins * 60)
            
            # 2. Misting Spike Logic
            if actual_dur <= 10:
                spike_target = random.uniform(75, 80)
                spike_time_mins = random.uniform(1, 2)
            elif actual_dur <= 20:
                spike_target = random.uniform(85, 90)
                spike_time_mins = random.uniform(2, 3)
            elif actual_dur <= 30:
                spike_target = random.uniform(95, 100)
                spike_time_mins = random.uniform(3, 4)
            else:
                spike_target = 100.0
                spike_time_mins = 5.0
            
            if spike_target > current_humidity:
                spike_active = True
                spike_target_humidity = spike_target
                spike_end_time = now + (spike_time_mins * 60)
            
            last_misting_duration_run = 0
        return

    # Normal Rule + Cooldown
    is_in_cooldown = last_misting_ended_at is not None and (now - last_misting_ended_at) < COOLDOWN_SECONDS

    if current_humidity <= bottom and not is_in_cooldown:
        misting_active = True
        misting_started_at = now
        last_misting_duration_run = config_duration

def simulate_environment():
    global current_temp, current_humidity, spike_active, cooling_active

    now_dt = datetime.now()
    hour = now_dt.hour
    minute = now_dt.minute
    time_float = hour + (minute / 60.0)
    now = time.monotonic()

    # --- Kurva Suhu & Kelembaban Ekstrem (Real Time) ---
    # Extreme Day: 08:00 - 16:00 (8 Jam)
    if 8.0 <= time_float < 16.0:
        # Pagi ke Siang Terik (08:00 - 13:00)
        if time_float <= 13.0:
            progress = (time_float - 8.0) / 5.0
            base_temp = 25.0 + (progress * 10.0) # 25 -> 35
            base_hum = 85.0 - (progress * 35.0)  # 85 -> 50
        else:
            # Siang ke Sore (13:00 - 16:00)
            progress = (time_float - 13.0) / 3.0
            base_temp = 35.0 - (progress * 5.0)  # 35 -> 30
            base_hum = 50.0 + (progress * 20.0)  # 50 -> 70
    elif 16.0 <= time_float < 20.0:
        # Sore ke Malam (16:00 - 20:00)
        progress = (time_float - 16.0) / 4.0
        base_temp = 30.0 - (progress * 5.0)      # 30 -> 25
        base_hum = 70.0 + (progress * 15.0)      # 70 -> 85
    else:
        # Malam Hari (Hutan Tropis) — kurva halus berbasis waktu
        # Suhu bergerak perlahan antara 22.0 - 25.0°C mengikuti pola sinusoidal
        import math
        night_progress = ((time_float - 20.0) % 24.0) / 12.0  # 0..1 selama 12 jam malam
        base_temp = 23.5 + 1.5 * math.sin(night_progress * math.pi)  # 22.0 - 25.0
        base_hum = 90.0 + 5.0 * math.sin(night_progress * math.pi * 0.5)  # 85.0 - 95.0
    
    current_temp = base_temp

    # Terapkan Evaporative Cooling jika aktif
    if cooling_active:
        if now <= cooling_end_time:
            current_temp -= cooling_temp_offset
        else:
            cooling_active = False

    # Tambah noise ke suhu
    current_temp += random.uniform(-0.15, 0.15)

    if misting_active:
        current_humidity += random.uniform(0.5, 1.0)
        current_temp -= random.uniform(0.1, 0.2)
    elif spike_active:
        if now <= spike_end_time and current_humidity < spike_target_humidity:
            time_remaining = max(1.0, spike_end_time - now)
            gap = spike_target_humidity - current_humidity
            current_humidity += (gap / (time_remaining / INTERVAL)) * random.uniform(0.9, 1.1)
        else:
            spike_active = False
    else:
        # Natural Drift menuju Base Humidity Ekstrem (Lebih cepat mengering)
        hum_diff = base_hum - current_humidity
        current_humidity += (hum_diff * 0.08) 
        current_humidity += random.uniform(-0.3, 0.3)

    # Batasi nilai logika
    current_humidity = max(30.0, min(100.0, current_humidity))

def send_telemetry():
    payload = {
        "enclosure_id": ENCLOSURE_ID,
        "temperature": round(current_temp, 2),
        "humidity": round(current_humidity, 2),
        "misting_status": misting_active,
        "misting_duration_executed": last_misting_duration_run if misting_active else None,
        "device_timestamp": datetime.now().isoformat(),
    }
    try:
        response = requests.post(f"{BASE_URL}/telemetry", json=payload, headers=headers(), timeout=5)
        return payload, response.status_code, response.json()
    except requests.exceptions.ConnectionError:
        return payload, None, {"error": "Connection refused"}
    except Exception as exc:
        return payload, None, {"error": str(exc)}

def main():
    print("=" * 72)
    print("  Smart Enclosure — Simulator Kandang 2 (Extreme Day)")
    print("=" * 72)
    print("  Mode     : Dry-out 8 jam (08:00 - 16:00)")
    print("  Interval : 10s")
    print("=" * 72)

    restore_last_state()
    fetch_control_config()

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
        spike_icon = "📈" if spike_active and not misting_active else "  "
        
        cfg = last_config
        config_text = f"B:{cfg['bottom_humidity']:.1f}% T:{cfg['top_humidity']:.1f}% D:{cfg['misting_duration_seconds']}s"

        cooldown_status = ""
        if not misting_active and last_misting_ended_at is not None:
            time_since_last = time.monotonic() - last_misting_ended_at
            if time_since_last < COOLDOWN_SECONDS:
                remains = int(COOLDOWN_SECONDS - time_since_last)
                h = remains // 3600
                m = (remains % 3600) // 60
                s = remains % 60
                cooldown_status = f" [Cd: {h:02d}:{m:02d}:{s:02d}]"

        if status_code == 201:
            print(f"[{timestamp}] #{tick:04d} T:{payload['temperature']:5.2f}C H:{payload['humidity']:5.2f}% {config_text}{cooldown_status} -> OK")
        else:
            reason = response.get('error', f"HTTP {status_code}") if isinstance(response, dict) else f"HTTP {status_code}"
            print(f"[{timestamp}] #{tick:04d} -> Error: {reason}")

        time.sleep(INTERVAL)

if __name__ == "__main__":
    try:
        main()
    except KeyboardInterrupt:
        sys.exit(0)
