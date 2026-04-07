<?php

// Enqueue scripts & styles
require_once(get_template_directory() . '/functions/enqueue-scripts.php');


// ===============================
// THEME SUPPORT + MENUS
// ===============================
add_action('after_setup_theme', function () {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('woocommerce');
    add_theme_support('wc-product-gallery-zoom');
    add_theme_support('wc-product-gallery-lightbox');
    add_theme_support('wc-product-gallery-slider');
    register_nav_menus([
        'menu'   => __('Main Menu', 'textdomain'),
        'footer' => __('Footer Menu', 'textdomain'),
    ]);
});

// ===============================
// POLYFILL: crypto.randomUUID (only available on HTTPS; polyfill for HTTP dev)
// ===============================
add_action( 'wp_head', function () {
    ?>
    <script>
    if (window.crypto && !window.crypto.randomUUID) {
      window.crypto.randomUUID = function () {
        return '10000000-1000-4000-8000-100000000000'.replace(/[018]/g, function (c) {
          var n = parseInt(c, 10);
          return (n ^ (window.crypto.getRandomValues(new Uint8Array(1))[0] & (15 >> (n / 4)))).toString(16);
        });
      };
    }
    </script>
    <?php
}, 1 );

// ===============================
// GOOGLE FONTS
// ===============================
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style(
        'google-fonts',
        'https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,700;1,800;1,900&display=swap',
        [],
        null
    );
    wp_enqueue_style(
        'material-symbols',
        'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=block',
        [],
        null
    );
}, 1);

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

add_action('wp_enqueue_scripts', function () {
    wp_localize_script('main', 'TipsAjax', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('tips_load_more_nonce'),
        'perPage' => 4,
    ]);
});

// ===============================
// GPX DOWNLOAD  (?gpx=1 on any post with GPS points)
// ===============================
add_action('template_redirect', function () {
    if (empty($_GET['gpx']) || $_GET['gpx'] !== '1') return;
    if (!is_singular()) return;

    $post_id = get_queried_object_id();
    $points  = get_field('points', $post_id);
    if (empty($points)) wp_die('This entry has no GPS points.');

    $pts = [];
    foreach ($points as $p) {
        $lat = floatval($p['latitude']  ?? 0);
        $lng = floatval($p['longitude'] ?? 0);
        if ($lat && $lng) $pts[] = [$lat, $lng];
    }
    if (empty($pts)) wp_die('No valid coordinates found.');

    $title    = get_the_title($post_id);
    $filename = sanitize_title($title) . '.gpx';
    $date     = get_the_date('c', $post_id);

    header('Content-Type: application/gpx+xml; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');

    $name = htmlspecialchars($title, ENT_XML1 | ENT_QUOTES, 'UTF-8');

    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<gpx version="1.1" creator="Venezuela Rutas"' . "\n";
    echo '  xmlns="http://www.topografix.com/GPX/1/1"' . "\n";
    echo '  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"' . "\n";
    echo '  xsi:schemaLocation="http://www.topografix.com/GPX/1/1 http://www.topografix.com/GPX/1/1/gpx.xsd">' . "\n";
    echo '  <metadata>' . "\n";
    echo '    <name>' . $name . '</name>' . "\n";
    echo '    <time>' . esc_html($date) . '</time>' . "\n";
    echo '  </metadata>' . "\n";
    echo '  <trk>' . "\n";
    echo '    <name>' . $name . '</name>' . "\n";
    echo '    <trkseg>' . "\n";
    foreach ($pts as $pt) {
        echo '      <trkpt lat="' . $pt[0] . '" lon="' . $pt[1] . '"></trkpt>' . "\n";
    }
    echo '    </trkseg>' . "\n";
    echo '  </trk>' . "\n";
    echo '</gpx>';
    exit;
});

// ===============================
// INCLUDE CPTs IN TAG ARCHIVES
// WordPress only queries 'post' by default in tag archives.
// This includes routes + point-of-interest so tag filters work.
// ===============================
add_action('pre_get_posts', function ($query) {
    if (!is_admin() && $query->is_main_query() && $query->is_tag()) {
        $query->set('post_type', ['post', 'routes', 'point-of-interest']);
    }
});


