<?php
$svg_path = get_template_directory() . '/assets/images/hero/shining.svg';
$shining = file_exists($svg_path) ? file_get_contents($svg_path) : '';
?>
<section class="hero-cards container card-<?php echo get_sub_field("card_color"); ?>">

    <div class="hero-content-container">
        <div class="hero-tip"><?php echo $shining; ?>
            <span><?php echo get_sub_field("type_of_card"); ?></span>
        </div>
        <div class="hero-content">
            <div class="hero-text">
                <h2><?php echo get_sub_field("title"); ?></h2>
                <p><?php echo get_sub_field("description"); ?></p>
                <?php if (get_sub_field("cta")): ?>
                    <a href="<?php echo get_sub_field("cta_link"); ?>" class="btn btn-<?php echo get_sub_field("cta_color"); ?>"><?php echo get_sub_field("cta"); ?><span>&#8594;</span></a>
                <?php endif; ?>
            </div>
            <?php if (get_sub_field('hero_image')): ?>
            <div class="hero-image">
                <img src="<?php echo get_sub_field('hero_image') ?>" alt="Hero Image">
            </div>
            <?php endif; ?>
        </div>
    </div>

</section>