<?php
$card_color = get_sub_field('card_color') ?: 'black';
$slides     = [];

// ── Nuevo: repeater "slides" ────────────────────────────
if ( have_rows('slides') ) {
  while ( have_rows('slides') ) { the_row();
    $slides[] = [
      'type'      => get_sub_field('type')        ?: '',
      'title'     => get_sub_field('title')       ?: '',
      'desc'      => get_sub_field('description') ?: '',
      'cta'       => get_sub_field('cta')         ?: '',
      'cta_link'  => get_sub_field('cta_link')    ?: '#',
      'cta_color' => get_sub_field('cta_color')   ?: 'primary',
      'image'     => get_sub_field('hero_image')  ?: '',
      'video'     => get_sub_field('hero_video')  ?: '',
    ];
  }
}

// ── Fallback: campos legacy sueltos ────────────────────
if ( empty($slides) ) {
  $slides[] = [
    'type'      => get_sub_field('type_of_card') ?: '',
    'title'     => get_sub_field('title')        ?: '',
    'desc'      => get_sub_field('description')  ?: '',
    'cta'       => get_sub_field('cta')          ?: '',
    'cta_link'  => get_sub_field('cta_link')     ?: '#',
    'cta_color' => get_sub_field('cta_color')    ?: 'primary',
    'image'     => get_sub_field('hero_image')   ?: '',
    'video'     => '',
  ];
}

$is_slider = count($slides) > 1;
$slider_id = 'hero-slider-' . uniqid();
?>

<section class="block-hero block-hero--<?php echo esc_attr($card_color); ?>">
  <div class="block-hero__slider<?php echo $is_slider ? ' js-hero-flickity' : ''; ?>"
       id="<?php echo esc_attr($slider_id); ?>">

    <?php foreach ( $slides as $slide ) : ?>
    <div class="block-hero__slide">
      <div class="block-hero__inner">

        <div>
          <?php if ( $slide['type'] ) : ?>
          <div class="block-hero__tip">
            <span class="material-symbols-outlined">route</span>
            <?php echo esc_html($slide['type']); ?>
          </div>
          <?php endif; ?>

          <?php if ( $slide['title'] ) : ?>
          <h2 class="block-hero__title"><?php echo esc_html($slide['title']); ?></h2>
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

        <?php if ( $slide['video'] ) : ?>
        <div class="block-hero__image">
          <video src="<?php echo esc_url($slide['video']); ?>"
                 autoplay muted loop playsinline>
          </video>
        </div>
        <?php elseif ( $slide['image'] ) : ?>
        <div class="block-hero__image">
          <img src="<?php echo esc_url($slide['image']); ?>"
               alt="<?php echo esc_attr($slide['title']); ?>">
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
