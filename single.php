<?php get_template_part('parts/header'); ?>

<?php if (have_posts()) : while (have_posts()) : the_post();

$post_type = get_post_type();

// ══════════════════════════════════════════════════════════════
//  UNIFIED FIELD READING
//  Reads every possible field; templates display based on data,
//  not on post type — so any CPT can use any field freely.
// ══════════════════════════════════════════════════════════════

// ── Hero image & featured video ─────────────────────────────
$thumb          = get_the_post_thumbnail_url(null, 'full') ?: get_field('image') ?: '';
$video_featured = get_field('video_featured') ?: '';

// ── Route stats ──────────────────────────────────────────────
$distance   = get_field('distance')   ?: '';
$elevation  = get_field('elevation')  ?: '';
$difficulty = get_field('difficulty') ?: '';
$est_time   = get_field('time')       ?: '';

// ── Location / coordinates ───────────────────────────────────
$lat      = get_field('latitude')  ?: '';
$lon      = get_field('longitude') ?: '';
$location = get_field('location')  ?: '';

// ── GPS polyline points (Routes group) ──────────────────────
$points    = get_field('points') ?: [];
$route_pts = [];
$ele_data  = [];   // [ [lat,lng], ele ] paralelo a route_pts
foreach ($points as $p) {
    $plat = floatval($p['latitude']  ?? 0);
    $plng = floatval($p['longitude'] ?? 0);
    if ($plat && $plng) {
        $route_pts[] = [$plat, $plng];
        $ele = isset($p['elevation']) && $p['elevation'] !== '' ? floatval($p['elevation']) : null;
        $ele_data[]  = $ele;
    }
}
$has_elevation = count(array_filter($ele_data, fn($e) => $e !== null)) > 1;

// ── Checkpoints (Post group) ─────────────────────────────────
$checkpoints = get_field('checkpoints') ?: [];

// ── POIs linked to a route (Routes group) ───────────────────
$poi_posts = get_field('route_point_of_interest') ?: [];
if (!is_array($poi_posts)) $poi_posts = $poi_posts ? [$poi_posts] : [];

// ── Normalize both checkpoint sources into flat list of WP_Post ─
$all_cp_pois = [];
foreach ($checkpoints as $cp) {
    $p = $cp['poi'] ?? null;
    if ($p) $all_cp_pois[] = $p;
}
foreach ($poi_posts as $p) {
    if (is_object($p)) $all_cp_pois[] = $p;
}

// ── Gallery (Post group) ─────────────────────────────────────
$gallery = get_field('gallery') ?: [];

// ── Conditions alert (Post group) ───────────────────────────
$conditions = get_field('conditions_alert') ?: '';

// ── Embed video ──────────────────────────────────────────────
$embed_video = get_field('embed') ?: '';

// ── Blog / bitacora link ─────────────────────────────────────
$blog_raw = get_field('blog_entry') ?: null;
$blog_url = $blog_raw ? get_permalink($blog_raw) : '';

// ── Google Maps link ─────────────────────────────────────────
$gmaps_url = get_field('has_a_google_maps_card') ?: '';

// ── Gear / WooCommerce products ──────────────────────────────
$gear_products = get_field('products_gear') ?: [];
$has_gear      = ! empty($gear_products) && class_exists('WooCommerce');

// ── Guide profile fields ──────────────────────────────────────
$guide_whatsapp  = get_field('guide_whatsapp')   ?: '';
$guide_email     = get_field('guide_email')      ?: '';
$guide_instagram = get_field('guide_instagram')  ?: '';
$guide_price     = get_field('guide_price_from') ?: '';
$guide_featured  = get_field('guide_is_featured') ?: false;
$guide_specialty = get_field('guide_specialty')  ?: [];
$has_guide_contact = $guide_whatsapp || $guide_email || $guide_instagram;

// ── Sponsor (routes) ─────────────────────────────────────────
$sponsor_name  = get_field('sponsor_name')  ?: '';
$sponsor_logo  = get_field('sponsor_logo')  ?: '';
$sponsor_url   = get_field('sponsor_url')   ?: '';
$sponsor_label = get_field('sponsor_label') ?: 'Equipped by';
$has_sponsor   = $sponsor_name || $sponsor_logo;

// ── Taxonomy ─────────────────────────────────────────────────
$cats     = get_the_category();
$cat_name = $cats ? $cats[0]->name : '';
$tags = wp_get_post_terms(get_the_ID(), 'post_tag');
if (is_wp_error($tags)) $tags = [];

// ── Derived flags ────────────────────────────────────────────
$has_polyline    = !empty($route_pts);
$has_pin         = !$has_polyline && $lat && $lon;
$has_map         = $has_polyline || $has_pin;
$has_gallery     = !empty($gallery);
$has_checkpoints = !empty($all_cp_pois);
$has_stats       = $distance || $elevation || $difficulty || $est_time;

// ── Weather coordinates (first route point, or single lat/lon) ──
$last_pt     = $has_polyline ? $route_pts[count($route_pts) - 1] : null;
$weather_lat = $last_pt ? $last_pt[0] : ($has_pin ? floatval($lat) : '');
$weather_lon = $last_pt ? $last_pt[1] : ($has_pin ? floatval($lon) : '');
$has_weather = $weather_lat !== '' && $weather_lon !== '';

