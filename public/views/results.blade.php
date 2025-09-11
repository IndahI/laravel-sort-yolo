@extends('layouts.app')

@section('title', 'Hasil Deteksi & Lokasi')

@section('styles')
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <!-- Leaflet Compass CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet-compass/dist/leaflet-compass.min.css" />
    <style>
        #map {
            height: 400px;
            width: 100%;
            margin-top: 20px;
        }
    </style>
@endsection

@section('content')
<div class="container py-4">
    <div class="card shadow-sm">
        <div class="card-body">
            <h2 class="card-title mb-4 text-center">🧠 Hasil SORT & Lokasi Metadata</h2>

            {{-- SECTION 1: Peta Lokasi dari SRT --}}
            @if (!empty($trackPoints))
                <h4 class="mb-3">🗺️ Visualisasi Lokasi (Leaflet)</h4>

                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" id="toggleMarkers">
                    <label class="form-check-label" for="toggleMarkers">Tampilkan Semua Marker</label>
                </div>
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="toggleFirstLastMarkers" checked>
                    <label class="form-check-label" for="toggleFirstLastMarkers">Tampilkan Hanya Marker Pertama & Terakhir</label>
                </div>
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="toggleStraightLine">
                    <label class="form-check-label" for="toggleStraightLine">Tampilkan Garis Lurus (Awal → Akhir, Bukan Jalur Drone)</label>
                </div>

                <div id="map" class="rounded border"></div>
                
                <div class="mt-3">
                    <p id="totalDistance" class="fw-bold text-primary"></p>
                    <p id="straightDistance" class="fw-bold text-secondary"></p>
                </div>

                <div class="mt-3">
                <h6>Keterangan Warna Marker:</h6>
                <ul class="list-group small">
                    <li class="list-group-item">
                        <img src="https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png" width="20">
                        <strong>Hijau:</strong> Titik pertama (frame awal)
                    </li>
                    <li class="list-group-item" id="legend-red" style="display: none;">
                        <img src="https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png" width="20">
                        <strong>Merah:</strong> Titik lintasan di tengah (bukan awal/akhir)
                    </li>
                    <li class="list-group-item">
                        <img src="https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png" width="20">
                        <strong>Biru:</strong> Titik terakhir (frame akhir)
                    </li>
                </ul>
            </div>

            @endif

            {{-- SECTION 2: Hasil Deteksi SORT --}}
            <h4 class="mb-3">📸 Hasil Deteksi</h4>
            <div class="mb-4">
                @if ($isVideo)
                    <video class="w-100 rounded" controls>
                        <source src="{{ asset('storage/' . $detectedFilePath) }}" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                @else
                    <img src="{{ asset('storage/' . $detectedFilePath) }}" class="img-fluid rounded">
                    @if (!empty($predictions))
                        <ul class="list-group mt-3">
                            @foreach ($predictions as $prediction)
                                <li class="list-group-item">
                                    <strong>{{ $prediction['label'] }}</strong> - Confidence: {{ $prediction['confidence'] }}
                                </li>
                            @endforeach
                        </ul>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/leaflet-compass/dist/leaflet-compass.min.js"></script>

    {{-- Kirim trackPoints ke JS --}}
    <pre id="trackPoints" style="display: none;">@json($trackPoints)</pre>

    {{-- Panggil main.js sebagai module --}}
    <script type="module" src="{{ asset('js/map/main.js') }}"></script>

@endsection
