<?php
/**
 * Template Name: Suggest Point of Interest
 *
 * Community form to suggest a new POI. User clicks the map to place it,
 * fills in details, and submits as a draft `point-of-interest` post for review.
 */

$sg_logged_in = is_user_logged_in();
$sg_user      = $sg_logged_in ? wp_get_current_user() : null;
$sg_nonce     = wp_create_nonce('rutas_suggest_nonce');
$sg_ajaxurl   = admin_url('admin-ajax.php');

get_template_part('parts/header');
?>

<!-- ── HERO ─────────────────────────────────────────────────── -->
<section class="cat-hero">
  <div class="cat-hero__bg"></div>
  <div class="cat-hero__inner">
    <span class="cat-hero__eyebrow">
      <span class="material-symbols-outlined">location_on</span>
      Community
    </span>
    <h1 class="cat-hero__title">SUGGEST A POI</h1>
    <p class="cat-hero__desc">Is there a special place worth marking on the map? Drop a pin and tell us about it — our team will review and add it.</p>
    <div class="cat-hero__actions">
      <a href="<?php echo esc_url(get_permalink(get_page_by_path('nueva-ruta'))); ?>" class="btn btn--outline">
        <span class="material-symbols-outlined">add_location</span>
        Have a Route instead?
      </a>
    </div>
  </div>
</section>

<!-- ── MAIN FORM + MAP ───────────────────────────────────────── -->
<div class="suggest-layout">

  <!-- Form panel -->
  <div class="suggest-panel">

    <!-- Success state -->
    <div class="suggest-success" id="sp-success" hidden>
      <span class="material-symbols-outlined suggest-success__icon">check_circle</span>
      <h2 class="suggest-success__title">POI Submitted!</h2>
      <p class="suggest-success__text">Thank you! Our team will review your point of interest and add it to the map soon.</p>
      <button class="suggest-btn suggest-btn--primary" id="sp-another">Submit Another POI</button>
    </div>

    <form class="suggest-form" id="sp-form" novalidate>

      <div class="suggest-field">
        <label class="suggest-label" for="sp-title">
          POI Name <span class="suggest-req">*</span>
        </label>
        <input class="suggest-input" type="text" id="sp-title" name="title"
               placeholder="e.g. Salto Ángel, Cueva del Guácharo…" required>
      </div>

      <div class="suggest-field">
        <label class="suggest-label" for="sp-description">Description</label>
        <textarea class="suggest-input suggest-textarea" id="sp-description" name="description"
                  rows="4" placeholder="What makes this place special? How to get there, access difficulty, best time to visit…"></textarea>
      </div>

      <div class="suggest-field">
        <label class="suggest-label" for="sp-category">Category</label>
        <select class="suggest-input" id="sp-category" name="category">
          <option value="">— Select —</option>
          <option value="waterfall">Waterfall</option>
          <option value="mountain">Mountain / Peak</option>
          <option value="cave">Cave</option>
          <option value="beach">Beach / River</option>
          <option value="viewpoint">Viewpoint / Mirador</option>
          <option value="ruins">Historical / Ruins</option>
          <option value="camp">Campsite</option>
          <option value="other">Other</option>
        </select>
      </div>

      <?php if (!$sg_logged_in): ?>
      <div class="suggest-field">
        <label class="suggest-label" for="sp-email">
          Your Email <span class="suggest-label--muted">(optional, for updates)</span>
        </label>
        <input class="suggest-input" type="email" id="sp-email" name="email"
               placeholder="you@example.com">
      </div>
      <?php endif; ?>

      <div class="suggest-field">
        <label class="suggest-label" for="sp-notes">Notes for Reviewers</label>
        <textarea class="suggest-input suggest-textarea" id="sp-notes" name="notes"
                  rows="2" placeholder="Access info, GPS accuracy, last time visited…"></textarea>
      </div>

      <!-- Coordinates display -->
      <div class="suggest-coords" id="sp-coords-wrap">
        <span class="material-symbols-outlined">location_on</span>
        <span id="sp-coords-text">No location set yet — click on the map</span>
      </div>

      <!-- Error -->
      <div class="suggest-error" id="sp-error" hidden></div>

      <button type="submit" class="suggest-btn suggest-btn--primary" id="sp-submit">
        <span class="material-symbols-outlined">send</span>
        Submit POI Suggestion
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
  </div><!-- /.suggest-panel -->

  <!-- Map -->
  <div class="suggest-map-wrap">
    <div id="sp-map"></div>

    <div class="suggest-map-hint" id="sp-hint-overlay">
      <span class="material-symbols-outlined">touch_app</span>
      Click anywhere on the map to place the POI
    </div>

    <div class="suggest-map-toolbar">
      <button class="suggest-map-btn suggest-map-btn--danger" id="sp-clear">
        <span class="material-symbols-outlined">delete</span> Remove Pin
      </button>
    </div>
  </div>

</div><!-- /.suggest-layout -->

