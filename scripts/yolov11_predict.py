import os
import sys
import json
import cv2
import logging
import contextlib
from ultralytics.utils import LOGGER
from sahi import AutoDetectionModel
from sahi.predict import get_sliced_prediction
from pathlib import Path
import numpy as np
from sort import Sort
import subprocess

# === Konfigurasi direktori ===
MEDIA_DIR = "/var/www/html/public/media"
PROGRESS_FILE = os.path.join(MEDIA_DIR, "progress.txt")
RESULT_FILE = os.path.join(MEDIA_DIR, "result.json")
VIDEO_DIR = os.path.join(MEDIA_DIR, "output/videos")
IMAGE_DIR = os.path.join(MEDIA_DIR, "output/images")

os.makedirs(VIDEO_DIR, exist_ok=True)
os.makedirs(IMAGE_DIR, exist_ok=True)


# === Utility suppress log ===
@contextlib.contextmanager
def suppress_all_output():
    with open(os.devnull, "w") as devnull:
        with contextlib.redirect_stdout(devnull), contextlib.redirect_stderr(devnull):
            previous_level = LOGGER.level
            LOGGER.setLevel(logging.ERROR)
            yield
            LOGGER.setLevel(previous_level)


def update_json(json_text):
    try:
        with open(RESULT_FILE, "w", encoding="utf-8") as f:
            f.write(json_text)
    except Exception as e:
        print(f"Failed to write to result.json: {e}")


def update_progress(percent):
    try:
        with open(PROGRESS_FILE, "w") as f:
            f.write(str(percent))
    except Exception as e:
        print(f"Failed to write progress: {e}")


# === Warna ID untuk tracking ===
ID_COLOR_MAP = {}

def get_color_by_id(track_id):
    if track_id not in ID_COLOR_MAP:
        color = tuple(int(x) for x in np.random.choice(range(100, 255), size=3))
        ID_COLOR_MAP[track_id] = color
    return ID_COLOR_MAP[track_id]


# === Deteksi pakai SAHI ===
def get_sahi_detections(frame, detection_model, object_class):
    height, width = frame.shape[:2]

    results = get_sliced_prediction(
        frame[..., ::-1],
        detection_model,
        slice_height=height // 2,
        slice_width=width // 2,
        overlap_height_ratio=0.0,
        overlap_width_ratio=0.0,
        postprocess_type="NMS",
        postprocess_match_metric="IOU",
        postprocess_match_threshold=0.4,
    )

    dets_to_sort = np.empty((0, 6))
    for pred in results.object_prediction_list:
        score = pred.score.value
        label_id = pred.category.id
        if label_id in object_class and score > 0.6:
            x1, y1, x2, y2 = pred.bbox.to_xyxy()
            dets_to_sort = np.vstack(
                (dets_to_sort, np.array([x1, y1, x2, y2, score, label_id]))
            )

    return dets_to_sort


# === Tracking + Gambar box/garis ===
def process_and_track(detection_model, frame, sort_tracker, dets_to_sort):
    im0 = frame.copy()

    if dets_to_sort.shape[0] == 0:
        dets_to_sort = np.empty((0, 6), dtype=np.float32)

    sort_tracker.update(dets_to_sort)
    class_names = detection_model.model.names if hasattr(detection_model.model, 'names') else {}

    for track in sort_tracker.trackers:
        if getattr(track, 'time_since_update', 1) > 1:
            continue
        track_id = getattr(track, 'id', -1)
        detclass = getattr(track, 'detclass', -1)

        if len(track.bbox_history) == 0:
            continue

        bbox = track.bbox_history[-1][:4]
        x1, y1, x2, y2 = map(int, bbox)
        class_name = class_names.get(detclass, "Unknown")
        score = track.bbox_history[-1][4] if len(track.bbox_history[-1]) > 4 else 0.0

        color = get_color_by_id(track_id)

        # Box
        cv2.rectangle(im0, (x1, y1), (x2, y2), color, 2)
        label_text = f"ID {track_id} {class_name} {score:.2f}"

        text_size, _ = cv2.getTextSize(label_text, cv2.FONT_HERSHEY_SIMPLEX, 2.0, 4)
        text_width, text_height = text_size
        text_x, text_y = x1, y1 - 10 if y1 - 10 > 10 else y1 + 40

        cv2.rectangle(im0, (text_x, text_y - text_height - 10),
                      (text_x + text_width + 10, text_y + 10), color, -1)

        cv2.putText(im0, label_text, (text_x, text_y),
                    cv2.FONT_HERSHEY_SIMPLEX, 2.0, (0, 0, 0), 4)

        # Path
        if hasattr(track, 'centroidarr') and len(track.centroidarr) > 1:
            for j in range(len(track.centroidarr) - 1):
                pt1 = (int(track.centroidarr[j][0]), int(track.centroidarr[j][1]))
                pt2 = (int(track.centroidarr[j + 1][0]), int(track.centroidarr[j + 1][1]))
                cv2.line(im0, pt1, pt2, (0, 255, 0), 2)

    return im0


