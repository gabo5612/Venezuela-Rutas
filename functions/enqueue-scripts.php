<?php

/**
 * Enqueue scripts & styles
 */
function site_scripts() {
  $dist_uri  = get_template_directory_uri() . '/dist/styles/';
  $dist_path = get_template_directory() . '/dist/styles/';

  // JS
  $app_js_path = get_template_directory() . '/dist/scripts/main.js';
  if (file_exists($app_js_path)) {
    wp_enqueue_script(
      'main',
      get_template_directory_uri() . '/dist/scripts/main.js',
      array(),
      filemtime($app_js_path),
      true
    );
  }

  // Global CSS — siempre se carga (reset + base + nav + footer)
  $global_path = $dist_path . 'global.min.css';
  if (file_exists($global_path)) {
    wp_enqueue_style('global-css', $dist_uri . 'global.min.css', [], filemtime($global_path));
  }

  // Home / Blog index
  if (is_front_page() || is_home()) {
    $home_path = $dist_path . 'home.min.css';
    if (file_exists($home_path)) {
      wp_enqueue_style('home-css', $dist_uri . 'home.min.css', ['global-css'], filemtime($home_path));
    }
  }

  // Single post
  if (is_single()) {
    $single_path = $dist_path . 'single.min.css';
    if (file_exists($single_path)) {
      wp_enqueue_style('single-css', $dist_uri . 'single.min.css', ['global-css'], filemtime($single_path));
    }
  }

  // Category archive
  if (is_category()) {
    $category_path = $dist_path . 'category.min.css';
    if (file_exists($category_path)) {
      wp_enqueue_style('category-css', $dist_uri . 'category.min.css', ['global-css'], filemtime($category_path));
    }
  }
}
add_action('wp_enqueue_scripts', 'site_scripts', 9999);
