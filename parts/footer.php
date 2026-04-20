<?php
$is_guide_context = is_post_type_archive('guide') || is_tax('guide-zone') || (is_singular() && get_post_type() === 'guide');
$is_poi_context   = !$is_guide_context && (is_singular('point-of-interest') || is_post_type_archive('point-of-interest') || is_page_template('pages/page-suggest-route.php'));

if ($is_guide_context) :
  $url = '/login/';
?>
<section class="global-cta" data-animate="fade-up">
  <div class="global-cta__inner">
    <div class="global-cta__text">
      <span class="global-cta__eyebrow">Directory</span>
      <h2 class="global-cta__title">Are you a local guide?</h2>
      <p class="global-cta__desc">Create an account and join the HotFoot Adventure directory and connect with explorers looking for guides in Venezuela.</p>
    </div>
    <a href="<?php echo esc_url($url); ?>" class="btn btn--primary global-cta__btn">
      <span class="material-symbols-outlined">mail</span>
      I want to be a guide
    </a>
  </div>
</section>
<?php else :
  $cta_url  = $is_poi_context ? home_url('/new-poi') : home_url('/new-route');
  $cta_lbl  = $is_poi_context ? 'Suggest a POI'        : 'Suggest a Route';
  $cta_icon = $is_poi_context ? 'location_on'           : 'add_location';
?>
<section class="global-cta" data-animate="fade-up">
  <div class="global-cta__inner">
    <div class="global-cta__text">
      <span class="global-cta__eyebrow">Community</span>
      <h2 class="global-cta__title">Know a place that deserves to be here?</h2>
      <p class="global-cta__desc">The Venezuelan community grows with every shared route and point of interest.</p>
    </div>
    <a href="<?php echo esc_url($cta_url); ?>" class="btn btn--primary global-cta__btn">
      <span class="material-symbols-outlined"><?php echo esc_html($cta_icon); ?></span>
      <?php echo esc_html($cta_lbl); ?>
    </a>
  </div>
</section>
<?php endif; ?>

<footer class="site-footer">

  <div class="site-footer__top">

    <!-- Brand -->
    <div class="site-footer__brand" data-animate="fade-up">
      <a href="<?php echo esc_url( home_url('/') ); ?>" class="brand-logo">
        <span class="material-symbols-outlined">terrain</span>
        <span class="brand-name"><?php echo esc_html( get_bloginfo('name') ); ?></span>
      </a>
      <p class="brand-desc">
        <?php echo esc_html( get_bloginfo('description') ?: 'Exploring Venezuelan territory. Built for explorers.' ); ?>
      </p>
      <div class="brand-social">
        <a href="#" aria-label="Instagram"><span class="material-symbols-outlined">photo_camera</span></a>
        <a href="#" aria-label="RSS"><span class="material-symbols-outlined">rss_feed</span></a>
        <a href="#" aria-label="Share"><span class="material-symbols-outlined">hub</span></a>
      </div>
    </div>

    <!-- Rutas -->
    <div class="site-footer__col" data-animate="fade-up">
      <h4>Resources</h4>
      <nav class="footer-nav">
        <?php wp_nav_menu(['theme_location' => 'menu', 'container' => false, 'fallback_cb' => false]); ?>
      </nav>
    </div>

    <!-- Comunidad -->
    <div class="site-footer__col" data-animate="fade-up">
      <h4>Community</h4>
      <nav class="footer-nav">
        <?php wp_nav_menu(['theme_location' => 'footer', 'container' => false, 'fallback_cb' => false]); ?>
      </nav>
    </div>

    <!-- Ayuda -->
    <div class="site-footer__col" data-animate="fade-up">
      <h4>Help</h4>
      <ul>
        <li><a href="#">Gear Guide</a></li>
        <li><a href="#">Safety</a></li>
        <li><a href="#">Contact</a></li>
      </ul>
    </div>

  </div>

  <div class="site-footer__bottom">
    <div class="site-footer__bottom-inner">
      <span>&copy; <?php echo date('Y'); ?> <?php echo esc_html( get_bloginfo('name') ); ?>. All rights reserved.</span>
      <span>Made with <span class="material-symbols-outlined" style="font-size:12px;color:var(--primary);vertical-align:middle">favorite</span> by
        <a href="https://ve.linkedin.com/in/gabriel-oniel-arias/" target="_blank" rel="noopener">Gabriel Arias</a>
      </span>
    </div>
  </div>

</footer>

<?php wp_footer(); ?>
</body>
</html>
