<?php get_template_part('parts/header'); ?>

<?php if (have_posts()) : while (have_posts()) : the_post();
  $post_type = get_post_type();
?>

<?php /* ══════════════════════════════════════════════════════════════
  POST — blog route: stats, map, checkpoints, gallery
══════════════════════════════════════════════════════════════ */ ?>
<?php if ($post_type === 'post') : ?>

<?php
$thumb       = get_the_post_thumbnail_url(null, 'full');
$cats        = get_the_category();
$cat_name    = $cats ? $cats[0]->name : '';
$distance    = get_field('distance')         ?: '';
$elevation   = get_field('elevation')        ?: '';
$difficulty  = get_field('difficulty')       ?: '';
$est_time    = get_field('time')             ?: '';
$lat         = get_field('latitude')         ?: '';
$lon         = get_field('longitude')        ?: '';
$location    = get_field('location')         ?: '';
$checkpoints = get_field('checkpoints')      ?: [];
$gallery     = get_field('gallery')          ?: [];
$conditions  = get_field('conditions_alert') ?: '';
?>

<div class="page-route">

  <!-- ══ HERO ══ -->
  <?php if ($thumb) : ?>
  <div class="page-route__hero">
    <img src="<?php echo esc_url($thumb); ?>" alt="<?php the_title_attribute(); ?>">
    <div class="hero-overlay"></div>
  </div>
  <?php endif; ?>

  <!-- ══ STATS BAR ══ -->
  <?php if ($distance || $elevation || $difficulty || $est_time) : ?>
  <div class="page-route__stats">
    <?php if ($distance) : ?>
    <div class="page-route__stat">
      <span class="stat-icon material-symbols-outlined">distance</span>
      <span class="stat-value"><?php echo esc_html($distance); ?></span>
      <span class="stat-label">Distancia</span>
    </div>
    <?php endif; ?>
    <?php if ($elevation) : ?>
    <div class="page-route__stat">
      <span class="stat-icon material-symbols-outlined">elevation</span>
      <span class="stat-value"><?php echo esc_html($elevation); ?></span>
      <span class="stat-label">Elevación</span>
    </div>
    <?php endif; ?>
    <?php if ($difficulty) : ?>
    <div class="page-route__stat">
      <span class="stat-icon material-symbols-outlined">fitness_center</span>
      <span class="stat-value"><?php echo esc_html($difficulty); ?></span>
      <span class="stat-label">Dificultad</span>
    </div>
    <?php endif; ?>
    <?php if ($est_time) : ?>
    <div class="page-route__stat">
      <span class="stat-icon material-symbols-outlined">schedule</span>
      <span class="stat-value"><?php echo esc_html($est_time); ?></span>
      <span class="stat-label">Tiempo Est.</span>
    </div>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <!-- ══ TABS ══ -->
  <div class="page-route__tabs">
    <div class="page-route__tabs-inner">
      <button class="tab-btn tab-btn--active" data-tab="map">
        <span class="material-symbols-outlined">map</span> Mapa
      </button>
      <button class="tab-btn" data-tab="info">
        <span class="material-symbols-outlined">info</span> Info
      </button>
      <?php if (!empty($gallery)) : ?>
      <button class="tab-btn" data-tab="gallery">
        <span class="material-symbols-outlined">photo_library</span> Galería
      </button>
      <?php endif; ?>
    </div>
  </div>

  <!-- ══ ALERT ══ -->
  <?php if ($conditions) : ?>
  <div class="page-route__alert">
    <span class="material-symbols-outlined">warning</span>
    <?php echo esc_html($conditions); ?>
  </div>
  <?php endif; ?>

  <!-- ══ MAP + CHECKPOINTS ══ -->
  <?php if ($lat && $lon) : ?>
  <div class="page-route__map-section">
    <div class="page-route__map-wrap">
      <div id="route-map"></div>
    </div>
    <?php if (!empty($checkpoints)) : ?>
    <div class="page-route__checkpoints">
      <div class="cp-header">
        <span class="material-symbols-outlined">route</span>
        Checkpoints (<?php echo count($checkpoints); ?>)
      </div>
      <div class="cp-list">
        <?php foreach ($checkpoints as $cp) :
          $poi = $cp['poi'] ?? null;
          if (!$poi) continue;
          $poi_image_raw = get_field('interest_image', $poi->ID);
          $poi_image_url = is_array($poi_image_raw) ? ($poi_image_raw['url'] ?? '') : (string) $poi_image_raw;
          $poi_image_alt = is_array($poi_image_raw) ? ($poi_image_raw['alt'] ?? '') : '';
          $poi_maps      = get_field('has_a_google_maps_card', $poi->ID);
        ?>
        <div class="cp-item">
          <div class="cp-dot"></div>
          <div class="cp-content">
            <?php if ($poi_image_url) : ?>
              <div class="cp-img">
                <img src="<?php echo esc_url($poi_image_url); ?>" alt="<?php echo esc_attr($poi_image_alt); ?>">
              </div>
            <?php endif; ?>
            <a href="<?php echo esc_url(get_permalink($poi->ID)); ?>" class="cp-name">
              <?php echo esc_html($poi->post_title); ?>
            </a>
            <?php if ($poi_maps) : ?>
              <a href="<?php echo esc_url($poi_maps); ?>" class="cp-maps-link" target="_blank" rel="noopener">
                <span class="material-symbols-outlined">map</span> Ver en Maps
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

  <!-- ══ BODY ══ -->
  <div class="page-route__body">
    <h1 style="font-size:clamp(1.75rem,4vw,3rem);font-weight:900;text-transform:uppercase;color:var(--text);margin-bottom:1.5rem;line-height:1">
      <?php the_title(); ?>
    </h1>
    <?php if ($cat_name || $location) : ?>
    <div style="display:flex;gap:.75rem;align-items:center;margin-bottom:2rem">
      <?php if ($cat_name) : ?><span class="badge badge--terracotta"><?php echo esc_html($cat_name); ?></span><?php endif; ?>
      <?php if ($location) : ?><span style="font-size:.75rem;color:var(--text-muted);display:flex;align-items:center;gap:.3rem"><span class="material-symbols-outlined" style="font-size:1rem">location_on</span><?php echo esc_html($location); ?></span><?php endif; ?>
    </div>
    <?php endif; ?>
    <?php the_content(); ?>
  </div>

  <!-- ══ GALLERY ══ -->
  <?php if (!empty($gallery)) : ?>
  <div class="page-route__gallery">
    <div class="section-header">
      <div>
        <div class="section-header__eyebrow">Documentación Visual</div>
        <h2 class="section-header__title" style="color:var(--sand)">Galería</h2>
      </div>
    </div>
    <div class="gallery-grid">
      <?php foreach ($gallery as $img) : ?>
        <img src="<?php echo esc_url($img['url']); ?>" alt="<?php echo esc_attr($img['alt']); ?>">
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- ══ RELATED POSTS ══ -->
  <div class="page-route__related">
    <div class="section-header">
      <div>
        <div class="section-header__eyebrow">Continúa Explorando</div>
        <h2 class="section-header__title" style="color:var(--sand)">Rutas Similares</h2>
      </div>
    </div>
    <div class="related-grid">
      <?php
      $related = new WP_Query([
        'post_type'           => 'post',
        'posts_per_page'      => 3,
        'post__not_in'        => [get_the_ID()],
        'category__in'        => wp_list_pluck($cats ?: [], 'term_id'),
        'ignore_sticky_posts' => true,
        'post_status'         => 'publish',
      ]);
      if ($related->have_posts()) :
        while ($related->have_posts()) : $related->the_post();
          $r_thumb = get_the_post_thumbnail_url(null, 'medium');
          $r_cats  = get_the_category();
      ?>
      <a href="<?php the_permalink(); ?>" class="post-card">
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
            <span class="post-card__author"><?php the_author(); ?></span>
            <span class="post-card__arrow" style="color:var(--terracotta)">
              <span class="material-symbols-outlined">north_east</span>
            </span>
          </div>
        </div>
      </a>
      <?php
        endwhile;
        wp_reset_postdata();
      endif;
      ?>
    </div>
  </div>

