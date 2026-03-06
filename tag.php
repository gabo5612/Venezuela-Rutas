<?php get_template_part('parts/header'); ?>

<?php
$queried_tag = get_queried_object();
$tag_name    = $queried_tag ? $queried_tag->name : 'Tag';
$tag_desc    = $queried_tag ? $queried_tag->description : '';
?>

<div class="page-category">

  <!-- ══ HERO ══════════════════════════════════════════ -->
  <section class="cat-hero">
    <div class="cat-hero__bg"></div>
    <div class="cat-hero__inner">
      <span class="cat-hero__eyebrow">
        <span class="material-symbols-outlined">label</span>
        Etiqueta
      </span>
      <h1 class="cat-hero__title"><?php echo esc_html(strtoupper($tag_name)); ?></h1>
      <?php if ($tag_desc) : ?>
        <p class="cat-hero__desc"><?php echo esc_html($tag_desc); ?></p>
      <?php endif; ?>
      <div class="cat-hero__actions">
        <a href="<?php echo esc_url(home_url('/')); ?>" class="btn btn--primary">Ver Rutas</a>
      </div>
    </div>
  </section>

  <!-- ══ FILTROS: OTRAS TAGS ════════════════════════════ -->
  <div class="page-category__filters">
    <div class="filter-pills">
      <span class="filter-pills__label">Etiquetas:</span>
      <a href="<?php echo esc_url(get_post_type_archive_link('routes') ?: home_url('/')); ?>"
         class="filter-pills__pill">
        <span class="material-symbols-outlined">map</span> Todas
      </a>
      <?php
      $all_tags = get_tags(['hide_empty' => true]);
      foreach ($all_tags as $tag) :
        $is_cur    = $queried_tag && $queried_tag->term_id === $tag->term_id;
        $pill_href = $is_cur
          ? (get_post_type_archive_link('routes') ?: home_url('/'))
          : get_tag_link($tag->term_id);
      ?>
      <a href="<?php echo esc_url($pill_href); ?>"
         class="filter-pills__pill <?php echo $is_cur ? 'filter-pills__pill--active' : ''; ?>">
        <?php echo esc_html($tag->name); ?>
        <?php if ($is_cur) : ?>
          <span class="material-symbols-outlined" style="font-size:.9rem">close</span>
        <?php endif; ?>
      </a>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- ══ GRID DE POSTS ══════════════════════════════════ -->
  <div class="page-category__grid">
    <div class="posts-grid">
      <?php
      $is_first = true;
      if (have_posts()) : while (have_posts()) : the_post();
        $thumb = get_the_post_thumbnail_url(null, 'large');
        $cats  = get_the_category();
        $diff  = get_field('difficulty') ?: '';
        $dist  = get_field('distance')   ?: '';
        $time  = get_field('time')       ?: '';

        if ($is_first) : $is_first = false; ?>

        <!-- Post destacado (spans 2 cols) -->
        <article class="post-card--featured">
          <div class="featured-bg" <?php if ($thumb) echo 'style="background-image:url(\'' . esc_url($thumb) . '\')"'; ?>></div>
          <div class="featured-overlay"></div>
          <div class="featured-tags">
            <?php if ($cats) : ?><span class="badge badge--primary"><?php echo esc_html($cats[0]->name); ?></span><?php endif; ?>
            <?php if ($diff) : ?><span class="badge badge--orange"><?php echo esc_html($diff); ?></span><?php endif; ?>
          </div>
          <div class="featured-body">
            <h3 class="featured-title"><?php the_title(); ?></h3>
            <p class="featured-excerpt"><?php echo esc_html(wp_trim_words(get_the_excerpt(), 20, '...')); ?></p>
            <div class="featured-meta">
              <?php if ($dist) : ?>
                <div class="featured-stat"><span class="material-symbols-outlined">distance</span><?php echo esc_html($dist); ?></div>
              <?php endif; ?>
              <?php if ($time) : ?>
                <div class="featured-stat"><span class="material-symbols-outlined">schedule</span><?php echo esc_html($time); ?></div>
              <?php endif; ?>
              <span class="featured-read">Leer <span class="material-symbols-outlined">arrow_right_alt</span></span>
            </div>
          </div>
          <a href="<?php the_permalink(); ?>" class="featured-link" aria-label="<?php the_title_attribute(); ?>"></a>
        </article>

        <?php else : ?>

        <!-- Post normal -->
        <div class="post-card" data-animate="fade-up">
          <a href="<?php the_permalink(); ?>" class="post-card__link" aria-label="<?php the_title_attribute(); ?>"></a>
          <div class="post-card__image">
            <?php if ($thumb) : ?>
              <img src="<?php echo esc_url($thumb); ?>" alt="<?php the_title_attribute(); ?>">
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
                <?php foreach (array_slice(wp_get_post_terms(get_the_ID(), 'post_tag'), 0, 2) as $pt) : ?>
                  <a href="<?php echo esc_url(get_term_link($pt)); ?>" class="badge badge--outline"><?php echo esc_html($pt->name); ?></a>
                <?php endforeach; ?>
              </div>
              <span class="post-card__arrow">
                <span class="material-symbols-outlined">north_east</span>
              </span>
            </div>
          </div>
        </div>
        <?php endif; endwhile; else : ?>
        <p style="color:var(--text-muted);padding:2rem 0;">No hay entradas con esta etiqueta.</p>
        <?php endif; ?>
    </div>

    <?php if ($GLOBALS['wp_query']->max_num_pages > 1) : ?>
    <div class="load-more-wrap">
      <?php
      $next = next_posts($GLOBALS['wp_query']->max_num_pages, false);
      if ($next) : ?>
      <a href="<?php echo esc_url($next); ?>" class="load-more-btn">
        Cargar Más
        <span class="material-symbols-outlined">keyboard_double_arrow_right</span>
      </a>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>

</div>

<?php get_template_part('parts/footer'); ?>
