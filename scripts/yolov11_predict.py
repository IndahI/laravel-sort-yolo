import os
import sys
import json
import cv2
import re
from ultralytics import YOLO
from pathlib import Path

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
        detected_video_path = None

        if is_video:
            trackpoints = []
            last_prediction = None
            results = model.track(file_path, save=True, project=str(save_dir), name=save_name, stream=True)

            frame_id = 0
            for result in results:
                boxes = result.boxes
                if boxes is not None and boxes.id is not None and len(boxes.id) > 0:
                    trackpoints.append(frame_id)

                    last_box_idx = -1
                    try:
                        label_id = int(boxes.cls[last_box_idx].item())
                        label_name = model.names[label_id]
                        confidence = round(boxes.conf[last_box_idx].item(), 4)
                        last_prediction = {
                            "label": label_name,
                            "confidence": confidence
                        }
                    except Exception as e:
                        print(f"Gagal mengambil data deteksi terakhir: {e}")
                frame_id += 1

            saved_folders = sorted(save_dir.glob(f"{save_name}*"), key=os.path.getmtime, reverse=True)
            result_folder = saved_folders[0] if saved_folders else save_dir / save_name

            video_files = list(result_folder.glob("*.*"))
            detected_video_path = video_files[0] if video_files else None

            if detected_video_path and detected_video_path.exists():
                final_output_path = result_folder / (detected_video_path.stem + ".mp4")
                if detected_video_path.suffix.lower() != ".mp4":
                    convert_video_to_mp4(str(detected_video_path), str(final_output_path))
                else:
                    final_output_path = detected_video_path

                relative_save_path = f"videos/{result_folder.name}/{final_output_path.name}"
                predictions = [last_prediction] if last_prediction else []

                def format_trackpoint_single_line(json_text):
                    return re.sub(
                        r'"trackpoint": \[\s*((?:\d+,?\s*)+)\]',
                        lambda m: '"trackpoint": [' + ','.join(x.strip() for x in m.group(1).split(',')) + ']',
                        json_text
                    )

                output_json = json.dumps({
                    "predictions": predictions,
                    "isVideo": is_video,
                    "saved_file": relative_save_path,
                    "trackpoint": trackpoints
                }, indent=4)

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
