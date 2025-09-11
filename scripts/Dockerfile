FROM python:3.11-slim

WORKDIR /app

# Install dependency OS yang dibutuhkan
RUN apt-get update && apt-get install -y --no-install-recommends \
    ffmpeg libsm6 libxext6 \
    && rm -rf /var/lib/apt/lists/*

# Salin requirements.txt
COPY requirements.txt .

# Install Python dependencies
RUN pip install --upgrade pip && \
    pip install --no-cache-dir -i https://pypi.tuna.tsinghua.edu.cn/simple --default-timeout=200 --retries 10 -r requirements.txt

# Salin semua script ke container
COPY . /app

# Jalankan watcher.py saat container start
CMD ["python3", "/app/watcher.py"]
