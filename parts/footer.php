<footer class="site-footer">

  <div class="site-footer__top">

    <!-- Brand -->
    <div class="site-footer__brand">
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
    <div class="site-footer__col">
      <h4>Rutas</h4>
      <nav class="footer-nav">
        <?php wp_nav_menu(['theme_location' => 'menu', 'container' => false, 'fallback_cb' => false]); ?>
      </nav>
    </div>

    <!-- Comunidad -->
    <div class="site-footer__col">
      <h4>Comunidad</h4>
      <nav class="footer-nav">
        <?php wp_nav_menu(['theme_location' => 'footer', 'container' => false, 'fallback_cb' => false]); ?>
      </nav>
    </div>

    <!-- Recursos -->
    <div class="site-footer__col">
      <h4>Recursos</h4>
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
