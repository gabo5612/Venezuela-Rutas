<?php
$block_title = get_field('cat_title', 'option') ?: 'Categorías';
?>

<section class="block-categories">
  <div class="block-categories__inner">

    <div class="section-header" data-animate="fade-up" data-animate-delay="100">
      <div>
        <div class="section-header__eyebrow">Explorar Por</div>
        <h2 class="section-header__title"><?php echo esc_html($block_title); ?></h2>
      </div>
    </div>

    <?php if (have_rows('categories')) : ?>
    <div class="block-categories__list">
      <?php while (have_rows('categories')) : the_row();
        $tag  = get_sub_field('post_tag');
        $icon = get_sub_field('icon') ?: 'terrain';
        if (!$tag) continue;
      ?>
      <a href="<?php echo esc_url(get_term_link($tag)); ?>"
         class="block-categories__item" data-animate="fade-up">
        <span class="material-symbols-outlined"><?php echo esc_html($icon); ?></span>
        <?php echo esc_html($tag->name); ?>
      </a>
      <?php endwhile; ?>
    </div>
    <?php endif; ?>

  </div>
</section>
