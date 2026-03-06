<?php

get_template_part('parts/header');

$eyebrow  = get_field('gallery_eyebrow') ?: 'Archivo Visual';
$title    = get_field('gallery_title')   ?: get_the_title();
$desc     = get_field('gallery_desc')    ?: '';
$sections = get_field('gallery_sections') ?: [];

// Si no hay secciones, intentar campo de galería simple
$simple_gallery = get_field('gallery_images') ?: [];
?>

<div class="page-gallery">

  <!-- ══ HERO HEADER ══════════════════════════════════════════ -->
  <header class="gallery-hero">
    <div class="gallery-hero__inner container">
      <div class="gallery-hero__eyebrow">
        <span class="material-symbols-outlined">photo_library</span>
        <?php echo esc_html($eyebrow); ?>
      </div>
      <h1 class="gallery-hero__title"><?php echo esc_html($title); ?></h1>
      <?php if ($desc) : ?>
      <p class="gallery-hero__desc"><?php echo esc_html($desc); ?></p>
      <?php endif; ?>
    </div>
  </header>

  <!-- ══ SECTIONS ═════════════════════════════════════════════ -->
  <?php if ($sections) :
    $global_index = 0;
    $all_images = [];
    foreach ($sections as $sec) {
      foreach (($sec['section_images'] ?: []) as $img) {
        $all_images[] = $img;
      }
    }
  ?>

  <?php foreach ($sections as $s => $section) :
    $images = $section['section_images'] ?: [];
    if (empty($images)) continue;
    $sec_title = $section['section_title'] ?: '';
  ?>
  <section class="gallery-section">
    <?php if ($sec_title) : ?>
    <div class="gallery-section__header container">
      <h2 class="gallery-section__title"><?php echo esc_html($sec_title); ?></h2>
    </div>
    <?php endif; ?>

    <div class="masonry-grid masonry-grid--full js-masonry"
         data-gallery-id="gallery-section-<?php echo $s; ?>">
      <?php foreach ($images as $i => $img) :
        $href = $img['sizes']['large'] ?? $img['url'];
      ?>
      <a class="masonry-grid__item"
         href="<?php echo esc_url($href); ?>"
         data-glightbox="gallery: gallery-section-<?php echo $s; ?>; description: <?php echo esc_attr($img['alt'] ?? ''); ?>"
         data-index="<?php echo $i; ?>">
        <img src="<?php echo esc_url($img['url']); ?>"
             alt="<?php echo esc_attr($img['alt'] ?? ''); ?>"
             loading="lazy">
        <div class="masonry-grid__overlay">
          <span class="material-symbols-outlined">zoom_in</span>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endforeach; ?>

  <?php elseif ($simple_gallery) : ?>

  <section class="gallery-section">
    <div class="masonry-grid masonry-grid--full js-masonry"
         data-gallery-id="gallery-page-<?php echo get_the_ID(); ?>">
      <?php foreach ($simple_gallery as $i => $img) :
        $href = $img['sizes']['large'] ?? $img['url'];
      ?>
      <a class="masonry-grid__item"
         href="<?php echo esc_url($href); ?>"
         data-glightbox="gallery: gallery-page-<?php echo get_the_ID(); ?>; description: <?php echo esc_attr($img['alt'] ?? ''); ?>"
         data-index="<?php echo $i; ?>">
        <img src="<?php echo esc_url($img['url']); ?>"
             alt="<?php echo esc_attr($img['alt'] ?? ''); ?>"
             loading="lazy">
        <div class="masonry-grid__overlay">
          <span class="material-symbols-outlined">zoom_in</span>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </section>

  <?php else : ?>
  <div class="gallery-empty container">
    <span class="material-symbols-outlined">photo_library</span>
    <p>No hay imágenes todavía.</p>
  </div>
  <?php endif; ?>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  if (typeof GLightbox === 'undefined') return;
  document.querySelectorAll('.js-masonry').forEach(function (grid) {
    var id = grid.dataset.galleryId;
    GLightbox({ selector: '[data-glightbox*="' + id + '"]', loop: true, touchNavigation: true });
  });
});
</script>

<?php get_template_part('parts/footer'); ?>
