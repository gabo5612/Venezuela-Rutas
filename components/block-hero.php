<?php
$slides = [];

// ── Repeater "slides" ───────────────────────────────────
if ( have_rows('slides') ) {
  while ( have_rows('slides') ) { the_row();
    $slides[] = [
      'type'            => get_sub_field('type')            ?: '',
      'title'           => get_sub_field('title')           ?: '',
      'title_highlight' => get_sub_field('title_highlight') ?: '',
      'desc'            => get_sub_field('description')     ?: '',
      'cta'             => get_sub_field('cta')             ?: '',
      'cta_link'        => get_sub_field('cta_link')        ?: '#',
      'cta_color'       => get_sub_field('cta_color')       ?: 'primary',
      'image'           => get_sub_field('hero_image')      ?: '',
      'video'           => get_sub_field('hero_video')      ?: '',
      'hero_distance'   => get_sub_field('hero_distance')   ?: '',
      'hero_altitude'   => get_sub_field('hero_altitude')   ?: '',
    ];
  }
}


$is_slider = count($slides) > 1;
$slider_id = 'hero-slider-' . uniqid();
?>

<section class="block-hero">
  <div class="block-hero__slider<?php echo $is_slider ? ' js-hero-flickity' : ''; ?>"
       id="<?php echo esc_attr($slider_id); ?>">

    <?php foreach ( $slides as $slide ) :
      $has_stats = $slide['hero_distance'] || $slide['hero_altitude'];
    ?>
    <div class="block-hero__slide">
      <div class="block-hero__inner">

        <!-- ── Columna izquierda: texto ── -->
        <div class="block-hero__content">

          <?php if ( $slide['type'] ) : ?>
          <div class="block-hero__tip">
            <?php echo esc_html($slide['type']); ?>
          </div>
          <?php endif; ?>

          <?php if ( $slide['title'] || $slide['title_highlight'] ) : ?>
          <h2 class="block-hero__title">
            <?php echo esc_html($slide['title']); ?>
            <?php if ( $slide['title_highlight'] ) : ?>
              <span class="block-hero__title-accent"><?php echo esc_html($slide['title_highlight']); ?></span>
            <?php endif; ?>
          </h2>
          <?php endif; ?>

          <?php if ( $slide['desc'] ) : ?>
          <p class="block-hero__desc"><?php echo esc_html($slide['desc']); ?></p>
          <?php endif; ?>

          <?php if ( $slide['cta'] ) : ?>
          <div class="block-hero__cta">
            <a href="<?php echo esc_url($slide['cta_link']); ?>"
               class="btn btn--<?php echo esc_attr($slide['cta_color']); ?>">
              <?php echo esc_html($slide['cta']); ?>
              <span class="material-symbols-outlined">arrow_forward</span>
            </a>
          </div>
          <?php endif; ?>

        </div>

        <!-- ── Columna derecha: imagen + stats ── -->
        <?php if ( $slide['video'] || $slide['image'] ) : ?>
        <div class="block-hero__media">
          <?php if ( $slide['video'] ) : ?>
            <video src="<?php echo esc_url($slide['video']); ?>"
                   autoplay muted loop playsinline class="block-hero__video"></video>
          <?php else : ?>
            <img src="<?php echo esc_url($slide['image']); ?>"
                 alt="<?php echo esc_attr($slide['title'] . ' ' . $slide['title_highlight']); ?>"
                 class="block-hero__img">
          <?php endif; ?>

          <?php if ( $has_stats ) : ?>
          <div class="block-hero__stats-card">
            <?php if ( $slide['hero_distance'] ) : ?>
            <div class="block-hero__stat">
              <span class="block-hero__stat-value"><?php echo esc_html($slide['hero_distance']); ?></span>
              <span class="block-hero__stat-label">Distancia</span>
            </div>
            <?php endif; ?>
            <?php if ( $slide['hero_altitude'] ) : ?>
            <div class="block-hero__stat">
              <span class="block-hero__stat-value"><?php echo esc_html($slide['hero_altitude']); ?></span>
              <span class="block-hero__stat-label">Altitud</span>
            </div>
            <?php endif; ?>
          </div>
          <?php endif; ?>
        </div>
        <?php endif; ?>

      </div>
    </div>
    <?php endforeach; ?>

  </div>
</section>

<?php if ( $is_slider ) : ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var el = document.getElementById('<?php echo esc_js($slider_id); ?>');
  if (el && typeof Flickity !== 'undefined') {
    new Flickity(el, {
      wrapAround:      true,
      autoPlay:        false,
      adaptiveHeight:  true,
      prevNextButtons: false,
      pageDots:        true,
      cellAlign:       'left',
      imagesLoaded:    true,
    });
  }
});
</script>
<?php endif; ?>
