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
                    {{-- Error Alert --}}
                    @if(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    <form action="{{ route('predict-combined') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        {{-- File Video / Gambar --}}
                        <div class="mb-3">
                            <label for="file" class="form-label">1. Pilih Gambar / Video</label>
                            <input type="file" class="form-control" name="file" id="file" accept="image/*,video/*" required>
                            <div class="form-text text-danger">
                                ⚠️ Pastikan ukuran file tidak melebihi 100MB.
                            </div>
                        </div>

                        {{-- File SRT --}}
                        <div class="mb-3">
                            <label for="srt_file" class="form-label">2. File Metadata (.srt atau .txt)</label>
                            <input type="file" class="form-control" name="srt_file" id="srt_file" accept=".srt,.txt">
                            <div class="form-text text-danger">
                                ⚠️ Mohon masukan file metadata jika memasukan file bertipe VIDEO
                            </div>
                        </div>

                        <button type="submit" class="btn btn-success w-100">
                            🚀 Upload dan Proses
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

