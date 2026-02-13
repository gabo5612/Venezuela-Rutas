<section class="posts container">
    <h2 class="section-title"><?php echo esc_html(get_sub_field("title")); ?></h2>

    <div class="posts-container" id="postsContainer">
        <?php
        $q = new WP_Query([
            'post_type'           => 'post',
            'post_status'         => 'publish',
            'posts_per_page'      => 4,
            'paged'               => 1,
            'ignore_sticky_posts' => true,
        ]);

        if ($q->have_posts()):
            while ($q->have_posts()): $q->the_post(); ?>
                <a href="<?php the_permalink(); ?>" class="post-card card-shadow">
                    <?php
                        $categories = get_the_category();
                        $catIcon = '4'; 

                        if (!empty($categories)) {
                            $catName = $categories[0]->name;

                            if ($catName === 'Tech') {
                                $catIcon = '1';
                            } elseif ($catName === 'Hogar') {
                                $catIcon = '2';
                            } elseif ($catName === 'Makeup') {
                                $catIcon = '3';
                            } else {
                                $catIcon = '4';
                            }

                            echo '<div class="post-category post-icon-' . esc_attr($catIcon) . '">' . esc_html($catName) . '</div>';
                        }
                        ?>

                    <div class="post-image">
                        <?php if (has_post_thumbnail()): ?>
                            <?php the_post_thumbnail('medium'); ?>
                        <?php else: ?>
                            <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/default-post-image.jpg'); ?>" alt="Default Post Image">
                        <?php endif; ?>
                    </div>
                    <div class="post-content">
                        
                        <h3 class="post-title"><?php the_title(); ?></h3>
                        <p class="post-excerpt"><?php echo esc_html(wp_trim_words(get_the_excerpt(), 20, '...')); ?></p>
                    </div>
                </a>
        <?php endwhile;
            wp_reset_postdata();
        endif;
        ?>
    </div>

    <?php if ($q->max_num_pages > 1): ?>
        <div>
            <button id="moreTips" type="button" data-page="2">Load More Tips</button>
        </div>
    <?php endif; ?>
</section>