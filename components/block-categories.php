<section class="category-container container">
    <h1>
        <?php echo get_field('title', 'option'); ?>
    </h1>
    <div>
     <?php if (have_rows('categories', 'option')) : ?>

  <?php $counter = 1; ?>

  <ul class="categories-list">

    <?php while (have_rows('categories', 'option')) : the_row(); 
      
      $link = get_sub_field('categorie');

      if ($link) :

        $title  = $link['title'];
        $url    = $link['url'];
        $target = $link['target'] ? $link['target'] : '_self';
    ?>

        <li class="category btn category-<?php echo $counter; ?>">
          <a href="<?php echo esc_url($url); ?>" target="<?php echo esc_attr($target); ?>">
            <?php echo esc_html($title); ?>
          </a>
        </li>

    <?php 
        $counter++;

        if ($counter > 4) {
          $counter = 1;
        }

      endif; 
    endwhile; ?>

  </ul>

<?php endif; ?>
    </div>
</section>