</div>

<?php if ($lat && $lon) : ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var lat = <?php echo json_encode(floatval($lat)); ?>;
  var lon = <?php echo json_encode(floatval($lon)); ?>;
  var map = L.map('route-map', { zoomControl: true, attributionControl: false }).setView([lat, lon], 13);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
  var icon = L.divIcon({
    className: '',
    html: '<div style="width:16px;height:16px;background:#C74A2D;border-radius:50%;border:2px solid #E2C9A0;box-shadow:0 0 0 4px rgba(199,74,45,.25)"></div>',
    iconAnchor: [8, 8]
  });
  L.marker([lat, lon], { icon: icon }).addTo(map)
    .bindPopup('<?php echo esc_js(get_the_title()); ?>').openPopup();
  <?php foreach ($checkpoints as $cp) :
    $cp_poi = $cp['poi'] ?? null;
    if (!$cp_poi) continue;
    $cp_lat = floatval(get_field('interest_latitude', $cp_poi->ID) ?: 0);
    $cp_lon = floatval(get_field('interest_longitude', $cp_poi->ID) ?: 0);
    if (!$cp_lat || !$cp_lon) continue;
  ?>
  L.circleMarker([<?php echo $cp_lat; ?>, <?php echo $cp_lon; ?>], {
    radius: 5, color: '#F2B705', fillColor: '#F2B705', fillOpacity: 1, weight: 2
  }).addTo(map).bindPopup('<?php echo esc_js($cp_poi->post_title); ?>');
  <?php endforeach; ?>
});
</script>
<?php endif; ?>


