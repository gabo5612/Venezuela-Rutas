<?php get_template_part('parts/header'); ?>

<div class="page-404">

  <!-- ══ MAPA "SIGNAL LOST" ════════════════════════════ -->
  <div class="page-404__map-wrap container">
    <div id="map-404"></div>

    <!-- Overlay top-left: terminal info -->
    <div class="page-404__signal">
      <span>SIGNAL: LOST</span>
      <span>LAT: 0.00000</span>
      <span>LONG: 0.00000</span>
    </div>

    <!-- Overlay bottom-right: error code -->
    <div class="page-404__errcode">[ERR_ROUTE_EXPIRED_404]</div>
  </div>

  <!-- ══ CONTENIDO ════════════════════════════════════ -->
  <div class="page-404__body container">

    <h1 class="page-404__title">Coordenadas No<br>Encontradas</h1>

    <p class="page-404__desc">
      Parece que te saliste de la ruta. El terreno es desconocido
      o el enlace ha expirado. Regresa a terreno seguro antes de
      que caiga la noche.
    </p>

    <!-- Terminal search -->
    <form role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>" class="page-404__terminal">
      <div class="page-404__terminal-bar">
        <span class="material-symbols-outlined">terminal</span>
        SYSTEM SHELL V4.0.4
      </div>
      <div class="page-404__terminal-input-wrap">
        <span class="page-404__terminal-prompt">&gt;</span>
        <input type="search" name="s" class="page-404__terminal-input"
               placeholder="BUSCAR_SALIDA" autocomplete="off" spellcheck="false">
        <span class="page-404__terminal-cursor"></span>
      </div>
    </form>

    <!-- CTAs -->
    <div class="page-404__actions">
      <a href="<?php echo esc_url(home_url('/')); ?>" class="btn btn--primary">
        <span class="material-symbols-outlined">location_on</span>
        Volver al Campamento Base
      </a>
      <a href="<?php echo esc_url(get_post_type_archive_link('routes') ?: home_url('/')); ?>" class="btn btn--outline">
        <span class="material-symbols-outlined">map</span>
        Ver Todas las Rutas
      </a>
    </div>

  </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var map = L.map('map-404', {
    center:            [8.6, -66.2],
    zoom:              7,
    zoomControl:       false,
    scrollWheelZoom:   false,
    doubleClickZoom:   false,
    dragging:          false,
    touchZoom:         false,
    keyboard:          false,
    attributionControl: false,
  });

  L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
    maxZoom: 19,
  }).addTo(map);

  // Pulsing "lost" marker at map center
  var icon = L.divIcon({
    className: '',
    html: '<div class="lost-pin"><div class="lost-pin__dot"></div><div class="lost-pin__ring"></div></div>',
    iconSize:   [40, 40],
    iconAnchor: [20, 20],
  });

  L.marker([8.6, -66.2], { icon: icon }).addTo(map);
});
</script>

<?php get_template_part('parts/footer'); ?>
