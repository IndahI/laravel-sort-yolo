## Laravel with Bootstrap deploy YOLOv11 Object Tracking using Simple Online and Real Time Tracking 🚀

### What's you need in your local to run this code
1. Laravel
2. Composer
3. Node js
4. Mysql
5. Python


### Caution
I am using relative path for python call and other data like in yolov11_predict.py<br>
Please change it if you want to try in your local linux
1. YOLOController
    ```bash
    $python = "python3";
    ```
2. yolov11_predict.py
    ```bash
    SCRIPT_DIR = os.path.dirname(__file__)  # .../project/scripts

    BASE_DIR = os.path.abspath(os.path.join(SCRIPT_DIR, ".."))  # naik 1 level ke .../project

    result_path = os.path.join(BASE_DIR, "storage", "app", "result.json")
    progress_path = os.path.join(BASE_DIR, "storage", "app", "progress.txt")
    ```

    ```bash
    ffmpeg_path = "ffmpeg"
    ```


### How to Run the Code

1. Clone the repository:
    ```bash
    git clone https://github.com/IndahI/websitepa_final
    ```
2. Navigate to the cloned folder:
    ```bash
    cd websitepa_final
    ```
3. Get FFMPEG package
    ```bash
    sudo apt update
    sudo apt install ffmpeg libx264-dev
    ```
4. Update pip and install dependencies for the detect and tracking model:
    ```bash
    pip install --upgrade pip
    pip install -r requirements.txt
    ```
5. Migrate the database
    ```bash
    php artisan migrate
    ```
6. Make folder inside storage/app/public
    ```bash
    mkdir uploads
    mkdir videos
    mkdir images
    ```
7. Make those folder shortcut
    ```bash
    php artisan storage:link
    ```
8. Run the script:<br>
    You will need two terminal
    - Laravel:
      ```bash
      php artisan serve
      ```

    - Bootstrap:
      ```bash
      npm run dev
      ```

### References

- [YOLOv7 Object Tracking GitHub](https://github.com/RizwanMunawar/yolov7-object-tracking.git)
- [SORT GitHub](https://github.com/abewley/sort)