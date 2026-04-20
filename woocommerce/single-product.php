<?php
/**
 * WooCommerce single product
 */
defined('ABSPATH') || exit;

get_template_part('parts/header');

wc_print_notices();

while (have_posts()) :
  the_post();
  global $product;
  $product = wc_get_product(get_the_ID());
  if (!$product) continue;

  // ACF fields
  $gear_category  = get_field('product_gear_category')   ?: '';
  $features       = get_field('product_features')        ?: [];
  $specs          = get_field('product_specs')           ?: [];
  $attr_bars      = get_field('product_attributes_meta') ?: [];
  $pdf            = get_field('product_pdf');

  // WC data
  $price_html   = $product->get_price_html();
  $on_sale      = $product->is_on_sale();
  $in_stock     = $product->is_in_stock();
  $gallery_ids  = $product->get_gallery_image_ids();
  $main_img_id  = get_post_thumbnail_id();
  $short_desc   = $product->get_short_description();

  // Build image list: main + gallery
  $all_imgs = [];
  if ($main_img_id) {
    $all_imgs[] = [
      'full'  => wp_get_attachment_image_url($main_img_id, 'large'),
      'thumb' => wp_get_attachment_image_url($main_img_id, 'thumbnail'),
      'alt'   => get_post_meta($main_img_id, '_wp_attachment_image_alt', true) ?: get_the_title(),
    ];
  }
  foreach ($gallery_ids as $gid) {
    $all_imgs[] = [
      'full'  => wp_get_attachment_image_url($gid, 'large'),
      'thumb' => wp_get_attachment_image_url($gid, 'thumbnail'),
      'alt'   => get_post_meta($gid, '_wp_attachment_image_alt', true) ?: get_the_title(),
    ];
  }
?>

