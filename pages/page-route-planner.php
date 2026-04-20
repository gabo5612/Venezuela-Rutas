<?php
/**
 * Template Name: Route Planner
 *
 * Interactive route planning tool: set start/end points, calculate route via OSRM,
 * fetch elevation profile via OpenTopoData, download result as GPX.
 */

$rp_logged_in = is_user_logged_in();
$rp_nonce     = wp_create_nonce('rutas_suggest_nonce');
$rp_ajaxurl   = admin_url('admin-ajax.php');

get_template_part('parts/header');
?>

<div class="rp-layout">

  <!-- ── SIDEBAR PANEL ─────────────────────────────────────── -->
  <aside class="rp-panel" id="rp-panel">

    <div class="rp-panel__header">
      <span class="material-symbols-outlined rp-panel__icon">route</span>
      <h1 class="rp-panel__title">Route Planner</h1>
      <button class="rp-panel__toggle" id="rp-toggle" aria-label="Toggle panel">
        <span class="material-symbols-outlined">chevron_left</span>
      </button>
    </div>

    <div class="rp-panel__body" id="rp-body">

      <!-- Instructions -->
      <p class="rp-hint" id="rp-hint">Click the map or search to set your start and end points.</p>

      <!-- Waypoint inputs -->
      <div class="rp-waypoints">

        <div class="rp-wp" id="rp-wp-start">
          <div class="rp-wp__dot rp-wp__dot--start"></div>
          <div class="rp-wp__field">
            <input type="text" class="rp-input" id="rp-start-input"
                   placeholder="Start point…" autocomplete="off">
            <ul class="rp-suggestions" id="rp-start-suggestions"></ul>
          </div>
          <button class="rp-wp__pick rp-wp__pick--active" id="rp-pick-start" title="Click map to set start">
            <span class="material-symbols-outlined">my_location</span>
          </button>
        </div>

        <div class="rp-wp__connector"></div>

        <div class="rp-wp" id="rp-wp-end">
          <div class="rp-wp__dot rp-wp__dot--end"></div>
          <div class="rp-wp__field">
            <input type="text" class="rp-input" id="rp-end-input"
                   placeholder="End point…" autocomplete="off">
            <ul class="rp-suggestions" id="rp-end-suggestions"></ul>
          </div>
          <button class="rp-wp__pick" id="rp-pick-end" title="Click map to set end">
            <span class="material-symbols-outlined">flag</span>
          </button>
        </div>

      </div>

      <!-- Profile selector -->
      <div class="rp-profiles">
        <button class="rp-profile" data-profile="car" title="Driving">
          <span class="material-symbols-outlined">directions_car</span>
          <span>Driving</span>
        </button>
        <button class="rp-profile" data-profile="bike" title="Cycling">
          <span class="material-symbols-outlined">directions_bike</span>
          <span>Cycling</span>
        </button>
        <button class="rp-profile" data-profile="foot" title="Hiking / Walking">
          <span class="material-symbols-outlined">hiking</span>
          <span>Hiking</span>
        </button>
      </div>

      <!-- Calculate button -->
      <button class="rp-btn-calc" id="rp-calc" disabled>
        <span class="material-symbols-outlined">calculate</span>
        Calculate Route
      </button>

      <!-- Loading state -->
      <div class="rp-loading" id="rp-loading" hidden>
        <span class="rp-spinner"></span>
        <span>Calculating…</span>
      </div>

      <!-- Stats -->
      <div class="rp-stats" id="rp-stats" hidden>
        <div class="rp-stat">
          <span class="material-symbols-outlined rp-stat__icon">straighten</span>
          <div>
            <div class="rp-stat__label">Distance</div>
            <div class="rp-stat__value" id="rp-distance">—</div>
          </div>
        </div>
        <div class="rp-stat">
          <span class="material-symbols-outlined rp-stat__icon">trending_up</span>
          <div>
            <div class="rp-stat__label">Elevation Gain</div>
            <div class="rp-stat__value" id="rp-gain">—</div>
          </div>
        </div>
        <div class="rp-stat">
          <span class="material-symbols-outlined rp-stat__icon">trending_down</span>
          <div>
            <div class="rp-stat__label">Elevation Loss</div>
            <div class="rp-stat__value" id="rp-loss">—</div>
          </div>
        </div>
        <div class="rp-stat">
          <span class="material-symbols-outlined rp-stat__icon">schedule</span>
          <div>
            <div class="rp-stat__label">Est. Time</div>
            <div class="rp-stat__value" id="rp-time">—</div>
          </div>
        </div>
      </div>

      <!-- Elevation profile -->
      <div class="rp-elevation" id="rp-elevation" hidden>
        <div class="rp-elevation__header">
          <span class="rp-elevation__title">Elevation Profile</span>
          <span class="rp-elevation__range" id="rp-ele-range"></span>
        </div>
        <svg class="rp-elevation__chart" id="rp-chart"
             viewBox="0 0 400 120" preserveAspectRatio="none"
             aria-label="Elevation profile chart">
          <defs>
            <linearGradient id="rp-ele-grad" x1="0" y1="0" x2="0" y2="1">
              <stop offset="0%" stop-color="#0df246" stop-opacity="0.35"/>
              <stop offset="100%" stop-color="#0df246" stop-opacity="0.03"/>
            </linearGradient>
          </defs>
          <path id="rp-chart-fill" fill="url(#rp-ele-grad)"/>
          <path id="rp-chart-line" fill="none" stroke="#0df246" stroke-width="1.5"/>
        </svg>
      </div>

      <!-- Download GPX -->
      <button class="rp-btn-gpx" id="rp-gpx" hidden>
        <span class="material-symbols-outlined">download</span>
        Download GPX
      </button>

      <!-- Save as Route Draft -->
      <?php if ($rp_logged_in): ?>
      <div class="rp-save-wrap" id="rp-save-wrap" hidden>
        <input type="text" class="rp-input" id="rp-save-name"
               placeholder="Name this route…" autocomplete="off">
        <button class="rp-btn-calc" id="rp-save-confirm">
          <span class="material-symbols-outlined">save</span>
          Save Draft
        </button>
        <div class="rp-save-msg" id="rp-save-msg" hidden></div>
      </div>
      <button class="rp-btn-gpx rp-btn-save" id="rp-save" hidden>
        <span class="material-symbols-outlined">bookmark_add</span>
        Save as Route
      </button>
      <?php else: ?>
      <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>"
         class="rp-btn-gpx rp-btn-save" id="rp-save" hidden>
        <span class="material-symbols-outlined">login</span>
        Login to Save Route
      </a>
      <?php endif; ?>

      <!-- Clear -->
      <button class="rp-btn-clear" id="rp-clear" hidden>
        <span class="material-symbols-outlined">delete_sweep</span>
        Clear Route
      </button>

    </div><!-- /.rp-panel__body -->
  </aside><!-- /.rp-panel -->

  <!-- ── MAP ───────────────────────────────────────────────── -->
  <div class="rp-map-wrap">
    <div id="rp-map"></div>
    <!-- Map cursor hint -->
    <div class="rp-cursor-hint" id="rp-cursor-hint" hidden>
      <span class="material-symbols-outlined">add_location</span>
      <span id="rp-cursor-label">Click to set start</span>
    </div>
  </div>

