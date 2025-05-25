import os
import sys
import json
import cv2
import re
from ultralytics import YOLO
from pathlib import Path
import numpy as np
from sort import Sort

def convert_video_to_mp4(input_path, output_path):
    """
    Convert video to MP4 format using OpenCV with a more compatible codec
    """
    cap = cv2.VideoCapture(input_path)
    if not cap.isOpened():
        raise Exception("Gagal membuka video input")

    fourcc = cv2.VideoWriter_fourcc(*'avc1')  
    fps = cap.get(cv2.CAP_PROP_FPS)
    width = int(cap.get(cv2.CAP_PROP_FRAME_WIDTH))
    height = int(cap.get(cv2.CAP_PROP_FRAME_HEIGHT))

    out = cv2.VideoWriter(output_path, fourcc, fps, (width, height))

    while cap.isOpened():
        ret, frame = cap.read()
        if not ret:
            break
        out.write(frame)

    cap.release()
    out.release()
    os.remove(input_path)  


def get_bounding_boxes(frame, model, object_class, bbox_txt=None, frame_id=None):
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

def main(file_path):
    try:
        script_dir = os.path.dirname(os.path.abspath(__file__))
        model_path = os.path.join(script_dir, "best_nd_final_200epoch.pt")

        if not os.path.exists(model_path):
            print(json.dumps({"error": f"Model tidak ditemukan di {model_path}"}))
            return

        model = YOLO(model_path)

        if not os.path.isfile(file_path):
            print(json.dumps({"error": f"File tidak ditemukan: {file_path}"}))
            return
        
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

            final_output_path = result_folder / "tracked_output.mp4"
            out = cv2.VideoWriter(str(final_output_path), cv2.VideoWriter_fourcc(*'avc1'), fps, (width, height))

            frame_id = 0
            trackpoints = []
            last_prediction = None

            while True:
                ret, frame = cap.read()
                if not ret:
                    break

                im_tracked = process_and_track(model, frame, sort_tracker, frame_id)
                out.write(im_tracked)

                trackpoints.append(frame_id)

                # Ambil prediksi dari frame ini
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

            # Konversi output akhir jika belum MP4
            detected_video_path = final_output_path
            if detected_video_path and detected_video_path.exists():
                converted_output_path = result_folder / (detected_video_path.stem + ".mp4")
                if detected_video_path.suffix.lower() != ".mp4":
                    convert_video_to_mp4(str(detected_video_path), str(converted_output_path))
                    final_output_path = converted_output_path
                else:
                    final_output_path = detected_video_path

            relative_save_path = f"videos/{result_folder.name}/{final_output_path.name}"
            output_json = json.dumps({
                "predictions": [last_prediction] if last_prediction else [],
                "isVideo": True,
                "saved_file": relative_save_path,
                "trackpoint": trackpoints
            }, indent=4)

            def format_trackpoint_single_line(json_text):
                return re.sub(
                    r'"trackpoint": \[\s*((?:\d+,?\s*)+)\]',
                    lambda m: '"trackpoint": [' + ','.join(x.strip() for x in m.group(1).split(',')) + ']',
                    json_text
                )

            output_json = format_trackpoint_single_line(output_json)
            print(output_json)


        else:
            detected_img_path = None
            results = model(file_path, save=True, project=str(save_dir_img), name=save_name_images)
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
                "predictions": predictions,
                "saved_file": relative_save_path
            }, indent=4)
            print(output_json)

    except Exception as e:
        print(json.dumps({"error": str(e)}))

if __name__ == "__main__":
    if len(sys.argv) != 2:
        print(json.dumps({"error": "Usage: python yolo_predict.py <file_path>"}))
        sys.exit(1)

    file_path = sys.argv[1]
    main(file_path)