// ── Custom Login / Register pages ───────────────────────────────
add_filter('login_url', function($url, $redirect) {
    $page = get_page_by_path('login');
    if ($page) {
        $custom = get_permalink($page);
        return $redirect ? add_query_arg('redirect_to', urlencode($redirect), $custom) : $custom;
    }
    return $url;
}, 10, 2);

add_filter('register_url', function($url) {
    $page = get_page_by_path('registro');
    return $page ? get_permalink($page) : $url;
});

// Redirect wp-login.php to custom pages (except admin/ajax requests)
add_action('init', function() {
    if (!is_admin() && isset($_SERVER['REQUEST_URI'])) {
        $uri = $_SERVER['REQUEST_URI'];
        if (strpos($uri, 'wp-login.php') !== false && !isset($_POST['log']) && !isset($_POST['user_login'])) {
            $action = $_GET['action'] ?? '';
            if ($action === 'register') {
                $page = get_page_by_path('registro');
                if ($page) { wp_redirect(get_permalink($page)); exit; }
            } elseif (!$action || $action === 'login') {
                $page = get_page_by_path('login');
                if ($page) {
                    $redirect = isset($_GET['redirect_to']) ? '?redirect_to=' . urlencode($_GET['redirect_to']) : '';
                    wp_redirect(get_permalink($page) . $redirect); exit;
                }
            }
        }
    }
});

// ── Invalidate GPS block transients when saving routes or POIs ─
add_action('save_post', function ($post_id) {
    $type = get_post_type($post_id);
    if ($type === 'routes')           delete_transient('gps_block_routes');
    if ($type === 'point-of-interest') delete_transient('gps_block_pois');
});

// ── Public endpoint for GPS map data ────────────────────────────
add_action('wp_ajax_gps_map_data',        'gps_map_data_handler');
add_action('wp_ajax_nopriv_gps_map_data', 'gps_map_data_handler');
function gps_map_data_handler() {
    $routes = get_transient('gps_block_routes');
    $pois   = get_transient('gps_block_pois');
    // If no transient, generate it (rare, but just in case)
    if ($routes === false) $routes = [];
    if ($pois   === false) $pois   = [];
    wp_send_json(['routes' => $routes, 'pois' => $pois]);
}

add_action('wp_ajax_load_more_tips', 'mag_load_more_tips');
add_action('wp_ajax_nopriv_load_more_tips', 'mag_load_more_tips');

function mag_load_more_tips()
{
    check_ajax_referer('tips_load_more_nonce', 'nonce');

    $paged   = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
    $perPage = isset($_POST['perPage']) ? max(1, intval($_POST['perPage'])) : 4;

    $q = new WP_Query([
        'post_type'           => 'post',
        'post_status'         => 'publish',
        'posts_per_page'      => $perPage,
        'paged'               => $paged,
        'ignore_sticky_posts' => true,
    ]);

    ob_start();

    if ($q->have_posts()) {
        while ($q->have_posts()) {
            $q->the_post();
            $thumb = get_the_post_thumbnail_url(null, 'large');
            $vid   = get_field('video_featured') ?: '';
            $cats  = get_the_category();
            $tags  = wp_get_post_terms(get_the_ID(), 'post_tag');
            ?>
        <div class="post-card">
          <a href="<?php the_permalink(); ?>" class="post-card__link" aria-label="<?php the_title_attribute(); ?>"></a>
          <div class="post-card__image">
            <?php if ($thumb) : ?>
              <img src="<?php echo esc_url($thumb); ?>" alt="<?php the_title_attribute(); ?>">
            <?php elseif ($vid) : ?>
              <video autoplay loop muted playsinline style="width:100%;height:100%;object-fit:cover">
                <source src="<?php echo esc_url($vid); ?>" type="video/mp4">
              </video>
            <?php else : ?>
              <div class="post-card__empty"><span class="material-symbols-outlined">terrain</span></div>
            <?php endif; ?>
            <?php if ($cats) : ?>
            <div class="post-card__badge">
              <span class="badge badge--outline"><?php echo esc_html($cats[0]->name); ?></span>
            </div>
            <?php endif; ?>
          </div>
          <div class="post-card__body">
            <div class="post-card__date"><?php echo get_the_date('d M Y'); ?></div>
            <h4 class="post-card__title"><?php the_title(); ?></h4>
            <p class="post-card__excerpt"><?php echo esc_html(wp_trim_words(get_the_excerpt(), 20, '...')); ?></p>
            <div class="post-card__footer">
              <div class="post-card__tags">
                <?php foreach (array_slice($tags, 0, 2) as $pt) : ?>
                  <a href="<?php echo esc_url(get_term_link($pt)); ?>" class="badge badge--outline" style="position:relative;z-index:2"><?php echo esc_html($pt->name); ?></a>
                <?php endforeach; ?>
              </div>
              <span class="post-card__arrow"><span class="material-symbols-outlined">north_east</span></span>
            </div>
          </div>
        </div>
<?php
        }
        wp_reset_postdata();
    }

    $html = ob_get_clean();

    wp_send_json_success([
        'html'     => $html,
        'has_more' => ($paged < (int) $q->max_num_pages),
    ]);
}


