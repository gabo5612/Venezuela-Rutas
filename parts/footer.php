<?php
// CTA global: en POI → /nuevo-poi, en el resto → /nueva-ruta
$is_poi_context = is_singular('point-of-interest') || is_post_type_archive('point-of-interest') || is_page_template('pages/page-suggest-route.php');
$cta_url  = $is_poi_context ? home_url('/nuevo-poi')   : home_url('/nueva-ruta');
$cta_lbl  = $is_poi_context ? 'Sugerir un POI'        : 'Sugerir una Ruta';
$cta_icon = $is_poi_context ? 'location_on'            : 'add_location';
?>
<section class="global-cta" data-animate="fade-up">
  <div class="global-cta__inner">
    <div class="global-cta__text">
      <span class="global-cta__eyebrow">Comunidad</span>
      <h2 class="global-cta__title">¿Conoces un lugar que merece estar aquí?</h2>
      <p class="global-cta__desc">La comunidad venezolana crece con cada ruta y punto de interés compartido.</p>
    </div>
    <a href="<?php echo esc_url($cta_url); ?>" class="btn btn--primary global-cta__btn">
      <span class="material-symbols-outlined"><?php echo esc_html($cta_icon); ?></span>
      <?php echo esc_html($cta_lbl); ?>
    </a>
  </div>
</section>

<footer class="site-footer">

  <div class="site-footer__top">

    <!-- Brand -->
    <div class="site-footer__brand" data-animate="fade-up">
      <a href="<?php echo esc_url( home_url('/') ); ?>" class="brand-logo">
        <span class="material-symbols-outlined">terrain</span>
        <span class="brand-name"><?php echo esc_html( get_bloginfo('name') ); ?></span>
      </a>
      <p class="brand-desc">
        <?php echo esc_html( get_bloginfo('description') ?: 'Explorando el territorio venezolano. Construido para los exploradores.' ); ?>
      </p>
      <div class="brand-social">
        <a href="#" aria-label="Instagram"><span class="material-symbols-outlined">photo_camera</span></a>
        <a href="#" aria-label="RSS"><span class="material-symbols-outlined">rss_feed</span></a>
        <a href="#" aria-label="Share"><span class="material-symbols-outlined">hub</span></a>
      </div>
    </div>

    <!-- Rutas -->
    <div class="site-footer__col" data-animate="fade-up">
      <h4>Recursos</h4>
      <nav class="footer-nav">
        <?php wp_nav_menu(['theme_location' => 'menu', 'container' => false, 'fallback_cb' => false]); ?>
      </nav>
    </div>

    <!-- Comunidad -->
    <div class="site-footer__col" data-animate="fade-up">
      <h4>Comunidad</h4>
      <nav class="footer-nav">
        <?php wp_nav_menu(['theme_location' => 'footer', 'container' => false, 'fallback_cb' => false]); ?>
      </nav>
    </div>

    <!-- Ayuda -->
    <div class="site-footer__col" data-animate="fade-up">
      <h4>Ayuda</h4>
      <ul>
        <li><a href="#">Guía de Equipo</a></li>
        <li><a href="#">Seguridad</a></li>
        <li><a href="#">Contacto</a></li>
      </ul>
    </div>

  </div>

  <div class="site-footer__bottom">
    <div class="site-footer__bottom-inner">
      <span>&copy; <?php echo date('Y'); ?> <?php echo esc_html( get_bloginfo('name') ); ?>. Todos los derechos reservados.</span>
      <span>Hecho con <span class="material-symbols-outlined" style="font-size:12px;color:var(--primary);vertical-align:middle">favorite</span> por
        <a href="https://ve.linkedin.com/in/gabriel-oniel-arias/" target="_blank" rel="noopener">Gabriel Arias</a>
      </span>
    </div>
  </div>

</footer>

<?php wp_footer(); ?>
</body>
</html>
