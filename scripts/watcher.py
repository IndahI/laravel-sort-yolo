import time
import os
import mysql.connector
from datetime import datetime
from mysql.connector import Error
from yolov11_predict import run_yolo

MEDIA_DIR = "/var/www/html/public/media/input"
PYTHON_LOG = "/var/www/html/public/media/python.log"

# Pastikan direktori log ada
os.makedirs(os.path.dirname(PYTHON_LOG), exist_ok=True)

def python_log(level, message, context=None):
    """Tulis log ke python.log dengan format mirip Laravel"""
    timestamp = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    ctx_str = f" {context}" if context else ""
    with open(PYTHON_LOG, "a") as f:
        f.write(f"[{timestamp}] local.{level.upper()}: {message}{ctx_str}\n")

def get_connection():
    """Buat koneksi MySQL baru"""
    return mysql.connector.connect(
        host="laravel-mysql",
        user="user",
        password="secret",
        database="laravel"
    )

# Inisialisasi koneksi
conn = get_connection()
cursor = conn.cursor(dictionary=True)

while True:
    try:
        # Pastikan koneksi masih hidup, kalau tidak reconnect
        conn.ping(reconnect=True, attempts=3, delay=5)
        cursor = conn.cursor(dictionary=True)

        cursor.execute("SELECT * FROM detections WHERE status='pending'")
        rows = cursor.fetchall()

        for row in rows:
            media_path = os.path.join(MEDIA_DIR, os.path.basename(row['filename_original']))
            srt_path = os.path.join(MEDIA_DIR, os.path.basename(row['srt_file_path'])) if row['srt_file_path'] else None

            detection_id = row['id']

            # Log ambil pekerjaan
            python_log("info", "Watcher mendeteksi job baru", {
                "detection_id": detection_id,
                "file": row['filename_original'],
                "srt": row['srt_file_path']
            })

            try:
                # Update status jadi processing
                cursor.execute("UPDATE detections SET status='processing' WHERE id=%s", (detection_id,))
                conn.commit()

                # Log command yang akan dijalankan
                cmd_info = {
                    "media_path": media_path,
                    "srt_path": srt_path,
                    "script": "yolov11_predict.py"
                }
                python_log("info", "Menjalankan YOLO", cmd_info)

                # Jalankan YOLO
                run_yolo(media_path, detection_id, srt_path)

                # Update sukses
                cursor.execute("UPDATE detections SET status='done' WHERE id=%s", (detection_id,))
                conn.commit()
                python_log("info", "Job selesai", {"detection_id": detection_id})

            except Exception as e:
                cursor.execute("UPDATE detections SET status='failed' WHERE id=%s", (detection_id,))
                conn.commit()
                python_log("error", f"Job gagal: {e}", {"detection_id": detection_id})

    except Error as db_err:
        python_log("error", f"Koneksi MySQL error: {db_err}")
        try:
            conn = get_connection()
            cursor = conn.cursor(dictionary=True)
            python_log("info", "Koneksi MySQL berhasil direconnect")
        except Exception as e:
            python_log("error", f"Gagal reconnect MySQL: {e}")
            time.sleep(10)

    except Exception as e:
        python_log("error", f"Error tak terduga: {e}")

    time.sleep(5)
