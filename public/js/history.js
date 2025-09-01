import { calculateTotalDistance } from './map/map-utils.js';

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.distance-cell').forEach(cell => {
        let trackPoints;
        try {
            trackPoints = JSON.parse(cell.dataset.track || '[]');
        } catch (e) {
            trackPoints = [];
        }

        if (!Array.isArray(trackPoints)) {
            trackPoints = [];
        }

        if (trackPoints.length > 1) {
            const latlngs = trackPoints.map(p => [
                Number(p.latitude ?? p.lat),
                Number(p.longitude ?? p.lng)
            ]);

            const totalDistanceMeters = calculateTotalDistance(latlngs);

            cell.textContent = totalDistanceMeters.toFixed(0) + ' m';
        } else {
            cell.textContent = '-';
        }
    });
});
