export function haversineDistance(latlng1, latlng2) {
    const R = 6371e3;
    const toRadians = (deg) => deg * Math.PI / 180;
    const lat1 = toRadians(latlng1[0]);
    const lat2 = toRadians(latlng2[0]);
    const deltaLat = toRadians(latlng2[0] - latlng1[0]);
    const deltaLng = toRadians(latlng2[1] - latlng1[1]);
    const a = Math.sin(deltaLat / 2) ** 2 + Math.cos(lat1) * Math.cos(lat2) * Math.sin(deltaLng / 2) ** 2;
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return R * c;
}

export function calculateTotalDistance(latlngs) {
    return latlngs.reduce((acc, cur, idx, arr) => {
        if (idx === 0) return 0;
        return acc + haversineDistance(arr[idx - 1], cur);
    }, 0);
}

export function calculateDistance(latlng1, latlng2) {
    const R = 6371000; // Meter
    const toRad = angle => angle * Math.PI / 180;

    const lat1 = latlng1[0];
    const lon1 = latlng1[1];
    const lat2 = latlng2[0];
    const lon2 = latlng2[1];

    const dLat = toRad(lat2 - lat1);
    const dLon = toRad(lon2 - lon1);

    const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
              Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) *
              Math.sin(dLon / 2) * Math.sin(dLon / 2);

    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return R * c;
}

