<?php get_template_part('parts/header'); ?>

<div class="page-archive-poi">

  <!-- ══ HERO ══════════════════════════════════════════ -->
  <section class="archive-hero archive-hero--poi">
    <div class="archive-hero__inner container">
      <span class="archive-hero__eyebrow">
        <span class="material-symbols-outlined">location_on</span>
        Puntos de Interés
      </span>
      <h1 class="archive-hero__title">Atlas de Venezuela</h1>
      <p class="archive-hero__desc">Lugares, miradores, cascadas y puntos notables documentados en el campo.</p>
    </div>
  </section>

  <!-- ══ FILTROS POR CATEGORÍA ══════════════════════════ -->
  <div class="page-archive-poi__filters">
    <div class="filter-pills">
      <span class="filter-pills__label">Tipo:</span>
      <?php
      $poi_cats = get_categories(['hide_empty' => true]);
      $icons    = ['location_on','forest','water','filter_hdr','wb_sunny','landscape','park'];
      foreach ($poi_cats as $i => $cat) :
        $icon = $icons[$i % count($icons)];
      ?>
      <a href="<?php echo esc_url(get_category_link($cat->term_id)); ?>"
         class="filter-pills__pill">
        <span class="material-symbols-outlined"><?php echo esc_html($icon); ?></span>
        <?php echo esc_html($cat->name); ?>
      </a>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- ══ GRID DE POIs ═══════════════════════════════════ -->
  <div class="page-archive-poi__grid">
    <div class="posts-grid">
      <?php
      $is_first = true;
      if (have_posts()) : while (have_posts()) : the_post();
        $thumb    = get_the_post_thumbnail_url(null, 'large');
        $cats     = get_the_category();
        $lat      = get_field('latitude')  ?: '';
        $lon      = get_field('longitude') ?: '';
        $location = get_field('location')  ?: '';

        if ($is_first) : $is_first = false; ?>

        <!-- POI destacado (spans 2 cols) -->
        <article class="post-card--featured">
          <div class="featured-bg" <?php if ($thumb) echo 'style="background-image:url(\'' . esc_url($thumb) . '\')"'; ?>></div>
          <div class="featured-overlay"></div>
          <div class="featured-tags">
            <?php if ($cats) : ?><span class="badge badge--primary"><?php echo esc_html($cats[0]->name); ?></span><?php endif; ?>
            <?php if ($location) : ?><span class="badge badge--outline"><?php echo esc_html($location); ?></span><?php endif; ?>
          </div>
          <div class="featured-body">
            <h3 class="featured-title"><?php the_title(); ?></h3>
            <p class="featured-excerpt"><?php echo esc_html(wp_trim_words(get_the_excerpt(), 20, '...')); ?></p>
            <div class="featured-meta">
              <?php if ($lat && $lon) : ?>
                <div class="featured-stat">
                  <span class="material-symbols-outlined">my_location</span>
                  <?php echo esc_html(number_format($lat, 4)) . ', ' . esc_html(number_format($lon, 4)); ?>
                </div>
              <?php endif; ?>
              <span class="featured-read">Ver Punto <span class="material-symbols-outlined">arrow_right_alt</span></span>
            </div>
          </div>
          <a href="<?php the_permalink(); ?>" class="featured-link" aria-label="<?php the_title_attribute(); ?>"></a>
        </article>

        <?php else : ?>

        <!-- POI normal -->
        <div class="post-card" data-animate="fade-up">
          <a href="<?php the_permalink(); ?>" class="post-card__link" aria-label="<?php the_title_attribute(); ?>"></a>
          <div class="post-card__image">
            <?php if ($thumb) : ?>
              <img src="<?php echo esc_url($thumb); ?>" alt="<?php the_title_attribute(); ?>">
            <?php else : ?>
              <div class="post-card__empty"><span class="material-symbols-outlined">location_on</span></div>
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
            <p class="post-card__excerpt"><?php echo esc_html(wp_trim_words(get_the_excerpt(), 18, '...')); ?></p>
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
        <p style="color:var(--text-muted);padding:2rem 0;">No hay puntos de interés disponibles.</p>
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