// ===============================
// COMMUNITY SUGGESTIONS — Route, POI, Save Planned Route
// ===============================

/**
 * Shared helper: sanitize & validate an array of [[lat,lng]] waypoints.
 * Returns array of ['latitude' => '...', 'longitude' => '...'] or false.
 */
function rutas_parse_points( $json_string ) {
    $raw = json_decode( wp_unslash( $json_string ), true );
    if ( ! is_array( $raw ) ) return false;
    $out = [];
    foreach ( $raw as $p ) {
        $lat = floatval( $p[0] ?? 0 );
        $lng = floatval( $p[1] ?? 0 );
        if ( $lat && $lng ) {
            $point = [ 'latitude' => (string) $lat, 'longitude' => (string) $lng ];
            if ( isset( $p[2] ) && $p[2] !== '' ) {
                $point['elevation'] = (string) round( floatval( $p[2] ) );
            }
            $out[] = $point;
        }
    }
    return count( $out ) >= 2 ? $out : false;
}

// ── Routing proxy ────────────────────────────────────────────────────────────
// Uses OpenRouteService if RUTAS_ORS_API_KEY is defined (fast, reliable).
// Falls back to OSRM otherwise. Returns a normalised payload so the JS
// doesn't need to handle two different response formats.
//
// To enable ORS, add to wp-config.php:
//   define( 'RUTAS_ORS_API_KEY', 'your-free-key-from-openrouteservice.org' );
// ─────────────────────────────────────────────────────────────────────────────
add_action( 'wp_ajax_rutas_proxy_route',        'rutas_proxy_route_handler' );
add_action( 'wp_ajax_nopriv_rutas_proxy_route', 'rutas_proxy_route_handler' );
function rutas_proxy_route_handler() {
    $profile  = sanitize_text_field( $_POST['profile'] ?? 'foot' );
    $start_lat = floatval( $_POST['slat'] ?? 0 );
    $start_lng = floatval( $_POST['slng'] ?? 0 );
    $end_lat   = floatval( $_POST['elat'] ?? 0 );
    $end_lng   = floatval( $_POST['elng'] ?? 0 );

    if ( ! in_array( $profile, [ 'foot', 'bike', 'car' ], true ) ) $profile = 'foot';
    if ( ! $start_lat || ! $start_lng || ! $end_lat || ! $end_lng ) {
        wp_send_json_error( 'Missing coordinates.' );
    }

    $ors_key = defined( 'RUTAS_ORS_API_KEY' ) ? RUTAS_ORS_API_KEY
             : get_option( 'rutas_ors_api_key', '' );

    $http_args = [ 'timeout' => 12, 'sslverify' => false ];

    // ── OpenRouteService (preferred) ───────────────────────────
    if ( $ors_key ) {
        $ors_profiles = [
            'foot' => 'foot-hiking',
            'bike' => 'cycling-regular',
            'car'  => 'driving-car',
        ];
        $ors_profile = $ors_profiles[ $profile ] ?? 'foot-hiking';
        $url = "https://api.openrouteservice.org/v2/directions/{$ors_profile}"
             . "?api_key={$ors_key}"
             . "&start={$start_lng},{$start_lat}"
             . "&end={$end_lng},{$end_lat}";

        $response = wp_remote_get( $url, $http_args );

        if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
            $body = json_decode( wp_remote_retrieve_body( $response ), true );
            $feat = $body['features'][0] ?? null;
            if ( $feat ) {
                $coords = array_map( fn($c) => [ $c[1], $c[0] ], $feat['geometry']['coordinates'] );
                wp_send_json_success( [
                    'engine'      => 'ors',
                    'coordinates' => $coords,
                    'distance'    => $feat['properties']['summary']['distance'] ?? 0,
                    'duration'    => $feat['properties']['summary']['duration'] ?? 0,
                ] );
            }
        }
        // ORS failed – fall through to OSRM below
    }

    // ── OSRM (fallback) ────────────────────────────────────────
    $coords = "{$start_lng},{$start_lat};{$end_lng},{$end_lat}";
    $url    = "https://router.project-osrm.org/route/v1/{$profile}/{$coords}"
            . '?geometries=geojson&overview=full&steps=false';

    $response = wp_remote_get( $url, $http_args );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error( 'proxy_unreachable' ); // JS will try browser-direct
    }

    $body  = json_decode( wp_remote_retrieve_body( $response ), true );
    $route = $body['routes'][0] ?? null;

    if ( ! $route ) {
        wp_send_json_error( 'no_route' );
    }

    $coords_out = array_map( fn($c) => [ $c[1], $c[0] ], $route['geometry']['coordinates'] );
    wp_send_json_success( [
        'engine'      => 'osrm',
        'coordinates' => $coords_out,
        'distance'    => $route['distance'] ?? 0,
        'duration'    => $route['duration'] ?? 0,
    ] );
}

