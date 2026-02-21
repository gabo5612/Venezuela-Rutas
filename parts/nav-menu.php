<nav>
    <div class="nav-container container">
    <div>
        <a href="<?php echo home_url(); ?>" class="logo">
           <h1 class="material-symbols-rounded"><span>timer</span> <?php echo esc_html( get_bloginfo('name') ); ?></h1>
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
        <button class="darkmode" id="darkModeToggle">
            <span id="darkModeIcon" class="material-symbols-rounded">dark_mode</span>
            <span id="lightModeIcon" class="material-symbols-rounded">light_mode</span>
</button>
        <button class="subscribe-button btn" id="subscribeToggle">Subscribe</button>
    </div>

</div>
</nav>
