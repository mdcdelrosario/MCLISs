let map;
let markers  = [];   
let markere  = [];   
let markerc  = [];   
let kmlPolylines = [];


function initMap() {
  map = new google.maps.Map(document.getElementById('map'), {
    center:           new google.maps.LatLng(15.904513, 120.383833),
    zoom:             10,
    gestureHandling:  'greedy',
    scrollwheel:      true,
    mapTypeControl:   true,
    mapTypeControlOptions: {
      position: google.maps.ControlPosition.TOP_LEFT,
    },
    styles: [
      { featureType: 'poi',     stylers: [{ visibility: 'off' }] },
      { featureType: 'transit', stylers: [{ visibility: 'off' }] },
    ],
  });

  map.addListener('click', (e) => {
    const lat = e.latLng.lat();
    const lng = e.latLng.lng();

    /* Consumer location pin */
    if (typeof addtag !== 'undefined' && addtag === 'CY') {
      document.getElementById('t_latc').value = lat;
      document.getElementById('t_lngc').value = lng;
      map.setOptions({ draggableCursor: '' });
      addtag = 'N';
      return;
    }

    if (typeof addtag !== 'undefined' && addtag === 'CNRY') {
      document.getElementById('t_latCNR').value = lat;
      document.getElementById('t_lngCNR').value = lng;
      map.setOptions({ draggableCursor: '' });
      addtag = 'N';
      return;
    }
  });


  let syncThrottle = null;
  const syncDelay  = 250;

  function emitMapChange() {
    window.dispatchEvent(new CustomEvent('mapstatechange', {
      detail: {
        lat:  map.getCenter()?.lat() ?? WY_DEFAULT.lat,
        lon:  map.getCenter()?.lng() ?? WY_DEFAULT.lon,
        zoom: Math.max(3, Math.min(18, map.getZoom() ?? WY_DEFAULT.zoom)),
      }
    }));
  }

  ['center_changed', 'zoom_changed', 'bounds_changed'].forEach(evt => {
    map.addListener(evt, () => {
      clearTimeout(syncThrottle);
      syncThrottle = setTimeout(emitMapChange, syncDelay);
    });
  });

  google.maps.event.addListenerOnce(map, 'idle', emitMapChange);
}

//location pin

function ChangeLoc() {
  map.setOptions({ draggableCursor: 'crosshair' });
  addtag = 'CY';
}

function ChangeLocCNR() {
  map.setOptions({ draggableCursor: 'crosshair' });
  addtag = 'CNRY';
}

//kml colors
const LINE_STYLES = {
  primary3:   { color: '#f5f128', weight: 2, zIndex: 3 },
  primary2:   { color: '#ff6600', weight: 2, zIndex: 4 },
  primary1:   { color: '#5ee95e', weight: 2, zIndex: 5 },
  secondary:  { color: '#2f2ce9', weight: 2, zIndex: 2 },
  underbuilt: { color: '#00FF00', weight: 2, zIndex: 1 },
};

const LINE_TYPE_META = {
  primary3:   { color: '#a1ac3f', label: 'Primary 3'  },
  primary2:   { color: '#ff6600', label: 'Primary 2'  },
  primary1:   { color: '#5cf369', label: 'Primary 1'  },
  secondary:  { color: '#0000ff', label: 'Secondary'  },
  underbuilt: { color: '#00FF00', label: 'Underbuilt' },
};


window._kmlImported = window._kmlImported || {};

//kml clear

function clearKMLLines() {
  kmlPolylines.forEach(p => p.setMap(null));
  kmlPolylines = [];
}

function clearImportedKML() {
  Object.values(window._kmlImported).forEach(types =>
    Object.values(types).forEach(polys =>
      polys.forEach(p => p.setMap(null))
    )
  );
  window._kmlImported = {};
  _rebuildImportPanel();
}

//to load kml file

function loadKMLFile(kmlPath, lineType, clearFirst = true) {
  if (clearFirst) clearKMLLines();
  const style = LINE_STYLES[lineType] ?? LINE_STYLES.primary;

  fetch(kmlPath)
    .then(r => {
      if (!r.ok) throw new Error(`File not found: ${kmlPath}`);
      return r.text();
    })
    .then(xmlText => _parseAndDrawKML(xmlText, lineType, style, true))
    .catch(err => {
      console.error('[loadKMLFile]', err);
      alert(`Could not load line file:\n${kmlPath}\n\n${err.message}`);
    });
}

