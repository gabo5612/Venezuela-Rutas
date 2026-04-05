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
    <div class="site-nav__actions">

      <?php if ( is_single() ) : ?>
        <button class="site-nav__share"
                onclick="navigator.share ? navigator.share({title:document.title,url:location.href}) : (navigator.clipboard?.writeText(location.href), alert('Link copied'))">
          <span class="material-symbols-outlined">share</span>
          Share
        </button>
      <?php endif; ?>

      <!-- User account button -->
      <?php if ( is_user_logged_in() ) :
        $current_user = wp_get_current_user();
        $avatar_id    = get_user_meta( $current_user->ID, 'hfa_avatar_id', true );
        $avatar_url   = $avatar_id ? wp_get_attachment_image_url( $avatar_id, 'thumbnail' ) : '';
        $profile_url  = get_author_posts_url( $current_user->ID );
      ?>
      <div class="site-nav__user">
        <a href="<?php echo esc_url($profile_url); ?>" class="site-nav__user-btn" aria-label="My account">
          <?php if ( $avatar_url ) : ?>
            <img src="<?php echo esc_url($avatar_url); ?>" alt="" class="site-nav__user-avatar">
          <?php else : ?>
            <span class="material-symbols-outlined">account_circle</span>
          <?php endif; ?>
          <span class="site-nav__user-name"><?php echo esc_html( $current_user->display_name ); ?></span>
        </a>
      </div>
      <?php else : ?>
      <a href="<?php echo esc_url( get_permalink( get_page_by_path('login') ) ?: wp_login_url( get_permalink() ) ); ?>"
         class="site-nav__user-btn site-nav__user-btn--guest" aria-label="Sign in">
        <span class="material-symbols-outlined">person</span>
      </a>
      <?php endif; ?>

      <?php if ( class_exists('WooCommerce') ) : ?>
      <a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="site-nav__cart" aria-label="Cart">
        <span class="material-symbols-outlined">shopping_cart</span>
        <?php $cart_count = WC()->cart ? WC()->cart->get_cart_contents_count() : 0; ?>
        <?php if ( $cart_count > 0 ) : ?>
        <span class="site-nav__cart-count"><?php echo esc_html($cart_count); ?></span>
        <?php endif; ?>
      </a>
      <?php endif; ?>

      <a href="<?php echo esc_url( home_url('/') ); ?>#gps-filters" class="site-nav__cta" data-animate="fade-up" data-animate-delay="200">
        <span class="material-symbols-outlined">explore</span>
        <span class="mobile-span">Explore</span>
      </a>

    </div>
  </div>
</header>

<div class="nav-spacer"></div>
