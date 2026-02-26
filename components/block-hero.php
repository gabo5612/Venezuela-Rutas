
<section class="hero-cards card-shadow container card-<?php echo get_sub_field("card_color"); ?>">

    <div class="hero-content-container">
        <div class="hero-tip">
            <span class="material-symbols-outlined">route</span><span ><?php echo get_sub_field("type_of_card"); ?></span>
        </div>
        <div class="hero-content">
            <div class="hero-text">
                <h2><?php echo get_sub_field("title"); ?></h2>
                <p><?php echo get_sub_field("description"); ?></p>
                <?php if (get_sub_field("cta")): ?>
                    <a href="<?php echo get_sub_field("cta_link"); ?>" class="btn btn-<?php echo get_sub_field("cta_color"); ?>"><?php echo get_sub_field("cta"); ?><span class="material-symbols-outlined">arrow_forward</span></a>
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