function loadKMLByLineType() {
  const type   = document.getElementById('line_type')?.value ?? 'primary';
  const feeder = document.getElementById('vfeeder')?.value.trim();
  if (!feeder) { alert('Please enter a Feeder ID first.'); return; }

  clearKMLLines();

  if (type === 'all') {
    const types     = ['primary3','primary2', ,'primary1', 'secondary', 'underbuilt'];
    const allBounds = new google.maps.LatLngBounds();
    let   loaded    = 0;

    types.forEach(t => {
      fetch(`data/feeder${feeder}_${t}.kml`)
        .then(r => {
          if (!r.ok) throw new Error(`Not found: feeder${feeder}_${t}.kml`);
          return r.text();
        })
        .then(xmlText => {
          const style = LINE_STYLES[t] ?? LINE_STYLES.primary;
          const { bounds } = _parseAndDrawKML(xmlText, t, style, true);
          allBounds.union(bounds);
          loaded++;
          if (loaded === types.length && !allBounds.isEmpty()) map.fitBounds(allBounds);
        })
        .catch(err => {
          loaded++;
          console.warn(`[loadKMLByLineType] Skipped ${t}:`, err.message);
          if (loaded === types.length && !allBounds.isEmpty()) map.fitBounds(allBounds);
        });
    });
    return;
  }

  loadKMLFile(`data/feeder${feeder}_${type}.kml`, type, true);
}


function hexToRgba(hex, alpha) {
  const r = parseInt(hex.slice(1, 3), 16);
  const g = parseInt(hex.slice(3, 5), 16);
  const b = parseInt(hex.slice(5, 7), 16);
  return `rgba(${r},${g},${b},${alpha})`;
}

function _parseAndDrawKML(xmlText, lineType, style, pushToKmlPolylines = false) {
  const kml    = new DOMParser().parseFromString(xmlText, 'text/xml');
  const lines  = kml.querySelectorAll('LineString coordinates');
  const bounds = new google.maps.LatLngBounds();
  const drawn  = [];

 
  const meta  = LINE_TYPE_META[lineType] ?? { color: style.color, label: lineType };
  const color = meta.color;
  const label = meta.label;

  lines.forEach(coordNode => {
    const path = coordNode.textContent
      .trim().split(/\s+/)
      .map(pt => {
        const [lng, lat] = pt.split(',').map(Number);
        return { lat, lng };
      })
      .filter(p => !isNaN(p.lat) && !isNaN(p.lng));

    if (path.length < 2) return;

    const poly = new google.maps.Polyline({
      path,
      map,
      strokeColor:   style.color,
      strokeOpacity: 1.0,
      strokeWeight:  style.weight,
      zIndex:        style.zIndex,
    });

    const get = name => coordNode
      .closest('Placemark')
      ?.querySelector(`SimpleData[name="${name}"]`)
      ?.textContent ?? '—';

    const infowindow = new google.maps.InfoWindow();

    poly.addListener('mouseover', e => {
      infowindow.setContent(`
        <div style="font-family:inherit;font-size:12px;min-width:180px;">

          <!-- Colored type badge matching the line on the map -->
          <div style="
            display:inline-flex;align-items:center;gap:6px;
            padding:4px 10px;border-radius:20px;margin-bottom:8px;
            background:${hexToRgba(color, 0.12)};
            border:1.5px solid ${hexToRgba(color, 0.5)};">
            <span style="
              display:inline-block;width:10px;height:10px;
              border-radius:50%;background:${color};flex-shrink:0;">
            </span>
            <span style="font-weight:700;color:${color};font-size:12px;">
              ${label}
            </span>
          </div>

          <!-- Section details -->
          <table style="border-collapse:collapse;width:100%;font-size:11.5px;">
            <tr>
              <td style="color:#888;padding:2px 6px 2px 0;white-space:nowrap;">Section</td>
              <td style="font-weight:600;padding:2px 0;">${get('SectionID')}</td>
            </tr>
            <tr>
              <td style="color:#888;padding:2px 6px 2px 0;">Phase</td>
              <td style="padding:2px 0;">${get('Phase')}</td>
            </tr>
            <tr>
              <td style="color:#888;padding:2px 6px 2px 0;">Phase Cond</td>
              <td style="padding:2px 0;">${get('PhaseCondSize')} ${get('PhaseCondType')}</td>
            </tr>
            <tr>
              <td style="color:#888;padding:2px 6px 2px 0;">Neutral Cond</td>
              <td style="padding:2px 0;">${get('NeutralCondSize')} ${get('NeutralCondType')}</td>
            </tr>
            <tr>
              <td style="color:#888;padding:2px 6px 2px 0;">From</td>
              <td style="padding:2px 0;">${get('FromBus')}</td>
            </tr>
            <tr>
              <td style="color:#888;padding:2px 6px 2px 0;">To</td>
              <td style="padding:2px 0;">${get('ToBus')}</td>
            </tr>
          </table>

        </div>
      `);
      infowindow.setPosition(e.latLng);
      infowindow.open(map);
    });

    poly.addListener('mouseout', () => infowindow.close());

    path.forEach(p => bounds.extend(p));
    drawn.push(poly);
    if (pushToKmlPolylines) kmlPolylines.push(poly);
  });

  if (pushToKmlPolylines && kmlPolylines.length > 0) map.fitBounds(bounds);
  return { polylines: drawn, bounds };
}

