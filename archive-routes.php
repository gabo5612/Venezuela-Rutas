<?php get_template_part('parts/header'); ?>

<?php
$queried_tag = get_queried_object();
$active_tag  = is_tag() ? $queried_tag : null;
?>

<div class="page-archive-routes">

  <!-- ══ HERO ══════════════════════════════════════════ -->
  <section class="archive-hero">
    <div class="archive-hero__inner container">
      <span class="archive-hero__eyebrow">
        <span class="material-symbols-outlined">route</span>
        Venezuela Rutas
      </span>
      <h1 class="archive-hero__title">Todas las Rutas</h1>
      <p class="archive-hero__desc">Explora nuestra colección de rutas documentadas a través de Venezuela.</p>
    </div>
  </section>

  <!-- ══ FILTROS POR TAG ════════════════════════════════ -->
  <div class="page-archive-routes__filters">
    <div class="filter-pills">
      <span class="filter-pills__label">Filtrar:</span>
      <a href="<?php echo esc_url(get_post_type_archive_link('routes')); ?>"
         class="filter-pills__pill <?php echo !$active_tag ? 'filter-pills__pill--active' : ''; ?>">
        <span class="material-symbols-outlined">map</span> Todas
      </a>
      <?php
      $route_tags = get_tags(['hide_empty' => true]);
      foreach ($route_tags as $tag) :
        $is_cur = $active_tag && $active_tag->term_id === $tag->term_id;
      ?>
      <a href="<?php echo esc_url(get_tag_link($tag->term_id)); ?>"
         class="filter-pills__pill <?php echo $is_cur ? 'filter-pills__pill--active' : ''; ?>">
        <?php echo esc_html($tag->name); ?>
      </a>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- ══ GRID DE RUTAS ══════════════════════════════════ -->
  <div class="page-archive-routes__grid">
    <div class="posts-grid">
      <?php
      $is_first = true;
      if (have_posts()) : while (have_posts()) : the_post();
        $thumb = get_the_post_thumbnail_url(null, 'large');
        $tags  = get_the_tags();
        $diff  = get_field('difficulty') ?: '';
        $dist  = get_field('distance')   ?: '';
        $time  = get_field('time')       ?: '';
        $elev  = get_field('elevation')  ?: '';

        if ($is_first) : $is_first = false; ?>

        <!-- Ruta destacada (spans 2 cols) -->
        <article class="post-card--featured">
          <div class="featured-bg" <?php if ($thumb) echo 'style="background-image:url(\'' . esc_url($thumb) . '\')"'; ?>></div>
          <div class="featured-overlay"></div>
          <div class="featured-tags">
            <?php if ($tags) : ?><span class="badge badge--primary"><?php echo esc_html($tags[0]->name); ?></span><?php endif; ?>
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
              <?php if ($elev) : ?>
                <div class="featured-stat"><span class="material-symbols-outlined">moving</span><?php echo esc_html($elev); ?></div>
              <?php endif; ?>
              <span class="featured-read">Ver Ruta <span class="material-symbols-outlined">arrow_right_alt</span></span>
            </div>
          </div>
          <a href="<?php the_permalink(); ?>" class="featured-link" aria-label="<?php the_title_attribute(); ?>"></a>
        </article>

        <?php else : ?>

        <!-- Ruta normal -->
        <article class="post-card">
          <div class="post-card__image">
            <?php if ($thumb) : ?>
              <img src="<?php echo esc_url($thumb); ?>" alt="<?php the_title_attribute(); ?>">
            <?php else : ?>
              <div class="post-card__empty"><span class="material-symbols-outlined">route</span></div>
            <?php endif; ?>
            <?php if ($diff) : ?>
            <div class="post-card__badge">
              <span class="badge badge--orange"><?php echo esc_html($diff); ?></span>
            </div>
            <?php endif; ?>
          </div>
          <div class="post-card__body">
            <div class="post-card__date">
              <?php if ($dist) : ?><span><?php echo esc_html($dist); ?></span><?php endif; ?>
              <?php if ($time) : ?><span>· <?php echo esc_html($time); ?></span><?php endif; ?>
            </div>
            <h4 class="post-card__title"><?php the_title(); ?></h4>
            <p class="post-card__excerpt"><?php echo esc_html(wp_trim_words(get_the_excerpt(), 18, '...')); ?></p>
            <div class="post-card__footer">
              <?php if ($tags) : ?>
                <span class="post-card__author"><?php echo esc_html($tags[0]->name); ?></span>
              <?php else : ?>
                <span class="post-card__author">Ruta</span>
              <?php endif; ?>
              <a href="<?php the_permalink(); ?>" class="post-card__arrow">
                <span class="material-symbols-outlined">north_east</span>
              </a>
            </div>
          </div>
        </article>

        <?php endif; endwhile; else : ?>
        <p style="color:var(--text-muted);padding:2rem 0;">No hay rutas disponibles.</p>
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
