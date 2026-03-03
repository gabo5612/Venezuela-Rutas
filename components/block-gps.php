<section class="container">

  <!-- FILTROS -->
  <ul id="route-filters" class="route-filters"></ul>

  <!-- MAPA -->
  <div id="map" ></div>

  <script>
    var routes = [];
    <?php
    $args = array(
      'post_type'      => 'routes',
      'posts_per_page' => 10,
    );

    $routes_query = new WP_Query($args);

    if ($routes_query->have_posts()) :
      while ($routes_query->have_posts()) : $routes_query->the_post();

        $points    = get_field('points');
        $title     = get_the_title();
        $blog      = get_field('blog_entry');
        $image_url = get_field('image');

        // ✅ TAGS (cambia 'post_tag' por tu taxonomía si aplica)
        $tags = wp_get_post_terms(get_the_ID(), 'post_tag', ['fields' => 'names']);
        if (is_wp_error($tags) || empty($tags)) { $tags = []; }

        if ($points) :
          $route_points = [];
          foreach ($points as $point) {
            $lat = $point['latitude'];
            $lng = $point['longitude'];
            if (!empty($lat) && !empty($lng)) {
              $route_points[] = [$lat, $lng];
            }
          }

          // Waypoints POI (para tu link de Google Maps)
          $poi_waypoints = [];
          $poi_posts = get_field('route_point_of_interest');
          if (!empty($poi_posts)) {
            foreach ((array) $poi_posts as $poi) {
              $poi_lat = get_field('interest_latitude', $poi);
              $poi_lng = get_field('interest_longitude', $poi);
              if (!empty($poi_lat) && !empty($poi_lng)) {
                $poi_waypoints[] = $poi_lat . ',' . $poi_lng;
              }
            }
          }

          if (count($route_points) > 0) {
            $route_obj = array(
              'title' => $title,
              'points' => $route_points,
              'route_url' => get_permalink(),
              'blog_url' => $blog ? get_permalink($blog) : '',
              'image_url' => $image_url ? $image_url : '',
              'poi_waypoints' => $poi_waypoints,
              'tags' => $tags
            );
            echo "routes.push(" . json_encode($route_obj) . ");\n";
          }

        endif;

      endwhile;
      wp_reset_postdata();
    else :
      echo "console.error('No routes found.');";
    endif;
    ?>
  </script>

  <script>
    var pointsOfInterest = [];
    <?php
    $poi_args = array(
      'post_type'      => 'point-of-interest',
      'posts_per_page' => -1,
      'post_status'    => 'publish',
    );

    $poi_query = new WP_Query($poi_args);

    if ($poi_query->have_posts()) :
      while ($poi_query->have_posts()) : $poi_query->the_post();
        $lat = get_field('interest_latitude');
        $lng = get_field('interest_longitude');
        $entry = get_field('interest_entry');
        $image = get_field('interest_image');
        $custom_gmaps_url = get_field('has_a_google_maps_card');

        if (!empty($lat) && !empty($lng)) {
          $poi_obj = array(
            'lat' => $lat,
            'lng' => $lng,
            'entry_url' => get_permalink(),
            'image_url' => $image ? $image : '',
            'title' => get_the_title(),
            'google_maps_url' => $custom_gmaps_url ? esc_url($custom_gmaps_url) : ''
          );
          echo "pointsOfInterest.push(" . json_encode($poi_obj) . ");\n";
        }
      endwhile;
      wp_reset_postdata();
    endif;
    ?>
  </script>

  <script>
    document.addEventListener('DOMContentLoaded', function () {

      if (!routes || routes.length === 0) {
        console.error('No routes to display.');
        return;
      }

      // --- MAPA ---
      var fallbackCenter = [9.2443, -65.9320];
      var fallbackZoom = 12;

      var map = L.map('map', { zoomControl: false }).setView(fallbackCenter, fallbackZoom);

      L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors © <a href="https://carto.com/attributions">CARTO</a>'
      }).addTo(map);

      L.control.zoom({ position: 'bottomright' }).addTo(map);

      L.control.locate({
        position: 'bottomright',
        flyTo: true,
        keepCurrentZoomLevel: true,
        showCompass: true,
        drawCircle: true,
        showPopup: false,
        strings: { title: "Centrar en mi ubicación" }
      }).addTo(map);

      // Capas: rutas y POIs (para poder mostrar/ocultar)
      var routesLayer = L.layerGroup().addTo(map);
      var poiLayer = L.layerGroup().addTo(map);

      // --- Iconos custom ---
      function makePinIcon(color) {
        return L.divIcon({
          className: '',
          html: `<svg xmlns="http://www.w3.org/2000/svg" width="22" height="30" viewBox="0 0 22 30">
            <path d="M11 0C4.925 0 0 4.925 0 11c0 8.25 11 19 11 19S22 19.25 22 11C22 4.925 17.075 0 11 0z" fill="${color}"/>
            <circle cx="11" cy="11" r="4.5" fill="#0a0a0a"/>
          </svg>`,
          iconSize: [22, 30],
          iconAnchor: [11, 30],
          popupAnchor: [0, -32]
        });
      }
      var routeIcon = makePinIcon('#0df246');
      var poiIcon   = makePinIcon('#ff6b00');

      // --- Normalizador para comparar tags sin problemas ---
      function norm(s){ return String(s || '').trim().toLowerCase(); }

      // --- FILTROS UI ---
      var filtersEl = document.getElementById('route-filters');

      // extraer tags únicos
      var tagSet = new Set();
      routes.forEach(function (r) {
        (r.tags || []).forEach(function (t) {
          t = String(t || '').trim();
          if (t) tagSet.add(t);
        });
      });

      var tags = Array.from(tagSet).sort();

      // ✅ Botones: Todos, Solo POIs, luego tags
      var filterButtons = ['Todos', 'Solo POIs'].concat(tags);

      filtersEl.innerHTML = filterButtons.map(function (t, idx) {
        var isActive = idx === 0 ? 'is-active' : '';
        var value =
          (t === 'Todos') ? 'all' :
          (t === 'Solo POIs') ? 'pois' :
          t; // tag

        return `<li><button type="button" class="route-filter ${isActive}" data-tag="${value}">${t}</button></li>`;
      }).join('');

      // --- DIBUJAR POIs (siempre, pero en su layer) ---
      function drawPOIs() {
        poiLayer.clearLayers();

        pointsOfInterest.forEach(function (poi) {
          var lat = parseFloat(poi.lat);
          var lng = parseFloat(poi.lng);
          if (isNaN(lat) || isNaN(lng)) return;

          var popupHtml = `<strong>${poi.title || ''}</strong><br>`;
        
          if (poi.image_url) {
            popupHtml += `<img src="${poi.image_url}" alt="${poi.title || ''}" style="max-width: 100%; height: auto; margin: 5px 0;"><br>`;
          }

          if (poi.entry_url) {
            popupHtml += `<a href="${poi.entry_url}" target="_blank">Ver Punto de interés</a><br>`;
          }

          if (poi.google_maps_url && poi.google_maps_url !== '') {
            popupHtml += `<br><a href="${poi.google_maps_url}" target="_blank">Ver en Google Maps</a><br>`;
          } else {
            const googleMapsLink = `https://www.google.com/maps/search/?api=1&query=${lat},${lng}`;
            popupHtml += `<br><a href="${googleMapsLink}" target="_blank" class="map-link-button">Ver en Google Maps</a><br>`;
          }

          

          L.marker([lat, lng], { icon: poiIcon }).addTo(poiLayer).bindPopup(popupHtml);
        });
      }

      // --- DIBUJAR RUTAS (SEGÚN FILTRO) ---
      function drawRoutes(filterTagRaw) {
        var filterTag = norm(filterTagRaw);

        // mostrar/ocultar capas según modo
        if (filterTag === 'pois') {
          routesLayer.clearLayers();
          // POIs visibles
          if (!map.hasLayer(poiLayer)) poiLayer.addTo(map);

          // centrar en POIs
          var poiBounds = L.latLngBounds([]);
          var anyPoi = false;

          pointsOfInterest.forEach(function (poi) {
            var lat = parseFloat(poi.lat);
            var lng = parseFloat(poi.lng);
            if (isNaN(lat) || isNaN(lng)) return;
            poiBounds.extend([lat, lng]);
            anyPoi = true;
          });

          if (anyPoi && poiBounds.isValid()) {
            map.fitBounds(poiBounds, { padding: [30, 30] });
          } else {
            map.setView(fallbackCenter, fallbackZoom);
          }
          return;
        }

        // Modo rutas (all o tag)
        routesLayer.clearLayers();
        // POIs visibles (si quisieras ocultarlos en rutas, comenta estas 2 líneas)
        if (!map.hasLayer(poiLayer)) poiLayer.addTo(map);

        var bounds = L.latLngBounds([]);
        var drewAny = false;

        routes.forEach(function (route) {
          var routePoints = route.points || [];
          if (routePoints.length === 0) return;

          var routeTagsNorm = (route.tags || []).map(norm);
          var matches = (filterTag === 'all') || routeTagsNorm.includes(filterTag);
          if (!matches) return;

          var polyline = L.polyline(routePoints, {
            color: '#0df246',
            weight: 3,
            opacity: 0.85
          }).addTo(routesLayer);

          bounds.extend(polyline.getBounds());
          drewAny = true;

          // marker inicio + popup
          var start = routePoints[0];
          var end = routePoints[routePoints.length - 1];

          var routeTitle = route.title || '';
          var routeUrl = route.route_url || '';
          var blogUrl = route.blog_url || '';
          var imageUrl = route.image_url || '';
          var poiWaypoints = route.poi_waypoints || [];

          let popupHtml = `<strong>${routeTitle}</strong><br>`;

          if (imageUrl) {
            popupHtml += `<img src="${imageUrl}" alt="${routeTitle}" style="max-width: 100%; height: auto; margin-top: 5px; margin-bottom: 5px;"><br>`;
          }

          if (routeUrl) {
            popupHtml += `<a href="${routeUrl}" target="_blank">Ver Ruta</a><br>`;
          }

          if (blogUrl) {
            popupHtml += `<a href="${blogUrl}" target="_blank">Ver Bitácora</a><br>`;
          }

          if (routePoints.length >= 2) {
            const origin = start.join(',');
            const destination = end.join(',');
            const waypoints = poiWaypoints.join('|');
            const googleMapsUrl = `https://www.google.com/maps/dir/?api=1&origin=${origin}&destination=${destination}&waypoints=${encodeURIComponent(waypoints)}`;
            popupHtml += `<br><a href="${googleMapsUrl}" target="_blank" class="map-link-button">Abrir ruta en<br>Google Maps</a>`;
          }

          L.marker(start, { icon: routeIcon }).addTo(routesLayer).bindPopup(popupHtml);
        });

        if (drewAny && bounds.isValid()) {
          map.fitBounds(bounds, { padding: [30, 30] });
        } else {
          map.setView(fallbackCenter, fallbackZoom);
        }
      }

      // Click filtros
      filtersEl.addEventListener('click', function (e) {
        var btn = e.target.closest('.route-filter');
        if (!btn) return;

        filtersEl.querySelectorAll('.route-filter').forEach(function (b) {
          b.classList.remove('is-active');
        });
        btn.classList.add('is-active');

        drawRoutes(btn.dataset.tag);
      });

      // Primera carga
      drawPOIs();
      drawRoutes('all');

    });
  </script>


</section>