// ── Elevation proxy (OpenTopoData — avoids CORS from HTTP local dev) ──
add_action( 'wp_ajax_rutas_proxy_elevation',        'rutas_proxy_elevation_handler' );
add_action( 'wp_ajax_nopriv_rutas_proxy_elevation', 'rutas_proxy_elevation_handler' );
function rutas_proxy_elevation_handler() {
    $locations = sanitize_text_field( $_POST['locations'] ?? '' );
    if ( ! $locations ) wp_send_json_error( 'Missing locations.' );

    $url      = 'https://api.opentopodata.org/v1/srtm30m?locations=' . rawurlencode( $locations );
    $response = wp_remote_get( $url, [
        'timeout'   => 15,
        'sslverify' => false, // LocalWP may lack CA certs for outbound HTTPS
    ] );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error( $response->get_error_message() );
    }

    $data = json_decode( wp_remote_retrieve_body( $response ), true );
    if ( ! $data ) wp_send_json_error( 'Invalid elevation response.' );

    wp_send_json_success( $data );
}

// ── Suggest Route ────────────────────────────────────────────
add_action( 'wp_ajax_rutas_suggest_route',        'rutas_suggest_route_handler' );
add_action( 'wp_ajax_nopriv_rutas_suggest_route', 'rutas_suggest_route_handler' );
function rutas_suggest_route_handler() {
    check_ajax_referer( 'rutas_suggest_nonce', 'nonce' );

    $title       = sanitize_text_field( $_POST['title']       ?? '' );
    $description = sanitize_textarea_field( $_POST['description'] ?? '' );
    $difficulty  = sanitize_text_field( $_POST['difficulty']  ?? '' );
    $activity    = sanitize_text_field( $_POST['activity']    ?? '' );
    $notes       = sanitize_textarea_field( $_POST['notes']   ?? '' );
    $email       = sanitize_email( $_POST['email']            ?? '' );

    if ( empty( $title ) ) {
        wp_send_json_error( 'Route name is required.' );
    }

    $points = rutas_parse_points( $_POST['points'] ?? '[]' );
    if ( ! $points ) {
        wp_send_json_error( 'Please add at least 2 waypoints on the map.' );
    }

    $author = is_user_logged_in() ? get_current_user_id() : 1;

    $post_id = wp_insert_post( [
        'post_title'   => $title,
        'post_content' => $description,
        'post_type'    => 'routes',
        'post_status'  => 'pending',
        'post_author'  => $author,
        'meta_input'   => [
            '_is_community_suggestion' => '1',
            '_suggested_activity'      => $activity,
            '_suggested_notes'         => $notes,
            '_suggested_email'         => $email,
            '_suggested_by_ip'         => sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '' ),
        ],
    ] );

    if ( is_wp_error( $post_id ) ) {
        wp_send_json_error( 'Could not save suggestion. Please try again.' );
    }

    if ( function_exists( 'update_field' ) ) {
        update_field( 'points', $points, $post_id );
        if ( $difficulty ) update_field( 'difficulty', $difficulty, $post_id );
    }

    // Notify admin
    $admin_email = get_option( 'admin_email' );
    wp_mail(
        $admin_email,
        '[Venezuela Rutas] New route suggestion: ' . $title,
        "A new route suggestion has been submitted.\n\nTitle: {$title}\nActivity: {$activity}\nDifficulty: {$difficulty}\nPoints: " . count( $points ) . "\n\nReview it at: " . get_edit_post_link( $post_id, 'raw' )
    );

    wp_send_json_success( [ 'message' => 'Route submitted successfully!' ] );
}

