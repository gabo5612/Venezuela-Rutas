<?php
/**
 * Template Name: Suggest Route
 *
 * Community form: users draw waypoints on a Leaflet map, fill in metadata,
 * and submit. Creates a `pending` routes post for admin review.
 */

$sg_logged_in = is_user_logged_in();
$sg_nonce     = wp_create_nonce('rutas_suggest_nonce');
$sg_ajaxurl   = admin_url('admin-ajax.php');

get_template_part('parts/header');
?>

<div class="rp-layout">

  <!-- ── SIDEBAR PANEL ─────────────────────────────────────── -->
  <aside class="rp-panel" id="sg-panel">

    <div class="rp-panel__header">
      <span class="material-symbols-outlined rp-panel__icon">add_location</span>
      <h1 class="rp-panel__title">Suggest a Route</h1>
    </div>

    <div class="rp-panel__body">

      <!-- Success state -->
      <div class="suggest-success" id="sg-success" hidden>
        <span class="material-symbols-outlined suggest-success__icon">check_circle</span>
        <h2 class="suggest-success__title">Route Submitted!</h2>
        <p class="suggest-success__text">Thank you! Our team will review your suggestion and publish it soon.</p>
        <button class="rp-btn-calc" id="sg-another">
          <span class="material-symbols-outlined">add_location</span>
          Submit Another Route
        </button>
      </div>

      <form id="sg-form" novalidate>

        <div class="suggest-field">
          <label class="suggest-label" for="sg-title">
            Route Name <span class="suggest-req">*</span>
          </label>
          <input class="rp-input" type="text" id="sg-title" name="title"
                 placeholder="e.g. Roraima Base Camp, Ávila Sunrise Trail…" required>
        </div>

        <div class="suggest-field">
          <label class="suggest-label" for="sg-description">Description</label>
          <textarea class="rp-input suggest-textarea" id="sg-description" name="description"
                    rows="3" placeholder="Highlight points, hazards, best season, access info…"></textarea>
        </div>

        <div class="suggest-row">
          <div class="suggest-field">
            <label class="suggest-label" for="sg-difficulty">Difficulty</label>
            <select class="rp-input" id="sg-difficulty" name="difficulty">
              <option value="">— Select —</option>
              <option value="easy">Easy</option>
              <option value="moderate">Moderate</option>
              <option value="hard">Hard</option>
              <option value="extreme">Extreme</option>
            </select>
          </div>
          <div class="suggest-field">
            <label class="suggest-label" for="sg-activity">Activity</label>
            <select class="rp-input" id="sg-activity" name="activity">
              <option value="">— Select —</option>
              <option value="hiking">Hiking</option>
              <option value="trekking">Trekking</option>
              <option value="cycling">Cycling</option>
              <option value="camping">Camping</option>
              <option value="climbing">Climbing</option>
              <option value="kayaking">Kayaking</option>
              <option value="other">Other</option>
            </select>
          </div>
        </div>

        <?php if (!$sg_logged_in): ?>
        <div class="suggest-field">
          <label class="suggest-label" for="sg-email">
            Your Email <span class="suggest-label--muted">(optional)</span>
          </label>
          <input class="rp-input" type="email" id="sg-email" name="email"
                 placeholder="you@example.com">
        </div>
        <?php endif; ?>

        <div class="suggest-field">
          <label class="suggest-label" for="sg-notes">Notes for Reviewers</label>
          <textarea class="rp-input suggest-textarea" id="sg-notes" name="notes"
                    rows="2" placeholder="Source, GPS device, last time visited…"></textarea>
        </div>

        <!-- Waypoint counter -->
        <p class="rp-hint" id="sg-wp-hint">
          <span id="sg-wp-count">No waypoints yet.</span>
          Click the map to trace your route.
        </p>

        <!-- Error -->
        <div class="suggest-error" id="sg-error" hidden></div>

        <!-- Submit -->
        <button type="submit" class="rp-btn-calc" id="sg-submit">
          <span class="material-symbols-outlined">send</span>
          Submit Route Suggestion
        </button>

        <!-- Clear waypoints -->
        <button type="button" class="rp-btn-clear" id="sg-clear" hidden>
          <span class="material-symbols-outlined">delete_sweep</span>
          Clear Waypoints
        </button>

        <?php if (!$sg_logged_in): ?>
        <p class="suggest-login-note">
          <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>">Log in</a>
          or
          <a href="<?php echo esc_url(get_permalink(get_page_by_path('registro'))); ?>">register</a>
          to track your submissions.
        </p>
        <?php endif; ?>

      </form>

    </div><!-- /.rp-panel__body -->
  </aside><!-- /.rp-panel -->

  <!-- ── MAP ───────────────────────────────────────────────── -->
  <div class="rp-map-wrap">
    <div id="sg-map"></div>

    <!-- Hint overlay -->
    <div class="rp-cursor-hint" id="sg-hint-overlay">
      <span class="material-symbols-outlined">touch_app</span>
      Click to add waypoints &nbsp;·&nbsp; Right-click to undo
    </div>

    <!-- Undo toolbar -->
    <div class="suggest-map-toolbar">
      <button class="suggest-map-btn" id="sg-undo">
        <span class="material-symbols-outlined">undo</span> Undo
      </button>
    </div>
  </div>

</div><!-- /.rp-layout -->

