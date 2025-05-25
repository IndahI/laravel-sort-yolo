## Laravel with Bootstrap deploy YOLOv11 Object Tracking using Simple Online and Real Time Tracking 🚀

### What's you need in your local to run this code
1. Laravel
2. Composer
3. Node js
4. Mysql
5. Python

### How to Run the Code

1. Clone the repository:
    ```bash
    git clone https://github.com/IndahI/websitepa_final
    ```
2. Navigate to the cloned folder:
    ```bash
    cd websitepa_final
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