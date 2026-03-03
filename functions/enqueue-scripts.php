<?php

/**
 * Enqueue scripts & styles
 */
function site_scripts() {

  // ── Flickity: global (hero slider en cualquier página) ──
  wp_enqueue_style(  'flickity', 'https://unpkg.com/flickity@3/dist/flickity.min.css', [], '3.0.0' );
  wp_enqueue_script( 'flickity', 'https://unpkg.com/flickity@3/dist/flickity.pkgd.min.js', [], '3.0.0', true );

  // ── Leaflet + GLightbox: solo en singles ────────────────
  if ( is_single() ) {
    wp_enqueue_style(  'leaflet',        'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', [], '1.9.4' );
    wp_enqueue_style(  'leaflet-locate', 'https://unpkg.com/leaflet.locatecontrol/dist/L.Control.Locate.min.css', ['leaflet'], null );
    wp_enqueue_script( 'leaflet',        'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', [], '1.9.4', true );
    wp_enqueue_script( 'leaflet-locate', 'https://unpkg.com/leaflet.locatecontrol/dist/L.Control.Locate.min.js', ['leaflet'], null, true );

    wp_enqueue_style(  'glightbox', 'https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css', [], null );
    wp_enqueue_script( 'glightbox', 'https://cdn.jsdelivr.net/npm/glightbox/dist/js/glightbox.min.js', [], null, true );
  }

  // ── Main JS ─────────────────────────────────────────────
  $js_path = get_template_directory() . '/dist/scripts/main.js';
  if ( file_exists( $js_path ) ) {
    wp_enqueue_script( 'main', get_template_directory_uri() . '/dist/scripts/main.js', [], filemtime( $js_path ), true );
  }

  // ── CSS (Tailwind compilado — un solo archivo global) ────
  $css_path = get_template_directory() . '/dist/styles/app.min.css';
  if ( file_exists( $css_path ) ) {
    wp_enqueue_style( 'site-css', get_template_directory_uri() . '/dist/styles/app.min.css', [], filemtime( $css_path ) );
  }
}
add_action( 'wp_enqueue_scripts', 'site_scripts', 9999 );
