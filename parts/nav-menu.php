<nav>
    <div class="nav-container container">
    <div>
        <a href="<?php echo home_url(); ?>" class="logo">
            <!--<img src="<?php echo get_template_directory_uri(); ?>/assets/images/logo.png" alt="Logo Minute Tips"> -->
           <h1>X Minute.Tips</h1>
        </a>

    </div>
        <!-- Menú -->
    <div class="nav_menu" id="navMenu">
        <?php
        wp_nav_menu(array(
            'theme_location' => 'menu',
            'container' => false,
            'menu_class' => 'nav_menu_items',
            'fallback_cb' => false,
        ));
        ?>
    </div>
    <div class="nav-btn">
        <button class="darkmode" id="darkModeToggle"><img
  id="darkModeIcon"
  src="<?php echo get_template_directory_uri(); ?>/assets/images/nav-menu/moon.png"
  data-moon="<?php echo get_template_directory_uri(); ?>/assets/images/nav-menu/moon.png"
  data-sun="<?php echo get_template_directory_uri(); ?>/assets/images/nav-menu/sun.png"
  alt="Dark Mode"
/>
</button>
        <button class="subscribe-button btn" id="subscribeToggle">Subscribe</button>
    </div>

</div>
</nav>
