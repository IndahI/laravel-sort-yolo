
@extends('layouts.app')

@section('title', 'Upload File ke Container')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">

            <div class="card shadow-lg rounded-3">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Upload File</h4>
                </div>
                <div class="card-body">

                    <form id="uploadForm" enctype="multipart/form-data">
                        @csrf
                        
                        <div class="mb-3">
                            <label for="file" class="form-label">Upload Video/Gambar</label>
                            <input type="file" name="file" id="file" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label for="srt_file" class="form-label">Upload Metadata (.srt)</label>
                            <input type="file" name="srt_file" id="srt_file" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label for="model" class="form-label">Pilih Model</label>
                            <select name="model" id="model" class="form-select">
                                <option value="yolov7">YOLOv7</option>
                                <option value="yolov11">YOLOv11</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-cloud-upload"></i> Upload & Simpan
                        </button>
                    </form>

                </div>
            </div>

        </div>
    </div>
</div>

<script>
document.getElementById('uploadForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    let formData = new FormData(this);

    try {
        let response = await fetch("{{ url('/upload-test') }}", {
            method: "POST",
            body: formData,
            headers: {
                "X-CSRF-TOKEN": document.querySelector('input[name="_token"]').value
            }
        });

        let resultText = await response.text(); // ambil plain text dulu

        try {
            let result = JSON.parse(resultText); // coba parse kalau valid JSON
            if (response.ok) {
                console.log("✅ Upload berhasil:", result.message);
            } else {
                console.error("❌ Upload gagal:", result.message);
            }
        } catch (err) {
            console.error("⚠️ Server balikin non-JSON response:");
            console.error(resultText); // tampilkan isi HTML error Laravel
        }
    } catch (err) {
        console.error("🚨 Fetch error:", err);
    }
});
</script>
@endsection
