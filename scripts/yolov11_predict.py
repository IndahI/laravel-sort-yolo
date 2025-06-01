import os
import sys
import json
import cv2
import re
import logging
import contextlib
from ultralytics import YOLO
from pathlib import Path
import numpy as np
from sort import Sort
import subprocess
from ultralytics.utils import LOGGER

# __file__ adalah path ke yolov11_predict.py (misal: /home/user/project/scripts/yolov11_predict.py)
SCRIPT_DIR = os.path.dirname(__file__)  # .../project/scripts

BASE_DIR = os.path.abspath(os.path.join(SCRIPT_DIR, ".."))  # naik 1 level ke .../project

result_path = os.path.join(BASE_DIR, "storage", "app", "result.json")
progress_path = os.path.join(BASE_DIR, "storage", "app", "progress.txt")

@contextlib.contextmanager
def suppress_all_output():
    with open(os.devnull, "w") as devnull:
        with contextlib.redirect_stdout(devnull), contextlib.redirect_stderr(devnull):
            # Matikan Ultralytics LOGGER
            previous_level = LOGGER.level
            LOGGER.setLevel(logging.ERROR)
            yield
            # Kembalikan level logger setelah selesai
            LOGGER.setLevel(previous_level)

def update_json(json_text):
    try:
        with open(result_path, "w", encoding="utf-8") as f:
            f.write(json_text)
    except Exception as e:
        print(f"Failed to write to result.json: {e}")

def update_progress(percent):
    try:
        with open(progress_path, "w") as f:
            f.write(str(percent))
    except Exception as e:
        print(f"Failed to write progress: {e}")

def get_bounding_boxes(frame, model, object_class, bbox_txt=None, frame_id=None):
    with suppress_all_output():
        results = model(frame)
    dets_to_sort = np.empty((0, 6))  # Prepare an empty array for SORT
    class_names = model.names  # Get class names from the model
    bounding_boxes = [] # Reset data bounding box setiap frame baru

    for result in results:
        data = result.boxes.xywhn.cpu().numpy().tolist()
        bounding_boxes.extend(data)

        boxes = result.boxes.data.cpu().numpy()  # Convert to numpy array
        for r in boxes:
            x1, y1, x2, y2, score, class_id = r
            x1, x2, y1, y2 = int(x1), int(x2), int(y1), int(y2)
            class_id = int(class_id)

            if class_id in object_class and score > 0.25:
                dets_to_sort = np.vstack((dets_to_sort, np.array([x1, y1, x2, y2, score, class_id])))

    return dets_to_sort, bounding_boxes


def process_and_track(model, frame, sort_tracker, frame_id, object_class=[0, 1, 2]):
    global im0
    im0 = frame.copy()  # Salin frame agar bisa digambar garisnya

    dets_to_sort, bounding_boxes = get_bounding_boxes(frame, model, object_class)

    if dets_to_sort.shape[0] == 0:
        dets_to_sort = np.empty((0, 6), dtype=np.float32)

    tracked_dets = sort_tracker.update(dets_to_sort)
    class_names = model.names
    updated_dets = []

    for i, track in enumerate(sort_tracker.trackers):
        if getattr(track, 'time_since_update', 1) > 1:
            continue  # Lewati track yang tidak aktif

        track_id = getattr(track, 'id', -1)
        detclass = getattr(track, 'detclass', -1)
        score = getattr(track, 'lastscore', 0.0)

        if len(track.bbox_history) == 0:
            continue

        bbox = track.bbox_history[-1][:4]
        x1, y1, x2, y2 = map(int, bbox)
        class_name = class_names.get(detclass, "Unknown")

        # Gambar garis track yang tetap tampil meskipun objek berpindah posisi
        if hasattr(track, 'centroidarr') and len(track.centroidarr) > 1:
            for j in range(len(track.centroidarr) - 1):
                pt1 = (int(track.centroidarr[j][0]), int(track.centroidarr[j][1]))
                pt2 = (int(track.centroidarr[j + 1][0]), int(track.centroidarr[j + 1][1]))
                color = (0, 255, 0)
                cv2.line(im0, pt1, pt2, color, 2)

        # Gambar bounding box dan teks label
        cv2.rectangle(im0, (x1, y1), (x2, y2), (255, 0, 0), 3)
        cv2.putText(im0, f"ID {track_id} {class_name}", (x1, y1 - 10),
                    cv2.FONT_HERSHEY_SIMPLEX, 0.5, (255, 0, 0), 2)

    return im0

