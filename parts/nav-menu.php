<nav>
    <?php
    $route_assets = get_template_directory_uri() . '/assets/';
    $open_menu = $route_assets . 'nav-menu/menu.svg';
    $close_menu = $route_assets . 'nav-menu/close-menu.svg'; 
    ?>

    
    <div class="menu-toggle" id="menuToggle">
        <img src="<?php echo esc_url($open_menu); ?>" alt="Open Menu" class="menu-icon open-menu">
        <img src="<?php echo esc_url($close_menu); ?>" alt="Close Menu" class="menu-icon close-menu">
    </div>

    <!-- Menú -->
    <div class="nav_menu" id="navMenu">
        <?php
        wp_nav_menu(array(
            'theme_location' => 'menu',
            'container' => false,
            'menu_class' => 'nav_menu_items',
        ));
        ?>
    </div>
</nav>
