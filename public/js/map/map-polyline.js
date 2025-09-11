export function drawPolyline(map, latlngs) {
    if (latlngs.length > 1) {
        const polyline = L.polyline(latlngs, { color: 'blue' }).addTo(map);
        map.fitBounds(polyline.getBounds(), { maxZoom: 18 });
    } else if (latlngs.length === 1) {
        map.setView(latlngs[0], 16);
    }
}

export function animatePolyline(map, latlngs) {
    const animationLine = L.polyline([], { color: 'red' }).addTo(map);
    let index = 0;
    const delay = 50;
    function drawNextPoint() {
        if (index >= latlngs.length) return;
        animationLine.addLatLng(latlngs[index++]);
        setTimeout(drawNextPoint, delay);
    }
    drawNextPoint();
}
