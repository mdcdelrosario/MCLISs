const TB = {
  visible:    false,
  polygons:   [],
  allCache:   {},
  allBounds:  {},
  townCache:  {},           // town  → GeoJSON FeatureCollection
  brgyCache:  {},           // "town||brgy" → GeoJSON FeatureCollection
  townBounds: {},           // town → google.maps.LatLngBounds
  brgyBounds: {},           // key  → google.maps.LatLngBounds
  currentAreaOffice: null,
  currentTown: null,
  currentBrgy: null,
};

function tbAreaKey(areaOffice = '') {
  return areaOffice || '__all__';
}

/* ── Style tokens ── */
const STYLE = {
  town: {
    strokeColor:   '#1565C0',
    strokeOpacity: 0.85,
    strokeWeight:  2,
    fillColor:     '#1976D2',
    fillOpacity:   0.08,
  },
  brgy: {
    strokeColor:   '#2E7D32',
    strokeOpacity: 0.90,
    strokeWeight:  1.5,
    fillColor:     '#43A047',
    fillOpacity:   0.12,
  },
  hover: {
    fillOpacity: 0.28,
  },
  selected: {
    strokeColor:   '#E65100',
    strokeOpacity: 1,
    strokeWeight:  2.5,
    fillColor:     '#FF6D00',
    fillOpacity:   0.20,
  },
  all: {
    strokeColor:   '#1565C0',
    strokeOpacity: 0.70,
    strokeWeight:  1.25,
    fillColor:     '#1976d2',
    fillOpacity:   0.05,
  },
};

const DEFAULT_AREA_OFFICES = [
  'Central Area Office',
  'Northern Area Office',
  'Eastern Area Office',
  'Southern Area Office',
  'South Eastern Area Office',
];

/* ════════════════════════════════════════════════
   INIT — runs after Google Maps + DOM ready
════════════════════════════════════════════════ */
window.addEventListener('load', () => {
  const waitForMap = setInterval(() => {
    if (typeof map !== 'undefined' && map) {
      clearInterval(waitForMap);
      tbInit();
    }
  }, 100);
});

function tbInit() {
  loadAreaOffices();
  loadTowns();
  document.getElementById('vareaoffice')?.addEventListener('change', onAreaOfficeChange);
  document.getElementById('vtown')?.addEventListener('change', onTownChange);
  document.getElementById('vbrgy')?.addEventListener('change', onBrgyChange);
}

/* ════════════════════════════════════════════════
   API HELPERS
════════════════════════════════════════════════ */
async function tbFetch(params) {
  const url = 'api_boundaries.php?' + new URLSearchParams(params).toString();
  const res = await fetch(url);
  const text = await res.text();
  console.log('[DEBUG tbFetch RAW RESPONSE]', {url, status: res.status, contentType: res.headers.get('content-type'), preview: text.slice(0, 500) + (text.length > 500 ? '...' : '') });
  if (!res.ok) throw new Error(`HTTP ${res.status}: ${text.slice(0, 100)}`);
  try {
    return JSON.parse(text);
  } catch (e) {
    console.error('[DEBUG tbFetch JSON PARSE FAILED]', {url, rawResponse: text.slice(0, 1000)});
    throw new Error(`Invalid JSON from ${url}: ${text.slice(0, 200)}`);
  }
}

/* ════════════════════════════════════════════════
   DROPDOWNS
════════════════════════════════════════════════ */
async function loadAreaOffices() {
  try {
    let offices = await tbFetch({ action: 'area_offices' });
    const sel = document.getElementById('vareaoffice');
    if (!sel) return;
    if (!Array.isArray(offices) || offices.length === 0) offices = DEFAULT_AREA_OFFICES;
    sel.innerHTML = '<option value="">All Area Offices</option>';
    offices.forEach(a => {
      const o = document.createElement('option');
      o.value = a;
      o.textContent = a;
      sel.appendChild(o);
    });
  } catch (e) {
    console.error('[Boundaries] loadAreaOffices:', e);
    const sel = document.getElementById('vareaoffice');
    if (!sel) return;
    sel.innerHTML = '<option value="">All Area Offices</option>';
    DEFAULT_AREA_OFFICES.forEach(a => {
      const o = document.createElement('option');
      o.value = a;
      o.textContent = a;
      sel.appendChild(o);
    });
  }
}

