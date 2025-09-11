document.addEventListener('DOMContentLoaded', function () {
    const fileInput = document.getElementById('file');
    const srtFileInput = document.getElementById('srt_file');
    const modelSelect = document.getElementById('model');
    const uploadButton = document.getElementById('uploadButton');

    const alertError = document.getElementById('alertError');
    const alertSuccess = document.getElementById('alertSuccess');
    const progressContainer = document.getElementById('progress-container');
    const progressBar = document.getElementById('progress-bar');
    const progressText = document.getElementById('progress-text');

    let progressInterval;

    // === VALIDASI INPUT ===
    fileInput.addEventListener('change', validateInputs);
    srtFileInput.addEventListener('change', validateInputs);

    function validateInputs() {
        const file = fileInput.files[0];
        if (!file) return;

        if (file.size > 104857600) { // 100MB
            showError('Ukuran file gambar/video tidak boleh lebih dari 100MB.');
            uploadButton.disabled = true;
            return;
        }

        const isVideo = file.type.startsWith('video');
        if (isVideo && srtFileInput.files.length === 0) {
            uploadButton.disabled = true;
        } else {
            resetAlerts();
            uploadButton.disabled = false;
        }
    }

    // === HANDLE SUBMIT ===
    document.getElementById("upload-form").addEventListener("submit", function (e) {
        e.preventDefault();

        const formData = new FormData(this);
        const mediaFile = fileInput.files[0];
        const isVideo = mediaFile && mediaFile.type.startsWith('video');

        // Validasi metadata file
        if (srtFileInput.files.length > 0) {
            const srtFile = srtFileInput.files[0];
            const allowedExtensions = ['.srt', '.txt'];
            const fileName = srtFile.name.toLowerCase();
            const isValidExtension = allowedExtensions.some(ext => fileName.endsWith(ext));

            if (!isValidExtension) {
                showError('File metadata harus berupa file .srt atau .txt');
                return;
            }
        }

        if (isVideo && srtFileInput.files.length === 0) {
            showError('File metadata (.srt / .txt) wajib diisi jika Anda memilih file video.');
            return;
        }

        resetAlerts();
        disableUpload(true);
        resetProgressBar();

        const selectedModel = modelSelect.value;
        const endpoint = selectedModel === 'yolov7' ? '/predict-yolo7' : '/predict-yolo11';

        fetch(endpoint, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: formData
        })
        .then(response => {
            if (!response.ok) throw response;
            return response.json(); // ✅ baca JSON
        })
        .then(data => {
            if (data.success) {
                startTrackProgress();
            } else {
                showError('Upload gagal: ' + (data.message || 'Tidak diketahui'));
                disableUpload(false);
            }
        })
        .catch(async (error) => {
            disableUpload(false);
            if (error.status === 422) {
                const data = await error.json();
                showError(Object.values(data.errors).flat().join(' '));
            } else {
                showError('Terjadi kesalahan server.');
            }
            hideProgress();
        });
    });

    // === PROGRESS HANDLER ===
    function startTrackProgress() {
        progressContainer.style.display = 'block';
        progressBar.style.width = '0%';
        progressText.innerText = 'Progress: 0%';

        setTimeout(() => {
            progressBar.style.width = '1%'; // mulai animasi
        }, 50);

        progressInterval = setInterval(() => {
            fetch('/progress')
                .then(res => res.json())
                .then(data => {
                    const progress = data.progress || 0;
                    progressBar.style.width = progress + '%';
                    progressText.innerText = 'Progress: ' + progress + '%';

                    if (progress >= 100) {
                        clearInterval(progressInterval);
                        fetchResultRedirect();
                    }
                })
                .catch(() => {
                    clearInterval(progressInterval);
                    disableUpload(false);
                    showError('Gagal mendapatkan progress.');
                    hideProgress();
                });
        }, 1000);
    }

    function fetchResultRedirect() {
        fetch('/get-result')
            .then(response => response.json())
            .then(data => {
                alertSuccess.textContent = '✅ Proses selesai! Mengalihkan...';
                alertSuccess.classList.remove('d-none');

                setTimeout(() => {
                    window.location.href = `/hasil/${data.id}`;
                }, 1200);
            })
            .catch(() => {
                disableUpload(false);
                showError('Gagal mengambil hasil.');
                hideProgress();
            });
    }

    // === UTILITIES ===
    function showError(message) {
        alertError.classList.remove('d-none');
        alertError.innerText = message;
    }

    function resetAlerts() {
        alertSuccess.classList.add('d-none');
        alertError.classList.add('d-none');
        alertSuccess.innerText = '';
        alertError.innerText = '';
    }

    function resetProgressBar() {
        progressBar.style.width = '0%';
        progressText.innerText = 'Progress: 0%';
        progressContainer.style.display = 'block';
        progressText.style.display = 'block';
    }

    function disableUpload(state) {
        uploadButton.disabled = state;
        uploadButton.innerText = state ? 'Uploading...' : '🚀 Upload dan Proses';
    }

    function hideProgress() {
        progressContainer.style.display = 'none';
        progressText.style.display = 'none';
    }
});