</div><!-- /.rp-layout -->

<script>
(function () {
  /* ─── PHP config ────────────────────────────────────────── */
  var RP_AJAX    = '<?php echo esc_js($rp_ajaxurl); ?>';
  var RP_NONCE   = '<?php echo esc_js($rp_nonce); ?>';
  var RP_LOGGEDIN = <?php echo $rp_logged_in ? 'true' : 'false'; ?>;

  /* ─── Constants ─────────────────────────────────────────── */
  var OSRM_BASE   = 'https://router.project-osrm.org/route/v1';
  var TOPO_BASE   = 'https://api.opentopodata.org/v1/srtm30m';
  var NOMINATIM   = 'https://nominatim.openstreetmap.org/search';
  var TILE_DARK   = 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png';
  var TILE_LIGHT  = 'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png';
  var TILE_ATTR   = '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/">CARTO</a>';

  /* ─── State ─────────────────────────────────────────────── */
  var map, tileLayer;
  var startMarker = null, endMarker  = null;
  var startCoords = null, endCoords  = null; // { lat, lng, name }
  var routeLayer  = null;
  var placingMode = null; // 'start' | 'end' | null
  var activeProfile = 'car';
  var currentRoute  = null; // { points: [[lat,lng]...], elevations: [m...], distance: m, duration: s }
  var geocodeTimers = {};

  /* ─── DOM refs ───────────────────────────────────────────── */
  var $startInput  = document.getElementById('rp-start-input');
  var $endInput    = document.getElementById('rp-end-input');
  var $startSugg   = document.getElementById('rp-start-suggestions');
  var $endSugg     = document.getElementById('rp-end-suggestions');
  var $pickStart   = document.getElementById('rp-pick-start');
  var $pickEnd     = document.getElementById('rp-pick-end');
  var $calcBtn     = document.getElementById('rp-calc');
  var $loading     = document.getElementById('rp-loading');
  var $stats       = document.getElementById('rp-stats');
  var $elevation   = document.getElementById('rp-elevation');
  var $gpxBtn      = document.getElementById('rp-gpx');
  var $saveBtn     = document.getElementById('rp-save');
  var $saveWrap    = document.getElementById('rp-save-wrap');
  var $saveName    = document.getElementById('rp-save-name');
  var $saveConfirm = document.getElementById('rp-save-confirm');
  var $saveMsg     = document.getElementById('rp-save-msg');
  var $clearBtn    = document.getElementById('rp-clear');
  var $cursorHint  = document.getElementById('rp-cursor-hint');
  var $cursorLabel = document.getElementById('rp-cursor-label');
  var $hint        = document.getElementById('rp-hint');
  var $toggle      = document.getElementById('rp-toggle');
  var $panel       = document.getElementById('rp-panel');

  /* ─── Map init ───────────────────────────────────────────── */
  map = L.map('rp-map', {
    center: [7.5, -66.5],
    zoom: 6,
    zoomControl: false,
    renderer: L.svg({ padding: 0.5 })
  });

  tileLayer = L.tileLayer(TILE_LIGHT, {
    attribution: TILE_ATTR,
    subdomains: 'abcd',
    maxZoom: 19
  }).addTo(map);

  L.control.zoom({ position: 'bottomright' }).addTo(map);

  if (typeof L.control.locate !== 'undefined') {
    L.control.locate({ position: 'bottomright', flyTo: true }).addTo(map);
  }

  // Pre-add an invisible polyline so the SVG renderer and its _bounds
  // are fully initialised before any route arrives. We'll update it via
  // setLatLngs() instead of remove/re-add, avoiding the Bounds bug entirely.
  routeLayer = L.polyline([], {
    color: '#0df246', weight: 4, opacity: 0,
    lineCap: 'round', lineJoin: 'round'
  }).addTo(map);

  // Force the map to recalculate its size once the CSS grid has settled.
  setTimeout(function () { map.invalidateSize(false); }, 100);

  /* ─── Marker icons ───────────────────────────────────────── */
  function makeIcon(cls) {
    return L.divIcon({ className: 'map-pin ' + cls, iconSize: [14, 14], iconAnchor: [7, 7] });
  }

  /* ─── Place marker ───────────────────────────────────────── */
  function placeStart(latlng, name) {
    if (startMarker) map.removeLayer(startMarker);
    startMarker = L.marker(latlng, { icon: makeIcon('map-pin--start'), draggable: true }).addTo(map);
    startCoords = { lat: latlng.lat, lng: latlng.lng, name: name || formatCoord(latlng) };
    $startInput.value = startCoords.name;
    startMarker.on('dragend', function (e) {
      startCoords = { lat: e.target.getLatLng().lat, lng: e.target.getLatLng().lng, name: formatCoord(e.target.getLatLng()) };
      $startInput.value = startCoords.name;
      updateCalcBtn();
    });
    updateCalcBtn();
  }

  function placeEnd(latlng, name) {
    if (endMarker) map.removeLayer(endMarker);
    endMarker = L.marker(latlng, { icon: makeIcon('map-pin--end'), draggable: true }).addTo(map);
    endCoords = { lat: latlng.lat, lng: latlng.lng, name: name || formatCoord(latlng) };
    $endInput.value = endCoords.name;
    endMarker.on('dragend', function (e) {
      endCoords = { lat: e.target.getLatLng().lat, lng: e.target.getLatLng().lng, name: formatCoord(e.target.getLatLng()) };
      $endInput.value = endCoords.name;
      updateCalcBtn();
    });
    updateCalcBtn();
  }

  function formatCoord(latlng) {
    return latlng.lat.toFixed(5) + ', ' + latlng.lng.toFixed(5);
  }

  /* ─── Map click ──────────────────────────────────────────── */
  map.on('click', function (e) {
    if (placingMode === 'start') {
      placeStart(e.latlng);
      setPlacingMode(null);
      if (!endCoords) setPlacingMode('end'); // auto-prompt end
    } else if (placingMode === 'end') {
      placeEnd(e.latlng);
      setPlacingMode(null);
    } else {
      // Smart: if no start → set start; else if no end → set end
      if (!startCoords) {
        placeStart(e.latlng);
        setPlacingMode('end');
      } else if (!endCoords) {
        placeEnd(e.latlng);
      }
    }
  });

  /* ─── Placing mode ───────────────────────────────────────── */
  function setPlacingMode(mode) {
    placingMode = mode;
    $pickStart.classList.toggle('rp-wp__pick--active', mode === 'start');
    $pickEnd.classList.toggle('rp-wp__pick--active', mode === 'end');

    if (mode === 'start') {
      $cursorLabel.textContent = 'Click to set start point';
      $cursorHint.hidden = false;
      map.getContainer().style.cursor = 'crosshair';
    } else if (mode === 'end') {
      $cursorLabel.textContent = 'Click to set end point';
      $cursorHint.hidden = false;
      map.getContainer().style.cursor = 'crosshair';
    } else {
      $cursorHint.hidden = true;
      map.getContainer().style.cursor = '';
    }
  }

  $pickStart.addEventListener('click', function () {
    setPlacingMode(placingMode === 'start' ? null : 'start');
  });
  $pickEnd.addEventListener('click', function () {
    setPlacingMode(placingMode === 'end' ? null : 'end');
  });

  /* ─── Profile selector ───────────────────────────────────── */
  document.querySelectorAll('.rp-profile').forEach(function (btn) {
    btn.addEventListener('click', function () {
      document.querySelectorAll('.rp-profile').forEach(function (b) { b.classList.remove('is-active'); });
      btn.classList.add('is-active');
      activeProfile = btn.dataset.profile;
    });
    if (btn.dataset.profile === activeProfile) btn.classList.add('is-active');
  });

  /* ─── Geocoding (Nominatim) ──────────────────────────────── */
  function geocode(query, callback) {
    var url = NOMINATIM + '?q=' + encodeURIComponent(query) +
              '&format=json&limit=5&countrycodes=ve&accept-language=es';
    fetch(url, { headers: { 'Accept-Language': 'es' } })
      .then(function (r) { return r.json(); })
      .then(callback)
      .catch(function () { callback([]); });
  }

  function setupGeoInput(input, suggestionsEl, onSelect) {
    input.addEventListener('input', function () {
      var q = input.value.trim();
      clearTimeout(geocodeTimers[input.id]);
      suggestionsEl.innerHTML = '';
      if (q.length < 3) return;
      geocodeTimers[input.id] = setTimeout(function () {
        geocode(q, function (results) {
          suggestionsEl.innerHTML = '';
          results.slice(0, 5).forEach(function (r) {
            var li = document.createElement('li');
            li.className = 'rp-suggestion';
            li.textContent = r.display_name;
            li.addEventListener('click', function () {
              onSelect({ lat: parseFloat(r.lat), lng: parseFloat(r.lon), name: r.display_name.split(',')[0].trim() });
              suggestionsEl.innerHTML = '';
            });
            suggestionsEl.appendChild(li);
          });
        });
      }, 400);
    });

    document.addEventListener('click', function (e) {
      if (!input.contains(e.target) && !suggestionsEl.contains(e.target)) {
        suggestionsEl.innerHTML = '';
      }
    });
  }

  setupGeoInput($startInput, $startSugg, function (result) {
    var ll = L.latLng(result.lat, result.lng);
    placeStart(ll, result.name);
    map.setView(ll, 13);
    $startInput.value = result.name;
  });

  setupGeoInput($endInput, $endSugg, function (result) {
    var ll = L.latLng(result.lat, result.lng);
    placeEnd(ll, result.name);
    map.setView(ll, 13);
    $endInput.value = result.name;
  });

  /* ─── Calc button state ──────────────────────────────────── */
  function updateCalcBtn() {
    $calcBtn.disabled = !(startCoords && endCoords);
    if (startCoords && endCoords) {
      $hint.textContent = 'Ready! Click "Calculate Route" to get your route.';
    } else if (startCoords) {
      $hint.textContent = 'Now set the end point on the map or search above.';
    }
  }

  /* ─── Main: Calculate route ──────────────────────────────── */
  $calcBtn.addEventListener('click', calculateRoute);

  function calculateRoute() {
    if (!startCoords || !endCoords) return;
    console.log('[RP] 1. calculateRoute() — start:', startCoords, '| end:', endCoords, '| profile:', activeProfile);

    setLoading(true);
    clearRoute();

    var profile = { 'bike': 'bike', 'car': 'car' }[activeProfile] || 'foot';

    fetchRoute(profile, function (err, routeData) {
      if (err || !routeData) {
        setLoading(false);
        showError(err || 'No route found. Try different points or profile.');
        return;
      }

      console.log('[RP] 5. Route ready — engine:', routeData.engine,
                  '| points:', routeData.coordinates.length,
                  '| distance:', routeData.distance + 'm');
      console.log('[RP] 6. Fetching elevation…');

      fetchElevation(routeData.coordinates, function (elevations) {
        console.log('[RP] 7. Elevation — min:', Math.min.apply(null, elevations) + 'm',
                    '| max:', Math.max.apply(null, elevations) + 'm');

        currentRoute = {
          points:    routeData.coordinates,
          elevations: elevations,
          distance:  routeData.distance,
          duration:  routeData.duration
        };

        drawRoute(routeData.coordinates);
        displayStats(currentRoute);
        drawElevationProfile(elevations);
        setLoading(false);
        $gpxBtn.hidden   = false;
        if ($saveBtn) $saveBtn.hidden = false;
        $clearBtn.hidden = false;
        $stats.hidden    = false;

        try { map.fitBounds(routeLayer.getBounds(), { padding: [40, 40] }); }
        catch (e) { console.warn('[RP] fitBounds:', e.message); }
        console.log('[RP] 8. Done.');
      });
    });
  }

  /* ─── Routing: PHP proxy (ORS or OSRM) → browser-direct OSRM ── */
  function fetchRoute(profile, callback) {
    // Step 1: PHP proxy (uses ORS if key set, else OSRM).
    // Works in production. May fail on local dev (no outbound PHP).
    console.log('[RP] 2. Trying PHP proxy…');
    var fd = new FormData();
    fd.append('action',  'rutas_proxy_route');
    fd.append('profile', profile);
    fd.append('slat',    startCoords.lat);
    fd.append('slng',    startCoords.lng);
    fd.append('elat',    endCoords.lat);
    fd.append('elng',    endCoords.lng);

    fetch(RP_AJAX, { method: 'POST', body: fd })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data.success && data.data && data.data.coordinates) {
          console.log('[RP] 3. Proxy OK — engine:', data.data.engine);
          callback(null, data.data);
        } else {
          console.warn('[RP] 3. Proxy unavailable (' + data.data + ') — falling back to OSRM direct');
          fetchOSRMDirect(profile, callback);
        }
      })
      .catch(function (err) {
        console.warn('[RP] 3. Proxy error:', err.message, '— falling back to OSRM direct');
        fetchOSRMDirect(profile, callback);
      });
  }

  /* ─── Browser-direct OSRM (fallback for local dev) ──────── */
  function fetchOSRMDirect(profile, callback) {
    var url = OSRM_BASE + '/' + profile + '/' +
              startCoords.lng + ',' + startCoords.lat + ';' +
              endCoords.lng + ',' + endCoords.lat +
              '?geometries=geojson&overview=full&steps=true';

    console.log('[RP] 4. OSRM browser-direct:', url);

    var ctrl    = new AbortController();
    var timeout = setTimeout(function () {
      ctrl.abort();
      console.warn('[RP] OSRM timed out.');
    }, 20000);

    fetch(url, { signal: ctrl.signal })
      .then(function (r) {
        clearTimeout(timeout);
        console.log('[RP] OSRM status:', r.status);
        if (!r.ok) throw new Error('HTTP ' + r.status);
        return r.json();
      })
      .then(function (data) {
        var route = data.routes && data.routes[0];
        if (!route) { callback('No route found for these points.', null); return; }

        // Extract detailed coords from step geometries (much less simplified than overview)
        var detailed = [];
        (route.legs || []).forEach(function (leg) {
          (leg.steps || []).forEach(function (step) {
            var cs = step.geometry && step.geometry.coordinates;
            if (!cs) return;
            cs.forEach(function (c, idx) {
              if (idx === 0 && detailed.length > 0) return; // skip duplicate junction point
              detailed.push([c[1], c[0]]);
            });
          });
        });

        var coords = detailed.length > 1
          ? detailed
          : route.geometry.coordinates.map(function (c) { return [c[1], c[0]]; });

        callback(null, {
          engine:      'osrm-direct',
          coordinates: coords,
          distance:    route.distance,
          duration:    route.duration
        });
      })
      .catch(function (err) {
        clearTimeout(timeout);
        if (err.name === 'AbortError') {
          callback('Route timed out. For fast routing, add an OpenRouteService API key — see the site documentation.', null);
        } else {
          callback('Could not calculate route. The routing server may be overloaded — try again in a moment.', null);
        }
      });
  }

  /* ─── Draw route polyline ────────────────────────────────── */
  function drawRoute(points) {
    var valid = points.filter(function (p) {
      return Array.isArray(p) && p.length >= 2 &&
             !isNaN(p[0]) && !isNaN(p[1]) &&
             p[0] >= -90 && p[0] <= 90 &&
             p[1] >= -180 && p[1] <= 180;
    });

    console.log('[RP] drawRoute — total:', points.length, '| valid:', valid.length);

    if (valid.length < 2) {
      console.error('[RP] Not enough valid points to draw polyline.');
      return;
    }

    // Update the pre-existing layer instead of remove/re-add.
    // This avoids the SVG renderer _bounds initialisation race.
    routeLayer.setLatLngs(valid);
    routeLayer.setStyle({ opacity: 0.9 });
  }

  /* ─── Elevation via PHP proxy → OpenTopoData ────────────── */
  function fetchElevation(points, callback) {
    var sampled = sampleArray(points, 100);
    var locStr  = sampled.map(function (p) { return p[0] + ',' + p[1]; }).join('|');

    console.log('[RP] 6a. Fetching elevation via proxy | sampled points:', sampled.length);

    var body = new FormData();
    body.append('action',    'rutas_proxy_elevation');
    body.append('locations', locStr);

    var controller = new AbortController();
    var timeout    = setTimeout(function () {
      console.warn('[RP] Elevation proxy timed out — using flat profile.');
      controller.abort();
    }, 15000);

    fetch(RP_AJAX, { method: 'POST', body: body, signal: controller.signal })
      .then(function (r) {
        clearTimeout(timeout);
        console.log('[RP] 6b. Elevation proxy HTTP status:', r.status);
        return r.json();
      })
      .then(function (resp) {
        console.log('[RP] 6c. Elevation proxy response — success:', resp.success, '| results:', resp.data && resp.data.results ? resp.data.results.length : 'none');
        if (resp.success && resp.data && resp.data.results) {
          callback(resp.data.results.map(function (r) { return r.elevation || 0; }));
        } else {
          console.warn('[RP] No elevation results in proxy response:', resp);
          callback(sampled.map(function () { return 0; }));
        }
      })
      .catch(function (err) {
        clearTimeout(timeout);
        if (err.name === 'AbortError') {
          console.warn('[RP] Elevation timed out — continuing without elevation data.');
        } else {
          console.error('[RP] Elevation proxy error:', err);
        }
        callback(sampled.map(function () { return 0; }));
      });
  }

  function sampleArray(arr, max) {
    if (arr.length <= max) return arr;
    var step = arr.length / max;
    var result = [];
    for (var i = 0; i < max; i++) {
      result.push(arr[Math.round(i * step)]);
    }
    return result;
  }

  /* ─── Stats display ──────────────────────────────────────── */
  function displayStats(route) {
    document.getElementById('rp-distance').textContent = formatDistance(route.distance);
    document.getElementById('rp-time').textContent     = formatDuration(route.duration);

    var gain = 0, loss = 0;
    for (var i = 1; i < route.elevations.length; i++) {
      var diff = route.elevations[i] - route.elevations[i - 1];
      if (diff > 0) gain += diff;
      else          loss += Math.abs(diff);
    }
    document.getElementById('rp-gain').textContent = Math.round(gain) + ' m';
    document.getElementById('rp-loss').textContent = Math.round(loss) + ' m';
  }

  function formatDistance(meters) {
    if (meters >= 1000) return (meters / 1000).toFixed(1) + ' km';
    return Math.round(meters) + ' m';
  }

  function formatDuration(seconds) {
    var h = Math.floor(seconds / 3600);
    var m = Math.floor((seconds % 3600) / 60);
    if (h > 0) return h + 'h ' + m + 'min';
    return m + ' min';
  }

  /* ─── Elevation profile SVG ──────────────────────────────── */
  function drawElevationProfile(elevations) {
    if (!elevations || elevations.length < 2) return;

    var W = 400, H = 120, PAD = 10;
    var min = Math.min.apply(null, elevations);
    var max = Math.max.apply(null, elevations);
    var range = max - min || 1;

    function x(i)   { return PAD + ((i / (elevations.length - 1)) * (W - PAD * 2)); }
    function y(val) { return PAD + ((1 - (val - min) / range) * (H - PAD * 2)); }

    var linePts  = elevations.map(function (e, i) { return x(i) + ',' + y(e); });
    var linePath = 'M' + linePts.join(' L');

    var fillPath = linePath +
      ' L' + x(elevations.length - 1) + ',' + (H - PAD) +
      ' L' + x(0) + ',' + (H - PAD) + ' Z';

    document.getElementById('rp-chart-line').setAttribute('d', linePath);
    document.getElementById('rp-chart-fill').setAttribute('d', fillPath);

    document.getElementById('rp-ele-range').textContent =
      Math.round(min) + ' m – ' + Math.round(max) + ' m';

    $elevation.hidden = false;
  }

  /* ─── GPX download ───────────────────────────────────────── */
  $gpxBtn.addEventListener('click', function () {
    if (!currentRoute) return;
    var lines = [
      '<' + '?xml version="1.0" encoding="UTF-8"?>',
      '<gpx version="1.1" creator="Venezuela Rutas — Route Planner"',
      '  xmlns="http://www.topografix.com/GPX/1/1">',
      '  <metadata>',
      '    <name>' + escXml(($startInput.value || 'Start') + ' → ' + ($endInput.value || 'End')) + '</name>',
      '    <time>' + new Date().toISOString() + '</time>',
      '  </metadata>',
      '  <trk>',
      '    <name>' + escXml(($startInput.value || 'Start') + ' → ' + ($endInput.value || 'End')) + '</name>',
      '    <trkseg>'
    ];

    currentRoute.points.forEach(function (p, i) {
      var ele = currentRoute.elevations && currentRoute.elevations[i] !== undefined
        ? '\n        <ele>' + currentRoute.elevations[i].toFixed(1) + '</ele>'
        : '';
      lines.push('      <trkpt lat="' + p[0] + '" lon="' + p[1] + '">' + ele + '\n      </trkpt>');
    });

    lines.push('    </trkseg>', '  </trk>', '</gpx>');

    var blob = new Blob([lines.join('\n')], { type: 'application/gpx+xml' });
    var a    = document.createElement('a');
    a.href     = URL.createObjectURL(blob);
    a.download = 'ruta-planificada.gpx';
    a.click();
    URL.revokeObjectURL(a.href);
  });

  function escXml(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  /* ─── Clear route ────────────────────────────────────────── */
  $clearBtn.addEventListener('click', function () {
    clearAll();
  });

  function clearRoute() {
    // Hide the polyline by clearing its points (don't remove from map).
    if (routeLayer) { routeLayer.setLatLngs([]); routeLayer.setStyle({ opacity: 0 }); }
    $stats.hidden     = true;
    $elevation.hidden = true;
    $gpxBtn.hidden    = true;
    if ($saveBtn)  $saveBtn.hidden  = true;
    if ($saveWrap) $saveWrap.hidden = true;
    $clearBtn.hidden  = true;
    currentRoute      = null;
    document.getElementById('rp-chart-line').setAttribute('d', '');
    document.getElementById('rp-chart-fill').setAttribute('d', '');
  }

  /* ─── Save as Route (AJAX) ───────────────────────────────── */
  if ($saveBtn && $saveWrap) {
    $saveBtn.addEventListener('click', function () {
      $saveWrap.hidden = false;
      if ($saveName) $saveName.focus();
    });

    if ($saveConfirm) {
      $saveConfirm.addEventListener('click', function () {
        var name = ($saveName && $saveName.value.trim()) || 'Planned Route';
        if (!currentRoute) return;

        $saveConfirm.disabled = true;
        $saveConfirm.textContent = 'Saving…';

        // Sample route geometry to max 500 points for high-fidelity storage
        var pts   = sampleArray(currentRoute.points, 500);
        var elevs = currentRoute.elevations || []; // 100 sampled elevation values

        // Calculate elevation gain from original elevation data
        var elevGain = 0;
        for (var ei = 1; ei < elevs.length; ei++) {
          var diff = elevs[ei] - elevs[ei - 1];
          if (diff > 0) elevGain += diff;
        }

        // Interpolate elevations to match the 500-point geometry
        // (elevs has 100 values covering the same proportional span)
        var ptsWithEle = pts.map(function (p, i) {
          var ele = '';
          if (elevs.length > 0) {
            var t   = pts.length > 1 ? i / (pts.length - 1) : 0;
            var pos = t * (elevs.length - 1);
            var lo  = Math.floor(pos);
            var hi  = Math.min(lo + 1, elevs.length - 1);
            var ev  = elevs[lo] * (1 - (pos - lo)) + elevs[hi] * (pos - lo);
            ele = (ev !== null && !isNaN(ev)) ? Math.round(ev * 10) / 10 : '';
          }
          return [p[0], p[1], ele];
        });
        console.log('[RP] Save — points:', ptsWithEle.length, '| sample[0]:', ptsWithEle[0], '| elevs available:', elevs.length);

        var body = new FormData();
        body.append('action',          'rutas_save_planned_route');
        body.append('nonce',           RP_NONCE);
        body.append('title',           name);
        body.append('points',          JSON.stringify(ptsWithEle));
        body.append('distance',        currentRoute.distance || 0);
        body.append('elevation_gain',  Math.round(elevGain));

        fetch(RP_AJAX, { method: 'POST', body: body })
          .then(function (r) { return r.json(); })
          .then(function (data) {
            $saveConfirm.disabled = false;
            $saveConfirm.innerHTML = '<span class="material-symbols-outlined">save</span> Save Draft';
            if ($saveMsg) {
              $saveMsg.hidden = false;
              if (data.success) {
                $saveMsg.style.color = 'var(--primary)';
                $saveMsg.innerHTML   = 'Saved! <a href="' + data.data.edit_url + '" target="_blank">Edit draft →</a>';
              } else {
                $saveMsg.style.color = 'var(--terracotta)';
                $saveMsg.textContent = data.data || 'Error saving route.';
              }
            }
          })
          .catch(function () {
            $saveConfirm.disabled = false;
            $saveConfirm.innerHTML = '<span class="material-symbols-outlined">save</span> Save Draft';
          });
      });
    }
  }

  function clearAll() {
    clearRoute();
    if (startMarker) { map.removeLayer(startMarker); startMarker = null; }
    if (endMarker)   { map.removeLayer(endMarker);   endMarker   = null; }
    startCoords = null; endCoords = null;
    $startInput.value = '';
    $endInput.value   = '';
    $calcBtn.disabled = true;
    setPlacingMode(null);
    $hint.textContent = 'Click the map or search to set your start and end points.';
  }

  /* ─── Loading / error helpers ────────────────────────────── */
  function setLoading(state) {
    $loading.hidden = !state;
    $calcBtn.disabled = state;
  }

  function showError(msg) {
    $hint.textContent = '⚠ ' + msg;
    $hint.style.color = 'var(--terracotta)';
    setTimeout(function () {
      $hint.style.color = '';
      if (startCoords && endCoords) {
        $hint.textContent = 'Ready! Click "Calculate Route" to get your route.';
      }
    }, 5000);
  }

  /* ─── Panel toggle (mobile) ──────────────────────────────── */
  $toggle.addEventListener('click', function () {
    $panel.classList.toggle('rp-panel--collapsed');
    var icon = $toggle.querySelector('.material-symbols-outlined');
    icon.textContent = $panel.classList.contains('rp-panel--collapsed') ? 'chevron_right' : 'chevron_left';
  });

  /* ─── Auto-prompt: start placing mode on load ────────────── */
  setPlacingMode('start');

})();
</script>

<?php get_template_part('parts/footer'); ?>