// ── Suggest POI ──────────────────────────────────────────────
add_action( 'wp_ajax_rutas_suggest_poi',        'rutas_suggest_poi_handler' );
add_action( 'wp_ajax_nopriv_rutas_suggest_poi', 'rutas_suggest_poi_handler' );
function rutas_suggest_poi_handler() {
    check_ajax_referer( 'rutas_suggest_nonce', 'nonce' );

    $title       = sanitize_text_field( $_POST['title']       ?? '' );
    $description = sanitize_textarea_field( $_POST['description'] ?? '' );
    $category    = sanitize_text_field( $_POST['category']    ?? '' );
    $notes       = sanitize_textarea_field( $_POST['notes']   ?? '' );
    $email       = sanitize_email( $_POST['email']            ?? '' );
    $lat         = floatval( $_POST['lat']                    ?? 0 );
    $lng         = floatval( $_POST['lng']                    ?? 0 );

    if ( empty( $title ) ) {
        wp_send_json_error( 'POI name is required.' );
    }
    if ( ! $lat || ! $lng ) {
        wp_send_json_error( 'Please place the POI on the map.' );
    }

    $author = is_user_logged_in() ? get_current_user_id() : 1;

    $post_id = wp_insert_post( [
        'post_title'   => $title,
        'post_content' => $description,
        'post_type'    => 'point-of-interest',
        'post_status'  => 'pending',
        'post_author'  => $author,
        'meta_input'   => [
            '_is_community_suggestion' => '1',
            '_suggested_category'      => $category,
            '_suggested_notes'         => $notes,
            '_suggested_email'         => $email,
            '_suggested_by_ip'         => sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '' ),
        ],
    ] );

    if ( is_wp_error( $post_id ) ) {
        wp_send_json_error( 'Could not save suggestion. Please try again.' );
    }

    if ( function_exists( 'update_field' ) ) {
        update_field( 'latitude',  (string) $lat, $post_id );
        update_field( 'longitude', (string) $lng, $post_id );
    }

    // Notify admin
    $admin_email = get_option( 'admin_email' );
    wp_mail(
        $admin_email,
        '[Venezuela Rutas] New POI suggestion: ' . $title,
        "A new POI suggestion has been submitted.\n\nTitle: {$title}\nCategory: {$category}\nCoordinates: {$lat}, {$lng}\n\nReview it at: " . get_edit_post_link( $post_id, 'raw' )
    );

    wp_send_json_success( [ 'message' => 'POI submitted successfully!' ] );
}