<?php /* ══════════════════════════════════════════════════════════════
  ROUTES CPT — GPS route with polyline map
══════════════════════════════════════════════════════════════ */ ?>
<?php elseif ($post_type === 'routes') : ?>

<?php
$thumb       = get_the_post_thumbnail_url(null, 'full') ?: get_field('image');
$blog_entry  = get_field('blog_entry');
$points      = get_field('points')                  ?: [];
$poi_posts   = get_field('route_point_of_interest') ?: [];
$tags        = wp_get_post_terms(get_the_ID(), 'post_tag', ['fields' => 'names']);
if (is_wp_error($tags)) $tags = [];
?>

<div class="page-route page-route--routes">

  <!-- ══ HERO ══ -->
  <?php if ($thumb) : ?>
  <div class="page-route__hero">
    <img src="<?php echo esc_url($thumb); ?>" alt="<?php the_title_attribute(); ?>">
    <div class="hero-overlay"></div>
  </div>
  <?php endif; ?>

  <!-- ══ BODY ══ -->
  <div class="page-route__body">
    <h1 style="font-size:clamp(1.75rem,4vw,3rem);font-weight:900;text-transform:uppercase;color:var(--text);margin-bottom:1.5rem;line-height:1">
      <?php the_title(); ?>
    </h1>
    <?php if (!empty($tags)) : ?>
    <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:2rem">
      <?php foreach ($tags as $tag) : ?>
        <span class="badge badge--terracotta"><?php echo esc_html($tag); ?></span>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php the_content(); ?>
    <?php if ($blog_entry) : ?>
    <a href="<?php echo esc_url(get_permalink($blog_entry)); ?>" class="btn btn--primary" style="margin-top:1.5rem;display:inline-flex;align-items:center;gap:.4rem">
      <span class="material-symbols-outlined">article</span> Ver Bitácora
    </a>
    <?php endif; ?>
  </div>

  <!-- ══ MAP ══ -->
  <?php if (!empty($points)) : ?>
  <div class="page-route__map-section page-route__map-section--full">
    <div class="page-route__map-wrap" style="max-width:100%">
      <div id="route-map"></div>
    </div>
  </div>
  <?php endif; ?>

  <!-- ══ RELATED ROUTES ══ -->
  <div class="page-route__related">
    <div class="section-header">
      <div>
        <div class="section-header__eyebrow">Continúa Explorando</div>
        <h2 class="section-header__title" style="color:var(--sand)">Más Rutas GPS</h2>
      </div>
    </div>
    <div class="related-grid">
      <?php
      $related = new WP_Query([
        'post_type'           => 'routes',
        'posts_per_page'      => 3,
        'post__not_in'        => [get_the_ID()],
        'ignore_sticky_posts' => true,
        'post_status'         => 'publish',
      ]);
      if ($related->have_posts()) :
        while ($related->have_posts()) : $related->the_post();
          $r_thumb = get_the_post_thumbnail_url(null, 'medium') ?: get_field('image');
      ?>
      <a href="<?php the_permalink(); ?>" class="post-card">
        <div class="post-card__image">
          <?php if ($r_thumb) : ?>
            <img src="<?php echo esc_url($r_thumb); ?>" alt="<?php the_title_attribute(); ?>">
          <?php else : ?>
            <div class="post-card__empty"><span class="material-symbols-outlined">route</span></div>
          <?php endif; ?>
        </div>
        <div class="post-card__body">
          <div class="post-card__date"><?php echo get_the_date('d M Y'); ?></div>
          <h4 class="post-card__title" style="color:var(--sand)"><?php the_title(); ?></h4>
          <div class="post-card__footer">
            <span class="post-card__author"><?php the_author(); ?></span>
            <span class="post-card__arrow" style="color:var(--terracotta)">
              <span class="material-symbols-outlined">north_east</span>
            </span>
          </div>
        </div>
      </a>
      <?php
        endwhile;
        wp_reset_postdata();
      endif;
      ?>
    </div>
  </div>

