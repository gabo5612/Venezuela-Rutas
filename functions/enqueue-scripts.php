<?php

/**
 * Enqueue scripts & styles
 */
function site_scripts()
{
     $site_css_path = get_template_directory() . '/dist/styles/app.min.css';
        if (file_exists($site_css_path)) {
            wp_enqueue_style(
                'site-css',
                get_template_directory_uri() . '/dist/styles/app.min.css',
                array(),
                filemtime($site_css_path)
            );
        }
    if (is_front_page()) {
        $app_js_path = get_template_directory() . '/dist/scripts/main.js';
        if (file_exists($app_js_path)) {
            wp_enqueue_script(
                'homepage-js',
                get_template_directory_uri() . '/dist/scripts/main.js',
                filemtime($app_js_path),
                true
            );
        }
       
    } else {

       /* wp_enqueue_script(
            'masonry-js',
            '//unpkg.com/isotope-layout@3/dist/isotope.pkgd.min.js',
            array('jquery'),
            '',
            true
        );

        wp_enqueue_script(
            'popper',
            '//cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js',
            array('jquery'),
            '',
            true
        );

        wp_enqueue_script(
            'bootstrap',
            '//stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js',
            array('jquery', 'popper'),
            '',
            true
        );

        wp_enqueue_script(
            'jquery-lazy',
            'https://cdnjs.cloudflare.com/ajax/libs/jquery.lazy/1.7.10/jquery.lazy.min.js',
            array('jquery'),
            '1.7.10',
            true
        );

        $lazy_init = <<<JS
(function($){
  $(function(){
    if ($('.lazy').length) {
      $('.lazy').Lazy({
        attribute: 'data-bg',
        afterLoad: function(element){
          var bg = element.attr('data-bg');
          if (bg) {
            element.css('background-image', 'url(' + bg + ')');
            element.removeAttr('data-bg');
          }
        }
      });
    }
  });
})(jQuery);
JS;

        wp_add_inline_script('jquery-lazy', $lazy_init, 'after');

        wp_enqueue_style(
            'flickity-css',
            'https://unpkg.com/flickity@2/dist/flickity.min.css',
            array(),
            '2.3.0'
        );

        wp_enqueue_script(
            'flickity',
            'https://unpkg.com/flickity@2/dist/flickity.pkgd.min.js',
            array('jquery'),
            '2.3.0',
            true
        );

        wp_enqueue_script(
            'jquery-flip',
            'https://cdn.jsdelivr.net/gh/nnattawat/flip@1.1.2/dist/jquery.flip.min.js',
            array('jquery'),
            '1.1.2',
            true
        );
    }

    // CSS / JS por plantilla
    $template_styles = array(
        'page-cometogetherandgrill.php' => 'sweepstakes.css',
        'page-contact.php'              => 'contact.css',
        'page-heritage.php'             => 'heritage.css',
        'page-lamb.php'                 => 'veal.css',
        'page-lowes.php'                => 'lowes.css',
        'page-meat-masterclass.php'     => 'masterclass.css',
        'page-offers.php'               => 'offers.css',
        'page-privacy-policy.php'       => 'privacy.css',
        'page-products.php'             => 'products.css',
        'page-sitemap.php'              => 'privacy.css',
        'page-store-locator.php'        => 'locator.css',
        'page-sustainability.php'       => 'sustain.css',
        'page-sustainabilityUpdate.php' => 'sustain.css',
        'page-tailgate-with-swift.php'  => 'tailgate_with_swift.css',
        'page-terms.php'                => 'privacy.css',
        'page-tips-recipes.php'         => 'tips.css',
        'page-veal.php'                 => 'veal.css',
        'page-custom-recipes.php'       => 'custom_recipes.css',
        'search.php'                    => 'search.css',
        'single-tips.php'               => 'recipes.css',
        'single-products.php'           => 'product.css',
    );

    foreach ($template_styles as $template => $style_file) {
        if (
            (is_page_template($template)) ||
            ($template === 'search.php' && is_search()) ||
            ($template === 'single-tips.php' && is_singular('tips')) ||
            ($template === 'single-products.php' && is_singular('products'))
        ) {
            $style_path = get_template_directory() . "/dist/styles/{$style_file}";
            if (file_exists($style_path)) {
                wp_enqueue_style(
                    "{$template}-style",
                    get_template_directory_uri() . "/dist/styles/{$style_file}",
                    array('site-css'),
                    filemtime($style_path)
                );
            }

            $js_file = get_template_directory() . "/dist/scripts/" . basename($style_file, '.css') . ".js";
            if (file_exists($js_file)) {
                wp_enqueue_script(
                    basename($style_file, '.css') . "-script",
                    get_template_directory_uri() . "/dist/scripts/" . basename($style_file, '.css') . ".js",
                    array('jquery'),
                    filemtime($js_file),
                    true
                );
            }
        }*/
    }

    if (!is_front_page()) {
        $app_js_path = get_template_directory() . '/dist/scripts/main.js';
        if (file_exists($app_js_path)) {
            wp_enqueue_script(
                'site-js',
                get_template_directory_uri() . '/dist/scripts/main.js',
                array('jquery'),
                filemtime($app_js_path),
                true
            );
        }
    }
}
add_action('wp_enqueue_scripts', 'site_scripts', 9999);


