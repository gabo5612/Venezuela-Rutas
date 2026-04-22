<?php get_template_part('parts/header'); ?>

<?php
$zones        = get_terms(['taxonomy' => 'guide-zone', 'hide_empty' => true]);
$current_zone = is_tax('guide-zone') ? get_queried_object() : null;
?>

<div class="guide-archive">
  <div class="container">

    <!-- Header -->
    <div class="section-header" data-animate="fade-up">
      <div>
        <div class="section-header__eyebrow">Directorio</div>
        <h1 class="section-header__title" style="color:var(--sand)">Guides & Operators</h1>
      </div>
    </div>

    <!-- Mapa de zonas (solo en el archive general, no en filtros por zona) -->
    <?php if (!$current_zone) :
      $map_zones = [];
      if (!empty($zones) && !is_wp_error($zones)) {
        foreach ($zones as $z) {
          $tid   = 'guide-zone_' . $z->term_id;
          $zlat  = get_field('zone_latitude',  $tid);
          $zlng  = get_field('zone_longitude', $tid);
          $zr    = intval(get_field('zone_radius', $tid) ?: 3);
          if (empty($zlat) || empty($zlng)) continue;
          $map_zones[] = [
            'name'   => $z->name,
            'lat'    => floatval($zlat),
            'lng'    => floatval($zlng),
            'count'  => intval($z->count),
            'radius' => $zr * 1500,
            'url'    => get_term_link($z),
          ];
        }
      }
      if (!empty($map_zones)) :
    ?>
    <div id="guides-map" style="margin-bottom:2.5rem"></div>
    <script>
    (function () {
      var guideZones = <?php echo wp_json_encode($map_zones); ?>;
      if (!guideZones.length) return;

      // Venezuela fallback
      var vzCenter = [8.0, -66.0];

      var map = L.map('guides-map', { zoomControl: false, attributionControl: false })
                 .setView(vzCenter, 6);

      L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors © <a href="https://carto.com/attributions">CARTO</a>'
      }).addTo(map);

      L.control.zoom({ position: 'bottomright' }).addTo(map);

      var bounds = L.latLngBounds([]);

      guideZones.forEach(function (zone) {
        var circle = L.circle([zone.lat, zone.lng], {
          radius:      zone.radius,
          color:       '#0aab38',
          weight:      2,
          opacity:     0.7,
          fillColor:   '#0df246',
          fillOpacity: 0.18,
          className:   'guide-zone-circle'
        }).addTo(map);

        var labelIcon = L.divIcon({
          className: '',
          html: '<div class="guide-zone-label">'
              + '<span class="guide-zone-label__name">' + zone.name + '</span>'
              + '<span class="guide-zone-label__count">' + zone.count + ' guide' + (zone.count !== 1 ? 's' : '') + '</span>'
              + '</div>',
          iconSize:   [120, 44],
          iconAnchor: [60, 22]
        });
        var label = L.marker([zone.lat, zone.lng], { icon: labelIcon }).addTo(map);

        function goToZone() { window.location.href = zone.url; }
        circle.on('click', goToZone);
        label.on('click',  goToZone);

        circle.on('mouseover', function () { circle.setStyle({ fillOpacity: 0.35, weight: 3 }); });
        circle.on('mouseout',  function () { circle.setStyle({ fillOpacity: 0.18, weight: 2 }); });

        bounds.extend(circle.getBounds());
      });

      if (bounds.isValid()) map.fitBounds(bounds, { padding: [40, 40] });
    }());
    </script>
    <?php endif; endif; ?>

    <!-- Zone filters -->
    <?php if (!empty($zones) && !is_wp_error($zones)) : ?>
    <div class="guide-zone-filters" data-animate="fade-up" data-animate-delay="100">
      <a href="<?php echo esc_url(get_post_type_archive_link('guide')); ?>"
         class="gps-pill <?php echo !$current_zone ? 'is-active' : ''; ?>">
        All zones
      </a>
      <?php foreach ($zones as $zone) : ?>
      <a href="<?php echo esc_url(get_term_link($zone)); ?>"
         class="gps-pill <?php echo ($current_zone && $current_zone->term_id === $zone->term_id) ? 'is-active' : ''; ?>">
        <?php echo esc_html($zone->name); ?>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Guide grid -->
    <div class="guide-grid">
      <?php if (have_posts()) : while (have_posts()) : the_post();
        $g_photo    = get_field('guide_photo')     ?: get_the_post_thumbnail_url(null, 'medium') ?: '';
        $g_wp       = get_field('guide_whatsapp')  ?: '';
        $g_ig       = get_field('guide_instagram') ?: '';
        $g_price    = get_field('guide_price_from') ?: '';
        $g_featured = get_field('guide_is_featured') ?: false;
        $g_spec     = get_field('guide_specialty') ?: [];
        $g_zones    = wp_get_post_terms(get_the_ID(), 'guide-zone', ['fields' => 'names']);
      ?>
      <div class="guide-card <?php echo $g_featured ? 'guide-card--featured' : ''; ?>" data-animate="fade-up">
        <?php if ($g_featured) : ?>
        <div class="guide-card__badge">Featured</div>
        <?php endif; ?>
        <a href="<?php the_permalink(); ?>" class="guide-card__link" aria-label="<?php the_title_attribute(); ?>"></a>

        <div class="guide-card__photo">
          <?php if ($g_photo) : ?>
            <img src="<?php echo esc_url($g_photo); ?>" alt="<?php the_title_attribute(); ?>" loading="lazy">
          <?php else : ?>
            <div class="guide-card__photo-placeholder">
              <span class="material-symbols-outlined">person</span>
            </div>
          <?php endif; ?>
        </div>

        <div class="guide-card__body">
          <h3 class="guide-card__name"><?php the_title(); ?></h3>

          <?php if (!empty($g_zones) && !is_wp_error($g_zones)) : ?>
          <p class="guide-card__zone">
            <span class="material-symbols-outlined">location_on</span>
            <?php echo esc_html(implode(', ', $g_zones)); ?>
          </p>
          <?php endif; ?>

          <?php if (!empty($g_spec)) : ?>
          <div class="guide-card__specs">
            <?php foreach (array_slice($g_spec, 0, 3) as $s) : ?>
              <span class="badge badge--outline"><?php echo esc_html( rutas_translate_specialty($s) ); ?></span>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>

          <?php if ($g_price) : ?>
          <p class="guide-card__price">From <strong>$<?php echo esc_html($g_price); ?></strong>/day</p>
          <?php endif; ?>

          <div class="guide-card__cta">
            <?php if ($g_wp) : ?>
            <a href="https://wa.me/<?php echo esc_attr(preg_replace('/\D/', '', $g_wp)); ?>"
               class="btn btn--whatsapp btn--sm"
               target="_blank" rel="noopener"
               onclick="event.stopPropagation()">
              <span class="material-symbols-outlined">chat</span> WhatsApp
            </a>
            <?php endif; ?>
            <a href="<?php the_permalink(); ?>" class="btn btn--outline btn--sm">View profile</a>
          </div>
        </div>
      </div>
      <?php endwhile;
      else : ?>
      <p style="color:var(--text-muted);grid-column:1/-1">No guides registered yet.</p>
      <?php endif; ?>
    </div>

  </div>
</div>

<?php get_template_part('parts/footer'); ?>
