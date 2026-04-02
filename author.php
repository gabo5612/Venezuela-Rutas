<?php
get_template_part('parts/header');

$author    = get_queried_object();
$author_id = $author->ID;
$is_own    = is_user_logged_in() && get_current_user_id() === $author_id;

// Avatar
$avatar_id  = get_user_meta( $author_id, 'hfa_avatar_id', true );
$avatar_url = $avatar_id
    ? wp_get_attachment_image_url( $avatar_id, 'medium' )
    : get_avatar_url( $author_id, ['size' => 160] );

// HFA roles & meta
$hfa_roles  = class_exists('HFA_Roles') ? HFA_Roles::get_user_roles( $author_id ) : [];
$is_guide   = in_array( 'hfa_guide', $hfa_roles );
$is_org     = in_array( 'hfa_organizer', $hfa_roles );
$is_exp     = in_array( 'hfa_experience', $hfa_roles );
$approval   = class_exists('HFA_Roles') ? HFA_Roles::get_approval_status( $author_id ) : 'approved';
$is_approved = $approval === 'approved';

$activity_prefs  = get_user_meta( $author_id, 'hfa_activity_prefs', true ) ?: [];
$activity_labels = class_exists('HFA_Roles') ? HFA_Roles::activity_types() : [];

// Guide post link
$guide_post_id  = (int) get_user_meta( $author_id, 'hfa_guide_post_id', true );
$guide_post     = $guide_post_id ? get_post( $guide_post_id ) : null;

// Guide data: prefer ACF post fields, fall back to user meta
if ( $guide_post ) {
    $whatsapp    = get_field( 'guide_whatsapp',   $guide_post_id ) ?: get_user_meta( $author_id, 'hfa_whatsapp',  true );
    $instagram   = get_field( 'guide_instagram',  $guide_post_id ) ?: get_user_meta( $author_id, 'hfa_instagram', true );
    $guide_bio   = $guide_post->post_content ?: get_user_meta( $author_id, 'hfa_guide_bio', true );
    $guide_price = get_field( 'guide_price_from', $guide_post_id );
    $g_featured  = (bool) get_field( 'guide_is_featured', $guide_post_id );
    $g_specialty = get_field( 'guide_specialty', $guide_post_id ) ?: [];
    $g_zones     = wp_get_post_terms( $guide_post_id, 'guide-zone', ['fields' => 'names'] );
    $g_photo_url = get_field( 'guide_photo', $guide_post_id );
    if ( $g_photo_url && is_array($g_photo_url) ) $g_photo_url = $g_photo_url['url'] ?? '';
    // Use guide ACF photo if no pool avatar
    if ( $g_photo_url && ! $avatar_id ) $avatar_url = $g_photo_url;
} else {
    $whatsapp    = get_user_meta( $author_id, 'hfa_whatsapp',  true );
    $instagram   = get_user_meta( $author_id, 'hfa_instagram', true );
    $guide_bio   = get_user_meta( $author_id, 'hfa_guide_bio', true );
    $guide_price = '';
    $g_featured  = false;
    $g_specialty = [];
    $g_zones     = [];
}

// Expedition stats
global $wpdb;
$organized = (int) $wpdb->get_var( $wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_exp_organizer' AND meta_value = %d",
    $author_id
));
$joined = (int) $wpdb->get_var( $wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}hfa_requests WHERE user_id = %d AND status = 'approved'",
    $author_id
));

// Expeditions led (for guide public display)
$led_expeditions = [];
if ( $is_guide && $is_approved ) {
    $led_ids = $wpdb->get_col( $wpdb->prepare(
        "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_exp_organizer' AND meta_value = %d ORDER BY post_id DESC LIMIT 6",
        $author_id
    ));
    if ( $led_ids ) {
        $led_expeditions = get_posts([
            'post__in'  => $led_ids,
            'post_type' => 'expedition',
            'orderby'   => 'post__in',
            'numberposts' => 6,
        ]);
    }
}

// Posts query
$paged = get_query_var('paged') ?: 1;
$posts_query = new WP_Query([
    'author'         => $author_id,
    'post_type'      => ['post', 'routes', 'point-of-interest'],
    'posts_per_page' => 9,
    'paged'          => $paged,
]);
?>

