// Gunakan global scope agar bisa dipanggil dari mana saja
window.initLeafletMap = function(trackPoints, mapId = 'map') {
    var map;
    var allMarkers = [];
    var firstLastMarkers = [];
    var latlngs = [];
    var animationLine;

    var greenIcon = L.icon({
        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
        shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
        iconSize:     [25, 41],
        iconAnchor:   [12, 41],
        popupAnchor:  [1, -34],
        shadowSize:   [41, 41]
    });

    var blueIcon = L.icon({
        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png',
        shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
        iconSize:     [25, 41],
        iconAnchor:   [12, 41],
        popupAnchor:  [1, -34],
        shadowSize:   [41, 41]
    });

    var redIcon = L.icon({
        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
        shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
        iconSize:     [25, 41],
        iconAnchor:   [12, 41],
        popupAnchor:  [1, -34],
        shadowSize:   [41, 41]
    });


    if (trackPoints.length > 0) {
        map = L.map(mapId).setView([trackPoints[0].latitude, trackPoints[0].longitude], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 22,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        var compass = new L.Control.Compass({ autoActive: true, showDigit: true });
        map.addControl(compass);

        trackPoints.forEach(function(point, index) {
            if (point.latitude && point.longitude) {
                let marker;
                if (trackPoints.length === 1) {
                    marker = L.marker([point.latitude, point.longitude], { icon: greenIcon })
                        .bindPopup("📍 Single Frame: " + point.frame);
                    firstLastMarkers.push(marker);
                    allMarkers.push(marker);
                } else if (index === 0) {
                    marker = L.marker([point.latitude, point.longitude], { icon: greenIcon })
                        .bindPopup("📍 Frame Pertama: " + point.frame);
                    firstLastMarkers.push(marker);
                    allMarkers.push(marker);
                } else if (index === trackPoints.length - 1) {
                    marker = L.marker([point.latitude, point.longitude], { icon: blueIcon })
                        .bindPopup("📍 Frame Terakhir: " + point.frame);
                    firstLastMarkers.push(marker);
                    allMarkers.push(marker);
                } else {
                    marker = L.marker([point.latitude, point.longitude], { icon: redIcon })
                        .bindPopup("Frame: " + point.frame);
                    allMarkers.push(marker);
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

        function resetMap() {
            allMarkers.forEach(m => m.remove());
            firstLastMarkers.forEach(m => m.remove());
            if (animationLine) {
                animationLine.remove();
                animationLine = null;
            }
        }

        function ensureOneToggleSelected() {
            const toggleMarkers = document.getElementById('toggleMarkers');
            const toggleFirstLastMarkers = document.getElementById('toggleFirstLastMarkers');

            if (!toggleMarkers.checked && !toggleFirstLastMarkers.checked) {
                // Default kembali ke First & Last marker
                toggleFirstLastMarkers.checked = true;
                resetMap();
                firstLastMarkers.forEach(m => m.addTo(map));
                playTrackAnimation();
                document.getElementById('legend-red').style.display = 'none';
            }
        }

        document.getElementById('toggleMarkers').addEventListener('change', function () {
            const show = this.checked;
            resetMap();

            if (show) {
                allMarkers.forEach(m => m.addTo(map));
                document.getElementById('toggleFirstLastMarkers').checked = false;
                document.getElementById('legend-red').style.display = 'list-item';
            } else {
                document.getElementById('legend-red').style.display = 'none';
                ensureOneToggleSelected();
            }
        });

        document.getElementById('toggleFirstLastMarkers').addEventListener('change', function () {
            const show = this.checked;
            resetMap();

            if (show) {
                firstLastMarkers.forEach(m => m.addTo(map));
                playTrackAnimation();
                document.getElementById('toggleMarkers').checked = false;
                document.getElementById('legend-red').style.display = 'none';
            } else {
                ensureOneToggleSelected();
            }
        });

        window.addEventListener('DOMContentLoaded', () => {
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
    }
}