// ── Save Planned Route (logged-in users only) ────────────────
add_action( 'wp_ajax_rutas_save_planned_route', 'rutas_save_planned_route_handler' );
function rutas_save_planned_route_handler() {
    check_ajax_referer( 'rutas_suggest_nonce', 'nonce' );

    if ( ! is_user_logged_in() ) {
        wp_send_json_error( 'You must be logged in to save routes.' );
    }

    $title          = sanitize_text_field( $_POST['title']          ?? '' );
    $distance       = floatval( $_POST['distance']                  ?? 0 );
    $elevation_gain = intval( $_POST['elevation_gain']              ?? 0 );

    if ( empty( $title ) ) {
        wp_send_json_error( 'Route name is required.' );
    }

    $points = rutas_parse_points( $_POST['points'] ?? '[]' );
    if ( ! $points ) {
        wp_send_json_error( 'Route must have at least 2 valid coordinates.' );
    }

    $post_id = wp_insert_post( [
        'post_title'  => $title,
        'post_type'   => 'routes',
        'post_status' => 'pending',
        'post_author' => get_current_user_id(),
        'meta_input'  => [
            '_is_community_suggestion' => '1',
        ],
    ] );

    if ( is_wp_error( $post_id ) ) {
        wp_send_json_error( 'Could not save route. Please try again.' );
    }

    if ( function_exists( 'update_field' ) ) {
        update_field( 'points', $points, $post_id );

        // ACF may silently skip the elevation sub-field if it is not in its DB
        // registry. Write the meta keys directly using ACF's naming convention.
        foreach ( $points as $i => $p ) {
            if ( isset( $p['elevation'] ) && $p['elevation'] !== '' ) {
                update_post_meta( $post_id, "points_{$i}_elevation", $p['elevation'] );
                update_post_meta( $post_id, "_points_{$i}_elevation", 'field_points_elevation' );
            }
        }

        if ( $distance > 0 ) {
            update_field( 'distance',  round( $distance / 1000, 2 ), $post_id );
        }
        if ( $elevation_gain > 0 ) {
            update_field( 'elevation', $elevation_gain, $post_id );
        }
    }

    wp_send_json_success( [
        'message'  => 'Route saved as draft!',
        'edit_url' => get_edit_post_link( $post_id, 'raw' ),
    ] );
}


// ===============================
// COMMUNITY SUGGESTIONS — Admin listing page
// ===============================
add_action( 'admin_menu', function () {
    add_menu_page(
        'Community Suggestions',
        'Suggestions',
        'edit_posts',
        'rutas-suggestions',
        'rutas_suggestions_page',
        'dashicons-flag',
        26
    );
} );

