<header class="site-nav">
  <div class="site-nav__inner">

    <!-- Logo -->
    <a href="<?php echo esc_url( home_url('/') ); ?>" class="site-nav__logo">
      <span class="material-symbols-outlined">terrain</span>
      <h1 class="site-name"><?php echo esc_html( get_bloginfo('name') ); ?></h1>
    </a>

    <!-- Nav links (wp_nav_menu) -->
    <?php
    wp_nav_menu([
      'theme_location' => 'menu',
      'container'      => 'nav',
      'container_class'=> 'site-nav__links',
      'fallback_cb'    => false,
    ]);
    ?>

    <!-- Actions -->
    <div class="site-nav__actions" >

      <?php if ( is_single() ) : ?>
        <button class="site-nav__share"
                onclick="navigator.share ? navigator.share({title:document.title,url:location.href}) : (navigator.clipboard?.writeText(location.href), alert('Link copiado'))">
          <span class="material-symbols-outlined">share</span>
          Compartir
        </button>
      <?php endif; ?>

      <a href="<?php echo esc_url( home_url('/') ); ?>#newsletter" class="site-nav__cta" data-animate="fade-up" data-animate-delay="200">
        <span class="material-symbols-outlined">explore</span>
        Explorar
      </a>

    </div>
  </div>
</header>

<div class="nav-spacer"></div>