# === Proses video ===
def process_video(file_path, detection_model, detection_id, srt_path=None):
    update_progress(10)
    result_folder = Path(VIDEO_DIR) / f"track_{detection_id}"
    result_folder.mkdir(parents=True, exist_ok=True)

    sort_tracker = Sort(max_age=5, min_hits=2, iou_threshold=0.3)
    best_predictions = {}

    cap = cv2.VideoCapture(file_path)
    if not cap.isOpened():
        print(json.dumps({"error": "Gagal membuka video input"}))
        return

    width = int(cap.get(cv2.CAP_PROP_FRAME_WIDTH))
    height = int(cap.get(cv2.CAP_PROP_FRAME_HEIGHT))
    fps = cap.get(cv2.CAP_PROP_FPS)
    frame_count = int(cap.get(cv2.CAP_PROP_FRAME_COUNT))

    temp_output_path = result_folder / "temp_output.avi"
    out = cv2.VideoWriter(str(temp_output_path),
                          cv2.VideoWriter_fourcc(*'XVID'), fps, (width, height))

    frame_id = 0
    trackpoints = []

    while True:
        ret, frame = cap.read()
        if not ret:
            break

        all_class_ids = list(detection_model.model.names.keys())
        dets_to_sort = get_sahi_detections(frame, detection_model, object_class=all_class_ids)

        if dets_to_sort.shape[0] > 0:
            for det in dets_to_sort:
                x1, y1, x2, y2, score, class_id = det
                class_id = int(class_id)
                score = float(score)
                if class_id not in best_predictions or score > best_predictions[class_id]['confidence']:
                    best_predictions[class_id] = {
                        "label": detection_model.model.names.get(class_id, f"Class {class_id}"),
                        "confidence": score
                    }
            trackpoints.append(frame_id)

        im_tracked = process_and_track(detection_model, frame, sort_tracker, dets_to_sort)
        out.write(im_tracked)

        update_progress(10 + int(60 * (frame_id / frame_count)))
        frame_id += 1

    cap.release()
    out.release()
    update_progress(80)

    final_output_path = result_folder / "tracked_output.mp4"
    subprocess.run([
        "ffmpeg", "-y",
        "-i", str(temp_output_path),
        "-vcodec", "libx264",
        "-pix_fmt", "yuv420p",
        str(final_output_path)
    ], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)

    relative_save_path = f"output/videos/track_{detection_id}/tracked_output.mp4"
    output_json = json.dumps({
        "id": detection_id,
        "predictions": list(best_predictions.values()),
        "isVideo": True,
        "saved_file": relative_save_path,
        "trackpoint": trackpoints,
        "srt_file_path": srt_path
    }, indent=4)

    update_json(output_json)
    update_progress(100)


# === Proses gambar ===
def process_image(file_path, detection_model, detection_id):
    update_progress(10)
    result_folder = Path(IMAGE_DIR) / f"detect_{detection_id}"
    result_folder.mkdir(parents=True, exist_ok=True)

    sort_tracker = Sort(max_age=5, min_hits=2, iou_threshold=0.3)
    frame = cv2.imread(file_path)
    if frame is None:
        print(json.dumps({"error": "Gagal membuka file gambar"}))
        return

    all_class_ids = list(detection_model.model.names.keys())
    dets_to_sort = get_sahi_detections(frame, detection_model, object_class=all_class_ids)
    update_progress(40)

    best_predictions = {}
    trackpoints = [0] if dets_to_sort.shape[0] > 0 else []

    if dets_to_sort.shape[0] > 0:
        for det in dets_to_sort:
            x1, y1, x2, y2, score, class_id = det
            class_id = int(class_id)
            score = float(score)
            if class_id not in best_predictions or score > best_predictions[class_id]['confidence']:
                best_predictions[class_id] = {
                    "label": detection_model.model.names.get(class_id, f"Class {class_id}"),
                    "confidence": score
                }

    im_tracked = process_and_track(detection_model, frame, sort_tracker, dets_to_sort)
    update_progress(70)

    image_output_path = result_folder / "tracked_output.jpg"
    cv2.imwrite(str(image_output_path), im_tracked)

    relative_save_path = f"output/images/detect_{detection_id}/tracked_output.jpg"
    output_json = json.dumps({
        "id": detection_id,
        "predictions": list(best_predictions.values()),
        "isVideo": False,
        "saved_file": relative_save_path,
        "trackpoint": trackpoints
    }, indent=4)

    update_json(output_json)
    update_progress(100)


# === Main function ===
def run_yolo(file_path, detection_id, srt_path=None):
    script_dir = os.path.dirname(os.path.abspath(__file__))
    model_path = os.path.join(script_dir, "best_tcl_200epoch.pt")

    model = AutoDetectionModel.from_pretrained(
        model_type="ultralytics",
        model_path=model_path,
        confidence_threshold=0.7,
        device="cuda" if cv2.cuda.getCudaEnabledDeviceCount() > 0 else "cpu"
    )

    ext = os.path.splitext(file_path)[-1].lower()
    if ext in [".mp4", ".avi", ".mov", ".mkv"]:
        process_video(file_path, model, detection_id, srt_path)
    else:
        process_image(file_path, model, detection_id)


# === CLI entrypoint ===
if __name__ == "__main__":
    if len(sys.argv) < 3:
        print(json.dumps({"error": "Usage: python yolov11_predict.py <file_path> <detection_id> [srt_file_path]"}))
        sys.exit(1)

    file_path = sys.argv[1]
    detection_id = sys.argv[2]
    srt_path = sys.argv[3] if len(sys.argv) > 3 else None

    run_yolo(file_path, detection_id, srt_path)