<script>
(function () {
  var AJAXURL = '<?php echo esc_js($sg_ajaxurl); ?>';
  var NONCE   = '<?php echo esc_js($sg_nonce); ?>';

  /* ─── Map ─────────────────────────────────────────────────── */
  var map = L.map('sg-map', { center: [7.5, -66.5], zoom: 6, zoomControl: false });
  L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
    attribution: '&copy; OpenStreetMap &copy; CARTO', subdomains: 'abcd', maxZoom: 19
  }).addTo(map);
  L.control.zoom({ position: 'bottomright' }).addTo(map);
  if (typeof L.control.locate !== 'undefined') {
    L.control.locate({ position: 'bottomright', flyTo: true }).addTo(map);
  }
  setTimeout(function () { map.invalidateSize(false); }, 150);

  /* ─── State ──────────────────────────────────────────────── */
  var waypoints = [], markers = [], routeLine = null;

  var dotIcon   = L.divIcon({ className: 'map-pin map-pin--dot',   iconSize: [10,10], iconAnchor: [5,5] });
  var startIcon = L.divIcon({ className: 'map-pin map-pin--start', iconSize: [14,14], iconAnchor: [7,7] });
  var endIcon   = L.divIcon({ className: 'map-pin map-pin--end',   iconSize: [14,14], iconAnchor: [7,7] });

  map.on('click', function (e) { addPoint([e.latlng.lat, e.latlng.lng]); });
  map.on('contextmenu', function (e) {
    L.DomEvent.preventDefault(e.originalEvent);
    removeLastPoint();
  });

  function addPoint(pt) {
    waypoints.push(pt);
    redraw();
    updateStatus();
    dismissHint();
  }

  function removeLastPoint() {
    if (!waypoints.length) return;
    waypoints.pop();
    redraw();
    updateStatus();
  }

  function clearPoints() {
    waypoints = [];
    redraw();
    updateStatus();
  }

  function redraw() {
    markers.forEach(function (m) { map.removeLayer(m); });
    markers = [];
    if (routeLine) { map.removeLayer(routeLine); routeLine = null; }
    if (!waypoints.length) return;

    // Only draw polyline with 2+ points — a single-point polyline causes
    // a Leaflet SVG renderer bounds error that silently breaks the first click.
    if (waypoints.length >= 2) {
      routeLine = L.polyline(waypoints, {
        color: '#0df246', weight: 3, opacity: 0.9,
        lineCap: 'round', lineJoin: 'round'
      }).addTo(map);
    }

    waypoints.forEach(function (pt, i) {
      var icon = i === 0 ? startIcon : (i === waypoints.length - 1 ? endIcon : dotIcon);
      markers.push(L.marker(pt, { icon: icon }).addTo(map));
    });
  }

  function updateStatus() {
    var n = waypoints.length;
    var $count = document.getElementById('sg-wp-count');
    var $clear = document.getElementById('sg-clear');
    if ($count) {
      $count.textContent = n === 0
        ? 'No waypoints yet. '
        : n + ' waypoint' + (n > 1 ? 's' : '') + ' added. ';
    }
    if ($clear) $clear.hidden = n === 0;
  }

  function dismissHint() {
    var $h = document.getElementById('sg-hint-overlay');
    if ($h && !$h.dataset.dismissed) {
      $h.dataset.dismissed = '1';
      $h.style.opacity = '0';
      setTimeout(function () { $h.hidden = true; }, 400);
    }
  }

  document.getElementById('sg-undo').addEventListener('click', removeLastPoint);
  document.getElementById('sg-clear').addEventListener('click', clearPoints);

  /* ─── Form submit ─────────────────────────────────────────── */
  var $form    = document.getElementById('sg-form');
  var $submit  = document.getElementById('sg-submit');
  var $error   = document.getElementById('sg-error');
  var $success = document.getElementById('sg-success');

  $form.addEventListener('submit', function (e) {
    e.preventDefault();
    var title = document.getElementById('sg-title').value.trim();
    if (!title)               { return showError('Route name is required.'); }
    if (waypoints.length < 2) { return showError('Please add at least 2 waypoints on the map.'); }

    $submit.disabled = true;
    $submit.innerHTML = '<span class="rp-spinner"></span> Submitting…';
    $error.hidden = true;

    var fd = new FormData($form);
    fd.append('action', 'rutas_suggest_route');
    fd.append('nonce',  NONCE);
    fd.append('points', JSON.stringify(waypoints));

    fetch(AJAXURL, { method: 'POST', body: fd })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        $submit.disabled = false;
        $submit.innerHTML = '<span class="material-symbols-outlined">send</span> Submit Route Suggestion';
        if (data.success) {
          $form.hidden    = true;
          $success.hidden = false;
        } else {
          showError(data.data || 'Submission failed. Please try again.');
        }
      })
      .catch(function () {
        $submit.disabled = false;
        $submit.innerHTML = '<span class="material-symbols-outlined">send</span> Submit Route Suggestion';
        showError('Network error. Please check your connection.');
      });
  });

  function showError(msg) {
    $error.textContent = msg;
    $error.hidden = false;
  }

  document.getElementById('sg-another').addEventListener('click', function () {
    $form.reset();
    $form.hidden    = false;
    $success.hidden = true;
    clearPoints();
  });
})();
</script>

<?php get_template_part('parts/footer'); ?>