// ── Mobile Google Maps button URL ─────────────────────────
$mobile_maps_url = '';
if ($gmaps_url) {
    $mobile_maps_url = $gmaps_url;
} elseif ($has_polyline) {
    $pt_start = $route_pts[0];
    $pt_end   = $route_pts[count($route_pts) - 1];
    $wpts = [];
    foreach ($poi_posts as $poi_item) {
        if (!is_object($poi_item)) continue;
        $p_lat = get_field('latitude',  $poi_item->ID);
        $p_lng = get_field('longitude', $poi_item->ID);
        if ($p_lat && $p_lng) $wpts[] = $p_lat . ',' . $p_lng;
    }
    $wpts_str = implode('|', $wpts);
    $mobile_maps_url = 'https://www.google.com/maps/dir/?api=1'
        . '&origin='      . $pt_start[0] . ',' . $pt_start[1]
        . '&destination=' . $pt_end[0]   . ',' . $pt_end[1]
        . ($wpts_str ? '&waypoints=' . rawurlencode($wpts_str) : '');
} elseif ($has_pin) {
    $mobile_maps_url = 'https://www.google.com/maps/search/?api=1&query=' . $lat . ',' . $lon;
}

// ── Related posts label ──────────────────────────────────────
$related_label = match ($post_type) {
    'routes'            => 'GPS Routes',
    'point-of-interest' => 'Points of Interest',
    'guide'             => 'Other Guides',
    default             => 'Similar Posts',
};
?>

