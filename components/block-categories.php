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

    <?php if (have_rows('categories', 'option')) : ?>
    <div class="block-categories__list">
      <?php while (have_rows('categories', 'option')) : the_row();
        $link = get_sub_field('categorie');
        if (!$link) continue;
      ?>
      <a href="<?php echo esc_url($link['url']); ?>"
         target="<?php echo esc_attr($link['target'] ?: '_self'); ?>"
         class="block-categories__item" data-animate="fade-up">
        <span class="material-symbols-outlined">terrain</span>
        <?php echo esc_html($link['title']); ?>
      </a>
      <?php endwhile; ?>
    </div>
    <?php endif; ?>

  </div>
</section>