async function loadTowns(areaOffice = '') {
  try {
    const params = { action: 'towns' };
    if (areaOffice) params.area_office = areaOffice;
    const towns = await tbFetch(params);
    const sel   = document.getElementById('vtown');
    if (!sel) return;
    sel.innerHTML = '<option value="">Select Town</option>';
    towns.forEach(t => {
      const o       = document.createElement('option');
      o.value       = t;
      o.textContent = t;
      sel.appendChild(o);
    });
  } catch (e) {
    console.error('[Boundaries] loadTowns:', e);
  }
}

async function onAreaOfficeChange() {
  const areaOffice = this.value;
  const brSel = document.getElementById('vbrgy');

  TB.currentAreaOffice = areaOffice || null;
  TB.currentTown = null;
  TB.currentBrgy = null;
  TB.allCache = {};
  TB.allBounds = {};
  TB.townCache = {};
  TB.brgyCache = {};
  TB.townBounds = {};
  TB.brgyBounds = {};
  clearOverlays();

  if (brSel) brSel.innerHTML = '<option value="">Barangays</option>';
  await loadTowns(areaOffice);
  if (TB.visible) {
    await loadAllBoundary(areaOffice);
    renderOverlays();
    zoomToAll(areaOffice);
  }
}

async function onTownChange() {
  const town  = this.value;
  const brSel = document.getElementById('vbrgy');
  brSel.innerHTML  = '<option value="">— All Barangays —</option>';
  TB.currentBrgy   = null;
  TB.currentTown   = town || null;

  if (!town) { clearOverlays(); return; }

  try {
    const params = { action: 'barangays', town };
    if (TB.currentAreaOffice) params.area_office = TB.currentAreaOffice;
    const brgys = await tbFetch(params);
    brgys.forEach(b => {
      const o       = document.createElement('option');
      o.value       = b;
      o.textContent = b;
      brSel.appendChild(o);
    });

    await loadTownBoundary(town);
    if (TB.visible) renderOverlays();
    zoomToTown(town);

  } catch (e) {
    console.error('[Boundaries] onTownChange:', e);
  }
}

async function onBrgyChange() {
  const brgy = this.value;
  const town = document.getElementById('vtown').value;
  TB.currentBrgy = brgy || null;

  if (!brgy) {
    clearOverlays();
    if (town) {
      await loadTownBoundary(town);
      if (TB.visible) renderOverlays();
      zoomToTown(town);
    }
    return;
  }

  try {
    await loadBrgyBoundary(town, brgy);
    if (TB.visible) renderOverlays();
    zoomToBarangay(town, brgy);
  } catch (e) {
    console.error('[Boundaries] onBrgyChange:', e);
  }
}

/* ════════════════════════════════════════════════
   DATA LOADERS
════════════════════════════════════════════════ */
async function loadAllBoundary(areaOffice = '') {
  const key = tbAreaKey(areaOffice);
  if (TB.allCache[key]) return;
  const params = { action: 'geojson' };
  if (areaOffice) params.area_office = areaOffice;
  const fc = await tbFetch(params);
  TB.allCache[key] = fc;

  const bounds = new google.maps.LatLngBounds();
  fc.features.forEach(f => {
    f.geometry.coordinates[0].forEach(([lng, lat]) => bounds.extend({ lat, lng }));
  });
  TB.allBounds[key] = bounds;
}