<div class="page-route page-route--<?php echo esc_attr($post_type); ?>">

  <!-- ══ HERO ══════════════════════════════════════════════════ -->
  <?php if ($video_featured || $thumb) : ?>
  <div class="page-route__hero">
    <?php if ($video_featured) : ?>
      <video autoplay muted loop playsinline src="<?php echo esc_url($video_featured); ?>"></video>
    <?php else : ?>
      <img src="<?php echo esc_url($thumb); ?>" alt="<?php the_title_attribute(); ?>">
    <?php endif; ?>
    <div class="hero-overlay"></div>
  </div>
  <?php endif; ?>

  <!-- ══ TABS ══════════════════════════════════════════════════ -->
  <?php if ($has_map || $has_gallery || $has_gear) : ?>
  <div class="page-route__tabs">
    <div class="page-route__tabs-inner">
      <button class="tab-btn tab-btn--active" data-tab="info">
        <span class="material-symbols-outlined">info</span> Info
      </button>
      <?php if ($has_map) : ?>
      <button class="tab-btn" data-tab="map">
        <span class="material-symbols-outlined">map</span> Map
      </button>
      <?php endif; ?>
      <?php if ($has_gallery) : ?>
      <button class="tab-btn" data-tab="gallery">
        <span class="material-symbols-outlined">photo_library</span> Gallery
      </button>
      <?php endif; ?>
      <?php if ($has_gear) : ?>
      <button class="tab-btn" data-tab="gear">
        <span class="material-symbols-outlined">backpack</span> Gear
      </button>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- ══ STATS BAR ═════════════════════════════════════════════ -->
  <?php if ($has_stats) : ?>
  <div class="page-route__stats" data-animate="fade-up">
    <?php if ($distance) : ?>
    <div class="page-route__stat">
      <span class="stat-icon material-symbols-outlined">distance</span>
      <span class="stat-value"><?php echo esc_html($distance); ?></span>
      <span class="stat-label">Distance</span>
    </div>
    <?php endif; ?>
    <?php if ($elevation) : ?>
    <div class="page-route__stat">
      <span class="stat-icon material-symbols-outlined">elevation</span>
      <span class="stat-value"><?php echo esc_html($elevation); ?></span>
      <span class="stat-label">Elevation</span>
    </div>
    <?php endif; ?>
    <?php if ($difficulty) : ?>
    <div class="page-route__stat">
      <span class="stat-icon material-symbols-outlined">fitness_center</span>
      <span class="stat-value"><?php echo esc_html($difficulty); ?></span>
      <span class="stat-label">Difficulty</span>
    </div>
    <?php endif; ?>
    <?php if ($est_time) : ?>
    <div class="page-route__stat">
      <span class="stat-icon material-symbols-outlined">schedule</span>
      <span class="stat-value"><?php echo esc_html($est_time); ?></span>
      <span class="stat-label">Est. Time</span>
    </div>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <!-- ══ WEATHER ════════════════════════════════════════════════ -->
  <?php if ($has_weather) : ?>
  <div class="page-route__weather" data-animate="fade-up"
       data-lat="<?php echo esc_attr($weather_lat); ?>"
       data-lon="<?php echo esc_attr($weather_lon); ?>">
    <div class="weather-state weather-state--loading">
      <span class="material-symbols-outlined weather-spin">progress_activity</span>
      <span>Fetching weather…</span>
    </div>
    <div class="weather-state weather-state--data">
      <div class="weather-icon-wrap">
        <span class="material-symbols-outlined weather-icon">wb_sunny</span>
      </div>
      <div class="weather-primary">
        <span class="weather-temp"></span>
        <span class="weather-desc"></span>
      </div>
      <div class="weather-secondary">
        <span class="weather-wind">
          <span class="material-symbols-outlined">air</span>
          <span class="weather-wind-val"></span>
        </span>
        <span class="weather-hum">
          <span class="material-symbols-outlined">humidity_percentage</span>
          <span class="weather-hum-val"></span>
        </span>
      </div>
      <div class="weather-badge">Current weather at <?php the_title(); ?></div>
    </div>
    <div class="weather-state weather-state--error">
      <span class="material-symbols-outlined">cloud_off</span>
      <span>Weather unavailable</span>
    </div>
  </div>
  <?php endif; ?>

    <!-- ══ ELEVATION CHART ════════════════════════════════════════ -->
  <?php if ($has_elevation) :
    // Calcular distancia acumulada (Haversine simplificado)
    $dist_km  = [0.0];
    $eles     = [];
    $R        = 6371.0;
    foreach ($route_pts as $i => $pt) {
        $e = $ele_data[$i];
        if ($e === null) continue;
        $eles[] = $e;
        if ($i > 0) {
            $prev  = $route_pts[$i - 1];
            $dLat  = deg2rad($pt[0] - $prev[0]);
            $dLng  = deg2rad($pt[1] - $prev[1]);
            $a     = sin($dLat/2)**2 + cos(deg2rad($prev[0])) * cos(deg2rad($pt[0])) * sin($dLng/2)**2;
            $dist_km[] = end($dist_km) + $R * 2 * atan2(sqrt($a), sqrt(1-$a));
        }
    }
    // Elevation gain for stats bar
    $ele_gain = 0;
    for ($i = 1; $i < count($eles); $i++) {
        $diff = $eles[$i] - $eles[$i - 1];
        if ($diff > 0) $ele_gain += $diff;
    }
  ?>
  <div class="elevation-chart-wrap" id="elevation-chart-wrap">
    <div class="elevation-chart-header">
      <span class="material-symbols-outlined">elevation</span>
      Elevation Profile
      <span class="elevation-chart-gain">+<?php echo round($ele_gain); ?> m gain</span>
    </div>
    <div id="elevation-chart"></div>
  </div>

  <script>
  (function () {
    var xArr = <?php echo json_encode(array_values($dist_km)); ?>;
    var yArr = <?php echo json_encode($eles); ?>;
    var wrap = document.getElementById('elevation-chart');
    if (!wrap || xArr.length < 2) return;

    var W = wrap.offsetWidth || 700, H = 160, PAD = 44;
    var canvas = document.createElement('canvas');
    canvas.width = W; canvas.height = H;
    canvas.style.width = '100%';
    wrap.appendChild(canvas);
    var ctx = canvas.getContext('2d');

    var minX = xArr[0], maxX = xArr[xArr.length - 1];
    var minY = Math.min.apply(null, yArr), maxY = Math.max.apply(null, yArr);
    var rangeY = maxY - minY || 1;

    function pt(x, y) {
      return {
        x: PAD + (x - minX) / (maxX - minX) * (W - PAD * 2),
        y: H - PAD - (y - minY) / rangeY * (H - PAD * 1.4)
      };
    }

    function drawGrid() {
      [0, 0.25, 0.5, 0.75, 1].forEach(function (t) {
        var val = minY + t * rangeY;
        var p   = pt(minX, val);
        ctx.strokeStyle = 'rgba(255,255,255,.08)'; ctx.lineWidth = 1;
        ctx.beginPath(); ctx.moveTo(PAD, p.y); ctx.lineTo(W - PAD, p.y); ctx.stroke();
        ctx.fillStyle = 'rgba(255,255,255,.4)'; ctx.font = '10px sans-serif'; ctx.textAlign = 'right';
        ctx.fillText(Math.round(val) + 'm', PAD - 4, p.y + 3);
      });
      ctx.fillStyle = 'rgba(255,255,255,.4)'; ctx.font = '10px sans-serif'; ctx.textAlign = 'center';
      [0, 0.25, 0.5, 0.75, 1].forEach(function (t) {
        var x = minX + t * (maxX - minX);
        var p = pt(x, minY);
        ctx.fillText(x.toFixed(1) + 'km', p.x, H - 6);
      });
    }

    var pts = xArr.map(function(x, i) { return pt(x, yArr[i]); });

    function smoothPath(points) {
      ctx.moveTo(points[0].x, points[0].y);
      for (var i = 0; i < points.length - 1; i++) {
        var mx = (points[i].x + points[i+1].x) / 2;
        var my = (points[i].y + points[i+1].y) / 2;
        ctx.quadraticCurveTo(points[i].x, points[i].y, mx, my);
      }
      ctx.lineTo(points[points.length-1].x, points[points.length-1].y);
    }

    // Draw up to `fraction` (0→1) of the path
    function smoothPathFraction(fraction) {
      var count = Math.max(2, Math.round(pts.length * fraction));
      var sl    = pts.slice(0, count);
      ctx.moveTo(sl[0].x, sl[0].y);
      for (var i = 0; i < sl.length - 1; i++) {
        var mx = (sl[i].x + sl[i+1].x) / 2;
        var my = (sl[i].y + sl[i+1].y) / 2;
        ctx.quadraticCurveTo(sl[i].x, sl[i].y, mx, my);
      }
      ctx.lineTo(sl[sl.length-1].x, sl[sl.length-1].y);
      return sl[sl.length-1];
    }

    function drawFrame(fraction) {
      ctx.clearRect(0, 0, W, H);
      drawGrid();

      var p0   = pts[0];
      var count = Math.max(2, Math.round(pts.length * fraction));
      var pL   = pts[count - 1];

      // Fill — same as original: closePath + flat rgba fill
      ctx.beginPath();
      ctx.moveTo(p0.x, H - PAD);
      ctx.lineTo(p0.x, p0.y);
      smoothPathFraction(fraction);
      ctx.lineTo(pL.x, H - PAD);
      ctx.lineTo(p0.x, H - PAD);
      ctx.closePath();
      ctx.fillStyle = 'rgba(13,242,70,0.12)';
      ctx.fill();

      // Line
      ctx.beginPath();
      smoothPathFraction(fraction);
      ctx.strokeStyle = '#0df246'; ctx.lineWidth = 2; ctx.stroke();

      // Tip dot
      ctx.beginPath();
      ctx.arc(pL.x, pL.y, 4, 0, Math.PI * 2);
      ctx.fillStyle = '#0df246'; ctx.fill();
    }

    // Animate left→right on viewport entry
    var animated  = false;
    var outerWrap = document.getElementById('elevation-chart-wrap');

    function animate() {
      if (animated) return;
      animated = true;
      var start    = null;
      var DURATION = 1600;
      function step(ts) {
        if (!start) start = ts;
        var progress = Math.min((ts - start) / DURATION, 1);
        var ease     = 1 - Math.pow(1 - progress, 3); // ease out cubic
        drawFrame(ease);
        if (progress < 1) requestAnimationFrame(step);
      }
      requestAnimationFrame(step);
    }

    drawGrid();

    if ('IntersectionObserver' in window) {
      var io = new IntersectionObserver(function(entries) {
        if (entries[0].isIntersecting) { animate(); io.disconnect(); }
      }, { threshold: 0.3 });
      io.observe(outerWrap);
    } else {
      animate();
    }
  }());
  </script>
  <?php endif; ?>

  <!-- ══ ALERT ══════════════════════════════════════════════════ -->
  <?php if ($conditions) : ?>
  <div class="page-route__alert" data-animate="fade-up">
    <span class="material-symbols-outlined">warning</span>
    <?php echo esc_html($conditions); ?>
  </div>
  <?php endif; ?>

  <!-- ══ BODY ══════════════════════════════════════════════════ -->
  <div id="section-info" class="page-route__body">
    <h1 style="font-size:clamp(1.75rem,4vw,3rem);font-weight:900;text-transform:uppercase;color:var(--text);margin-bottom:1.5rem;line-height:1">
      <?php the_title(); ?>
    </h1>

    <?php if ($cat_name || $location || !empty($tags) || $has_pin) : ?>
    <div style="display:flex;gap:.75rem;align-items:center;flex-wrap:wrap;margin-bottom:2rem">
      <?php if ($cat_name) : ?>
        <span class="badge badge--terracotta"><?php echo esc_html($cat_name); ?></span>
      <?php endif; ?>
      <?php foreach ($tags as $tag) : ?>
        <a href="<?php echo esc_url(get_term_link($tag)); ?>" class="badge badge--terracotta"><?php echo esc_html($tag->name); ?></a>
      <?php endforeach; ?>
      <?php if ($location) : ?>
        <span style="font-size:.75rem;color:var(--text-muted);display:flex;align-items:center;gap:.3rem">
          <span class="material-symbols-outlined" style="font-size:1rem">location_on</span>
          <?php echo esc_html($location); ?>
        </span>
      <?php endif; ?>
      <?php if ($has_pin) : ?>
        <span style="font-size:.75rem;color:var(--text-muted);display:flex;align-items:center;gap:.3rem">
          <span class="material-symbols-outlined" style="font-size:1rem">my_location</span>
          <?php echo esc_html($lat); ?>, <?php echo esc_html($lon); ?>
        </span>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php the_content(); ?>

    <?php if ( $embed_video ) : ?>
    <div class="video-embed">
      <?php echo $embed_video; ?>
    </div>
    <?php endif; ?>

    <!-- ── Action buttons ── -->
    <?php if ($gmaps_url || $blog_url || $has_polyline) : ?>
    <div style="display:flex;gap:1rem;flex-wrap:wrap;margin-top:1.5rem">
      <?php if ($gmaps_url) : ?>
      <a href="<?php echo esc_url($gmaps_url); ?>" class="btn btn--primary" target="_blank" rel="noopener">
        <span class="material-symbols-outlined">map</span> View on Google Maps
      </a>
      <?php endif; ?>
      <?php if ($blog_url) : ?>
      <a href="<?php echo esc_url($blog_url); ?>" class="btn btn--outline">
        <span class="material-symbols-outlined">article</span> View Log
      </a>
      <?php endif; ?>
      <?php if ($has_polyline) : ?>
      <a href="<?php echo esc_url(add_query_arg('gpx', '1', get_permalink())); ?>" class="btn btn--outline">
        <span class="material-symbols-outlined">download</span> Download GPX
      </a>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- ══ MAP + CHECKPOINTS ════════════════════════════════════ -->
  <?php if ($has_map) : ?>
  <div id="section-map" class="page-route__map-section <?php echo !$has_checkpoints ? 'page-route__map-section--full' : ''; ?>" data-animate="fade-up">
    <div class="page-route__map-wrap" <?php echo !$has_checkpoints ? 'style="max-width:100%"' : ''; ?>>
      <div id="route-map"></div>
      <?php if ($mobile_maps_url) : ?>
      <a href="<?php echo esc_url($mobile_maps_url); ?>"
         class="page-route__map-gmaps-btn"
         target="_blank" rel="noopener">
        <span class="material-symbols-outlined">map</span>
        View on Google Maps
      </a>
      <?php endif; ?>
    </div>

    <?php if ($has_checkpoints) : ?>
    <div class="page-route__checkpoints">
      <div class="cp-header">
        <span class="material-symbols-outlined">route</span>
        Checkpoints (<?php echo count($all_cp_pois); ?>)

      </div>
      <div class="cp-list">
        <?php foreach ($all_cp_pois as $poi) :
          $poi_image_url = get_field('image', $poi->ID) ?: get_the_post_thumbnail_url($poi->ID, 'medium') ?: '';
          $poi_maps_url  = get_field('has_a_google_maps_card', $poi->ID) ?: '';
        ?>
        <div class="cp-item">
          <div class="cp-dot"></div>
          <div class="cp-content">
            <?php if ($poi_image_url) : ?>
            <div class="cp-img">
              <img src="<?php echo esc_url($poi_image_url); ?>" alt="<?php echo esc_attr($poi->post_title); ?>">
            </div>
            <?php endif; ?>
            <a href="<?php echo esc_url(get_permalink($poi->ID)); ?>" class="cp-name">
              <?php echo esc_html($poi->post_title); ?>
            </a>
            <?php if ($poi_maps_url) : ?>
            <a href="<?php echo esc_url($poi_maps_url); ?>" class="cp-maps-link" target="_blank" rel="noopener">
              <span class="material-symbols-outlined">map</span> View on Maps
            </a>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
  <?php endif; ?>



  <!-- ══ GALLERY ════════════════════════════════════════════════ -->
  <?php if ($has_gallery) : ?>
  <div id="section-gallery" class="page-route__gallery">
    <div class="section-header">
      <div>
        <div class="section-header__eyebrow">Visual Documentation</div>
        <h2 class="section-header__title" style="color:var(--sand)">Gallery</h2>
      </div>
    </div>
    <div class="masonry-grid js-masonry" data-gallery-id="gallery-<?php echo get_the_ID(); ?>">
      <?php foreach ($gallery as $i => $item) :
        $src    = $item['url'] ?? '';
        $alt    = $item['alt'] ?? '';
        $mime   = $item['mime_type'] ?? '';
        $ext    = strtolower(pathinfo($src, PATHINFO_EXTENSION));
        $is_vid = strpos($mime, 'video/') === 0 || in_array($ext, ['mp4','webm','ogg','mov'], true);
        $href   = $is_vid ? $src : ($item['sizes']['large'] ?? $src);
      ?>
      <?php if ( $is_vid ) : ?>
      <div class="masonry-grid__item masonry-grid__item--video js-gal-item"
           data-type="video"
           data-src="<?php echo esc_url($src); ?>">
        <video src="<?php echo esc_url($src); ?>"
               muted loop playsinline preload="metadata"
               class="masonry-grid__video"></video>
        <div class="masonry-grid__overlay masonry-grid__overlay--play">
          <span class="material-symbols-outlined">play_circle</span>
        </div>
      </div>
      <?php else : ?>
      <a class="masonry-grid__item js-gal-item"
         data-type="image"
         data-src="<?php echo esc_url($href); ?>"
         href="<?php echo esc_url($href); ?>">
        <img src="<?php echo esc_url($src); ?>"
             alt="<?php echo esc_attr($alt); ?>"
             loading="lazy">
        <div class="masonry-grid__overlay">
          <span class="material-symbols-outlined">zoom_in</span>
        </div>
      </a>
      <?php endif; ?>
      <?php endforeach; ?>
    </div>
  </div>
  <script>
  (function () {
    document.querySelectorAll('.masonry-grid__item--video').forEach(function (item) {
      var vid = item.querySelector('video');
      if (!vid) return;
      item.addEventListener('mouseenter', function () { vid.play(); });
      item.addEventListener('mouseleave', function () { vid.pause(); vid.currentTime = 0; });
    });
    var grid = document.querySelector('#section-gallery .js-masonry');
    if (grid) {
      var items = Array.from(grid.querySelectorAll('.js-gal-item'));
      items.forEach(function (el, idx) {
        el.addEventListener('click', function (e) {
          e.preventDefault();
          if (typeof openGalleryModal === 'function') openGalleryModal(items, idx);
        });
      });
    }
  }());
  </script>
  <?php endif; ?>

  <!-- ══ GUIDE CONTACT ═══════════════════════════════════════════ -->
  <?php if ($has_guide_contact || !empty($guide_specialty) || $guide_price) : ?>
  <div class="guide-contact" data-animate="fade-up">
    <?php if (!empty($guide_specialty)) : ?>
    <div class="guide-contact__specs">
      <?php foreach ($guide_specialty as $s) : ?>
        <span class="badge badge--outline"><?php echo esc_html($s); ?></span>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="guide-contact__actions">
      <?php if ($guide_price) : ?>
      <div class="guide-contact__price">
        <span class="material-symbols-outlined">payments</span>
        From <strong>$<?php echo esc_html($guide_price); ?></strong>/day
      </div>
      <?php endif; ?>
      <?php if ($guide_whatsapp) : ?>
      <a href="https://wa.me/<?php echo esc_attr(preg_replace('/\D/', '', $guide_whatsapp)); ?>"
         class="btn btn--whatsapp" target="_blank" rel="noopener">
        <span class="material-symbols-outlined">chat</span> WhatsApp
      </a>
      <?php endif; ?>
      <?php if ($guide_instagram) : ?>
      <a href="https://instagram.com/<?php echo esc_attr(ltrim($guide_instagram, '@')); ?>"
         class="btn btn--outline" target="_blank" rel="noopener">
        <span class="material-symbols-outlined">photo_camera</span>
        @<?php echo esc_html(ltrim($guide_instagram, '@')); ?>
      </a>
      <?php endif; ?>
      <?php if ($guide_email) : ?>
      <a href="mailto:<?php echo esc_attr($guide_email); ?>" class="btn btn--outline">
        <span class="material-symbols-outlined">mail</span> Email
      </a>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- ══ SPONSOR ══════════════════════════════════════════════════ -->
  <?php if ($has_sponsor) : ?>
  <div class="sponsor-band" data-animate="fade-up">
    <span class="sponsor-band__label"><?php echo esc_html($sponsor_label); ?></span>
    <?php if ($sponsor_url) : ?>
    <a href="<?php echo esc_url($sponsor_url); ?>" target="_blank" rel="noopener sponsored" class="sponsor-band__link">
    <?php endif; ?>
      <?php if ($sponsor_logo) : ?>
      <img src="<?php echo esc_url($sponsor_logo); ?>" alt="<?php echo esc_attr($sponsor_name); ?>" class="sponsor-band__logo">
      <?php else : ?>
      <span class="sponsor-band__name"><?php echo esc_html($sponsor_name); ?></span>
      <?php endif; ?>
    <?php if ($sponsor_url) : ?>
    </a>
    <?php endif; ?>
  </div>
  <?php endif; ?>

