<?php get_template_part('parts/header'); ?>

<main class="post-page posts-aside-wrapper container">

    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
            <?php
            $categories = get_the_category();
            $catIcon = '4';

            if (!empty($categories)) {
                $catName = $categories[0]->name;

                if ($catName === 'Hogar') {
                    $catIcon = '1';
                } elseif ($catName === 'Tech') {
                    $catIcon = '2';
                } elseif ($catName === 'Lifestyle') {
                    $catIcon = '3';
                } else {
                    $catIcon = '4';
                }
            }
            ?>
            <article class="post">
                <div class="share-section">
                    <a class="btn category category-<?php echo $catIcon; ?>" href="/category/<?php echo esc_html($catName); ?>" target="_black">
                        <?php echo esc_html($catName); ?>
                    </a>
                    <span class="material-symbols-outlined shareBtn" >Share</span>
                    <span class="material-symbols-outlined printBtn" >print</span>
                </div>
                <h1><?php the_title(); ?></h1>
                    <div class="embed-container">
                <?php if(the_field('embed')): ?>
                    <div class="embed-container">
                        <?php the_field('embed'); ?>
                    </div>
                <?php endif; ?> 
                    </div>
                <div class="post-content">
                    <?php the_content(); ?>
                </div>
                <section class="continue-reading ">
                    <div class="share-section share-2">
                        <h3>Más artículos</h3>   <span class="material-symbols-outlined shareBtn" >Share</span>
                    <span class="material-symbols-outlined printBtn" >print</span>
                    </div>
                    <div class="posts-container" id="postsContainer">
                        <?php
                        $queried = get_queried_object();

                        $current_cat_id = (is_category() && !empty($queried) && isset($queried->term_id))
                            ? (int) $queried->term_id
                            : 0;

                        $args = [
                            'post_type'           => 'post',
                            'post_status'         => 'publish',
                            'posts_per_page'      => 2,
                            'paged'               => 1,
                            'ignore_sticky_posts' => true,
                        ];

                        // Si estamos en un archive de categoría, filtramos por esa categoría
                        if ($current_cat_id) {
                            $args['cat'] = $current_cat_id;
                        }

                        $q = new WP_Query($args);


                        if ($q->have_posts()):
                            while ($q->have_posts()): $q->the_post(); ?>
                                <a href="<?php the_permalink(); ?>" class="post-card card-shadow-hover">
                                    <?php
                                    $categories = get_the_category();
                                    $catIcon = '4';

                                    if (!empty($categories)) {
                                        $catName = $categories[0]->name;

                                        if ($catName === 'Hogar') {
                                            $catIcon = '1';
                                        } elseif ($catName === 'Tech') {
                                            $catIcon = '2';
                                        } elseif ($catName === 'Lifestyle') {
                                            $catIcon = '3';
                                        } else {
                                            $catIcon = '4';
                                        }

                                        echo '<div class="post-category post-icon-' . esc_attr($catIcon) . '">' . esc_html($catName) . '</div>';
                                    }
                                    ?>

                                 <div class="post-image post-video">
                            <?php if (get_field('video_featured')): ?>
                                <video autoplay loop muted playsinline>
                                    <source src="<?php echo esc_url(get_field('video_featured')); ?>" type="video/mp4">
                                    Your browser does not support the video tag.
                                </video>
                             <?php elseif (has_post_thumbnail()): ?>
                                <?php the_post_thumbnail('medium'); ?>
                            <?php else: ?>
                                <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/default-post-image.jpg'); ?>" alt="Default Post Image">
                            <?php endif; ?>
                        </div>
                                    <article class="post-content">

                                        <h3 class="post-title"><?php the_title(); ?></h3>
                                        <p class="post-excerpt"><?php echo esc_html(wp_trim_words(get_the_excerpt(), 20, '...')); ?></p>
                                        <div class="post-author">
                                            <div class="author-avatar">
                                                <?php echo get_avatar(get_the_author_meta('ID'), 40); ?>
                                            </div>
                                            <h5 class="author-name">
                                                <?php echo esc_html(get_the_author()); ?>
                                            </h5>
                                        </div>

                                    </article>
                                </a>
                        <?php endwhile;
                            wp_reset_postdata();
                        endif;
                        ?>
                    </div>
                </section>
            </article>

    <?php endwhile;
    endif; ?>
    <?php get_template_part('parts/aside'); ?>
</main>
<script>
  const shareData = {
    title: "<?php echo esc_js(get_the_title()); ?>",
    text: "<?php echo esc_js(get_the_excerpt()); ?>",
    url: "<?php echo esc_url(get_permalink()); ?>"
  };

  document.querySelectorAll('.shareBtn').forEach((btn) => {
    btn.addEventListener('click', () => {
      if (navigator.share) {
        navigator.share(shareData);
      } else {
        navigator.clipboard?.writeText(shareData.url);
        alert('Link copiado');
      }
    });
  });

  document.querySelectorAll('.printBtn').forEach((btn) => {
    btn.addEventListener('click', () => window.print());
  });
</script>

<?php get_template_part('parts/footer'); ?>