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
                    <input class="form-check-input" type="checkbox" id="toggleFirstLastMarkers">
                    <label class="form-check-label" for="toggleFirstLastMarkers">Tampilkan Hanya Marker Pertama & Terakhir</label>
                </div>
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="toggleMarkers" checked>
                    <label class="form-check-label" for="toggleMarkers">Tampilkan Semua Marker</label>
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

        if (trackPoints.length > 0) {
            map = L.map('map').setView([trackPoints[0].lat, trackPoints[0].lng], 10);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 22,
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            trackPoints.forEach(function(point, index) {
                if (point.lat && point.lng) {
                    var marker = L.marker([point.lat, point.lng])
                        .bindPopup("Frame ke-" + point.frame);
                    allMarkers.push(marker);

                    if (index === 0 || index === trackPoints.length - 1) {
                        firstLastMarkers.push(marker);
                    }

                    marker.addTo(map);
                    latlngs.push([point.lat, point.lng]);
                }
            });

            if (latlngs.length > 1) {
                var polyline = L.polyline(latlngs, { color: 'blue' }).addTo(map);
                map.fitBounds(polyline.getBounds());
            } else {
                map.setView(latlngs[0], 19);
            }
        }

        document.getElementById('toggleMarkers').addEventListener('change', function () {
            const show = this.checked;
            allMarkers.forEach(m => show ? m.addTo(map) : m.remove());
            document.getElementById('toggleFirstLastMarkers').checked = false;
        });

        document.getElementById('toggleFirstLastMarkers').addEventListener('change', function () {
            const show = this.checked;
            allMarkers.forEach(m => m.remove());
            if (show) {
                firstLastMarkers.forEach(m => m.addTo(map));
                document.getElementById('toggleMarkers').checked = false;
            }
        });
    </script>
    @endif
@endsection