function rutas_suggestions_page() {
    // ── Handle approve / reject actions ──────────────────────
    if ( isset( $_GET['sg_action'], $_GET['post_id'], $_GET['_wpnonce'] ) ) {
        $action  = sanitize_text_field( $_GET['sg_action'] );
        $post_id = intval( $_GET['post_id'] );

        if ( wp_verify_nonce( $_GET['_wpnonce'], 'rutas_sg_' . $action . '_' . $post_id ) ) {
            if ( $action === 'approve' ) {
                wp_update_post( [ 'ID' => $post_id, 'post_status' => 'publish' ] );
                echo '<div class="notice notice-success is-dismissible"><p>Suggestion <strong>approved</strong> and published.</p></div>';
            } elseif ( $action === 'reject' ) {
                wp_trash_post( $post_id );
                echo '<div class="notice notice-warning is-dismissible"><p>Suggestion <strong>rejected</strong> and moved to trash.</p></div>';
            }
        }
    }

    // ── Query pending community suggestions ──────────────────
    $items = get_posts( [
        'post_type'   => [ 'routes', 'point-of-interest' ],
        'post_status' => 'pending',
        'numberposts' => -1,
        'orderby'     => 'date',
        'order'       => 'DESC',
        'meta_query'  => [ [
            'key'   => '_is_community_suggestion',
            'value' => '1',
        ] ],
    ] );

    $base = admin_url( 'admin.php?page=rutas-suggestions' );
    ?>
    <div class="wrap">
      <h1 class="wp-heading-inline">Community Suggestions</h1>
      <span class="title-count" style="margin-left:.5rem;font-size:1rem;color:#888;">(<?php echo count( $items ); ?> pending)</span>
      <hr class="wp-header-end">

      <?php if ( empty( $items ) ) : ?>
        <p style="margin-top:1.5rem;color:#888;">No pending suggestions — all caught up!</p>
      <?php else : ?>
        <table class="wp-list-table widefat fixed striped" style="margin-top:1rem;">
          <thead>
            <tr>
              <th style="width:22%">Title</th>
              <th style="width:10%">Type</th>
              <th style="width:12%">Activity / Category</th>
              <th style="width:10%">Difficulty</th>
              <th style="width:10%">Points</th>
              <th style="width:10%">Date</th>
              <th style="width:14%">Submitted by</th>
              <th style="width:12%">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ( $items as $item ) :
                $type       = get_post_type_object( $item->post_type )->labels->singular_name;
                $activity   = get_post_meta( $item->ID, '_suggested_activity', true )
                           ?: get_post_meta( $item->ID, '_suggested_category', true )
                           ?: '—';
                $difficulty = get_field( 'difficulty', $item->ID ) ?: '—';
                $pts        = get_field( 'points', $item->ID );
                $pt_count   = is_array( $pts ) ? count( $pts ) : '—';
                $email      = get_post_meta( $item->ID, '_suggested_email', true );
                $ip         = get_post_meta( $item->ID, '_suggested_notes', true );
                $author     = get_userdata( $item->post_author );
                $by         = ( $author && (int) $author->ID !== 1 )
                            ? esc_html( $author->display_name )
                            : ( $email ? esc_html( $email ) : esc_html( get_post_meta( $item->ID, '_suggested_by_ip', true ) ?: '—' ) );

                $approve_url = wp_nonce_url(
                    add_query_arg( [ 'sg_action' => 'approve', 'post_id' => $item->ID ], $base ),
                    'rutas_sg_approve_' . $item->ID
                );
                $reject_url  = wp_nonce_url(
                    add_query_arg( [ 'sg_action' => 'reject',  'post_id' => $item->ID ], $base ),
                    'rutas_sg_reject_' . $item->ID
                );
            ?>
            <tr>
              <td><strong><?php echo esc_html( $item->post_title ); ?></strong></td>
              <td><?php echo esc_html( $type ); ?></td>
              <td><?php echo esc_html( $activity ); ?></td>
              <td><?php echo esc_html( ucfirst( $difficulty ) ); ?></td>
              <td><?php echo esc_html( $pt_count ); ?></td>
              <td><?php echo get_the_date( 'd M Y', $item->ID ); ?></td>
              <td><?php echo $by; ?></td>
              <td>
                <a href="<?php echo esc_url( $approve_url ); ?>"
                   class="button button-primary button-small">Approve</a>
                <a href="<?php echo esc_url( $reject_url ); ?>"
                   class="button button-small"
                   onclick="return confirm('Reject and trash this suggestion?')">Reject</a>
                <a href="<?php echo esc_url( get_edit_post_link( $item->ID ) ); ?>"
                   class="button button-small" target="_blank">Edit</a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
    <?php
}

// ===============================
// WOOCOMMERCE — custom page templates
// ===============================
add_filter('template_include', function ($template) {
    if (function_exists('is_cart') && is_cart()) {
        $custom = get_stylesheet_directory() . '/woocommerce/cart.php';
        if (file_exists($custom)) return $custom;
    }
    if (function_exists('is_checkout') && is_checkout() && !is_order_received_page() && !is_checkout_pay_page()) {
        $custom = get_stylesheet_directory() . '/woocommerce/checkout.php';
        if (file_exists($custom)) return $custom;
    }
    return $template;
});

// ===============================
// ADMIN BAR — administrators only
// ===============================
add_action('after_setup_theme', function () {
    if ( ! current_user_can('administrator') ) {
        show_admin_bar( false );
    }
});

