<?php if(have_rows('blocks')): ?>
    <?php while(have_rows('blocks')): the_row(); 
             $layout = get_row_layout();?>
         <?php get_template_part('components/block', $layout); ?>
    <?php endwhile; ?>
<?php endif; ?>