/* ── Legacy wrappers ── */
function loadKMLFilePrimary(kmlPath)    { loadKMLFile(kmlPath, 'primary');   }
function loadKMLFilesecondary(kmlPath)  { loadKMLFile(kmlPath, 'secondary'); }
function loadKMLFileunderbuilt(kmlPath) { loadKMLFile(kmlPath, 'underbuilt'); }



// import multiple kml files

function triggerKMLImport() {
  const input = document.getElementById('kml-file-input');
  input.multiple = true;
  input.click();
}

async function handleKMLImport(event) {
  const files = Array.from(event.target.files);
  if (!files.length) return;

  const ok   = [];
  const fail = [];

  for (const file of files) {
    const fd = new FormData();
    fd.append('kml_file', file);

    try {
      const res  = await fetch('upload_kml.php', { method: 'POST', body: fd });
      const data = await res.json();

      if (!data.success) {
        fail.push({ file: file.name, error: data.message ?? 'Upload failed.' });
        continue;
      }

      /* Draw from the server-saved file */
      const style    = LINE_STYLES[data.lineType] ?? LINE_STYLES.primary;
      const fetchRes = await fetch(data.path);
      const xmlText  = await fetchRes.text();
      const { polylines } = _parseAndDrawKML(xmlText, data.lineType, style, false);

      /* Store in _kmlImported registry */
      const feeder = data.feeder;
      const lt     = data.lineType;
      if (!window._kmlImported[feeder])     window._kmlImported[feeder] = {};
      if (!window._kmlImported[feeder][lt]) window._kmlImported[feeder][lt] = [];

      /* Replace if same feeder+type already imported */
      window._kmlImported[feeder][lt].forEach(p => p.setMap(null));
      window._kmlImported[feeder][lt] = polylines;

      ok.push({ file: file.name, feeder, lineType: lt, segments: polylines.length, path: data.path });

    } catch (err) {
      fail.push({ file: file.name, error: err.message });
    }
  }

  _rebuildImportPanel(ok, fail);

  /* Fit map to all newly imported lines */
  if (ok.length > 0) {
    const bounds = new google.maps.LatLngBounds();
    ok.forEach(f => {
      (window._kmlImported[f.feeder]?.[f.lineType] || []).forEach(poly => {
        poly.getPath().forEach(pt => bounds.extend(pt));
      });
    });
    if (!bounds.isEmpty()) map.fitBounds(bounds);
  }

  event.target.value = '';
}

function toggleKMLLayer(feeder, lineType, visible) {
  (window._kmlImported?.[feeder]?.[lineType] || []).forEach(p => p.setMap(visible ? map : null));
}

function removeKMLFeeder(feeder) {
  Object.values(window._kmlImported[feeder] || {}).forEach(polys =>
    polys.forEach(p => p.setMap(null))
  );
  delete window._kmlImported[feeder];
  _rebuildImportPanel();
}


