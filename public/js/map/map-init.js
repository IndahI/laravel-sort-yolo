import { createMarkerIcons, addMarkers, setupMarkerToggle } from './map-marker.js';
import { drawPolyline, animatePolyline } from './map-polyline.js';
import { calculateTotalDistance, calculateDistance } from './map-utils.js';

export function initLeafletMap(trackPoints, mapId = 'map') {
    const map = L.map(mapId).setView([trackPoints[0].latitude, trackPoints[0].longitude], 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 22,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    map.addControl(new L.Control.Compass({ autoActive: true, showDigit: true }));

    const icons = createMarkerIcons();
    const { allMarkers, firstLastMarkers, latlngs } = addMarkers(map, trackPoints, icons);

    firstLastMarkers.forEach(m => m.addTo(map));
    drawPolyline(map, latlngs);

    setupMarkerToggle(map, allMarkers, firstLastMarkers, () => animatePolyline(map, latlngs));

    // ✅ Jarak tempuh semua marker
    const totalDistance = calculateTotalDistance(latlngs);

    // ✅ Jarak lurus dari awal ke akhir
    const firstLatLng = latlngs[0];
    const lastLatLng = latlngs[latlngs.length - 1];
    const straightDistance = calculateDistance(firstLatLng, lastLatLng);

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


    document.getElementById('totalDistance').textContent =
        "Total Jarak Tempuh: " + totalDistance.toFixed(0) + " meter";

    document.getElementById('straightDistance').textContent =
        "Jarak Garis Lurus: " + straightDistance.toFixed(0) + " meter";
}
