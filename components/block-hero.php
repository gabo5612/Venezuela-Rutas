<?php
$card_color  = get_sub_field('card_color') ?: 'black';
$type        = get_sub_field('type_of_card') ?: '';
$title       = get_sub_field('title')       ?: '';
$desc        = get_sub_field('description') ?: '';
$cta         = get_sub_field('cta')         ?: '';
$cta_link    = get_sub_field('cta_link')    ?: '#';
$cta_color   = get_sub_field('cta_color')  ?: 'primary';
$hero_image  = get_sub_field('hero_image') ?: '';
?>

<section class="block-hero block-hero--<?php echo esc_attr($card_color); ?>">
  <div class="block-hero__inner">

    <div>
      <?php if ($type) : ?>
      <div class="block-hero__tip">
        <span class="material-symbols-outlined">route</span>
        <?php echo esc_html($type); ?>
      </div>
      <?php endif; ?>

      <?php if ($title) : ?>
        <h2 class="block-hero__title"><?php echo esc_html($title); ?></h2>
      <?php endif; ?>

      <?php if ($desc) : ?>
        <p class="block-hero__desc"><?php echo esc_html($desc); ?></p>
      <?php endif; ?>

      <?php if ($cta) : ?>
      <div class="block-hero__cta">
        <a href="<?php echo esc_url($cta_link); ?>" class="btn btn--<?php echo esc_attr($cta_color); ?>">
          <?php echo esc_html($cta); ?>
          <span class="material-symbols-outlined">arrow_forward</span>
        </a>
      </div>
      <?php endif; ?>
    </div>

    <?php if ($hero_image) : ?>
    <div class="block-hero__image">
      <img src="<?php echo esc_url($hero_image); ?>" alt="<?php echo esc_attr($title); ?>">
    </div>
    <?php endif; ?>

  </div>
</section>
