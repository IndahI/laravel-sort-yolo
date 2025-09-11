## Laravel with Bootstrap deploy YOLOv11 Object Tracking using Simple Online and Real Time Tracking Development Environment (Docker)

### What's you need in your local to run this code
1. Docker & Docker Compose 
2. Node.js & npm 

### How to Run the Code

1. Clone the repository:
    ```bash
    git clone https://github.com/IndahI/laravel-sort-yolo
    ```
2. Navigate to the cloned folder:
    ```bash
    cd laravel-sort-yolo
    ```
3. Build container:
    ```bash
   docker compose build
    ```
4. Run the container:
    ```bash
    docker compose up -d
    ```
5. Install depedency laravel
    ```bash
    docker exec -it laravel-app composer install
    docker exec -it laravel-app php artisan key:generate
    ```
6. Application access:
    ```bash
    Laravel: http://localhost:8000
    ```

### References

- [YOLOv7 Object Tracking GitHub](https://github.com/RizwanMunawar/yolov7-object-tracking.git)
- [SORT GitHub](https://github.com/abewley/sort)