</div>

<?php if (!empty($points)) :
  $route_pts = [];
  foreach ($points as $p) {
    $plat = floatval($p['latitude']  ?? 0);
    $plng = floatval($p['longitude'] ?? 0);
    if ($plat && $plng) $route_pts[] = [$plat, $plng];
  }
?>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var routePoints = <?php echo json_encode($route_pts); ?>;
  if (!routePoints.length) return;

  var map = L.map('route-map', { zoomControl: true, attributionControl: false });
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

  var poly = L.polyline(routePoints, { color: '#0df246', weight: 4, opacity: 0.85 }).addTo(map);
  map.fitBounds(poly.getBounds(), { padding: [30, 30] });

  var startIcon = L.divIcon({
    className: '',
    html: '<div style="width:14px;height:14px;background:#0df246;border-radius:50%;border:2px solid #fff;box-shadow:0 0 0 3px rgba(13,242,70,.3)"></div>',
    iconAnchor: [7, 7]
  });
  L.marker(routePoints[0], { icon: startIcon }).addTo(map).bindPopup('Inicio').openPopup();

  var endIcon = L.divIcon({
    className: '',
    html: '<div style="width:14px;height:14px;background:#C74A2D;border-radius:50%;border:2px solid #fff;box-shadow:0 0 0 3px rgba(199,74,45,.3)"></div>',
    iconAnchor: [7, 7]
  });
  L.marker(routePoints[routePoints.length - 1], { icon: endIcon }).addTo(map).bindPopup('Fin');

  <?php foreach ((array) $poi_posts as $rpoi) :
    $rplat = floatval(get_field('interest_latitude',  $rpoi->ID) ?: 0);
    $rplng = floatval(get_field('interest_longitude', $rpoi->ID) ?: 0);
    if (!$rplat || !$rplng) continue;
  ?>
  L.circleMarker([<?php echo $rplat; ?>, <?php echo $rplng; ?>], {
    radius: 6, color: '#F2B705', fillColor: '#F2B705', fillOpacity: 1, weight: 2
  }).addTo(map).bindPopup('<?php echo esc_js($rpoi->post_title); ?>');
  <?php endforeach; ?>
});
</script>
<?php endif; ?>


