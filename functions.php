<?php

// Enqueue scripts & styles
require_once(get_template_directory() . '/functions/enqueue-scripts.php');

// ===============================
// THEME SUPPORT + MENUS
// ===============================
add_action('after_setup_theme', function () {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    register_nav_menus([
        'menu'   => __('Main Menu', 'textdomain'),
        'footer' => __('Footer Menu', 'textdomain'),
    ]);
});

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
        'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap',
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
            $cats  = get_the_category();
            $diff  = get_field('difficulty') ?: '';
            $dist  = get_field('distance')   ?: '';
            $time  = get_field('time')       ?: '';
            ?>
        <div class="group border border-primary/10 rounded-xl bg-primary/5 hover:border-primary/40 transition-all flex flex-col">
          <div class="h-48 overflow-hidden rounded-t-xl relative">
            <?php if ($thumb) : ?>
              <img class="w-full h-full object-cover transition-transform group-hover:scale-110" src="<?php echo esc_url($thumb); ?>" alt="<?php the_title_attribute(); ?>">
            <?php else : ?>
              <div class="w-full h-full bg-bg-forest flex items-center justify-center"><span class="material-symbols-outlined text-primary/30 text-4xl">terrain</span></div>
            <?php endif; ?>
            <?php if ($cats) : ?>
            <div class="absolute top-3 right-3">
              <span class="px-2 py-1 bg-bg-forest/80 backdrop-blur-md text-primary text-[10px] font-black uppercase rounded border border-primary/20"><?php echo esc_html($cats[0]->name); ?></span>
            </div>
            <?php endif; ?>
          </div>
          <div class="p-6 flex-1 flex flex-col">
            <div class="text-[10px] font-black uppercase text-primary/60 mb-2 tracking-widest"><?php echo get_the_date('d M Y'); ?></div>
            <h4 class="text-xl font-bold uppercase text-slate-100 mb-3"><?php the_title(); ?></h4>
            <p class="text-slate-400 text-sm mb-6 flex-1"><?php echo esc_html(wp_trim_words(get_the_excerpt(), 20, '...')); ?></p>
            <div class="flex items-center justify-between pt-4 border-t border-primary/5">
              <span class="text-[10px] font-bold text-slate-500 uppercase"><?php the_author(); ?></span>
              <a href="<?php the_permalink(); ?>" class="text-primary"><span class="material-symbols-outlined">north_east</span></a>
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
