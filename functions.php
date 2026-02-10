<?php
function register_my_menus() {
    register_nav_menus(array(
        'menu'   => __('Menu'),
        'footer' => __('Footer'),
    ));
}
add_action('after_setup_theme', 'register_my_menus');