async function loadTownBoundary(town) {
  if (TB.townCache[town]) return;
  const params = { action: 'geojson', town };
  if (TB.currentAreaOffice) params.area_office = TB.currentAreaOffice;
  const fc = await tbFetch(params);
  TB.townCache[town] = fc;

  const bounds = new google.maps.LatLngBounds();
  fc.features.forEach(f => {
    f.geometry.coordinates[0].forEach(([lng, lat]) => bounds.extend({ lat, lng }));
  });
  TB.townBounds[town] = bounds;
}

async function loadBrgyBoundary(town, brgy) {
  const key = `${town}||${brgy}`;
  if (TB.brgyCache[key]) return;
  const params = { action: 'geojson', town, brgy };
  if (TB.currentAreaOffice) params.area_office = TB.currentAreaOffice;
  const fc  = await tbFetch(params);
  TB.brgyCache[key] = fc;

  const bounds = new google.maps.LatLngBounds();
  fc.features.forEach(f => {
    f.geometry.coordinates[0].forEach(([lng, lat]) => bounds.extend({ lat, lng }));
  });
  TB.brgyBounds[key] = bounds;
}

/* ════════════════════════════════════════════════
   POLYGON EXTRACTION HELPERS  ← NEW
   These are called by loadTownCust() / loadBrgyCust()
   in index.php to get the polygon coords to POST
   to search.php for server-side spatial filtering.
════════════════════════════════════════════════ */

/**
 * Returns all polygon rings for a town as an array of arrays of {lat, lng}.
 * One entry per barangay feature (multi-polygon towns → multiple rings).
 * Returns null if the boundary has not been loaded yet.
 *
 * @param  {string} town
 * @returns {Array<Array<{lat:number,lng:number}>>|null}
 */
function tbGetTownPolygons(town) {
  const fc = TB.townCache[town];
  if (!fc) return null;

  return fc.features.map(f =>
    f.geometry.coordinates[0].map(([lng, lat]) => ({ lat, lng }))
  );
}

/**
 * Returns the polygon ring for a single barangay as an array of {lat, lng}.
 * Returns null if the boundary has not been loaded yet.
 *
 * @param  {string} town
 * @param  {string} brgy
 * @returns {Array<{lat:number,lng:number}>|null}
 */
function tbGetBrgyPolygon(town, brgy) {
  const key = `${town}||${brgy}`;
  const fc  = TB.brgyCache[key];
  if (!fc) return null;

  // Use the matching feature if multiple features returned, else first
  const feature =
    fc.features.find(f => f.properties.barangayName === brgy) ||
    fc.features[0];

  if (!feature) return null;
  return feature.geometry.coordinates[0].map(([lng, lat]) => ({ lat, lng }));
}

/**
 * Ensures boundary data is loaded for a town, then returns polygons.
 * Async wrapper — use when you can't guarantee the cache is warm.
 *
 * @param  {string} town
 * @returns {Promise<Array<Array<{lat:number,lng:number}>>>}
 */
async function tbEnsureTownPolygons(town) {
  await loadTownBoundary(town);
  return tbGetTownPolygons(town);
}

/**
 * Ensures boundary data is loaded for a barangay, then returns the polygon.
 *
 * @param  {string} town
 * @param  {string} brgy
 * @returns {Promise<Array<{lat:number,lng:number}>>}
 */
async function tbEnsureBrgyPolygon(town, brgy) {
  await loadBrgyBoundary(town, brgy);
  return tbGetBrgyPolygon(town, brgy);
}

/* ════════════════════════════════════════════════
   OVERLAY RENDERING
════════════════════════════════════════════════ */
function clearOverlays() {
  TB.polygons.forEach(p => p.setMap(null));
  TB.polygons = [];
}

