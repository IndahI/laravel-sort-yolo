import { initLeafletMap } from './map-init.js';

const trackPoints = JSON.parse(document.getElementById('trackPoints').textContent);
initLeafletMap(trackPoints);


