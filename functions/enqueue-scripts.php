<?php

/**
 * Enqueue scripts & styles
 */
function site_scripts() {
  $app_js_path = get_template_directory() . '/dist/scripts/main.js';

  if (file_exists($app_js_path)) {
    wp_enqueue_script(
      'main', 
      get_template_directory_uri() . '/dist/scripts/main.js',
      array(), // deps
      filemtime($app_js_path), 
      true 
    );

  }

  $site_css_path = get_template_directory() . '/dist/styles/app.min.css';
  if (file_exists($site_css_path)) {
    wp_enqueue_style(
      'site-css',
      get_template_directory_uri() . '/dist/styles/app.min.css',
      array(),
      filemtime($site_css_path)
    );
  }
}
add_action('wp_enqueue_scripts', 'site_scripts', 9999);