<div class="pdp-wrap">

  <!-- HERO -->
  <section class="pdp-hero">

    <!-- Image gallery column -->
    <div class="pdp-hero__img-col">
      <?php if ($on_sale) : ?>
      <span class="pdp-badge pdp-badge--sale">Sale</span>
      <?php endif; ?>
      <span class="pdp-badge pdp-badge--status <?php echo $in_stock ? 'pdp-badge--in' : 'pdp-badge--out'; ?>">
        <?php echo $in_stock ? 'In stock' : 'Out of stock'; ?>
      </span>

      <?php if (!empty($all_imgs)) : ?>
      <div class="pdp-gallery" id="pdpGallery">
        <!-- Main viewer -->
        <div class="pdp-gallery__main" id="pdpMain">
          <img src="<?php echo esc_url($all_imgs[0]['full']); ?>"
               alt="<?php echo esc_attr($all_imgs[0]['alt']); ?>"
               class="pdp-gallery__main-img" id="pdpMainImg"
               loading="eager">
          <?php if (count($all_imgs) > 1) : ?>
          <button class="pdp-gallery__arrow pdp-gallery__arrow--prev" id="pdpPrev" aria-label="Previous image">
            <span class="material-symbols-outlined">chevron_left</span>
          </button>
          <button class="pdp-gallery__arrow pdp-gallery__arrow--next" id="pdpNext" aria-label="Next image">
            <span class="material-symbols-outlined">chevron_right</span>
          </button>
          <?php endif; ?>
        </div>

        <!-- Thumbnail strip -->
        <?php if (count($all_imgs) > 1) : ?>
        <div class="pdp-gallery__thumbs" id="pdpThumbs">
          <?php foreach ($all_imgs as $i => $img) : ?>
          <button class="pdp-gallery__thumb<?php echo $i === 0 ? ' pdp-gallery__thumb--active' : ''; ?>"
                  data-index="<?php echo $i; ?>"
                  data-full="<?php echo esc_attr($img['full']); ?>"
                  data-alt="<?php echo esc_attr($img['alt']); ?>"
                  aria-label="View image <?php echo $i + 1; ?>">
            <img src="<?php echo esc_url($img['thumb']); ?>"
                 alt="<?php echo esc_attr($img['alt']); ?>"
                 loading="lazy">
          </button>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

      <?php else : ?>
      <div class="pdp-hero__img-placeholder">
        <span class="material-symbols-outlined">inventory_2</span>
      </div>
      <?php endif; ?>
    </div>

    <!-- Details column -->
    <div class="pdp-hero__details">

      <?php if ($gear_category) : ?>
      <p class="pdp-eyebrow"><?php echo esc_html($gear_category); ?></p>
      <?php endif; ?>

      <h1 class="pdp-title">
        <?php the_title(); ?>
      </h1>

      <div class="pdp-price-row">
        <span class="pdp-price"><?php echo $price_html; ?></span>
      </div>

      <?php if (!empty($features)) : ?>
      <ul class="pdp-features">
        <?php foreach ($features as $f) : ?>
        <li class="pdp-features__item">
          <span class="material-symbols-outlined pdp-features__icon">check_circle</span>
          <?php echo esc_html($f['feature_text']); ?>
        </li>
        <?php endforeach; ?>
      </ul>
      <?php elseif ($short_desc) : ?>
      <div class="pdp-short-desc"><?php echo wp_kses_post($short_desc); ?></div>
      <?php endif; ?>

      <!-- Add to cart -->
      <div class="pdp-actions">
        <?php woocommerce_template_single_add_to_cart(); ?>

        <?php if ($pdf) : ?>
        <a href="<?php echo esc_url(is_array($pdf) ? $pdf['url'] : $pdf); ?>"
           target="_blank" rel="noopener" class="pdp-pdf-btn">
          <span class="material-symbols-outlined">picture_as_pdf</span>
          Download spec sheet (PDF)
        </a>
        <?php endif; ?>
      </div>

    </div>
  </section>

  <!-- BENTO — NOTES / SPECS / GALLERY -->
  <section class="pdp-bento">

    <div class="pdp-bento__notes">
      <div class="pdp-bento__hd">
        <span class="material-symbols-outlined pdp-bento__hd-icon">edit_note</span>
        <h2 class="pdp-bento__hd-title">Description</h2>
      </div>

      <?php if ($short_desc) : ?>
      <div class="pdp-bento__body"><?php echo wp_kses_post($short_desc); ?></div>
      <?php else : ?>
      <div class="pdp-bento__body"><?php echo wp_kses_post(get_the_content()); ?></div>
      <?php endif; ?>

      <?php if (!empty($attr_bars)) : ?>
      <div class="pdp-attr-bars">
        <?php foreach ($attr_bars as $bar) :
          $pct = min(100, max(0, intval($bar['attribute_value'])));
        ?>
        <div class="pdp-attr-bar">
          <p class="pdp-attr-bar__label"><?php echo esc_html($bar['attribute_name']); ?></p>
          <div class="pdp-attr-bar__track">
            <div class="pdp-attr-bar__fill" style="width:<?php echo $pct; ?>%"></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <div class="pdp-bento__specs">
      <div class="pdp-bento__hd">
        <span class="material-symbols-outlined pdp-bento__hd-icon">settings_input_component</span>
        <h2 class="pdp-bento__hd-title">Specifications</h2>
      </div>

      <?php if (!empty($specs)) : ?>
      <dl class="pdp-specs-table">
        <?php foreach ($specs as $s) : ?>
        <div class="pdp-specs-table__row">
          <dt><?php echo esc_html($s['spec_label']); ?></dt>
          <dd><?php echo esc_html($s['spec_value']); ?></dd>
        </div>
        <?php endforeach; ?>
      </dl>
      <?php else : ?>
      <?php
      if (function_exists('wc_display_product_attributes')) {
        wc_display_product_attributes($product);
      }
      ?>
      <?php endif; ?>
    </div>

    <!-- Gallery 2×2 -->
    <?php if (!empty($all_imgs)) :
      $bento_imgs = array_slice($all_imgs, 1, 4);
    ?>
    <div class="pdp-bento__gallery">
      <?php
      $placeholders_needed = 4 - count($bento_imgs);
      foreach ($bento_imgs as $i => $img) : ?>
      <div class="pdp-gallery-cell">
        <img src="<?php echo esc_url($img['full']); ?>"
             alt="<?php echo esc_attr($img['alt']); ?>"
             loading="lazy">
        <div class="pdp-gallery-cell__hover"></div>
      </div>
      <?php endforeach; ?>
      <?php for ($p = 0; $p < $placeholders_needed; $p++) : ?>
      <div class="pdp-gallery-cell">
        <div class="pdp-gallery-cell__placeholder">
          <span class="material-symbols-outlined">image</span>
        </div>
      </div>
      <?php endfor; ?>
    </div>
    <?php endif; ?>

  </section>

  <!-- RELATED PRODUCTS -->
  <?php
  $related_ids = wc_get_related_products(get_the_ID(), 8);
  if (!empty($related_ids)) :
    $related = array_filter(array_map('wc_get_product', $related_ids));
  ?>
  <section class="pdp-related container">
    <div class="pdp-related__hd">
      <div>
        <p class="pdp-related__eyebrow">Shop</p>
        <h2 class="pdp-related__title">You might also like</h2>
      </div>
      <div class="pdp-slider-nav">
        <button class="pdp-slider-btn js-pdp-prev" aria-label="Previous">
          <span class="material-symbols-outlined">chevron_left</span>
        </button>
        <button class="pdp-slider-btn js-pdp-next" aria-label="Next">
          <span class="material-symbols-outlined">chevron_right</span>
        </button>
      </div>
    </div>

    <div class="pdp-slider-viewport">
      <div class="pdp-related__grid js-pdp-track">
        <?php foreach ($related as $rp) :
          $r_img  = wp_get_attachment_image_url(get_post_thumbnail_id($rp->get_id()), 'medium');
          $r_cats = get_the_terms($rp->get_id(), 'product_cat');
          $r_cat  = $r_cats ? $r_cats[0]->name : '';
        ?>
        <a href="<?php echo esc_url(get_permalink($rp->get_id())); ?>" class="pdp-rcard">
          <div class="pdp-rcard__img-wrap">
            <?php if ($r_img) : ?>
            <img src="<?php echo esc_url($r_img); ?>"
                 alt="<?php echo esc_attr($rp->get_name()); ?>"
                 loading="lazy">
            <?php else : ?>
            <div class="pdp-rcard__img-placeholder">
              <span class="material-symbols-outlined">inventory_2</span>
            </div>
            <?php endif; ?>
          </div>
          <div class="pdp-rcard__body">
            <?php if ($r_cat) : ?>
            <p class="pdp-rcard__cat"><?php echo esc_html($r_cat); ?></p>
            <?php endif; ?>
            <h3 class="pdp-rcard__name"><?php echo esc_html($rp->get_name()); ?></h3>
            <div class="pdp-rcard__foot">
              <span class="pdp-rcard__price"><?php echo $rp->get_price_html(); ?></span>
              <span class="material-symbols-outlined pdp-rcard__cart-icon">add_shopping_cart</span>
            </div>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <script>
  (function () {
    // ── Image gallery ──────────────────────────────────────────
    var mainImg  = document.getElementById('pdpMainImg');
    var thumbs   = document.querySelectorAll('.pdp-gallery__thumb');
    var prevBtn  = document.getElementById('pdpPrev');
    var nextBtn  = document.getElementById('pdpNext');
    if (!mainImg || !thumbs.length) return;

    var total   = thumbs.length;
    var current = 0;

    function goTo(idx) {
      current = (idx + total) % total;
      thumbs.forEach(function (t, i) {
        t.classList.toggle('pdp-gallery__thumb--active', i === current);
      });
      var active = thumbs[current];
      mainImg.src = active.dataset.full;
      mainImg.alt = active.dataset.alt || '';
    }

    thumbs.forEach(function (btn) {
      btn.addEventListener('click', function () { goTo(parseInt(btn.dataset.index, 10)); });
    });
    if (prevBtn) prevBtn.addEventListener('click', function () { goTo(current - 1); });
    if (nextBtn) nextBtn.addEventListener('click', function () { goTo(current + 1); });

    // Touch swipe
    var tx = 0;
    mainImg.addEventListener('touchstart', function (e) { tx = e.touches[0].clientX; }, {passive: true});
    mainImg.addEventListener('touchend', function (e) {
      var dx = tx - e.changedTouches[0].clientX;
      if (Math.abs(dx) > 40) goTo(dx > 0 ? current + 1 : current - 1);
    }, {passive: true});
  }());

  // ── Related slider ─────────────────────────────────────────
  (function () {
    var track    = document.querySelector('.js-pdp-track');
    var viewport = track && track.parentElement;
    if (!track) return;

    var origCards = Array.from(track.children);
    var total     = origCards.length;
    var CLONE_N   = Math.min(total, 4);
    var autoTimer = null;
    var isTransitioning = false;

    origCards.slice(-CLONE_N).forEach(function (c) { track.insertBefore(c.cloneNode(true), track.firstChild); });
    origCards.slice(0, CLONE_N).forEach(function (c) { track.appendChild(c.cloneNode(true)); });

    var allCards = Array.from(track.children);
    var current  = CLONE_N;

    function cardWidth() {
      if (!allCards[0]) return 0;
      return allCards[0].offsetWidth + parseFloat(getComputedStyle(track).gap || 24);
    }

    function jumpTo(idx) {
      current = idx;
      track.style.transition = 'none';
      track.style.transform  = 'translateX(-' + (current * cardWidth()) + 'px)';
    }

    function goTo(idx) {
      if (isTransitioning) return;
      isTransitioning = true;
      current = idx;
      track.style.transition = 'transform .45s cubic-bezier(.25,.46,.45,.94)';
      track.style.transform  = 'translateX(-' + (current * cardWidth()) + 'px)';
    }

    track.addEventListener('transitionend', function () {
      isTransitioning = false;
      var realEnd = CLONE_N + total;
      if (current >= realEnd)  jumpTo(CLONE_N + (current - realEnd));
      else if (current < CLONE_N) jumpTo(realEnd - CLONE_N + current);
    });

    function startAuto() {
      clearInterval(autoTimer);
      autoTimer = setInterval(function () { goTo(current + 1); }, 5000);
    }

    document.querySelector('.js-pdp-prev').addEventListener('click', function () { goTo(current - 1); startAuto(); });
    document.querySelector('.js-pdp-next').addEventListener('click', function () { goTo(current + 1); startAuto(); });

    viewport.addEventListener('mouseenter', function () { clearInterval(autoTimer); });
    viewport.addEventListener('mouseleave', startAuto);

    var tx = 0;
    viewport.addEventListener('touchstart', function (e) { tx = e.touches[0].clientX; }, {passive: true});
    viewport.addEventListener('touchend', function (e) {
      var dx = tx - e.changedTouches[0].clientX;
      if (Math.abs(dx) > 40) { goTo(dx > 0 ? current + 1 : current - 1); startAuto(); }
    }, {passive: true});

    jumpTo(CLONE_N);
    track.getBoundingClientRect();
    startAuto();
    window.addEventListener('resize', function () { jumpTo(current); });
  }());
  </script>
  <?php endif; ?>

</div>

<?php endwhile; ?>

<?php get_template_part('parts/footer'); ?>
