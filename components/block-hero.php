<section class="hero-section" style="height: <?php print get_sub_field('hero_height');?>dvh;">
  
    <div class="hero-content">
        <h1><?php print get_sub_field('title'); ?>
    </h1>
    <p><?php print get_sub_field('description'); ?>
    </p>
    <a href="<?php print get_sub_field('cta_url'); ?>" class="hero-button btn shadow-btn"><?php print get_sub_field('cta'); ?></a>
    </div>
      <picture>
        <source media="(max-width: 768px)" srcset="<?php print get_sub_field('background_image_mobile'); ?>">
        <img src="<?php print get_sub_field('background_image'); ?>" alt="Hero Image" class="hero-image">
    </picture>
</section>