function _rebuildImportPanel(newOk, newFail) {
  const dvBody  = document.getElementById('dvpanel-body');
  const dvPanel = document.getElementById('dvpanel');
  if (!dvBody) return;
  if (dvPanel) dvPanel.style.display = 'flex';
  if (typeof positionDvPanel === 'function') requestAnimationFrame(positionDvPanel);

  const registry = window._kmlImported;
  const feeders  = Object.keys(registry);

  /* ── Use top-level LINE_TYPE_META — fixed bug ── */
  const feederRows = feeders.map(feeder => {
    const types = registry[feeder];
    const chips = Object.keys(types).map(lt => {
      const meta  = LINE_TYPE_META[lt] ?? { color: '#888', label: lt };
      const count = types[lt].length;
      const uid   = `kml_${feeder}_${lt}`.replace(/[^a-z0-9_]/gi, '_');
      return `
        <label style="display:inline-flex;align-items:center;gap:4px;padding:2px 8px;
               border-radius:20px;font-size:11px;cursor:pointer;user-select:none;
               border:1px solid ${meta.color}44;background:${meta.color}18;color:${meta.color};">
          <input type="checkbox" id="${uid}" checked
                 onchange="toggleKMLLayer('${feeder}','${lt}',this.checked)"
                 style="accent-color:${meta.color};margin:0;width:11px;height:11px;">
          ${meta.label} <span style="opacity:.6;">(${count})</span>
        </label>`;
    }).join('');

    return `
      <div style="padding:8px 10px;border-bottom:1px solid rgba(0,0,0,.06);
                  display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
        <span style="font-size:12px;font-weight:600;flex:1;min-width:60px;">Feeder ${feeder}</span>
        ${chips}
        <button onclick="removeKMLFeeder('${feeder}')"
                style="background:none;border:none;cursor:pointer;color:#c62828;
                       font-size:13px;padding:2px 4px;" title="Remove feeder">
          <i class="fa fa-times"></i>
        </button>
      </div>`;
  }).join('');

  const importSummary = newOk?.length ? `
    <div style="padding:6px 10px 2px;font-size:11px;color:#2e7d32;">
      <i class="fa fa-check-circle"></i>
      ${newOk.length} file(s) uploaded —
      ${newOk.map(f => `Feeder ${f.feeder} ${f.lineType} (${f.segments} seg)`).join(', ')}
    </div>` : '';

  const failHTML = newFail?.length ? `
    <div style="padding:6px 10px;color:#c62828;font-size:11px;">
      <i class="fa fa-exclamation-triangle"></i>
      ${newFail.map(f => `<b>${f.file}</b>: ${f.error}`).join('<br>')}
    </div>` : '';

  const emptyHTML = !feeders.length ? `
    <div style="padding:20px 12px;color:#999;font-size:12px;text-align:center;">
      <i class="fa fa-upload" style="font-size:24px;display:block;margin-bottom:8px;opacity:.35;"></i>
      No KML files imported yet.<br>
      <span style="font-size:11px;">
        Name files as <b>feeder{ID}_{type}.kml</b><br>
        e.g. feeder11_primary.kml, feeder65_primary2.kml
      </span>
    </div>` : '';

  dvBody.innerHTML = `
    <div style="padding:8px 12px 4px;font-size:11px;font-weight:600;letter-spacing:.05em;
                text-transform:uppercase;color:#888;display:flex;align-items:center;
                justify-content:space-between;">
      <span><i class="fa fa-upload" style="margin-right:5px;"></i>Imported KML Lines</span>
      ${feeders.length ? `<button onclick="clearImportedKML()"
        style="background:none;border:none;color:#c62828;font-size:11px;cursor:pointer;">
        <i class="fa fa-trash"></i> Clear all</button>` : ''}
    </div>
    ${importSummary}${failHTML}${emptyHTML}${feederRows}
    ${feeders.length ? `
    <div style="padding:6px 10px 8px;font-size:10px;color:#aaa;line-height:1.8;">
      <span style="color:#cc0000;">■</span> Primary3 &nbsp;
      <span style="color:#ff6600;">■</span> Primary2 &nbsp;
      <span style="color:#9900cc;">■</span> Primary1 &nbsp;
      <span style="color:#0000ff;">■</span> Secondary &nbsp;
      <span style="color:#00AA00;">■</span> Underbuilt
    </div>` : ''}`;
}


