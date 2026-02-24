
<div class="posts-aside-wrapper container">
    <section class="posts">
        <h2 class="section-title"><?php echo get_sub_field('post_title'); ?></h2>

        <div class="posts-container" id="postsContainer">
            <?php
            $queried = get_queried_object();

            $current_cat_id = (is_category() && !empty($queried) && isset($queried->term_id))
                ? (int) $queried->term_id
                : 0;

            $args = [
                'post_type'           => 'post',
                'post_status'         => 'publish',
                'posts_per_page'      => 4,
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

        <?php if ($q->max_num_pages > 1): ?>
            <div class="load-more-container">
                <button id="moreTips" class="btn" type="button" data-page="2">Load More Tips</button>
            </div>
        <?php endif; ?>
    </section>
    <?php get_template_part('parts/aside'); ?>
</div>