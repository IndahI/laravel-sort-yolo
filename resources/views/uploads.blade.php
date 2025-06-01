@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 text-center">
            <h2>Tracking Lintasan Korban Hanyut</h2>
            <h4>Menggunakan deteksi YOLOv11 and Simple Online and Realtime Tracking</h4>
        </div>
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white text-center">
                    <h4 class="mb-0">📤 Upload Gambar/Video & File Metadata (.srt)</h4>
                </div>

                <div class="card-body">

                    {{-- Alert sukses dan error --}}
                    <div id="alertSuccess" class="alert alert-success d-none"></div>
                    <div id="alertError" class="alert alert-danger d-none"></div>

                    <form id="upload-form" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-3">
                            <label for="file" class="form-label">1. Pilih Gambar / Video</label>
                            <input type="file" class="form-control" name="file" id="file" accept="image/*,video/*" required>
                            <div class="form-text text-danger">
                                ⚠️ Pastikan ukuran file tidak melebihi 100MB.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="srt_file" class="form-label">2. File Metadata (.srt atau .txt)</label>
                            <input type="file" class="form-control" name="srt_file" id="srt_file" accept=".srt,.txt">
                            <div class="form-text text-danger">
                                ⚠️ Mohon masukan file metadata jika memasukan file bertipe VIDEO
                            </div>
                        </div>

                        <button type="submit" class="btn btn-success w-100" id="uploadButton">
                            🚀 Upload dan Proses
                        </button>
                    </form>

                    {{-- Progress Bar --}}
                    <div class="progress mt-3" style="height: 25px; background: #e9ecef; display:none;" id="progress-container">
                        <div id="progress-bar" class="progress-bar" style="width: 0%; background-color: #0d6efd;"></div>
                    </div>
                    <p id="progress-text" style="display:none;">Progress: 0%</p>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.getElementById("upload-form").addEventListener("submit", function(e) {
    e.preventDefault();

    const form = this;
    const formData = new FormData(form);

    // Reset alerts
    document.getElementById('alertSuccess').classList.add('d-none');
    document.getElementById('alertError').classList.add('d-none');
    document.getElementById('alertSuccess').innerText = '';
    document.getElementById('alertError').innerText = '';

    // Disable submit button
    const uploadButton = document.getElementById('uploadButton');
    uploadButton.disabled = true;
    uploadButton.innerText = 'Uploading...';

    // Show progress bar
    const progressContainer = document.getElementById('progress-container');
    const progressBar = document.getElementById('progress-bar');
    const progressText = document.getElementById('progress-text');
    progressContainer.style.display = 'block';
    progressBar.style.width = '0%';
    progressText.style.display = 'block';
    progressText.innerText = 'Progress: 0%';

    fetch('/predict-ajax', {  // Ganti dengan route uploadmu
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: formData
    })
    .then(response => {
        if (!response.ok) throw response;
        // Mulai tracking progress setelah upload request sukses
        trackProgress();
    })
    .catch(async (error) => {
        uploadButton.disabled = false;
        uploadButton.innerText = '🚀 Upload dan Proses';

        if (error.status === 422) {
            const data = await error.json();
            let errors = Object.values(data.errors).flat().join(' ');
            document.getElementById('alertError').classList.remove('d-none');
            document.getElementById('alertError').innerText = errors;
        } else {
            document.getElementById('alertError').classList.remove('d-none');
            document.getElementById('alertError').innerText = 'Terjadi kesalahan server.';
        }
        progressContainer.style.display = 'none';
        progressText.style.display = 'none';
    });

    function trackProgress() {
        const interval = setInterval(() => {
            fetch('/progress')  // Endpoint yang return { progress: <angka> }
                .then(res => res.json())
                .then(data => {
                    progressBar.style.width = data.progress + '%';
                    progressText.innerText = 'Progress: ' + data.progress + '%';
                    if (data.progress >= 100) {
                        clearInterval(interval);
                        uploadButton.disabled = false;
                        uploadButton.innerText = '🚀 Upload dan Proses';
                        // Redirect ke halaman hasil
                        window.location.href = '/result-page';
                    }
                })
                .catch(() => {
                    clearInterval(interval);
                    uploadButton.disabled = false;
                    uploadButton.innerText = '🚀 Upload dan Proses';
                    document.getElementById('alertError').classList.remove('d-none');
                    document.getElementById('alertError').innerText = 'Gagal mendapatkan progress.';
                    progressContainer.style.display = 'none';
                    progressText.style.display = 'none';
                });
        }, 1000);
    }
});
</script>
@endsection