<?php do_action('hotfootadventure_expedition_panel', get_the_ID(), get_post_type()); ?>

  <!-- ══ GEAR / RECOMMENDED PRODUCTS ══════════════════════════ -->
  <?php
  $gear_products = get_field('products_gear');
  if ( ! empty($gear_products) && class_exists('WooCommerce') ) :
  ?>
  <div class="page-route__gear" id="section-gear" data-section="gear">
    <div class="section-header" data-animate="fade-up">
      <div>
        <div class="section-header__eyebrow">Gear up</div>
        <h2 class="section-header__title" style="color:var(--sand)">Recommended Equipment</h2>
      </div>
      <?php if ( count($gear_products) > 4 ) : ?>
      <div class="gear-slider-nav">
        <button class="gear-slider-btn js-gear-prev" aria-label="Previous">
          <span class="material-symbols-outlined">chevron_left</span>
        </button>
        <button class="gear-slider-btn js-gear-next" aria-label="Next">
          <span class="material-symbols-outlined">chevron_right</span>
        </button>
      </div>
      <?php endif; ?>
    </div>

    <div class="gear-slider-viewport">
      <div class="gear-grid js-gear-track">
        <?php foreach ( $gear_products as $product ) :
          $wc_product  = wc_get_product( $product->ID );
          if ( ! $wc_product ) continue;
          $thumb       = get_the_post_thumbnail_url( $product->ID, 'medium' );
          $price       = $wc_product->get_price_html();
          $product_url = get_permalink( $product->ID );
          $on_sale     = $wc_product->is_on_sale();
        ?>
        <div class="gear-card">
          <?php if ($on_sale) : ?>
          <span class="gear-card__sale">Sale</span>
          <?php endif; ?>
          <a href="<?php echo esc_url($product_url); ?>" class="gear-card__img-wrap">
            <?php if ($thumb) : ?>
            <img src="<?php echo esc_url($thumb); ?>" alt="<?php echo esc_attr($product->post_title); ?>" loading="lazy">
            <?php else : ?>
            <div class="gear-card__img-placeholder"><span class="material-symbols-outlined">backpack</span></div>
            <?php endif; ?>
          </a>
          <div class="gear-card__body">
            <h4 class="gear-card__title">
              <a href="<?php echo esc_url($product_url); ?>"><?php echo esc_html($product->post_title); ?></a>
            </h4>
            <div class="gear-card__price"><?php echo $price; ?></div>
            <a href="<?php echo esc_url($product_url); ?>" class="gear-card__btn">
              <span class="material-symbols-outlined">shopping_bag</span>
              View product
            </a>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <?php if ( count($gear_products) > 4 ) : ?>
  <script>
  (function () {
    var track    = document.querySelector('.js-gear-track');
    var viewport = track && track.closest('.gear-slider-viewport');
    if (!track) return;

    // ── Clone cards for infinite loop ──────────────────────────
    var origCards = Array.from(track.children);
    var total     = origCards.length;
    // Prepend clones of last N + append clones of first N
    var CLONE_N = Math.min(total, 4);
    origCards.slice(-CLONE_N).forEach(function (c) {
      track.insertBefore(c.cloneNode(true), track.firstChild);
    });
    origCards.slice(0, CLONE_N).forEach(function (c) {
      track.appendChild(c.cloneNode(true));
    });

    var allCards = Array.from(track.children); // includes clones
    var current  = CLONE_N; // start at first real card
    var autoTimer = null;
    var isTransitioning = false;

    function visibleCount() {
      var vw = viewport.offsetWidth;
      if (vw >= 900) return 4;
      if (vw >= 600) return 3;
      if (vw >= 400) return 2;
      return 1;
    }

    function cardWidth() {
      if (!allCards[0]) return 0;
      var style = getComputedStyle(track);
      var gap = parseFloat(style.gap || style.columnGap || 20);
      return allCards[0].offsetWidth + gap;
    }

    // Move without animation (for loop jump)
    function jumpTo(idx) {
      current = idx;
      track.style.transition = 'none';
      track.style.transform  = 'translateX(-' + (current * cardWidth()) + 'px)';
    }

    // Move with animation
    function goTo(idx) {
      if (isTransitioning) return;
      isTransitioning = true;
      current = idx;
      track.style.transition = 'transform .45s cubic-bezier(.25,.46,.45,.94)';
      track.style.transform  = 'translateX(-' + (current * cardWidth()) + 'px)';
    }

    // After transition ends, silently jump back if we hit a clone zone
    track.addEventListener('transitionend', function () {
      isTransitioning = false;
      var realEnd = CLONE_N + total; // index of first trailing clone
      if (current >= realEnd) {
        jumpTo(CLONE_N + (current - realEnd)); // loop to start
      } else if (current < CLONE_N) {
        jumpTo(realEnd - CLONE_N + current);   // loop to end
      }
    });

    function startAuto() {
      clearInterval(autoTimer);
      autoTimer = setInterval(function () { goTo(current + 1); }, 5000);
    }

    document.querySelector('.js-gear-prev').addEventListener('click', function () { goTo(current - 1); startAuto(); });
    document.querySelector('.js-gear-next').addEventListener('click', function () { goTo(current + 1); startAuto(); });

    viewport.addEventListener('mouseenter', function () { clearInterval(autoTimer); });
    viewport.addEventListener('mouseleave', startAuto);

    var tx = 0;
    viewport.addEventListener('touchstart', function (e) { tx = e.touches[0].clientX; }, {passive:true});
    viewport.addEventListener('touchend', function (e) {
      var dx = tx - e.changedTouches[0].clientX;
      if (Math.abs(dx) > 40) { goTo(dx > 0 ? current + 1 : current - 1); startAuto(); }
    }, {passive:true});

    // Init: jump to first real card (no animation), then start
    jumpTo(CLONE_N);
    // Force reflow so the jump takes effect before enabling transitions
    track.getBoundingClientRect();
    startAuto();

    window.addEventListener('resize', function () { jumpTo(current); });
  }());
  </script>
  <?php endif; ?>
  <?php endif; ?>

  <!-- ══ RELATED ════════════════════════════════════════════════ -->
  <div class="page-route__related">
    <div class="section-header" data-animate="fade-up" data-animate-delay="100">
      <div>
        <div class="section-header__eyebrow">Keep Exploring</div>
        <h2 class="section-header__title" style="color:var(--sand)"><?php echo esc_html($related_label); ?></h2>
      </div>
    </div>
    <div class="related-grid">
      <?php
      $related = new WP_Query([
        'post_type'           => $post_type,
        'posts_per_page'      => 3,
        'post__not_in'        => [get_the_ID()],
        'category__in'        => wp_list_pluck($cats ?: [], 'term_id'),
        'ignore_sticky_posts' => true,
        'post_status'         => 'publish',
      ]);
      // Fallback: any post of same type if none match by category
      if (!$related->have_posts()) {
        $related = new WP_Query([
          'post_type'           => $post_type,
          'posts_per_page'      => 3,
          'post__not_in'        => [get_the_ID()],
          'ignore_sticky_posts' => true,
          'post_status'         => 'publish',
        ]);
      }
      if ($related->have_posts()) :
        while ($related->have_posts()) : $related->the_post();
          $r_thumb = get_the_post_thumbnail_url(null, 'medium') ?: get_field('image') ?: '';
          $r_cats    = get_the_category();
      ?>
      <div class="post-card" data-animate="fade-up">
        <a href="<?php the_permalink(); ?>" class="post-card__link" aria-label="<?php the_title_attribute(); ?>"></a>
        <div class="post-card__image">
          <?php if ($r_thumb) : ?>
            <img src="<?php echo esc_url($r_thumb); ?>" alt="<?php the_title_attribute(); ?>">
          <?php else : ?>
            <div class="post-card__empty"><span class="material-symbols-outlined">terrain</span></div>
          <?php endif; ?>
          <?php if ($r_cats) : ?>
          <div class="post-card__badge">
            <span class="badge badge--terracotta"><?php echo esc_html($r_cats[0]->name); ?></span>
          </div>
          <?php endif; ?>
        </div>
        <div class="post-card__body">
          <div class="post-card__date"><?php echo get_the_date('d M Y'); ?></div>
          <h4 class="post-card__title" style="color:var(--sand)"><?php the_title(); ?></h4>
          <div class="post-card__footer">
            <div class="post-card__tags">
              <?php foreach (array_slice(wp_get_post_terms(get_the_ID(), 'post_tag'), 0, 2) as $rt) : ?>
                <a href="<?php echo esc_url(get_term_link($rt)); ?>" class="badge badge--outline"><?php echo esc_html($rt->name); ?></a>
              <?php endforeach; ?>
            </div>
            <span class="post-card__arrow" style="color:var(--terracotta)">
              <span class="material-symbols-outlined">north_east</span>
            </span>
          </div>
        </div>
      </div>
      <?php
        endwhile;
        wp_reset_postdata();
      endif;
      ?>
    </div>
  </div>

  <!-- ══ FIXED MOBILE MAPS BUTTON ══════════════════════════════ -->
  <?php if ($mobile_maps_url) : ?>
  <a href="<?php echo esc_url($mobile_maps_url); ?>"
     class="page-route__mobile-maps-btn"
     target="_blank" rel="noopener">
    <span class="material-symbols-outlined">map</span>
    View route on Google Maps
  </a>
  <?php endif; ?>