<?php /* ══════════════════════════════════════════════════════════════
  POINT OF INTEREST CPT
══════════════════════════════════════════════════════════════ */ ?>
<?php elseif ($post_type === 'point-of-interest') : ?>

<?php
$poi_img_raw = get_field('interest_image');
$poi_img_url = is_array($poi_img_raw) ? ($poi_img_raw['url'] ?? '') : (string) $poi_img_raw;
$poi_img_alt = is_array($poi_img_raw) ? ($poi_img_raw['alt'] ?? '') : get_the_title();
$poi_lat     = get_field('interest_latitude')      ?: '';
$poi_lng     = get_field('interest_longitude')     ?: '';
$poi_entry   = get_field('interest_entry');
$poi_gmaps   = get_field('has_a_google_maps_card') ?: '';
$hero_url    = $poi_img_url ?: get_the_post_thumbnail_url(null, 'full');
?>

<div class="page-route page-route--poi">

  <!-- ══ HERO ══ -->
  <?php if ($hero_url) : ?>
  <div class="page-route__hero">
    <img src="<?php echo esc_url($hero_url); ?>" alt="<?php echo esc_attr($poi_img_alt); ?>">
    <div class="hero-overlay"></div>
  </div>
  <?php endif; ?>

  <!-- ══ BODY ══ -->
  <div class="page-route__body">
    <h1 style="font-size:clamp(1.75rem,4vw,3rem);font-weight:900;text-transform:uppercase;color:var(--text);margin-bottom:1.5rem;line-height:1">
      <?php the_title(); ?>
    </h1>

    <?php if ($poi_lat && $poi_lng) : ?>
    <div style="font-size:.75rem;color:var(--text-muted);display:flex;align-items:center;gap:.3rem;margin-bottom:1.5rem">
      <span class="material-symbols-outlined" style="font-size:1rem">location_on</span>
      <?php echo esc_html($poi_lat); ?>, <?php echo esc_html($poi_lng); ?>
    </div>
    <?php endif; ?>

    <div style="display:flex;gap:1rem;flex-wrap:wrap;margin-bottom:2rem">
      <?php if ($poi_gmaps) : ?>
        <a href="<?php echo esc_url($poi_gmaps); ?>" class="btn btn--primary" target="_blank" rel="noopener">
          <span class="material-symbols-outlined">map</span> Ver en Google Maps
        </a>
      <?php endif; ?>
      <?php if ($poi_entry) : ?>
        <a href="<?php echo esc_url(get_permalink($poi_entry)); ?>" class="btn btn--outline">
          <span class="material-symbols-outlined">article</span> Ver Bitácora
        </a>
      <?php endif; ?>
    </div>

    <?php the_content(); ?>
  </div>

  <!-- ══ MAP ══ -->
  <?php if ($poi_lat && $poi_lng) : ?>
  <div class="page-route__map-section page-route__map-section--full">
    <div class="page-route__map-wrap" style="max-width:100%">
      <div id="route-map"></div>
    </div>
  </div>
  <?php endif; ?>

</div>

<?php if ($poi_lat && $poi_lng) : ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var lat = <?php echo json_encode(floatval($poi_lat)); ?>;
  var lng = <?php echo json_encode(floatval($poi_lng)); ?>;
  var map = L.map('route-map', { zoomControl: true, attributionControl: false }).setView([lat, lng], 15);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
  var icon = L.divIcon({
    className: '',
    html: '<div style="width:16px;height:16px;background:#F2B705;border-radius:50%;border:2px solid #fff;box-shadow:0 0 0 4px rgba(242,183,5,.25)"></div>',
    iconAnchor: [8, 8]
  });
  L.marker([lat, lng], { icon: icon }).addTo(map)
    .bindPopup('<?php echo esc_js(get_the_title()); ?>').openPopup();
});
</script>
<?php endif; ?>

<?php endif; /* end post_type switch */ ?>

<?php endwhile; endif; ?>

<?php get_template_part('components/blocks'); ?>
<?php get_template_part('parts/footer'); ?>