// element markers

const ELEMENT_ICON_MAP = {
  'ppole.png':   'ppole.png',
  'spole.png':   'spole.png',
  'upole.png':   'upole.png',
  'xformer.png': 'xformer.png',
  'primary':     'ppole.png',
  'secondary':   'spole.png',
  'underbuilt':  'upole.png',
  'transformer': 'xformer.png',
};

function clearElements() {
  markere.forEach(m => m.setMap(null));
  markere = [];

  if (window.activeElementMarkers?.length) {
    window.activeElementMarkers.forEach(m => m.setMap(null));
    window.activeElementMarkers = [];
  }

  if (window._linePolyline) {
    window._linePolyline.setMap(null);
    window._linePolyline = null;
  }
}

window.clearAllMap = clearElements;

function _addPoleMarkerToMap({ trn, lat, lng, type, feeder, icon }) {
  const marker = new google.maps.Marker({
    position: { lat, lng },
    map,
    title:  `${type} — ${feeder}`,
    icon:   _makePoleMarkerIcon(icon),
    zIndex: 10,
  });
  marker.set('trn',         trn);
  marker.set('elementType', 'pole');
  marker.addListener('click', () => showPoleInfo(trn, marker));
  markere.push(marker);
  return marker;
}

function viewElements(features = []) {
  clearElements();
  const bounds = new google.maps.LatLngBounds();

  features.forEach(feature => {
    if (!feature.position) return;

    const iconFile = ELEMENT_ICON_MAP[(feature.icon ?? '').toLowerCase().trim()] ?? 'ppole.png';
    const infowindow = new google.maps.InfoWindow();

    const marker = new google.maps.Marker({
      position: feature.position,
      map,
      icon: { url: 'iconsx/' + iconFile, scaledSize: new google.maps.Size(10, 10) },
      draggable: false,
    });

    marker.set('trn', feature.trn);
    bounds.extend(feature.position);

    marker.addListener('mouseover', () => {
      infowindow.setContent(
        `<b>ID: ${feature.id}</b>` +
        `<br>TYPE: ${feature.type}` +
        `<br>FEEDER: ${feature.feeder}`
      );
      infowindow.open(map, marker);
    });
    marker.addListener('mouseout',  () => infowindow.close());
    marker.addListener('click',     () => {
      if (feature.trn) showPoleInfo(feature.trn, marker);
    });

    markere.push(marker);
  });

  if (markere.length > 0) {
    map.fitBounds(bounds);
    map.setZoom(map.getZoom() - 1);
  }
}

//customer markers

function searchMap(features = []) {
  markers.forEach(m => m.setMap(null));
  markers = [];
  const bounds = new google.maps.LatLngBounds();

  features.forEach(feature => {
    let iconFile = 'meter.png';
    if (feature.type === 'A') iconFile = 'meterg.png';
    if (feature.type === 'D') iconFile = 'meterr.png';
    if (feature.type === 'T') iconFile = 'metery.png';

    const infowindow = new google.maps.InfoWindow();

    const marker = new google.maps.Marker({
      position:  feature.position,
      map,
      icon: { url: 'iconsx/' + iconFile, scaledSize: new google.maps.Size(25, 25) },
      draggable: false,
    });

    bounds.extend(feature.position);

    marker.addListener('mouseover', () => {
      const findings = feature.ffdesc?.trim()
        ? `<br>FINDINGS: <font color='red'>${feature.ffdesc}</font>` : '';
      infowindow.setContent(
        `<b>${feature.name}</b>` +
        `<br>ACCOUNT NO: ${feature.faccountno}` +
        `<br>METER NO: ${feature.meterno}` +
        findings
      );
      infowindow.open(map, marker);
    });
    marker.addListener('mouseout',  () => infowindow.close());
    marker.addListener('click',     () => {
      PageLink('custinfo.php', 'dvcustinfo', 'fsearch', '&vaccount=' + feature.accountno);
      document.getElementById('id01').style.display = 'block';
    });

    markers.push(marker);

    /* Tag overlay marker */
    if (feature.btag) {
      const tagInfowindow = new google.maps.InfoWindow();
      const tagMarker = new google.maps.Marker({
        position:  feature.position,
        map,
        icon: { url: feature.btag, scaledSize: new google.maps.Size(40, 40) },
        draggable: false,
      });

      tagMarker.addListener('mouseover', () => {
        const findings = feature.ffdesc?.trim()
          ? `<br>FINDINGS: <font color='red'>${feature.ffdesc}</font>` : '';
        tagInfowindow.setContent(
          `<b>${feature.name}</b>` +
          `<br>ACCOUNT NO: ${feature.faccountno}` +
          `<br>METER NO: ${feature.meterno}` +
          `<br>TAG: ${feature.tag}` +
          findings
        );
        tagInfowindow.open(map, tagMarker);
      });
      tagMarker.addListener('mouseout',  () => tagInfowindow.close());
      tagMarker.addListener('click',     () => {
        PageLink('custinfo.php', 'dvcustinfo', 'fsearch', '&vaccount=' + feature.accountno);
        document.getElementById('id01').style.display = 'block';
      });

      markers.push(tagMarker);
    }
  });

  if (markers.length > 0) {
    map.fitBounds(bounds);
    map.setZoom(map.getZoom() - 1);
  }
}