function renderOverlays() {
  clearOverlays();

  const town = TB.currentTown;
  const brgy = TB.currentBrgy;

  const key = brgy ? `${town}||${brgy}` : null;
  const fc  = key
    ? TB.brgyCache[key]
    : town
      ? TB.townCache[town]
      : TB.allCache[tbAreaKey(TB.currentAreaOffice)];
  if (!fc) return;

  fc.features.forEach(feature => {
    const isSelected = brgy
      ? feature.properties.barangayName === brgy
      : false;

    const style = isSelected ? STYLE.selected
                : brgy       ? STYLE.brgy
                : town       ? STYLE.town
                :              STYLE.all;

    const path = feature.geometry.coordinates[0].map(([lng, lat]) => ({ lat, lng }));

    const poly = new google.maps.Polygon({
      paths:         path,
      map:           map,
      strokeColor:   style.strokeColor,
      strokeOpacity: style.strokeOpacity,
      strokeWeight:  style.strokeWeight,
      fillColor:     style.fillColor,
      fillOpacity:   style.fillOpacity,
      clickable:     true,
      zIndex:        brgy ? 2 : town ? 1 : 0,
    });

    // Tooltip
    const infoWin = new google.maps.InfoWindow();
    poly.addListener('mouseover', function (e) {
      this.setOptions({ fillOpacity: STYLE.hover.fillOpacity });
      infoWin.setContent(
        `<div style="font-size:12px;font-weight:600;padding:2px 4px;">
           ${feature.properties.barangayName}<br>
           <span style="font-weight:400;color:#555;">${feature.properties.town}</span>
           ${feature.properties.areaOffice ? `<br><span style="font-weight:400;color:#777;">${feature.properties.areaOffice}</span>` : ''}
         </div>`
      );
      infoWin.setPosition(e.latLng);
      infoWin.open(map);
    });

    poly.addListener('mouseout', function () {
      this.setOptions({ fillOpacity: style.fillOpacity });
      infoWin.close();
    });

    // Click → select barangay in dropdown
    poly.addListener('click', () => {
      const townSel = document.getElementById('vtown');
      const brSel = document.getElementById('vbrgy');
      if (townSel && townSel.value !== feature.properties.town) {
        townSel.value = feature.properties.town;
        townSel.dispatchEvent(new Event('change'));
        return;
      }
      if (brSel) {
        brSel.value = feature.properties.barangayName;
        brSel.dispatchEvent(new Event('change'));
      }
    });

    TB.polygons.push(poly);
  });
}

/* ════════════════════════════════════════════════
   PUBLIC FUNCTIONS
════════════════════════════════════════════════ */

/** Toggle all boundary overlays on/off */
async function toggleBoundaries() {
  const btn = document.getElementById('btn-boundaries');

  if (TB.visible) {
    clearOverlays();
    TB.visible = false;
    btn?.classList.remove('btn-chip-active');
    btn?.querySelector('i')?.setAttribute('class', 'fa fa-map-o');
  } else {
    TB.visible = true;
    btn?.classList.add('btn-chip-active');
    btn?.querySelector('i')?.setAttribute('class', 'fa fa-map');
    if (TB.currentTown) {
      renderOverlays();
    } else {
      await loadAllBoundary(TB.currentAreaOffice || '');
      renderOverlays();
      zoomToAll(TB.currentAreaOffice || '');
    }
  }
}

/** Zoom map to selected barangay; fall back to town */
function searchBarangay() {
  const town = document.getElementById('vtown')?.value;
  const brgy = document.getElementById('vbrgy')?.value;
  if (!town) return;
  brgy ? zoomToBarangay(town, brgy) : zoomToTown(town);
}

function zoomToTown(town) {
  const b = TB.townBounds[town];
  if (b) map.fitBounds(b);
}

function zoomToAll(areaOffice = '') {
  const b = TB.allBounds[tbAreaKey(areaOffice)];
  if (b) map.fitBounds(b);
}

function zoomToBarangay(town, brgy) {
  const key = `${town}||${brgy}`;
  const b   = TB.brgyBounds[key];
  if (b) map.fitBounds(b);
}
