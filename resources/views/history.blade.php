@extends('layouts.app')

@section('title', 'Histori Deteksi')

@section('content')
<div class="container py-4">
    <div class="card shadow-sm">
        <div class="card-body">
            <h2 class="card-title mb-4 text-center">📂 Riwayat Deteksi SORT</h2>

            @if ($detections->isEmpty())
                <p class="text-center">Belum ada data deteksi yang tersimpan.</p>
            @else
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Nama File</th>
                            <th>Tipe</th>
                            <th>Deteksi</th>
                            <th>Jumlah Titik Lokasi</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($detections as $detection)
                            <tr>
                                <td>{{ $detection->created_at->format('d M Y H:i') }}</td>
                                <td>{{ $detection->filename_original }}</td>
                                <td>{{ $detection->is_video ? 'Video' : 'Gambar' }}</td>
                                <td>
                                    @if(is_array($detection->predictions))
                                        {{ implode(', ', array_map(fn($p) => $p['label'] . ' (' . number_format($p['confidence'] * 100, 2) . '%)', $detection->predictions)) }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ is_array($detection->track_points) ? count($detection->track_points) : '-' }}</td>
                                <td>
                                    <a href="{{ route('yolo.show', $detection->id) }}" class="btn btn-sm btn-primary">Lihat</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</div>
@endsection