<main class="page-author">

  <!-- ── Profile hero ──────────────────────────────────────────── -->
  <section class="author-hero">
    <div class="author-hero__inner container">

      <div class="author-hero__avatar-wrap">
        <img src="<?php echo esc_url($avatar_url); ?>"
             alt="<?php echo esc_attr($author->display_name); ?>"
             class="author-hero__avatar">
        <?php if ( $is_guide && $is_approved ) : ?>
        <span class="author-hero__verified" title="Verified Guide">
          <span class="material-symbols-outlined">verified</span>
        </span>
        <?php endif; ?>
      </div>

      <div class="author-hero__info">

        <!-- Name + badges -->
        <div class="author-hero__name-row">
          <h1 class="author-hero__name"><?php echo esc_html($author->display_name); ?></h1>
          <?php if ( $is_guide && $is_approved ) : ?>
          <span class="author-role-badge author-role-badge--guide">
            <span class="material-symbols-outlined">explore</span> Guide
          </span>
          <?php endif; ?>
          <?php if ( $is_guide && $g_featured ) : ?>
          <span class="author-role-badge author-role-badge--featured">
            <span class="material-symbols-outlined">star</span> Featured
          </span>
          <?php endif; ?>
          <?php if ( $is_org && $is_approved && ! $is_guide ) : ?>
          <span class="author-role-badge author-role-badge--organizer">
            <span class="material-symbols-outlined">flag</span> Organizer
          </span>
          <?php endif; ?>
          <?php if ( $is_exp && $is_approved ) : ?>
          <span class="author-role-badge author-role-badge--experience">
            <span class="material-symbols-outlined">storefront</span> Experience
          </span>
          <?php endif; ?>
        </div>

        <!-- Guide zones & price -->
        <?php if ( $is_guide && ( ! empty($g_zones) || $guide_price ) ) : ?>
        <div class="author-hero__guide-meta">
          <?php if ( ! empty($g_zones) && ! is_wp_error($g_zones) ) : ?>
          <span class="author-guide-meta__item">
            <span class="material-symbols-outlined">location_on</span>
            <?php echo esc_html( implode(', ', $g_zones) ); ?>
          </span>
          <?php endif; ?>
          <?php if ( $guide_price ) : ?>
          <span class="author-guide-meta__item author-guide-meta__item--price">
            <span class="material-symbols-outlined">payments</span>
            From <strong>$<?php echo esc_html($guide_price); ?></strong>/day
          </span>
          <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Guide specialties -->
        <?php if ( $is_guide && ! empty($g_specialty) ) : ?>
        <div class="author-hero__activities" style="margin-bottom:.75rem">
          <?php foreach ( $g_specialty as $s ) : ?>
          <span class="author-activity-chip author-activity-chip--specialty"><?php echo esc_html($s); ?></span>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ($author->description) : ?>
        <p class="author-hero__bio"><?php echo esc_html($author->description); ?></p>
        <?php endif; ?>

        <!-- Guide bio -->
        <?php if ( $is_guide && $guide_bio ) : ?>
        <p class="author-hero__guide-bio"><?php echo esc_html($guide_bio); ?></p>
        <?php endif; ?>

        <!-- Activity chips -->
        <?php if (!empty($activity_prefs) && !empty($activity_labels)) : ?>
        <div class="author-hero__activities">
          <?php foreach ($activity_prefs as $key) :
            if (!isset($activity_labels[$key])) continue;
            [$icon, $label] = $activity_labels[$key];
          ?>
          <span class="author-activity-chip">
            <span class="material-symbols-outlined"><?php echo esc_html($icon); ?></span>
            <?php echo esc_html($label); ?>
          </span>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="author-hero__stats">
          <div class="author-stat">
            <span class="author-stat__value"><?php echo $posts_query->found_posts; ?></span>
            <span class="author-stat__label">Posts</span>
          </div>
          <div class="author-stat">
            <span class="author-stat__value"><?php echo $organized; ?></span>
            <span class="author-stat__label">Expeditions organized</span>
          </div>
          <div class="author-stat">
            <span class="author-stat__value"><?php echo $joined; ?></span>
            <span class="author-stat__label">Expeditions completed</span>
          </div>
        </div>

        <!-- Guide contact buttons -->
        <?php if ( $is_guide && $is_approved && ( $whatsapp || $instagram ) ) : ?>
        <div class="author-hero__contact">
          <?php if ( $whatsapp ) :
            $wa_number = preg_replace('/\D/', '', $whatsapp);
          ?>
          <a href="https://wa.me/<?php echo esc_attr($wa_number); ?>"
             target="_blank" rel="noopener" class="author-contact-btn author-contact-btn--wa">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
            Contact via WhatsApp
          </a>
          <?php endif; ?>
          <?php if ( $instagram ) : ?>
          <a href="https://instagram.com/<?php echo esc_attr($instagram); ?>"
             target="_blank" rel="noopener" class="author-contact-btn author-contact-btn--ig">
            <span class="material-symbols-outlined">photo_camera</span>
            @<?php echo esc_html($instagram); ?>
          </a>
          <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Actions -->
        <?php if ($is_own) : ?>
        <div class="author-hero__actions">
          <a href="<?php echo esc_url( get_permalink( get_page_by_path('editar-perfil') ) ?: admin_url('profile.php') ); ?>"
             class="btn btn--outline btn--sm">
            <span class="material-symbols-outlined">edit</span> Edit profile
          </a>
          <?php if ( $is_guide && $is_approved ) :
            $listing_page = get_permalink( get_page_by_path('guide-listing') );
          ?>
          <a href="<?php echo esc_url( $listing_page ?: home_url('/guide-listing/') ); ?>"
             class="btn btn--outline btn--sm">
            <span class="material-symbols-outlined"><?php echo $guide_post ? 'manage_accounts' : 'add'; ?></span>
            <?php echo $guide_post ? 'Edit guide listing' : 'Create guide listing'; ?>
          </a>
          <?php endif; ?>
          <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" class="btn btn--ghost btn--sm">
            <span class="material-symbols-outlined">logout</span> Sign out
          </a>
        </div>
        <?php endif; ?>

        <!-- Link to guide listing (visible to all) -->
        <?php if ( $is_guide && $guide_post && $is_approved ) : ?>
        <div style="margin-top:.75rem">
          <a href="<?php echo esc_url( get_permalink($guide_post_id) ); ?>" class="author-guide-listing-link">
            <span class="material-symbols-outlined">open_in_new</span>
            View full guide listing
          </a>
        </div>
        <?php endif; ?>

      </div>
    </div>
  </section>

  <!-- ── Guide expeditions led ─────────────────────────────────── -->
  <?php if ( $is_guide && $is_approved && ! empty($led_expeditions) ) : ?>
  <section class="author-guide-expeditions container">
    <h2 class="author-section-title">
      <span class="material-symbols-outlined">groups</span>
      Led Expeditions
    </h2>
    <div class="author-exp-grid">
      <?php foreach ($led_expeditions as $exp) :
        $exp_date     = get_post_meta($exp->ID, '_exp_date', true);
        $exp_activity = get_post_meta($exp->ID, '_exp_activity_type', true);
        $exp_status   = get_post_meta($exp->ID, '_exp_status', true);
        $exp_slots    = get_post_meta($exp->ID, '_exp_slots', true);
        $status_map   = ['open' => 'Open', 'full' => 'Full', 'completed' => 'Completed'];
        $act_label    = $activity_labels[$exp_activity][1] ?? $exp_activity;
      ?>
      <div class="author-exp-card">
        <div class="author-exp-card__top">
          <span class="author-exp-card__activity"><?php echo esc_html($act_label); ?></span>
          <span class="author-exp-card__status author-exp-card__status--<?php echo esc_attr($exp_status); ?>">
            <?php echo esc_html($status_map[$exp_status] ?? $exp_status); ?>
          </span>
        </div>
        <h4 class="author-exp-card__title"><?php echo esc_html($exp->post_title); ?></h4>
        <?php if ($exp_date) : ?>
        <span class="author-exp-card__date">
          <span class="material-symbols-outlined">calendar_today</span>
          <?php echo esc_html( date_i18n('j M Y', strtotime($exp_date)) ); ?>
        </span>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- ── Posts grid ────────────────────────────────────────────── -->
  <section class="author-posts container">
    <h2 class="author-section-title">
      <span class="material-symbols-outlined">article</span>
      Posts
    </h2>

    <?php if ($posts_query->have_posts()) : ?>
    <div class="author-posts__grid">
      <?php while ($posts_query->have_posts()) : $posts_query->the_post();
        $type        = get_post_type();
        $thumb       = get_the_post_thumbnail_url(null, 'medium_large');
        $type_labels = ['routes' => 'Route', 'point-of-interest' => 'POI', 'post' => 'Log'];
        $type_label  = $type_labels[$type] ?? ucfirst($type);
      ?>
      <article class="author-post-card">
        <?php if ($thumb) : ?>
        <a href="<?php the_permalink(); ?>" class="author-post-card__img-wrap">
          <img src="<?php echo esc_url($thumb); ?>" alt="<?php the_title_attribute(); ?>" loading="lazy">
          <span class="author-post-card__type"><?php echo esc_html($type_label); ?></span>
        </a>
        <?php endif; ?>
        <div class="author-post-card__body">
          <h3 class="author-post-card__title">
            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
          </h3>
          <time class="author-post-card__date"><?php echo get_the_date('j M Y'); ?></time>
        </div>
      </article>
      <?php endwhile; wp_reset_postdata(); ?>
    </div>

    <?php echo paginate_links([
        'total'   => $posts_query->max_num_pages,
        'current' => $paged,
        'before_page_number' => '<span>',
        'after_page_number'  => '</span>',
    ]); ?>

    <?php else : ?>
    <p class="author-posts__empty">No posts yet.</p>
    <?php endif; ?>
  </section>

</main>

<?php get_template_part('parts/footer'); ?>
