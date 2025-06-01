@extends('layouts.app')

@section('title', 'Histori Deteksi')

@section('content')
<div class="container py-4">
    <div class="card shadow-sm">
        <div class="card-body">
            <h2 class="card-title mb-4 text-center">📂 Riwayat Deteksi SORT</h2>

            <form method="GET" id="perPageForm" class="mb-3 d-flex justify-content-end align-items-center" style="gap: 10px;">
                <label for="perPageSelect" class="mb-0">Tampilkan per halaman:</label>
                <select id="perPageSelect" name="per_page" class="form-select" style="width: auto;">
                    <option value="25" {{ ($perPage ?? 25) == 25 ? 'selected' : '' }}>25</option>
                    <option value="50" {{ ($perPage ?? 25) == 50 ? 'selected' : '' }}>50</option>
                    <option value="100" {{ ($perPage ?? 25) == 100 ? 'selected' : '' }}>100</option>
                </select>
            </form>

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
                            @php
                                $predictions = json_decode($detection->predictions, true);
                                $trackPoints = json_decode($detection->track_points, true);
                            @endphp
                            <tr>
                                <td>{{ $detection->created_at->format('d M Y H:i') }}</td>
                                <td>{{ $detection->filename_original }}</td>
                                <td>{{ $detection->is_video ? 'Video' : 'Gambar' }}</td>
                                <td>
                                    @if (is_array($predictions))
                                        {{ implode(', ', array_map(fn($p) => $p['label'] . ' (' . number_format($p['confidence'] * 100, 2) . '%)', $predictions)) }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ is_array($trackPoints) ? count($trackPoints) : '-' }}</td>
                                <td>
                                    <a href="{{ route('yolo.show', $detection->id) }}" class="btn btn-sm btn-primary">Lihat</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <!-- Pagination links -->
                <div class="d-flex justify-content-center">
                    {{ $detections->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<script>
    document.getElementById('perPageSelect').addEventListener('change', function () {
        document.getElementById('perPageForm').submit();
    });
</script>
@endsection
