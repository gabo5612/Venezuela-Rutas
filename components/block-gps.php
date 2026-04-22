<section class="container">

  <!-- FILTERS -->
  <div id="gps-filters" class="gps-filters"></div>

  <!-- MAP -->
  <div id="map" ></div>

  <script>
    <?php
    // ── Prefiltro por contexto (tag.php, archive CPT, URL param) ──────────
    $gps_prefilter = ['type' => 'all', 'value' => ''];

    $url_gps = isset($_GET['gps']) ? sanitize_text_field(wp_unslash($_GET['gps'])) : '';
    if ($url_gps === 'pois') {
      $gps_prefilter = ['type' => 'pois', 'value' => 'pois'];
    } elseif ($url_gps) {
      $gps_prefilter = ['type' => 'tag', 'value' => $url_gps];
    } elseif (is_tag()) {
      $current_tag = get_queried_object();
      $gps_prefilter = ['type' => 'tag', 'value' => $current_tag->name];
    } elseif (is_post_type_archive('point-of-interest')) {
      $gps_prefilter = ['type' => 'pois', 'value' => 'pois'];
    }
    // routes archive → modo 'all' por defecto (no hace falta caso especial)
    ?>
    var gpsPrefilter = <?php echo wp_json_encode($gps_prefilter); ?>;
  </script>

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
          $group_color = get_sub_field('group_color') ?: '';
          $group_dash  = get_sub_field('group_dash')  ?: '';
          echo "filterGroups.push(" . json_encode(['label' => $group_label, 'tags' => $tag_names, 'color' => $group_color, 'dash' => $group_dash]) . ");\n";
        }
      endwhile;
    endif;
    ?>
  </script>

  <?php
  // ── Asegurar que los transients existan (se generan en background si faltan) ──
  // El endpoint AJAX los leerá; si no existen los genera el handler
  if (get_transient('gps_block_routes') === false) {
    $routes_data = [];
    $routes_query = new WP_Query(['post_type' => 'routes', 'posts_per_page' => -1]);
    if ($routes_query->have_posts()) :
      while ($routes_query->have_posts()) : $routes_query->the_post();
        $points    = get_field('points');
        $blog      = get_field('blog_entry');
        $image_url = get_field('image');
        $tags      = wp_get_post_terms(get_the_ID(), 'post_tag', ['fields' => 'names']);
        if (is_wp_error($tags)) $tags = [];
        if ($points) :
          $route_points = []; $poi_waypoints = [];
          foreach ($points as $p) {
            if (!empty($p['latitude']) && !empty($p['longitude']))
              $route_points[] = [$p['latitude'], $p['longitude']];
          }
          $poi_posts = get_field('route_point_of_interest');
          if (!empty($poi_posts)) {
            foreach ((array) $poi_posts as $poi) {
              $plat = get_field('latitude', $poi); $plng = get_field('longitude', $poi);
              if (!empty($plat) && !empty($plng)) $poi_waypoints[] = $plat . ',' . $plng;
            }
          }
          if (count($route_points) > 0) {
            $routes_data[] = ['title' => get_the_title(), 'points' => $route_points,
              'route_url' => get_permalink(), 'blog_url' => $blog ? get_permalink($blog) : '',
              'image_url' => $image_url ?: '', 'poi_waypoints' => $poi_waypoints, 'tags' => $tags];
          }
        endif;
      endwhile;
      wp_reset_postdata();
    endif;
    set_transient('gps_block_routes', $routes_data, 12 * HOUR_IN_SECONDS);
  }
  if (get_transient('gps_block_pois') === false) {
    $pois_data = [];
    $poi_query = new WP_Query(['post_type' => 'point-of-interest', 'posts_per_page' => -1, 'post_status' => 'publish']);
    if ($poi_query->have_posts()) :
      while ($poi_query->have_posts()) : $poi_query->the_post();
        $lat = get_field('latitude'); $lng = get_field('longitude');
        $image = get_field('image') ?: get_the_post_thumbnail_url(null, 'medium');
        $gmaps = get_field('has_a_google_maps_card');
        $poi_tags = wp_get_post_terms(get_the_ID(), 'post_tag', ['fields' => 'names']);
        if (is_wp_error($poi_tags)) $poi_tags = [];
        if (!empty($lat) && !empty($lng)) {
          $pois_data[] = ['lat' => $lat, 'lng' => $lng, 'entry_url' => get_permalink(),
            'image_url' => $image ?: '', 'title' => get_the_title(),
            'google_maps_url' => $gmaps ? esc_url($gmaps) : '',
            'tags' => $poi_tags];
        }
      endwhile;
      wp_reset_postdata();
    endif;
    set_transient('gps_block_pois', $pois_data, 12 * HOUR_IN_SECONDS);
  }
  ?>

  <script>
    function initGpsMap() {

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
        strings: { title: "Center on my location" }
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

      var GROUP_PALETTE = ['#0df246', '#4fc3f7', '#ff9800', '#ce93d8', '#f06292', '#80cbc4', '#ffeb3b'];
      var tagColorMap = {};
      var tagDashMap  = {};
      filterGroups.forEach(function (group, i) {
        var color = group.color || GROUP_PALETTE[i % GROUP_PALETTE.length];
        var dash  = group.dash  || null;
        group._color = color;
        group._dash  = dash;
        (group.tags || []).forEach(function (t) {
          tagColorMap[norm(t)] = color;
          tagDashMap[norm(t)]  = dash;
        });
      });
      function getRouteStyle(tags) {
        for (var i = 0; i < (tags || []).length; i++) {
          var key = norm(tags[i]);
          if (tagColorMap[key]) return { color: tagColorMap[key], dash: tagDashMap[key] || null };
        }
        return { color: '#aaaaaa', dash: null };
      }

      // --- FILTROS UI ---
      var filtersEl = document.getElementById('gps-filters');

      // Extraer tags únicos presentes en rutas Y POIs
      var tagSet = new Set();
      routes.forEach(function (r) {
        (r.tags || []).forEach(function (t) { t = String(t||'').trim(); if (t) tagSet.add(t); });
      });
      pointsOfInterest.forEach(function (p) {
        (p.tags || []).forEach(function (t) { t = String(t||'').trim(); if (t) tagSet.add(t); });
      });

      // Construir grupos válidos (con tags que existan en rutas o POIs), preservando color y dash
      var validGroups = [];
      filterGroups.forEach(function (group) {
        var validTags = (group.tags || []).filter(function (t) {
          return tagSet.has(String(t || '').trim());
        });
        if (group.label && validTags.length > 0) {
          validGroups.push({ label: group.label, tags: validTags, _color: group._color, _dash: group._dash });
        }
      });

      // Construir HTML de filtros
      // Píldoras fijas
      var html = '<button type="button" class="gps-pill is-active" data-filter="all" data-filter-type="all">All</button>';
      html += '<button type="button" class="gps-pill" data-filter="pois" data-filter-type="pois">POIs Only</button>';

      // Grupo como píldora con sub-tags desplegables
      validGroups.forEach(function (group, i) {
        var groupId = 'gps-group-' + i;
        var groupTagsNorm = group.tags.map(norm).join(',');
        html += `<div class="gps-dropdown" id="${groupId}-wrap">
          <div class="gps-group-pill">
            <button type="button" class="gps-pill gps-pill--group" data-filter-type="group" data-group-tags="${groupTagsNorm}" data-group-id="${groupId}" style="--pill-color:${group._color}"><span class="gps-pill__dot"></span>${group.label}</button><button type="button" class="gps-pill__expand" data-group-id="${groupId}" aria-label="Ver sub-filtros">▾</button>
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

      // --- DIBUJAR POIs (filtrables por tags) ---
      function drawPOIs(activeTags) {
        poiLayer.clearLayers();
        var filterTags = (activeTags || []).map(norm);

        pointsOfInterest.forEach(function (poi) {
          if (filterTags.length > 0) {
            var poiTagsNorm = (poi.tags || []).map(norm);
            if (!filterTags.some(function(t){ return poiTagsNorm.includes(t); })) return;
          }
          var lat = parseFloat(poi.lat);
          var lng = parseFloat(poi.lng);
          if (isNaN(lat) || isNaN(lng)) return;

          var popupHtml = `<strong>${poi.title || ''}</strong><br>`;
        
          if (poi.image_url) {
            popupHtml += `<img src="${poi.image_url}" alt="${poi.title || ''}" style="max-width: 100%; height: auto; margin: 5px 0;"><br>`;
          }

          if (poi.entry_url) {
            popupHtml += `<a href="${poi.entry_url}" target="_blank">View Point of Interest</a><br>`;
          }

          if (poi.google_maps_url && poi.google_maps_url !== '') {
            popupHtml += `<br><a href="${poi.google_maps_url}" target="_blank" class="map-link-button" style="text-align:center;display:block">View on Google Maps</a><br>`;
          } else {
            const googleMapsLink = `https://www.google.com/maps/search/?api=1&query=${lat},${lng}`;
            popupHtml += `<br><a href="${googleMapsLink}" target="_blank" class="map-link-button" style="text-align:center;display:block">View on Google Maps</a><br>`;
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
          drawPOIs([]);
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

        // Modo rutas (all o tag/group)
        routesLayer.clearLayers();
        var poiFilterTags = (mode === 'all') ? [] : activeTags;
        drawPOIs(poiFilterTags);
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

          var style   = getRouteStyle(route.tags);
          var polyline = L.polyline(routePoints, {
            color:     style.color,
            weight:    3,
            opacity:   0.8,
            dashArray: style.dash
          }).addTo(routesLayer);

          polyline.on('mouseover', function () {
            this.setStyle({ opacity: 1, weight: 5, dashArray: null });
            this.bringToFront();
          });
          polyline.on('mouseout', function () {
            this.setStyle({ opacity: 0.8, weight: 3, dashArray: style.dash });
          });

          bounds.extend(polyline.getBounds());
          drewAny = true;

          var start = routePoints[0];
          var end = routePoints[routePoints.length - 1];
          var routeTitle = route.title || '';
          var routeUrl = route.route_url || '';
          var blogUrl = route.blog_url || '';
          var imageUrl = route.image_url || '';
          var poiWaypoints = route.poi_waypoints || [];

          let popupHtml = `<strong>${routeTitle}</strong><br>`;
          if (imageUrl) popupHtml += `<img src="${imageUrl}" alt="${routeTitle}" style="max-width:100%;height:auto;margin:5px 0"><br>`;
          if (routeUrl) popupHtml += `<a href="${routeUrl}" target="_blank">View Route</a><br>`;
          if (blogUrl)  popupHtml += `<a href="${blogUrl}" target="_blank">View Journal</a><br>`;
          if (routePoints.length >= 2) {
            var isMobile = window.matchMedia('(max-width: 1023px)').matches;
            var googleMapsUrl;
            if (isMobile) {
              var daddrParts = [start.join(',')].concat(poiWaypoints).concat([end.join(',')]);
              googleMapsUrl = 'https://maps.google.com/maps?daddr=' + daddrParts.join('+to:') + '&directionsmode=driving';
            } else {
              googleMapsUrl = 'https://www.google.com/maps/dir/?api=1'
                + '&origin='      + start.join(',')
                + '&destination=' + end.join(',')
                + (poiWaypoints.length ? '&waypoints=' + encodeURIComponent(poiWaypoints.join('|')) : '')
                + '&travelmode=driving';
            }
            popupHtml += `<br><a href="${googleMapsUrl}" target="_blank" class="map-link-button" style="text-align:center;display:block">View on Google Maps</a>`;
          }
          L.marker(start, { icon: routeIcon }).addTo(routesLayer).bindPopup(popupHtml);
        });

        // Also extend bounds with matching POIs so centering works for POI-only tag groups
        if (mode !== 'all') {
          pointsOfInterest.forEach(function (poi) {
            var poiTagsNorm = (poi.tags || []).map(norm);
            if (!activeTags.some(function(t){ return poiTagsNorm.includes(t); })) return;
            var plat = parseFloat(poi.lat), plng = parseFloat(poi.lng);
            if (!isNaN(plat) && !isNaN(plng)) { bounds.extend([plat, plng]); drewAny = true; }
          });
        }

        if (drewAny && bounds.isValid()) {
          map.fitBounds(bounds, { padding: [30, 30] });
        } else {
          map.setView(fallbackCenter, fallbackZoom);
        }
      }

      // ── Sincronizar URL sin recargar la página ───────────────────────────
      function updateUrl(filterType, filterValue) {
        var params = new URLSearchParams(window.location.search);
        if (filterType === 'all') {
          params.delete('gps');
        } else if (filterType === 'pois') {
          params.set('gps', 'pois');
        } else if (filterType === 'tag') {
          params.set('gps', filterValue);
        }
        var newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '') + window.location.hash;
        history.replaceState(null, '', newUrl);
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
          updateUrl('all', '');
          btn.blur();
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

        // Llamar drawRoutes + sincronizar URL
        if (filterType === 'all') {
          drawRoutes('all', []);
          updateUrl('all', '');
        } else if (filterType === 'pois') {
          drawRoutes('pois', []);
          updateUrl('pois', '');
        } else if (filterType === 'group') {
          var groupTagsRaw = btn.dataset.groupTags || '';
          drawRoutes('group', groupTagsRaw.split(','));
          // grupos no sincronizan URL
        } else if (filterType === 'tag') {
          drawRoutes('tag', [norm(filterValue)]);
          updateUrl('tag', filterValue);
        }

        btn.blur();
      });

      // ── Primera carga ─────────────────────────────────────────────────────
      drawPOIs([]);

      // URL param tiene prioridad sobre contexto PHP
      var urlGps = new URLSearchParams(window.location.search).get('gps');
      var prefilter = urlGps
        ? (urlGps === 'pois' ? { type: 'pois', value: 'pois' } : { type: 'tag', value: urlGps })
        : (gpsPrefilter || { type: 'all', value: '' });

      if (prefilter.type && prefilter.type !== 'all') {
        // Aplicar prefiltro
        if (prefilter.type === 'pois') {
          setActiveFilter('pois', 'pois');
          drawRoutes('pois', []);
        } else if (prefilter.type === 'tag') {
          setActiveFilter('tag', prefilter.value);
          drawRoutes('tag', [norm(prefilter.value)]);
        }
        activateMap();
        // Scroll al mapa con offset del header fijo
        setTimeout(function () {
          var offsetTop = filtersEl.getBoundingClientRect().top + window.scrollY - 75;
          window.scrollTo({ top: offsetTop, behavior: 'smooth' });
        }, 400);
      } else {
        drawRoutes('all', []);
      }

      // Los listeners se registran después para que fitBounds no active el mapa
      setTimeout(attachMapListeners, 600);
    }

    // ── Lazy init: fetch datos + arrancar mapa cuando entra en viewport ──
    var routes           = [];
    var pointsOfInterest = [];
    var _mapInited = false;
    var _observer  = new IntersectionObserver(function (entries) {
      if (entries[0].isIntersecting && !_mapInited) {
        _mapInited = true;
        _observer.disconnect();
        fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>?action=gps_map_data', { credentials: 'same-origin' })
          .then(function(r) { return r.json(); })
          .then(function(data) {
            routes           = data.routes || [];
            pointsOfInterest = data.pois   || [];
            initGpsMap();
          });
      }
    }, { rootMargin: '200px' });
    _observer.observe(document.getElementById('map'));
  </script>


</section>
