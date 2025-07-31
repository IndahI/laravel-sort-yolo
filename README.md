## Laravel with Bootstrap deploy YOLOv11 Object Tracking using Simple Online and Real Time Tracking 🚀

### What's you need in your local to run this code
1. Laravel
2. Composer
3. Node js
4. Mysql
5. Python

### Caution
I am using absolute path for python call and other data like in yolov11_predict.py
Please change it if you want to try in your local windows
1. YOLOController
    ```bash
    $python = "C:\\Users\\Indah\\AppData\\Local\\Programs\\Python\\Python313\\python.exe";
    ```
2. yolov11_predict.py
    ```bash
    with open(r"C:\xampp\htdocs\laravel\websitepa_versi3\storage\app\result.json", "w", encoding="utf-8") as f:
    ```

     ```bash
    with open(r"C:\xampp\htdocs\laravel\websitepa_versi3\storage\app\progress.txt", "w") as f:
    ```

    ```bash
    ffmpeg_path =  r"C:\Users\Indah\Downloads\ffmpeg-2025-05-29-git-75960ac270-essentials_build\ffmpeg-2025-05-29-git-75960ac270-essentials_build\bin\ffmpeg.exe"
    ```

### How to Run the Code

1. Clone the repository:
    ```bash
    git clone https://github.com/IndahI/laravel-sort-yolo
    ```
2. Navigate to the cloned folder:
    ```bash
    cd laravel-sort-yolo
    ```
3. Update pip and install dependencies for the detect and tracking model:
    ```bash
    pip install --upgrade pip
    pip install -r requirements.txt
    ```
4. Migrate the database
    ```bash
    php artisan migrate
    ```
5. Make folder inside storage/app/public
    ```bash
    mkdir uploads
    mkdir videos
    mkdir images
    ```
6. Make those folder shortcut
    ```bash
    php artisan storage:link
    ```
6. Run the script:
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