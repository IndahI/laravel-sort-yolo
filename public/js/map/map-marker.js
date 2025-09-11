export function createMarkerIcons() {
    return {
        greenIcon: L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
            shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41],
        }),
        blueIcon: L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png',
            shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41],
        }),
        redIcon: L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
            shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41],
        }),
    };
}

export function addMarkers(map, trackPoints, icons) {
    const allMarkers = [];
    const firstLastMarkers = [];
    const latlngs = [];

    trackPoints.forEach(function (point, index) {
        if (point.latitude && point.longitude) {
            let marker;
            if (trackPoints.length === 1) {
                marker = L.marker([point.latitude, point.longitude], { icon: icons.greenIcon })
                    .bindPopup("📍 Single Frame: " + point.frame);
                firstLastMarkers.push(marker);
            } else if (index === 0) {
                marker = L.marker([point.latitude, point.longitude], { icon: icons.greenIcon })
                    .bindPopup("📍 Frame Pertama: " + point.frame);
                firstLastMarkers.push(marker);
            } else if (index === trackPoints.length - 1) {
                marker = L.marker([point.latitude, point.longitude], { icon: icons.blueIcon })
                    .bindPopup("📍 Frame Terakhir: " + point.frame);
                firstLastMarkers.push(marker);
            } else {
                marker = L.marker([point.latitude, point.longitude], { icon: icons.redIcon })
                    .bindPopup("Frame: " + point.frame);
            }

            allMarkers.push(marker);
            latlngs.push([point.latitude, point.longitude]);
        }
    });

    return { allMarkers, firstLastMarkers, latlngs };
}

export function setupStraightLineToggle(map, latlngs) {
    const firstLatLng = latlngs[0];
    const lastLatLng = latlngs[latlngs.length - 1];

    let straightLine = null;

    document.getElementById('toggleStraightLine').addEventListener('change', function () {
        const show = this.checked;
        if (straightLine) {
            map.removeLayer(straightLine);
            straightLine = null;
        }
        if (show && firstLatLng && lastLatLng) {
            straightLine = L.polyline([firstLatLng, lastLatLng], { color: 'black', dashArray: '5, 10' }).addTo(map);
        }
    });
}

export function setupMarkerToggle(map, allMarkers, firstLastMarkers, playTrackAnimation) {
    function resetMap() {
        allMarkers.forEach(m => m.remove());
        firstLastMarkers.forEach(m => m.remove());
    }

    function ensureOneToggleSelected() {
        const toggleMarkers = document.getElementById('toggleMarkers');
        const toggleFirstLastMarkers = document.getElementById('toggleFirstLastMarkers');

        if (!toggleMarkers.checked && !toggleFirstLastMarkers.checked) {
            toggleFirstLastMarkers.checked = true;
            resetMap();
            firstLastMarkers.forEach(m => m.addTo(map));
            playTrackAnimation();
            document.getElementById('legend-red').style.display = 'none';
        }
    }

    document.getElementById('toggleMarkers').addEventListener('change', function () {
        resetMap();
        if (this.checked) {
            allMarkers.forEach(m => m.addTo(map));
            document.getElementById('toggleFirstLastMarkers').checked = false;
            document.getElementById('legend-red').style.display = 'list-item';
        } else {
            document.getElementById('legend-red').style.display = 'none';
            ensureOneToggleSelected();
        }
    });

    document.getElementById('toggleFirstLastMarkers').addEventListener('change', function () {
        resetMap();
        if (this.checked) {
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
