 <aside class="">
        <div class="aside-header ">
            <h3><?php echo get_field("title", "option"); ?><span></span></h3>
            <div class="social-icons">
                <?php if (have_rows('social', 'option')): ?>
                    <?php while (have_rows('social', 'option')): the_row(); ?>

                        <?php
                        $icon = get_sub_field('icon', 'option');
                        $url  = get_sub_field('url', 'option');
                        $SN = get_sub_field('social_network', 'option');
                        $style = get_sub_field('style', 'option');
                        ?>

                        <?php if ($url): ?>
                            <a class="sn style-<?php echo esc_attr($style); ?>" href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener noreferrer">
                                <div class="icon-container ">
                                    <span class="material-symbols-rounded">
                                        <?php echo esc_html($icon); ?>
                                    </span>
                                </div>
                                <span class="sr-only"><?php echo esc_html($SN); ?></span>
                            </a>
                        <?php endif; ?>

                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </div>
        <div class="trending ">
            <h3><?php echo get_field("trending_title", "option"); ?><span></span></h3>
            <?php
            $trending = get_field('trending', 'option');

            if ($trending): ?>

                <ul>
                    <?php
                    $i = 1;

                    foreach ($trending as $post):
                        setup_postdata($post); ?>

                        <li> <a class="trending-post" href="<?php the_permalink(); ?>">
                                <span class="number number-<?php echo $i; ?>">0<?php echo $i; ?></span>
                                <span class="title"><?php the_title(); ?></span>
                            </a>
                        </li>

                    <?php
                        $i++;
                    endforeach; ?>
                </ul>

                <?php wp_reset_postdata(); ?>
            <?php endif; ?>
        </div>
    </aside>