</div>

<!-- ══ MAP SCRIPT ══════════════════════════════════════════════ -->
<?php if ($has_map) : ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
  <?php if ($has_polyline) : ?>
  var routePoints = <?php echo json_encode($route_pts); ?>;
  if (!routePoints.length) return;
  var map = L.map('route-map', { zoomControl: false, attributionControl: false });
  L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png').addTo(map);
  L.control.zoom({ position: 'bottomright' }).addTo(map);
  var poly = L.polyline(routePoints, { color: '#0df246', weight: 3, opacity: 0.85 }).addTo(map);
  map.fitBounds(poly.getBounds(), { padding: [30, 30] });
  var startIcon = L.divIcon({ className: '', html: '<div class="map-pin map-pin--start"></div>', iconAnchor: [7, 7] });
  var endIcon   = L.divIcon({ className: '', html: '<div class="map-pin map-pin--end"></div>',   iconAnchor: [7, 7] });
  L.marker(routePoints[0],                      { icon: startIcon }).addTo(map).bindPopup('Start').openPopup();
  L.marker(routePoints[routePoints.length - 1], { icon: endIcon   }).addTo(map).bindPopup('End');
  <?php else : ?>
  var lat  = <?php echo json_encode(floatval($lat)); ?>;
  var lon  = <?php echo json_encode(floatval($lon)); ?>;
  var zoom = <?php echo $post_type === 'point-of-interest' ? 15 : 13; ?>;
  var map  = L.map('route-map', { zoomControl: false, attributionControl: false }).setView([lat, lon], zoom);
  L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png').addTo(map);
  L.control.zoom({ position: 'bottomright' }).addTo(map);
  var pinClass = '<?php echo $post_type === "point-of-interest" ? "map-pin--poi" : "map-pin--start"; ?>';
  var icon = L.divIcon({ className: '', html: '<div class="map-pin ' + pinClass + '"></div>', iconAnchor: [8, 8] });
  L.marker([lat, lon], { icon: icon }).addTo(map).bindPopup('<?php echo esc_js(get_the_title()); ?>').openPopup();
  <?php endif; ?>

  <?php foreach ($all_cp_pois as $cp_poi) :
    $cp_lat = floatval(get_field('latitude',  $cp_poi->ID) ?: 0);
    $cp_lon = floatval(get_field('longitude', $cp_poi->ID) ?: 0);
    if (!$cp_lat || !$cp_lon) continue;
  ?>
  L.circleMarker([<?php echo $cp_lat; ?>, <?php echo $cp_lon; ?>], {
    radius: 5, color: '#ff6b00', fillColor: '#ff6b00', fillOpacity: 1, weight: 2
  }).addTo(map).bindPopup('<?php echo esc_js($cp_poi->post_title); ?>');
  <?php endforeach; ?>
});
</script>
<?php endif; ?>