def main(file_path, srt_path=None, detection_id=None):
    try:
        script_dir = os.path.dirname(os.path.abspath(__file__))
        model_path = os.path.join(script_dir, "best_nd_final_200epoch.pt")

        if not os.path.exists(model_path):
            print(json.dumps({"error": f"Model tidak ditemukan di {model_path}"}))
            return
        
        
        output_path = os.path.join("storage", "app", "result.json")  # pastikan path benar

        model = YOLO(model_path)
        update_progress(5)

        if not os.path.isfile(file_path):
            print(json.dumps({"error": f"File tidak ditemukan: {file_path}"}))
            return
        update_progress(10)
        
        file_ext = os.path.splitext(file_path)[1].lower()
        is_video = file_ext in [".mp4", ".avi", ".mov", ".mkv"]

        save_dir = Path(script_dir).parent / "storage/app/public/videos/"
        save_dir_img = Path(script_dir).parent / "storage/app/public/images/"
        save_name = "track"
        save_name_images = "detect"

        if is_video:
            sort_tracker = Sort(max_age=5, min_hits=2, iou_threshold=0.3)
            cap = cv2.VideoCapture(file_path)
            if not cap.isOpened():
                print(json.dumps({"error": "Gagal membuka video input"}))
                return

            width = int(cap.get(cv2.CAP_PROP_FRAME_WIDTH))
            height = int(cap.get(cv2.CAP_PROP_FRAME_HEIGHT))
            fps = cap.get(cv2.CAP_PROP_FPS)

            # Membuat folder iterasi track, track2, track3, ...
            i = 1
            while True:
                suffix = f"{save_name}{i}" if i > 1 else save_name
                result_folder = save_dir / suffix
                if not result_folder.exists():
                    result_folder.mkdir(parents=True)
                    break
                i += 1

            temp_output_path = result_folder / "temp_output.avi"
            out = cv2.VideoWriter(str(temp_output_path), cv2.VideoWriter_fourcc(*'XVID'), fps, (width, height))

            frame_id = 0
            trackpoints = []
            last_prediction = None
            total_frames = int(cap.get(cv2.CAP_PROP_FRAME_COUNT))

            while True:
                ret, frame = cap.read()
                if not ret:
                    break

                progress_now = int((frame_id / total_frames) * 80) + 10
                update_progress(progress_now)
                dets_to_sort, _ = get_bounding_boxes(frame, model, object_class=[0, 1, 2])

                if dets_to_sort.shape[0] == 0:
                    dets_to_sort = np.empty((0, 6), dtype=np.float32)

                # Update tracker sekali saja dan simpan hasilnya
                tracked_dets = sort_tracker.update(dets_to_sort)

                # Tambahkan frame_id hanya jika ada track aktif
                if tracked_dets.shape[0] > 0:
                    trackpoints.append(frame_id)

                # Proses gambar dan visualisasi
                im_tracked = process_and_track(model, frame, sort_tracker, frame_id)
                out.write(im_tracked)

                # Simpan prediksi terakhir (jika ada)
                with suppress_all_output():
                    results = model(frame)
                for result in results:
                    if result.boxes is not None and len(result.boxes) > 0:
                        last_box = result.boxes[-1]
                        label_id = int(last_box.cls.item())
                        label_name = model.names[label_id]
                        confidence = round(last_box.conf.item(), 4)
                        last_prediction = {
                            "label": label_name,
                            "confidence": confidence
                        }

                frame_id += 1

            cap.release()
            out.release()

            ffmpeg_path = "ffmpeg" # sesuaikan dengan milikmu
            final_output_path = result_folder / "tracked_output.mp4"

            update_progress(95)

            process = subprocess.Popen([
                ffmpeg_path, "-y",
                "-i", str(temp_output_path),
                "-vcodec", "libx264",
                "-pix_fmt", "yuv420p",
                str(final_output_path)
            ], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)

            process.wait()

            update_progress(98)

            relative_save_path = f"videos/{result_folder.name}/{final_output_path.name}"
            # Buat dict dulu, bukan string JSON
            output_dict = {
                "id" : detection_id,
                "predictions": [last_prediction] if last_prediction else [],
                "isVideo": True,
                "saved_file": relative_save_path,
                "trackpoint": trackpoints,
                "srt_file_path" : srt_path
            }

            # Buat string JSON setelah modifikasi dict
            output_json = json.dumps(output_dict, indent=4)

            def format_trackpoint_single_line(json_text):
                return re.sub(
                    r'"trackpoint": \[\s*((?:\d+,?\s*)+)\]',
                    lambda m: '"trackpoint": [' + ','.join(x.strip() for x in m.group(1).split(',')) + ']',
                    json_text
                )

            output_json = format_trackpoint_single_line(output_json)

            # Simpan JSON ke file atau update sesuai fungsimu
            update_json(output_json)
            update_progress(100)


        else:
            detected_img_path = None
            results = model(file_path, save=True, project=str(save_dir_img), name=save_name_images)
            update_progress(50)
            predictions = []
            for box in results[0].boxes:
                label_id = int(box.cls.item())
                label_name = model.names[label_id]
                confidence = round(box.conf.item(), 4)
                predictions.append({
                    "label": label_name,
                    "confidence": confidence
                })

            result_img_folders = sorted(save_dir_img.glob(f"{save_name_images}*"), key=os.path.getmtime, reverse=True)
            result_img_folder = result_img_folders[0] if result_img_folders else save_dir_img / save_name_images

            image_files = list(result_img_folder.glob("*.jpg"))
            if image_files:
                detected_img_path = image_files[0]

            if detected_img_path and detected_img_path.exists():
                final_output_path = detected_img_path
                relative_save_path = f"images/{result_img_folder.name}/{final_output_path.name}"
            else:
                image_name = os.path.basename(file_path)
                relative_save_path = f"images/detected_{image_name}"

            output_json = json.dumps({
                "id" : detection_id,
                "predictions": predictions,
                "saved_file": relative_save_path
            }, indent=4)
            # print(output_json)
            update_json(output_json)
            update_progress(100)

    except Exception as e:
        print(json.dumps({"error": str(e)}))

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print(json.dumps({"error": "Usage: python yolo_predict.py <file_path> [srt_file_path] [detection_id]"}))
        sys.exit(1)

    file_path = sys.argv[1]

    srt_path = None
    detection_id = None

    # Jika argumen ke-3 adalah file .srt, maka itu srt_path
    if len(sys.argv) >= 3 and sys.argv[2].lower().endswith('.srt'):
        srt_path = sys.argv[2]
        if len(sys.argv) >= 4:
            detection_id = sys.argv[3]
    elif len(sys.argv) >= 3:
        detection_id = sys.argv[2]

    main(file_path, srt_path, detection_id)
