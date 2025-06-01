@extends('layouts.app')

@section('title', 'Hasil Deteksi & Lokasi')

@section('styles')
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
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

                <div id="map" class="rounded border"></div>
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
<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

@if (!empty($trackPoints))
<script>
    var trackPoints = @json($trackPoints);
    var map;
    var allMarkers = [];
    var firstLastMarkers = [];
    var latlngs = [];

    var animationLine; // untuk menyimpan referensi polyline animasi

    if (trackPoints.length > 0) {
        map = L.map('map').setView([trackPoints[0].latitude, trackPoints[0].longitude], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 22,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        trackPoints.forEach(function(point, index) {
            if (point.latitude && point.longitude) {
                var marker = L.marker([point.latitude, point.longitude])
                    .bindPopup("Frame ke-" + point.frame);
                allMarkers.push(marker);

                if (index === 0 || index === trackPoints.length - 1) {
                    firstLastMarkers.push(marker);
                }

                latlngs.push([point.latitude, point.longitude]);
            }
        });

        if (latlngs.length > 1) {
            var polyline = L.polyline(latlngs, { color: 'blue' }).addTo(map);
            map.fitBounds(polyline.getBounds(), { maxZoom: 18 });
        } else if (latlngs.length === 1) {
            map.setView(latlngs[0], 16);
        }
    }

    function playTrackAnimation() {
        if (animationLine) {
            animationLine.remove();
        }

        animationLine = L.polyline([], { color: 'red' }).addTo(map);
        let index = 0;
        const delay = 50;

        function drawNextPoint() {
            if (index >= latlngs.length) return;
            animationLine.addLatLng(latlngs[index]);
            index++;
            setTimeout(drawNextPoint, delay);
        }

        drawNextPoint();
    }

    // Fungsi untuk reset semua marker dan animasi
    function resetMap() {
        allMarkers.forEach(m => m.remove());
        firstLastMarkers.forEach(m => m.remove());
        if (animationLine) {
            animationLine.remove();
            animationLine = null;
        }
    }

    document.getElementById('toggleMarkers').addEventListener('change', function () {
        const show = this.checked;
        resetMap();

        if (show) {
            allMarkers.forEach(m => m.addTo(map));
            // Tidak ada animasi saat toggleMarkers aktif
            document.getElementById('toggleFirstLastMarkers').checked = false;
        }
    });

    document.getElementById('toggleFirstLastMarkers').addEventListener('change', function () {
        const show = this.checked;
        resetMap();

        if (show) {
            firstLastMarkers.forEach(m => m.addTo(map));
            playTrackAnimation();  // Animasi hanya di sini
            document.getElementById('toggleMarkers').checked = false;
        }
    });

    // Inisialisasi tampilan saat load page
    window.addEventListener('DOMContentLoaded', (event) => {
        if (document.getElementById('toggleMarkers').checked) {
            resetMap();
            allMarkers.forEach(m => m.addTo(map));
            document.getElementById('toggleFirstLastMarkers').checked = false;
        } else if (document.getElementById('toggleFirstLastMarkers').checked) {
            resetMap();
            firstLastMarkers.forEach(m => m.addTo(map));
            playTrackAnimation();
            document.getElementById('toggleMarkers').checked = false;
        } else {
            resetMap();
        }
    });
</script>
@endif
@endsection