<script>
(function () {
  var AJAXURL = '<?php echo esc_js($sg_ajaxurl); ?>';
  var NONCE   = '<?php echo esc_js($sg_nonce); ?>';

  /* ─── Map ─────────────────────────────────────────────────── */
  var map = L.map('sp-map', { center: [7.5, -66.5], zoom: 6, zoomControl: false });
  L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
    attribution: '&copy; OpenStreetMap &copy; CARTO', subdomains: 'abcd', maxZoom: 19
  }).addTo(map);
  L.control.zoom({ position: 'bottomright' }).addTo(map);
  if (typeof L.control.locate !== 'undefined') {
    L.control.locate({ position: 'bottomright', flyTo: true }).addTo(map);
  }

  setTimeout(function () { map.invalidateSize(false); }, 150);

  /* ─── State ──────────────────────────────────────────────── */
  var poiMarker = null;
  var poiLat    = null;
  var poiLng    = null;

  var poiIcon = L.divIcon({
    className: '',
    html: '<svg xmlns="http://www.w3.org/2000/svg" width="26" height="34" viewBox="0 0 26 34">'
        + '<path d="M13 0C5.82 0 0 5.82 0 13c0 9.75 13 21 13 21S26 22.75 26 13C26 5.82 20.18 0 13 0z" fill="#ff6b00"/>'
        + '<circle cx="13" cy="13" r="5" fill="#0a0a0a"/>'
        + '</svg>',
    iconSize: [26, 34], iconAnchor: [13, 34], popupAnchor: [0, -36]
  });

  map.on('click', function (e) {
    placeMarker(e.latlng.lat, e.latlng.lng);
    dismissHint();
  });

  function placeMarker(lat, lng) {
    if (poiMarker) map.removeLayer(poiMarker);
    poiLat    = lat;
    poiLng    = lng;
    poiMarker = L.marker([lat, lng], { icon: poiIcon, draggable: true }).addTo(map);
    poiMarker.on('dragend', function (e) {
      poiLat = e.target.getLatLng().lat;
      poiLng = e.target.getLatLng().lng;
      updateCoordsDisplay();
    });
    updateCoordsDisplay();
  }

  function clearMarker() {
    if (poiMarker) { map.removeLayer(poiMarker); poiMarker = null; }
    poiLat = null; poiLng = null;
    updateCoordsDisplay();
  }

  function updateCoordsDisplay() {
    var $el = document.getElementById('sp-coords-text');
    if (!$el) return;
    if (poiLat !== null && poiLng !== null) {
      $el.textContent = poiLat.toFixed(6) + ', ' + poiLng.toFixed(6);
      $el.style.color = 'var(--primary)';
      document.getElementById('sp-coords-wrap').classList.add('has-coords');
    } else {
      $el.textContent = 'No location set yet — click on the map';
      $el.style.color = '';
      document.getElementById('sp-coords-wrap').classList.remove('has-coords');
    }
  }

  function dismissHint() {
    var $h = document.getElementById('sp-hint-overlay');
    if ($h && !$h.dataset.dismissed) {
      $h.dataset.dismissed = '1';
      $h.style.opacity = '0';
      setTimeout(function () { $h.hidden = true; }, 400);
    }
  }

  document.getElementById('sp-clear').addEventListener('click', clearMarker);

  /* ─── Form submit ─────────────────────────────────────────── */
  var $form    = document.getElementById('sp-form');
  var $submit  = document.getElementById('sp-submit');
  var $error   = document.getElementById('sp-error');
  var $success = document.getElementById('sp-success');

  $form.addEventListener('submit', function (e) {
    e.preventDefault();
    var title = document.getElementById('sp-title').value.trim();
    if (!title)               { return showError('POI name is required.'); }
    if (!poiLat || !poiLng)   { return showError('Please click on the map to place the POI location.'); }

    $submit.disabled = true;
    $submit.innerHTML = '<span class="material-symbols-outlined">hourglass_empty</span> Submitting…';
    $error.hidden = true;

    var fd = new FormData($form);
    fd.append('action', 'rutas_suggest_poi');
    fd.append('nonce',  NONCE);
    fd.append('lat',    poiLat);
    fd.append('lng',    poiLng);

    fetch(AJAXURL, { method: 'POST', body: fd })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        $submit.disabled = false;
        $submit.innerHTML = '<span class="material-symbols-outlined">send</span> Submit POI Suggestion';
        if (data.success) {
          $form.hidden    = true;
          $success.hidden = false;
          window.scrollTo({ top: 0, behavior: 'smooth' });
        } else {
          showError(data.data || 'Submission failed. Please try again.');
        }
      })
      .catch(function () {
        $submit.disabled = false;
        $submit.innerHTML = '<span class="material-symbols-outlined">send</span> Submit POI Suggestion';
        showError('Network error. Please check your connection.');
      });
  });

  function showError(msg) {
    $error.textContent = msg;
    $error.hidden = false;
  }

  document.getElementById('sp-another').addEventListener('click', function () {
    $form.reset();
    $form.hidden    = false;
    $success.hidden = true;
    clearMarker();
  });
})();
</script>

<?php get_template_part('parts/footer'); ?>
