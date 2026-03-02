<?php
$heading = get_sub_field('heading') ?: 'Por qué Rutas';
?>

<section class="block-features">
  <div class="block-features__inner">

    <div class="section-header">
      <div>
        <div class="section-header__eyebrow">Protocolo de Misión</div>
        <h2 class="section-header__title"><?php echo esc_html($heading); ?></h2>
      </div>
    </div>

    <?php if (have_rows('items')) : ?>
    <div class="block-features__grid">
      <?php while (have_rows('items')) : the_row();
        $icon  = get_sub_field('icon')  ?: 'terrain';
        $title = get_sub_field('title') ?: '';
        $text  = get_sub_field('text')  ?: '';
      ?>
      <div class="block-features__item">
        <div class="feat-icon">
          <span class="material-symbols-outlined"><?php echo esc_html($icon); ?></span>
        </div>
        <?php if ($title) : ?><div class="feat-title"><?php echo esc_html($title); ?></div><?php endif; ?>
        <?php if ($text)  : ?><div class="feat-text"><?php echo esc_html($text); ?></div><?php endif; ?>
      </div>
      <?php endwhile; ?>
    </div>
    <?php endif; ?>

  </div>
</section>