//complaints marker

function searchMapCNR(features = []) {
  markers.forEach(m => m.setMap(null));
  markers = [];
  const bounds = new google.maps.LatLngBounds();

  features.forEach(feature => {
    let iconFile = 'cnr.png';
    if (feature.req_dt) {
      const diffDays = (Date.now() - new Date(feature.req_dt)) / 86400000;
      if (diffDays >= 3) iconFile = 'cnr3.png';
      else if (diffDays >= 1) iconFile = 'cnr2.png';
    }

    const infowindow = new google.maps.InfoWindow();

    const marker = new google.maps.Marker({
      position:  feature.position,
      map,
      icon: { url: 'iconsx/' + iconFile, scaledSize: new google.maps.Size(25, 25) },
      draggable: false,
    });

    bounds.extend(feature.position);

    marker.addListener('mouseover', () => {
      infowindow.setContent(
        `<b>${feature.name}</b>` +
        `<br>ADDRESS: ${feature.address}` +
        `<br>CONTACT NO: ${feature.contactno}` +
        `<br>COMPLAINT: ${feature.cnr}` +
        `<br>CREW: ${feature.crew}` +
        `<br>R. DATE: ${feature.req_dt}`
      );
      infowindow.open(map, marker);
    });
    marker.addListener('mouseout',  () => infowindow.close());
    marker.addListener('click',     () => {
      PageLink('comnreq.php', 'dvcustinfo3', 'fsearch', '&trn=' + feature.trn);
      document.getElementById('id03').style.display = 'block';
    });

    markers.push(marker);
  });
}

//vehicle marker

function clearCars() {
  markerc.forEach(m => m.setMap(null));
  markerc = [];
}

function viewCars(features = []) {
  clearCars();
  const bounds = new google.maps.LatLngBounds();

  features.forEach(feature => {
    const lat = parseFloat(feature.lat);
    const lng = parseFloat(feature.lng);
    if (isNaN(lat) || isNaN(lng)) { console.warn('[viewCars] Skipped invalid point:', feature); return; }

    const marker = new google.maps.Marker({
      position: { lat, lng },
      map,
      icon: { url: 'iconsx/car.png', scaledSize: new google.maps.Size(16, 16) },
      label: { text: feature.crew || 'N/A', color: 'black', fontSize: '14px', fontWeight: 'bold' },
      draggable: false,
    });

    bounds.extend({ lat, lng });
    markerc.push(marker);
  });
}


//zoom map to customer 

function setmapView(lat, lng, vaccountno) {
  map.setZoom(20);
  map.panTo(new google.maps.LatLng(lat, lng + 0.0002));
  PageLink('custinfo.php', 'dvcustinfo', 'fsearch', '&vaccount=' + vaccountno);
  document.getElementById('id01').style.display = 'block';
}

function setmapViewCNR(lat, lng, vtrn) {
  map.setZoom(20);
  map.panTo(new google.maps.LatLng(lat, lng + 0.0002));
  PageLink('comnreq.php', 'dvcustinfo3', 'fsearch', '&trn=' + vtrn);
  document.getElementById('id03').style.display = 'block';
}