<?php endwhile; endif; ?>

<!-- ══ WEATHER SCRIPT ═══════════════════════════════════════════ -->
<?php if (isset($has_weather) && $has_weather) : ?>
<script>
(function () {
  var el = document.querySelector('.page-route__weather');
  if (!el) return;

  var WMO_LABELS = {
    0:'Clear', 1:'Mostly clear', 2:'Partly cloudy', 3:'Overcast',
    45:'Fog', 48:'Icy fog',
    51:'Light drizzle', 53:'Drizzle', 55:'Heavy drizzle',
    61:'Light rain', 63:'Moderate rain', 65:'Heavy rain',
    71:'Light snow', 73:'Snow', 75:'Heavy snow',
    80:'Showers', 81:'Moderate showers', 82:'Heavy showers',
    85:'Sleet', 86:'Heavy sleet',
    95:'Thunderstorm', 96:'Thunderstorm with hail', 99:'Severe thunderstorm'
  };

  var WMO_ICONS = {
    0:'wb_sunny', 1:'wb_sunny', 2:'partly_cloudy_day', 3:'cloud',
    45:'foggy', 48:'foggy',
    51:'rainy', 53:'rainy', 55:'rainy',
    61:'rainy', 63:'rainy', 65:'rainy',
    71:'snowing', 73:'snowing', 75:'snowing',
    80:'rainy', 81:'rainy', 82:'rainy', 85:'snowing', 86:'snowing',
    95:'thunderstorm', 96:'thunderstorm', 99:'thunderstorm'
  };

  var lat = el.dataset.lat;
  var lon = el.dataset.lon;
  var url = 'https://api.open-meteo.com/v1/forecast'
    + '?latitude=' + lat + '&longitude=' + lon
    + '&current=temperature_2m,weathercode,windspeed_10m,relative_humidity_2m'
    + '&wind_speed_unit=kmh&timezone=auto';

  fetch(url)
    .then(function (r) { return r.json(); })
    .then(function (d) {
      var c    = d.current;
      var code = c.weathercode;
      el.querySelector('.weather-icon').textContent    = WMO_ICONS[code]  || 'wb_sunny';
      el.querySelector('.weather-temp').textContent    = Math.round(c.temperature_2m) + '°C';
      el.querySelector('.weather-desc').textContent    = WMO_LABELS[code] || 'Unknown condition';
      el.querySelector('.weather-wind-val').textContent = Math.round(c.windspeed_10m) + ' km/h';
      el.querySelector('.weather-hum-val').textContent  = c.relative_humidity_2m + '%';
      el.classList.add('is-ready');
    })
    .catch(function () {
      el.classList.add('is-error');
    });
})();
</script>
<?php endif; ?>

