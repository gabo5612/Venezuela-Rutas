<?php get_template_part('parts/header'); ?>

<main class="interest-page">

    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
    
        <article class="route">
            <h1><?php the_title(); ?></h1>
            
            <div class="route-content">
                <?php the_content(); ?>
            </div>
        </article>

    <?php endwhile; endif; ?>

</main>

<?php get_template_part('parts/footer'); ?>
