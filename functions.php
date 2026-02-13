<?php

// Enqueue scripts & styles
require_once(get_template_directory() . '/functions/enqueue-scripts.php');


// ===============================
// REGISTER MENUS
// ===============================
function register_my_menus()
{
    register_nav_menus([
        'menu'   => __('Main Menu', 'textdomain'),
        'footer' => __('Footer Menu', 'textdomain'),
    ]);
}
add_action('after_setup_theme', 'register_my_menus');

add_theme_support('post-thumbnails');

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
            $q->the_post(); ?>
            <a href="<?php the_permalink(); ?>" class="post-card card-shadow">
                 <?php
                    $categories = get_the_category();
                    $catIcon = '4'; 
                    if (!empty($categories)) {
                        $catName = $categories[0]->name;

                        if ($catName === 'Tech') {
                            $catIcon = '1';
                        } elseif ($catName === 'Hogar') {
                            $catIcon = '2';
                        } elseif ($catName === 'Makeup') {
                            $catIcon = '3';
                        } else {
                            $catIcon = '4';
                        }

                        echo '<div class="post-category post-icon-' . esc_attr($catIcon) . '">' . esc_html($catName) . '</div>';
                    }
                    ?>
                <div class="post-image">
                    <?php if (has_post_thumbnail()): ?>
                        <?php the_post_thumbnail('medium'); ?>
                    <?php else: ?>
                        <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/default-post-image.jpg'); ?>" alt="Default Post Image">
                    <?php endif; ?>
                </div>
                <div class="post-content">
                   

                    <h3 class="post-title"><?php the_title(); ?></h3>
                    <p class="post-excerpt"><?php echo esc_html(wp_trim_words(get_the_excerpt(), 20, '...')); ?></p>
                </div>
            </a>
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