<!-- ══ TAB NAVIGATION SCRIPT ════════════════════════════════════ -->
<script>
(function () {
  var tabs   = Array.from(document.querySelectorAll('.tab-btn'));
  var tabBar = document.querySelector('.page-route__tabs');
  if (!tabs.length || !tabBar) return;

  var sections = {
    info:    document.getElementById('section-info'),
    map:     document.getElementById('section-map'),
    gallery: document.getElementById('section-gallery'),
    gear:    document.getElementById('section-gear'),
  };

  function scrollToSection(key) {
    var target = sections[key];
    if (!target) return;
    var offset = 64 + tabBar.offsetHeight + 8;
    var top = target.getBoundingClientRect().top + window.scrollY - offset;
    window.scrollTo({ top: top, behavior: 'smooth' });
  }

  function setActive(key) {
    tabs.forEach(function (b) {
      b.classList.toggle('tab-btn--active', b.dataset.tab === key);
    });
  }

  tabs.forEach(function (btn) {
    btn.addEventListener('click', function () {
      setActive(btn.dataset.tab);
      scrollToSection(btn.dataset.tab);
    });
  });

  // Highlight active tab while scrolling
  var observer = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (!entry.isIntersecting) return;
      var key = entry.target.id.replace('section-', '');
      setActive(key);
    });
  }, { rootMargin: '-20% 0px -75% 0px' });

  Object.values(sections).forEach(function (el) {
    if (el) observer.observe(el);
  });
})();
</script>



<?php get_template_part('components/blocks'); ?>
<?php get_template_part('parts/footer'); ?>
