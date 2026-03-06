<?php get_template_part('parts/header'); ?>

<?php $search_query = get_search_query(); ?>

<div class="page-search">

  <!-- ══ HERO / SEARCH BAR ══════════════════════════════ -->
  <section class="search-hero">
    <div class="search-hero__inner container">
      <span class="search-hero__eyebrow">
        <span class="material-symbols-outlined">search</span>
        Búsqueda
      </span>
      <h1 class="search-hero__title">
        <?php if ($search_query) : ?>
          <?php echo esc_html($search_query); ?>
        <?php else : ?>
          Buscar
        <?php endif; ?>
      </h1>
      <?php if (have_posts()) : ?>
        <p class="search-hero__count">
          <?php echo number_format_i18n($GLOBALS['wp_query']->found_posts); ?>
          resultado<?php echo $GLOBALS['wp_query']->found_posts !== 1 ? 's' : ''; ?> encontrado<?php echo $GLOBALS['wp_query']->found_posts !== 1 ? 's' : ''; ?>
        </p>
      <?php endif; ?>

      <!-- Search form -->
      <form role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>" class="search-form">
        <input type="search" name="s" value="<?php echo esc_attr($search_query); ?>"
               placeholder="Buscar rutas, puntos, artículos…" class="search-form__input" autocomplete="off">
        <button type="submit" class="search-form__btn">
          <span class="material-symbols-outlined">search</span>
        </button>
      </form>
    </div>
  </section>

  <!-- ══ RESULTADOS ═════════════════════════════════════ -->
  <div class="page-search__results container">
    <?php if (have_posts()) : ?>
      <div class="posts-grid">
        <?php while (have_posts()) : the_post();
          $thumb     = get_the_post_thumbnail_url(null, 'large');
          $post_type = get_post_type();
          $cats      = get_the_category();
          $diff      = get_field('difficulty') ?: '';
          $dist      = get_field('distance')   ?: '';
          $type_labels = [
            'post'              => ['label' => 'Artículo',         'icon' => 'article'],
            'routes'            => ['label' => 'Ruta',             'icon' => 'route'],
            'point-of-interest' => ['label' => 'Punto de Interés', 'icon' => 'location_on'],
          ];
          $type_info = $type_labels[$post_type] ?? ['label' => ucfirst($post_type), 'icon' => 'draft'];
        ?>
        <article class="post-card search-result-card">
          <div class="post-card__image">
            <?php if ($thumb) : ?>
              <img src="<?php echo esc_url($thumb); ?>" alt="<?php the_title_attribute(); ?>">
            <?php else : ?>
              <div class="post-card__empty">
                <span class="material-symbols-outlined"><?php echo esc_html($type_info['icon']); ?></span>
              </div>
            <?php endif; ?>
            <div class="post-card__badge">
              <span class="badge badge--primary"><?php echo esc_html($type_info['label']); ?></span>
            </div>
          </div>
          <div class="post-card__body">
            <div class="post-card__date">
              <?php if ($dist) : ?><?php echo esc_html($dist); ?> · <?php endif; ?>
              <?php echo get_the_date('d M Y'); ?>
            </div>
            <h4 class="post-card__title"><?php the_title(); ?></h4>
            <p class="post-card__excerpt"><?php echo esc_html(wp_trim_words(get_the_excerpt(), 18, '...')); ?></p>
            <div class="post-card__footer">
              <span class="post-card__author">
                <?php if ($cats) : ?>
                  <?php echo esc_html($cats[0]->name); ?>
                <?php else : ?>
                  <?php the_author(); ?>
                <?php endif; ?>
              </span>
              <a href="<?php the_permalink(); ?>" class="post-card__arrow">
                <span class="material-symbols-outlined">north_east</span>
              </a>
            </div>
          </div>
        </article>
        <?php endwhile; ?>
      </div>

      <?php if ($GLOBALS['wp_query']->max_num_pages > 1) : ?>
      <div class="load-more-wrap">
        <?php
        $next = next_posts($GLOBALS['wp_query']->max_num_pages, false);
        if ($next) : ?>
        <a href="<?php echo esc_url($next); ?>" class="load-more-btn">
          Más resultados
          <span class="material-symbols-outlined">keyboard_double_arrow_right</span>
        </a>
        <?php endif; ?>
      </div>
      <?php endif; ?>

    <?php else : ?>
      <div class="search-empty">
        <span class="material-symbols-outlined">search_off</span>
        <p>No se encontraron resultados para <strong>"<?php echo esc_html($search_query); ?>"</strong></p>
        <p class="search-empty__hint">Intenta con otras palabras clave o explora nuestras rutas.</p>
        <a href="<?php echo esc_url(home_url('/')); ?>" class="btn btn--primary">Ir al Inicio</a>
      </div>
    <?php endif; ?>
  </div>

</div>

<?php get_template_part('parts/footer'); ?>
