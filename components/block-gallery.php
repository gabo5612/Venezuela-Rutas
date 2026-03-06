<?php
$eyebrow = get_sub_field('gallery_eyebrow') ?: '';
$title   = get_sub_field('gallery_title')   ?: '';
$desc    = get_sub_field('gallery_desc')    ?: '';

// Helper: detect video by mime_type or extension
function gallery_is_video( $item ) {
  if ( isset($item['mime_type']) && strpos($item['mime_type'], 'video/') === 0 ) return true;
  if ( isset($item['url']) ) {
    $ext = strtolower(pathinfo($item['url'], PATHINFO_EXTENSION));
    return in_array($ext, ['mp4', 'webm', 'ogg', 'mov'], true);
  }
  return false;
}

$sections = [];
if ( have_rows('gallery_sections') ) {
  while ( have_rows('gallery_sections') ) { the_row();
    $raw = get_sub_field('section_images') ?: [];
    $normalized = [];
    foreach ( $raw as $item ) {
      if ( is_array($item) ) {
        $normalized[] = $item;
      } else {
        $normalized[] = [ 'url' => $item, 'alt' => '', 'mime_type' => '', 'sizes' => [] ];
      }
    }
    if ( $normalized ) {
      $sections[] = [
        'title'  => get_sub_field('section_title') ?: '',
        'images' => $normalized,
      ];
    }
  }
}

if ( empty($sections) ) return;

$block_id = uniqid('gallery-blk-');
?>

<section class="block-gallery">

  <?php if ( $eyebrow || $title ) : ?>
  <div class="block-gallery__header container">
    <?php if ( $eyebrow ) : ?>
    <div class="section-header__eyebrow"><?php echo esc_html($eyebrow); ?></div>
    <?php endif; ?>
    <?php if ( $title ) : ?>
    <h2 class="section-header__title"><?php echo esc_html($title); ?></h2>
    <?php endif; ?>
    <?php if ( $desc ) : ?>
    <p class="block-gallery__desc"><?php echo esc_html($desc); ?></p>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <?php foreach ( $sections as $s => $section ) :
    $gid = $block_id . '-' . $s;
  ?>
  <div class="gallery-section">
    <?php if ( $section['title'] ) : ?>
    <div class="gallery-section__header container">
      <h3 class="gallery-section__title"><?php echo esc_html($section['title']); ?></h3>
    </div>
    <?php endif; ?>

    <div class="masonry-grid masonry-grid--full js-masonry"
         data-gallery-id="<?php echo esc_attr($gid); ?>">
      <?php foreach ( $section['images'] as $i => $item ) :
        $src    = $item['url'] ?? '';
        $alt    = $item['alt'] ?? '';
        $is_vid = gallery_is_video($item);
        $href   = $is_vid ? $src : ($item['sizes']['large'] ?? $src);
      ?>
      <?php if ( $is_vid ) : ?>
      <div class="masonry-grid__item masonry-grid__item--video js-gal-item"
           data-type="video"
           data-src="<?php echo esc_url($src); ?>">
        <video src="<?php echo esc_url($src); ?>"
               muted loop playsinline preload="metadata"
               class="masonry-grid__video"></video>
        <div class="masonry-grid__overlay masonry-grid__overlay--play">
          <span class="material-symbols-outlined">play_circle</span>
        </div>
      </div>
      <?php else : ?>
      <a class="masonry-grid__item js-gal-item"
         data-type="image"
         data-src="<?php echo esc_url($href); ?>"
         href="<?php echo esc_url($href); ?>">
        <img src="<?php echo esc_url($src); ?>"
             alt="<?php echo esc_attr($alt); ?>"
             loading="lazy">
        <div class="masonry-grid__overlay">
          <span class="material-symbols-outlined">zoom_in</span>
        </div>
      </a>
      <?php endif; ?>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endforeach; ?>

</section>

<script>
(function () {
  // Hover preview for video thumbnails
  document.querySelectorAll('.masonry-grid__item--video').forEach(function (item) {
    var vid = item.querySelector('video');
    if (!vid) return;
    item.addEventListener('mouseenter', function () { vid.play(); });
    item.addEventListener('mouseleave', function () { vid.pause(); vid.currentTime = 0; });
  });
  // Unified click → gallery modal
  document.querySelectorAll('.js-masonry').forEach(function (grid) {
    var items = Array.from(grid.querySelectorAll('.js-gal-item'));
    items.forEach(function (el, idx) {
      el.addEventListener('click', function (e) {
        e.preventDefault();
        if (typeof openGalleryModal === 'function') openGalleryModal(items, idx);
      });
    });
  });
}());
</script>
