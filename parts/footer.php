<footer class="container">
    <h5>footer</h5>
        <div class="footer_menu" >
        <?php
        wp_nav_menu(array(
            'theme_location' => 'footer',
            'container' => false,
            'menu_class' => 'footer_menu_items',
        ));
        ?>
    </div>
</footer>
<?php wp_footer(); ?>
</body>
</html>