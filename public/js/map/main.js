import { initLeafletMap } from './map-init.js';

document.addEventListener("DOMContentLoaded", () => {
    const trackPointsEl = document.getElementById('trackPoints');

    if (trackPointsEl) {
        try {
            const trackPoints = JSON.parse(trackPointsEl.textContent);

            // Pastikan ada data koordinat
            if (Array.isArray(trackPoints) && trackPoints.length > 0) {
                initLeafletMap(trackPoints);
            } else {
                console.warn("trackPoints kosong → peta tidak diinisialisasi.");
            }
        } catch (err) {
            console.error("Gagal parse trackPoints:", err);
        }
    } else {
        console.warn("Elemen trackPoints tidak ditemukan → skip inisialisasi peta.");
    }
});
