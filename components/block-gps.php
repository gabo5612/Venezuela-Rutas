<section class="container">

  <!-- FILTROS -->
  <div id="gps-filters" class="gps-filters"></div>

  <!-- MAPA -->
  <div id="map" ></div>

  <script>
    var filterGroups = [];
    <?php
    if (have_rows('filter_groups')) :
      while (have_rows('filter_groups')) : the_row();
        $group_label = get_sub_field('group_label');
        $group_tags  = get_sub_field('group_tags'); // array of term objects or names
        $tag_names   = [];
        if (!empty($group_tags)) {
          foreach ((array) $group_tags as $t) {
            if (is_object($t)) {
              $tag_names[] = $t->name;
            } elseif (is_string($t)) {
              $tag_names[] = $t;
            }
          }
        }
        if ($group_label && !empty($tag_names)) {
          echo "filterGroups.push(" . json_encode(['label' => $group_label, 'tags' => $tag_names]) . ");\n";
        }
      endwhile;
    endif;
    ?>
  </script>

  <script>
    var routes = [];
    <?php
    $args = array(
      'post_type'      => 'routes',
      'posts_per_page' => -1,
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
              $poi_lat = get_field('latitude', $poi);
              $poi_lng = get_field('longitude', $poi);
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
        $lat = get_field('latitude');
        $lng = get_field('longitude');
        $image = get_field('image') ?: get_the_post_thumbnail_url(null, 'medium');
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

      var mapEl = document.getElementById('map');
      var map = L.map('map', { zoomControl: false }).setView(fallbackCenter, fallbackZoom);

      var tileAttrib = '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors © <a href="https://carto.com/attributions">CARTO</a>';
      var tileDark  = L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png',  { attribution: tileAttrib });
      var tileLight = L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', { attribution: tileAttrib });
      var mapActive = false;

      tileDark.addTo(map);

      function activateMap() {
        if (mapActive) return;
        mapActive = true;
        mapEl.classList.add('is-active');
        map.removeLayer(tileDark);
        tileLight.addTo(map);
      }

      // Registrar listeners DESPUÉS de la carga inicial para evitar
      // que fitBounds/setView disparen movestart/zoomstart prematuramente
      function attachMapListeners() {
        map.on('mousedown touchstart', activateMap);
        mapEl.addEventListener('mouseenter', activateMap, { once: true });
      }

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
      var filtersEl = document.getElementById('gps-filters');

      // Extraer tags únicos presentes en rutas cargadas
      var tagSet = new Set();
      routes.forEach(function (r) {
        (r.tags || []).forEach(function (t) {
          t = String(t || '').trim();
          if (t) tagSet.add(t);
        });
      });

      // Construir grupos válidos (solo con tags que existan en rutas)
      var validGroups = [];
      filterGroups.forEach(function (group) {
        var validTags = (group.tags || []).filter(function (t) {
          return tagSet.has(String(t || '').trim());
        });
        if (group.label && validTags.length > 0) {
          validGroups.push({ label: group.label, tags: validTags });
        }
      });

      // Construir HTML de filtros
      // Píldoras fijas
      var html = '<button type="button" class="gps-pill is-active" data-filter="all" data-filter-type="all">Todos</button>';
      html += '<button type="button" class="gps-pill" data-filter="pois" data-filter-type="pois">Solo POIs</button>';

      // Grupo como píldora con sub-tags desplegables
      validGroups.forEach(function (group, i) {
        var groupId = 'gps-group-' + i;
        var groupTagsNorm = group.tags.map(norm).join(',');
        html += `<div class="gps-dropdown" id="${groupId}-wrap">
          <div class="gps-group-pill">
            <button type="button" class="gps-pill gps-pill--group" data-filter-type="group" data-group-tags="${groupTagsNorm}" data-group-id="${groupId}">${group.label}</button><button type="button" class="gps-pill__expand" data-group-id="${groupId}" aria-label="Ver sub-filtros">▾</button>
          </div>
          <div class="gps-panel" id="${groupId}-panel">`;
        group.tags.forEach(function (t) {
          html += `<button type="button" class="gps-tag" data-filter-type="tag" data-filter="${t}">${t}</button>`;
        });
        html += `</div></div>`;
      });

      filtersEl.innerHTML = `<div class="gps-filters__inner">${html}</div>`;

      // Panels: abrir/cerrar expand arrows
      filtersEl.querySelectorAll('.gps-pill__expand').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
          e.stopPropagation();
          var gid    = btn.dataset.groupId;
          var panel  = document.getElementById(gid + '-panel');
          var isOpen = panel.classList.toggle('is-open');
          btn.classList.toggle('is-open', isOpen);
          // Cerrar los demás
          filtersEl.querySelectorAll('.gps-panel').forEach(function (p) {
            if (p !== panel) {
              p.classList.remove('is-open');
              var otherId = p.id.replace('-panel', '');
              var otherBtn = filtersEl.querySelector(`.gps-pill__expand[data-group-id="${otherId}"]`);
              if (otherBtn) otherBtn.classList.remove('is-open');
            }
          });
        });
      });

      // Cerrar todos los panels al hacer click fuera (sin bloquear clicks en tags)
      document.addEventListener('click', function (e) {
        if (!filtersEl.contains(e.target)) {
          filtersEl.querySelectorAll('.gps-panel').forEach(function (p) { p.classList.remove('is-open'); });
          filtersEl.querySelectorAll('.gps-pill__expand').forEach(function (b) { b.classList.remove('is-open'); });
        }
      });

      // Marcar visualmente el filtro activo
      function setActiveFilter(filterType, filterValue) {
        filtersEl.querySelectorAll('.gps-pill, .gps-pill__expand').forEach(function (b) { b.classList.remove('is-active'); });
        filtersEl.querySelectorAll('.gps-tag').forEach(function (b) { b.classList.remove('is-active'); });

        if (filterType === 'all' || filterType === 'pois') {
          var pill = filtersEl.querySelector(`.gps-pill[data-filter="${filterValue}"]`);
          if (pill) pill.classList.add('is-active');
        } else if (filterType === 'group') {
          var groupBtn = filtersEl.querySelector(`.gps-pill--group[data-group-id="${filterValue}"]`);
          if (groupBtn) {
            groupBtn.classList.add('is-active');
            var expandBtn = filtersEl.querySelector(`.gps-pill__expand[data-group-id="${filterValue}"]`);
            if (expandBtn) expandBtn.classList.add('is-active');
          }
        } else if (filterType === 'tag') {
          var tagBtn = filtersEl.querySelector(`.gps-tag[data-filter="${filterValue}"]`);
          if (tagBtn) {
            tagBtn.classList.add('is-active');
            // Marcar también el grupo padre
            var parentDrop = tagBtn.closest('.gps-dropdown');
            if (parentDrop) {
              var gid = parentDrop.id.replace('-wrap', '');
              var parentPill = filtersEl.querySelector(`.gps-pill--group[data-group-id="${gid}"]`);
              var parentExpand = filtersEl.querySelector(`.gps-pill__expand[data-group-id="${gid}"]`);
              if (parentPill) parentPill.classList.add('is-active');
              if (parentExpand) parentExpand.classList.add('is-active');
            }
          }
        }
      }

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
      // mode: 'all' | 'pois' | 'group' | 'tag'
      // activeTags: array de strings normalizados (para group/tag)
      function drawRoutes(mode, activeTags) {
        activeTags = (activeTags || []).map(norm);

        // mostrar/ocultar capas según modo
        if (mode === 'pois') {
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
          var matches = (mode === 'all') ||
                        (mode === 'group' && activeTags.some(function (t) { return routeTagsNorm.includes(t); })) ||
                        (mode === 'tag'   && activeTags.some(function (t) { return routeTagsNorm.includes(t); }));
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
        var btn = e.target.closest('[data-filter-type]');
        if (!btn) return;
        if (btn.classList.contains('gps-pill__expand')) return;

        // Activar el mapa al interactuar con filtros (mobile + desktop)
        activateMap();

        var filterType  = btn.dataset.filterType;
        var filterValue = btn.dataset.filter || btn.dataset.groupId;

        // Toggle: si ya está activo, volver a "Todos"
        if (btn.classList.contains('is-active') && filterType !== 'all') {
          setActiveFilter('all', 'all');
          drawRoutes('all', []);
          btn.blur(); // limpiar estado visual en mobile
          return;
        }

        setActiveFilter(filterType, filterValue);

        // Cerrar panel si viene de un sub-tag
        if (filterType === 'tag') {
          var parentPanel = btn.closest('.gps-panel');
          if (parentPanel) parentPanel.classList.remove('is-open');
          var dropWrap = btn.closest('.gps-dropdown');
          if (dropWrap) {
            var gid = dropWrap.id.replace('-wrap', '');
            var expandBtn = filtersEl.querySelector(`.gps-pill__expand[data-group-id="${gid}"]`);
            if (expandBtn) expandBtn.classList.remove('is-open');
          }
        }

        // Llamar drawRoutes
        if (filterType === 'all') {
          drawRoutes('all', []);
        } else if (filterType === 'pois') {
          drawRoutes('pois', []);
        } else if (filterType === 'group') {
          var groupTagsRaw = btn.dataset.groupTags || '';
          drawRoutes('group', groupTagsRaw.split(','));
        } else if (filterType === 'tag') {
          drawRoutes('tag', [norm(filterValue)]);
        }

        btn.blur(); // limpiar estado :focus/:hover en mobile tras seleccionar
      });

      // Primera carga — los listeners se registran después para que
      // fitBounds/setView no activen el mapa prematuramente
      drawPOIs();
      drawRoutes('all', []);
      setTimeout(attachMapListeners, 600);

    });
  </script>


</section>
