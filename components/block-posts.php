<?php
$block_title = get_sub_field('post_title') ?: 'Últimas Rutas';
$post_type   = get_sub_field('post_type')  ?: 'post';
$per_page    = intval(get_sub_field('posts_per_page')) ?: 4;

$q = new WP_Query([
  'post_type'           => $post_type,
  'post_status'         => 'publish',
  'posts_per_page'      => $per_page,
  'paged'               => 1,
  'ignore_sticky_posts' => true,
]);
?>

<section class="block-posts">
  <div class="block-posts__inner">

    <div class="section-header">
      <div>
        <div class="section-header__eyebrow">Explorar</div>
        <h2 class="section-header__title"><?php echo esc_html($block_title); ?></h2>
      </div>
      <a href="<?php echo esc_url( get_post_type_archive_link($post_type) ?: home_url('/') ); ?>" class="section-header__link">
        Ver Todas <span class="material-symbols-outlined">arrow_right_alt</span>
      </a>
    </div>

    <div class="posts-grid" id="postsContainer">
      <?php if ($q->have_posts()) : while ($q->have_posts()) : $q->the_post();
        $thumb = get_the_post_thumbnail_url(null, 'large');
        $cats  = get_the_category();
      ?>
      <a href="<?php the_permalink(); ?>" class="post-card">
        <div class="post-card__image">
          <?php if ($thumb) : ?>
            <img src="<?php echo esc_url($thumb); ?>" alt="<?php the_title_attribute(); ?>">
          <?php elseif (get_field('video_featured')) : ?>
            <video autoplay loop muted playsinline style="width:100%;height:100%;object-fit:cover">
              <source src="<?php echo esc_url(get_field('video_featured')); ?>" type="video/mp4">
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
            <span class="post-card__author"><?php the_author(); ?></span>
            <span class="post-card__arrow"><span class="material-symbols-outlined">north_east</span></span>
          </div>
        </div>
      </a>
      <?php endwhile; wp_reset_postdata(); endif; ?>
    </div>

    <?php if ($q->max_num_pages > 1) : ?>
    <div class="load-more-wrap">
      <button id="moreTips" class="load-more-btn" type="button" data-page="2">
        Cargar Más
        <span class="material-symbols-outlined">keyboard_double_arrow_right</span>
      </button>
    </div>
    <?php endif; ?>

  </div>
</section>
