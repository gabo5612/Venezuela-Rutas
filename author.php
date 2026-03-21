<?php
get_template_part('parts/header');

$author     = get_queried_object();
$author_id  = $author->ID;
$is_own     = is_user_logged_in() && get_current_user_id() === $author_id;

// Avatar
$avatar_id  = get_user_meta( $author_id, 'pce_avatar_id', true );
$avatar_url = $avatar_id
    ? wp_get_attachment_image_url( $avatar_id, 'medium' )
    : get_avatar_url( $author_id, ['size' => 160] );

// PataCaliente meta
$activity_prefs = get_user_meta( $author_id, 'pce_activity_prefs', true ) ?: [];
$activity_labels = [
    'hiking'       => ['hiking',         'Senderismo'],
    'road-cycling' => ['directions_bike','Bici de ruta'],
    'mtb'          => ['forest',         'MTB'],
    'moto'         => ['two_wheeler',    'Moto'],
    'car'          => ['directions_car', 'Carro'],
    '4x4'          => ['terrain',        'Offroad 4x4'],
    'camping'      => ['camping',        'Campismo'],
    'gastronomy'   => ['restaurant',     'Gastronomía'],
];

// Expedition stats
global $wpdb;
$organized = (int) $wpdb->get_var( $wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_exp_organizer' AND meta_value = %d",
    $author_id
));
$joined = (int) $wpdb->get_var( $wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}pce_requests WHERE user_id = %d AND status = 'approved'",
    $author_id
));

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

  <!-- ── Profile hero ── -->
  <section class="author-hero">
    <div class="author-hero__inner container">

      <div class="author-hero__avatar-wrap">
        <img src="<?php echo esc_url($avatar_url); ?>"
             alt="<?php echo esc_attr($author->display_name); ?>"
             class="author-hero__avatar">
      </div>

      <div class="author-hero__info">
        <h1 class="author-hero__name"><?php echo esc_html($author->display_name); ?></h1>

        <?php if ($author->description) : ?>
        <p class="author-hero__bio"><?php echo esc_html($author->description); ?></p>
        <?php endif; ?>

        <!-- Activity chips -->
        <?php if (!empty($activity_prefs)) : ?>
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
            <span class="author-stat__label">Publicaciones</span>
          </div>
          <div class="author-stat">
            <span class="author-stat__value"><?php echo $organized; ?></span>
            <span class="author-stat__label">Expediciones organizadas</span>
          </div>
          <div class="author-stat">
            <span class="author-stat__value"><?php echo $joined; ?></span>
            <span class="author-stat__label">Expediciones realizadas</span>
          </div>
        </div>

        <?php if ($is_own) : ?>
        <div class="author-hero__actions">
          <a href="<?php echo esc_url( get_permalink( get_page_by_path('editar-perfil') ) ?: admin_url('profile.php') ); ?>" class="btn btn--outline btn--sm">
            <span class="material-symbols-outlined">edit</span>
            Editar perfil
          </a>
          <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" class="btn btn--ghost btn--sm">
            <span class="material-symbols-outlined">logout</span>
            Cerrar sesión
          </a>
        </div>
        <?php endif; ?>
      </div>

    </div>
  </section>

  <!-- ── Posts grid ── -->
  <section class="author-posts container">
    <h2 class="author-posts__title">Publicaciones</h2>

    <?php if ($posts_query->have_posts()) : ?>
    <div class="author-posts__grid">
      <?php while ($posts_query->have_posts()) : $posts_query->the_post();
        $type      = get_post_type();
        $thumb     = get_the_post_thumbnail_url(null, 'medium_large');
        $type_labels = ['routes' => 'Ruta', 'point-of-interest' => 'POI', 'post' => 'Bitácora'];
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

    <!-- Pagination -->
    <?php
    echo paginate_links([
        'total'   => $posts_query->max_num_pages,
        'current' => $paged,
        'before_page_number' => '<span>',
        'after_page_number'  => '</span>',
    ]);
    ?>

    <?php else : ?>
    <p class="author-posts__empty">Aún no hay publicaciones.</p>
    <?php endif; ?>

  </section>

</main>

<?php get_template_part('parts/footer'); ?>
