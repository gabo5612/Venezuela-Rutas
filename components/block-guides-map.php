<section class="container guides-map-section">

  <?php
  $eyebrow = get_sub_field('eyebrow') ?: 'Directory';
  $title   = get_sub_field('title')   ?: 'Guides by Zone';
  ?>

  <div class="section-header" data-animate="fade-up">
    <div>
      <div class="section-header__eyebrow"><?php echo esc_html($eyebrow); ?></div>
      <h2 class="section-header__title" style="color:var(--sand)"><?php echo esc_html($title); ?></h2>
    </div>
    <a href="<?php echo esc_url(get_post_type_archive_link('guide')); ?>" class="btn btn--outline">
      View all guides
    </a>
  </div>

  <div id="guides-map"></div>

  <script>
    var guideZones = [];
    <?php
    $zones = get_terms(['taxonomy' => 'guide-zone', 'hide_empty' => true]);

    if (!empty($zones) && !is_wp_error($zones)) :
      foreach ($zones as $zone) :
        $tid    = 'guide-zone_' . $zone->term_id;
        $lat    = get_field('zone_latitude',  $tid);
        $lng    = get_field('zone_longitude', $tid);
        $range  = intval(get_field('zone_radius', $tid) ?: 3);

        if (empty($lat) || empty($lng)) continue;

        // Mapear 1-10 a metros: 1 = 15 km, 10 = 150 km
        $radius_m = $range * 1500;

        echo "guideZones.push(" . json_encode([
          'name'     => $zone->name,
          'lat'      => floatval($lat),
          'lng'      => floatval($lng),
          'count'    => intval($zone->count),
          'radius'   => $radius_m,
          'url'      => get_term_link($zone),
        ]) . ");\n";
      endforeach;
    endif;
    ?>
  </script>

  <script>
  document.addEventListener('DOMContentLoaded', function () {
    if (!guideZones.length) return;

    var map = L.map('guides-map', { zoomControl: false, scrollWheelZoom: false });

    var tileAttrib = '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors © <a href="https://carto.com/attributions">CARTO</a>';
    var isDark = document.documentElement.classList.contains('dark-mode');
    L.tileLayer(
      isDark
        ? 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png'
        : 'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png',
      { attribution: tileAttrib }
    ).addTo(map);

    L.control.zoom({ position: 'bottomright' }).addTo(map);

    var bounds = L.latLngBounds([]);

    guideZones.forEach(function (zone) {
      // Círculo de zona
      var circle = L.circle([zone.lat, zone.lng], {
        radius:      zone.radius,
        color:       '#0aab38',
        weight:      2,
        opacity:     0.7,
        fillColor:   '#0df246',
        fillOpacity: 0.18,
        className:   'guide-zone-circle'
      }).addTo(map);

      // Label central (marker invisible con HTML)
      var labelIcon = L.divIcon({
        className: '',
        html: `<div class="guide-zone-label">
                 <span class="guide-zone-label__name">${zone.name}</span>
                 <span class="guide-zone-label__count">${zone.count} guide${zone.count !== 1 ? 's' : ''}</span>
               </div>`,
        iconSize: [120, 44],
        iconAnchor: [60, 22]
      });
      var label = L.marker([zone.lat, zone.lng], { icon: labelIcon, interactive: true }).addTo(map);

      // Click en círculo o label → redirige a la zona
      function goToZone() { window.location.href = zone.url; }
      circle.on('click', goToZone);
      label.on('click', goToZone);

      // Hover: resaltar círculo
      circle.on('mouseover', function () {
        circle.setStyle({ fillOpacity: 0.35, weight: 3 });
      });
      circle.on('mouseout', function () {
        circle.setStyle({ fillOpacity: 0.18, weight: 2 });
      });

      bounds.extend(circle.getBounds());
    });

    if (bounds.isValid()) {
      map.fitBounds(bounds, { padding: [40, 40] });
    }
  });
  </script>

</section>
