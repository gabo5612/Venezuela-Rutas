<?php get_template_part('parts/header'); ?>

<main>

  <!-- ══ HERO ══════════════════════════════════════════ -->
  <section class="home-hero">
    <div class="home-hero__inner">

      <div class="home-hero__content">
        <span class="home-hero__eyebrow">
          <span class="material-symbols-outlined">sensors</span>
          <?php echo esc_html( get_bloginfo('description') ?: 'Rutas activas en Venezuela' ); ?>
        </span>
        <h1 class="home-hero__title">
          Conquista lo <br>Salvaje
        </h1>
        <p class="home-hero__subtitle">
          Mapeo de aventuras para el explorador venezolano. Navega rutas con topografía, datos de elevación e inteligencia comunitaria.
        </p>
        <div class="home-hero__actions">
          <a href="<?php echo esc_url( home_url('/rutas') ); ?>" class="btn btn--primary">
            Explorar Rutas <span class="material-symbols-outlined">explore</span>
          </a>
          <a href="#rutas-destacadas" class="btn btn--outline">
            Ver Rutas <span class="material-symbols-outlined">arrow_downward</span>
          </a>
        </div>
      </div>

      <!-- Latest post card -->
      <div class="home-hero__aside">
        <?php
        $latest = new WP_Query(['posts_per_page' => 1, 'post_status' => 'publish']);
        if ($latest->have_posts()) : $latest->the_post(); ?>
        <a href="<?php the_permalink(); ?>" class="home-hero__post-card">
          <?php if (has_post_thumbnail()) : ?>
          <div class="card-image">
            <?php the_post_thumbnail('large'); ?>
          </div>
          <?php endif; ?>
          <div class="card-body">
            <div class="card-meta"><?php echo get_the_date('d M Y'); ?></div>
            <div class="card-title"><?php the_title(); ?></div>
            <span class="card-link">
              Leer ruta <span class="material-symbols-outlined">arrow_right_alt</span>
            </span>
          </div>
        </a>
        <?php wp_reset_postdata(); endif; ?>
      </div>

    </div>
  </section>

  <!-- ══ ACF BLOCKS (page builder sections) ═══════════ -->
  <?php get_template_part('components/blocks'); ?>

</main>

<?php get_template_part('parts/footer'); ?>
