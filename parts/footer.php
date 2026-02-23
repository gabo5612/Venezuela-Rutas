<footer>
    <div class="container">
        <div class="footer-top">
            <a href="<?php echo home_url(); ?>" class="logo">
                <div>
                    <h4 class="material-symbols-rounded"><span>timer</span> <?php echo esc_html(get_bloginfo('name')); ?></h4>
            </a>
            <p><?php echo esc_html(get_option('blogdescription')); ?></p>
        </div>
        <div class="footer_menus">
            <div class="footer_menu">
                <h4>Categories</h4>
                <?php
                wp_nav_menu(array(
                    'theme_location' => 'menu',
                    'container' => false,
                    'menu_class' => 'footer_menu_items',
                ));
                ?>
            </div>
            <div class="footer_menu">
                <h4>Quick Links</h4>
                <?php
                wp_nav_menu(array(
                    'theme_location' => 'footer',
                    'container' => false,
                    'menu_class' => 'footer_menu_items',
                ));
                ?>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <h6>&copy; <?php echo date('Y'); ?> <?php echo esc_html(get_bloginfo('name')); ?>.</h6>
        <h6>Made with <span class="material-symbols-rounded">favorite</span> by <a target="_blank" href="https://ve.linkedin.com/in/gabriel-oniel-arias/">Gabriel Arias</a></h6>
    </div>
    </div>
</footer>
<?php wp_footer(); ?>
</body>

</html>