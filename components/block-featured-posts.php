<?php
$eyebrow    = get_sub_field('eyebrow')    ?: 'Editorial';
$title      = get_sub_field('fp_title')   ?: 'Bitácora de Campo';
$link_label = get_sub_field('link_label') ?: 'Leer Todo';
$link_url   = get_sub_field('link_url')   ?: '';

$fp_posts = get_sub_field('fp_posts') ?: [];
if (!$fp_posts) return;
?>

<section class="block-featured-posts">
  <div class="block-featured-posts__inner">

    <div class="section-header" data-animate="fade-up" data-animate-delay="100">
      <div>
        <div class="section-header__eyebrow"><?php echo esc_html($eyebrow); ?></div>
        <h2 class="section-header__title"><?php echo esc_html($title); ?></h2>
      </div>
      <?php if ($link_url) : ?>
      <a href="<?php echo esc_url($link_url); ?>" class="section-header__link">
        <?php echo esc_html($link_label); ?> <span class="material-symbols-outlined">arrow_right_alt</span>
      </a>
      <?php endif; ?>
    </div>

    <div class="fp-grid">
      <?php foreach ($fp_posts as $row) :
        $post  = $row['post_object'];
        if (!$post) continue;

        $thumb   = get_the_post_thumbnail_url($post->ID, 'large');
        $excerpt = get_the_excerpt($post->ID);
        $date    = get_the_date('d M Y', $post->ID);
      ?>
      <article class="fp-card" data-animate="fade-up">
        <a href="<?php echo esc_url(get_permalink($post->ID)); ?>" class="fp-card__link" aria-label="<?php echo esc_attr($post->post_title); ?>"></a>

        <div class="fp-card__image">
          <?php if ($thumb) : ?>
            <img src="<?php echo esc_url($thumb); ?>" alt="<?php echo esc_attr($post->post_title); ?>">
          <?php else : ?>
            <div class="fp-card__empty"><span class="material-symbols-outlined">terrain</span></div>
          <?php endif; ?>
          <span class="fp-card__date"><?php echo esc_html($date); ?></span>
        </div>

        <div class="fp-card__body">
          <h3 class="fp-card__title"><?php echo esc_html($post->post_title); ?></h3>
          <?php if ($excerpt) : ?>
          <p class="fp-card__excerpt"><?php echo esc_html(wp_trim_words($excerpt, 22, '...')); ?></p>
          <?php endif; ?>
          <span class="fp-card__read">Seguir Leyendo</span>
        </div>
      </article>
      <?php endforeach; ?>
    </div>

  </div>
</section>
