<?php

// Enqueue scripts & styles
require_once(get_template_directory() . '/functions/enqueue-scripts.php');


// ===============================
// REGISTER MENUS
// ===============================
function register_my_menus() {
    register_nav_menus([
        'menu'   => __('Main Menu', 'textdomain'),
        'footer' => __('Footer Menu', 'textdomain'),
    ]);
}
add_action('after_setup_theme', 'register_my_menus');


// ===============================
// ADD REPEATING CLASSES menu-1..menu-4
// ===============================
add_filter('wp_nav_menu_objects', function ($items, $args) {

    // Only apply to main menu
    if (empty($args->theme_location) || $args->theme_location !== 'menu') {
        return $items;
    }

    $i = 0;

    foreach ($items as $item) {

        // Only top-level items (remove this IF if you want submenus included)
        if ((int) $item->menu_item_parent !== 0) {
            continue;
        }

        $i++;
        $sequence = (($i - 1) % 4) + 1; // 1-4 repeating
        $item->classes[] = 'menu-' . $sequence;
    }

    return $items;

}, 10, 2);
