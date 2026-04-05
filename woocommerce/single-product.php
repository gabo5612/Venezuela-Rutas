<?php
/**
 * WooCommerce single product — "PDP Full Width" layout
 * Inspired by Stitch design: hero split, bento specs, related products.
 */
defined('ABSPATH') || exit;

get_template_part('parts/header');

wc_print_notices();

while (have_posts()) :
  the_post();
  global $product;
  $product = wc_get_product(get_the_ID());
  if (!$product) continue;

  // ── ACF fields ──────────────────────────────────────────────
  $gear_category  = get_field('product_gear_category')   ?: '';
  $features       = get_field('product_features')        ?: [];
  $specs          = get_field('product_specs')           ?: [];
  $attr_bars      = get_field('product_attributes_meta') ?: [];
  $image_ref      = get_field('product_image_ref')       ?: '';
  $pdf            = get_field('product_pdf');

  // ── WC data ─────────────────────────────────────────────────
  $price_html   = $product->get_price_html();
  $on_sale      = $product->is_on_sale();
  $in_stock     = $product->is_in_stock();
  $gallery_ids  = $product->get_gallery_image_ids();
  $main_img_id  = get_post_thumbnail_id();
  $main_img     = $main_img_id ? wp_get_attachment_image_url($main_img_id, 'large') : '';
  $short_desc   = $product->get_short_description();

  // 4 gallery thumbs (fill with placeholder if fewer)
  $gallery_imgs = array_slice(array_merge(
    $gallery_ids ? array_map(fn($id) => wp_get_attachment_image_url($id, 'medium'), $gallery_ids) : [],
    array_fill(0, 4, '')
  ), 0, 4);
?>

<div class="pdp-wrap">

  <!-- ══ HERO ═════════════════════════════════════════════════ -->
  <section class="pdp-hero">

    <!-- Image column -->
    <div class="pdp-hero__img-col">
      <?php if ($on_sale) : ?>
      <span class="pdp-badge pdp-badge--sale">SALE</span>
      <?php endif; ?>
      <span class="pdp-badge pdp-badge--status <?php echo $in_stock ? 'pdp-badge--in' : 'pdp-badge--out'; ?>">
        <?php echo $in_stock ? 'DEPLOYMENT READY' : 'OUT OF STOCK'; ?>
      </span>

      <?php if ($main_img) : ?>
      <img src="<?php echo esc_url($main_img); ?>"
           alt="<?php the_title_attribute(); ?>"
           class="pdp-hero__img" loading="eager">
      <?php else : ?>
      <div class="pdp-hero__img-placeholder">
        <span class="material-symbols-outlined">inventory_2</span>
      </div>
      <?php endif; ?>

      <?php if ($image_ref) : ?>
      <span class="pdp-hero__img-ref">IMG_REF: <?php echo esc_html($image_ref); ?></span>
      <?php endif; ?>
    </div>

    <!-- Details column -->
    <div class="pdp-hero__details">

      <?php if ($gear_category) : ?>
      <p class="pdp-eyebrow">GEAR CATEGORY: <?php echo esc_html(strtoupper($gear_category)); ?></p>
      <?php endif; ?>

      <h1 class="pdp-title">
        <?php the_title(); ?>
      </h1>

      <div class="pdp-price-row">
        <span class="pdp-price"><?php echo $price_html; ?></span>
      </div>

      <!-- Features / bullets -->
      <?php if (!empty($features)) : ?>
      <ul class="pdp-features">
        <?php foreach ($features as $f) : ?>
        <li class="pdp-features__item">
          <span class="material-symbols-outlined pdp-features__icon">check_circle</span>
          <?php echo esc_html(strtoupper($f['feature_text'])); ?>
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
          REQUEST SPEC SHEET (PDF)
        </a>
        <?php endif; ?>
      </div>

    </div>
  </section>

  <!-- ══ BENTO — NOTES / SPECS / GALLERY ══════════════════════ -->
  <section class="pdp-bento">

    <!-- Field Notes (short description or ACF specs narrative) -->
    <div class="pdp-bento__notes">
      <div class="pdp-bento__hd">
        <span class="material-symbols-outlined pdp-bento__hd-icon">edit_note</span>
        <h2 class="pdp-bento__hd-title">FIELD NOTES // PERFORMANCE</h2>
      </div>

      <?php if ($short_desc) : ?>
      <div class="pdp-bento__body"><?php echo wp_kses_post($short_desc); ?></div>
      <?php else : ?>
      <div class="pdp-bento__body"><?php echo wp_kses_post(get_the_content()); ?></div>
      <?php endif; ?>

      <!-- Attribute bars -->
      <?php if (!empty($attr_bars)) : ?>
      <div class="pdp-attr-bars">
        <?php foreach ($attr_bars as $bar) :
          $pct = min(100, max(0, intval($bar['attribute_value'])));
        ?>
        <div class="pdp-attr-bar">
          <p class="pdp-attr-bar__label"><?php echo esc_html(strtoupper($bar['attribute_name'])); ?></p>
          <div class="pdp-attr-bar__track">
            <div class="pdp-attr-bar__fill" style="width:<?php echo $pct; ?>%"></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- Technical Specs -->
    <div class="pdp-bento__specs">
      <div class="pdp-bento__hd">
        <span class="material-symbols-outlined pdp-bento__hd-icon">settings_input_component</span>
        <h2 class="pdp-bento__hd-title">TECHNICAL SPECIFICATIONS</h2>
      </div>

      <?php if (!empty($specs)) : ?>
      <dl class="pdp-specs-table">
        <?php foreach ($specs as $s) : ?>
        <div class="pdp-specs-table__row">
          <dt><?php echo esc_html(strtoupper($s['spec_label'])); ?></dt>
          <dd><?php echo esc_html(strtoupper($s['spec_value'])); ?></dd>
        </div>
        <?php endforeach; ?>
      </dl>
      <?php else : ?>
      <!-- Fallback: WooCommerce product attributes -->
      <?php
      if (function_exists('wc_display_product_attributes')) {
        wc_display_product_attributes($product);
      }
      ?>
      <?php endif; ?>
    </div>

    <!-- Gallery 2×2 -->
    <div class="pdp-bento__gallery">
      <?php foreach ($gallery_imgs as $i => $img_url) : ?>
      <div class="pdp-gallery-cell">
        <?php if ($img_url) : ?>
        <img src="<?php echo esc_url($img_url); ?>"
             alt="<?php the_title_attribute(); ?> — view <?php echo $i + 1; ?>"
             loading="lazy">
        <?php else : ?>
        <div class="pdp-gallery-cell__placeholder">
          <span class="material-symbols-outlined">image</span>
        </div>
        <?php endif; ?>
        <div class="pdp-gallery-cell__hover"></div>
      </div>
      <?php endforeach; ?>
    </div>

  </section>

  <!-- ══ RELATED PRODUCTS SLIDER ════════════════════════════════ -->
  <?php
  $related_ids = wc_get_related_products(get_the_ID(), 8);
  if (!empty($related_ids)) :
    $related = array_filter(array_map('wc_get_product', $related_ids));
  ?>
  <section class="pdp-related container">
    <div class="pdp-related__hd">
      <div>
        <p class="pdp-related__eyebrow">RELATED ASSETS</p>
        <h2 class="pdp-related__title">RECOMMENDED MISSION GEAR</h2>
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
          $r_cat  = $r_cats ? strtoupper($r_cats[0]->name) : 'GEAR';
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
            <div class="pdp-rcard__overlay">
              <span class="pdp-rcard__ready">READY</span>
            </div>
          </div>
          <div class="pdp-rcard__body">
            <p class="pdp-rcard__cat"><?php echo esc_html($r_cat); ?></p>
            <h3 class="pdp-rcard__name"><?php echo esc_html(strtoupper($rp->get_name())); ?></h3>
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
    var track    = document.querySelector('.js-pdp-track');
    var viewport = track && track.parentElement;
    if (!track) return;

    var origCards = Array.from(track.children);
    var total     = origCards.length;
    var CLONE_N   = Math.min(total, 4);
    var autoTimer = null;
    var isTransitioning = false;

    // Clone cards for infinite loop
    origCards.slice(-CLONE_N).forEach(function (c) {
      track.insertBefore(c.cloneNode(true), track.firstChild);
    });
    origCards.slice(0, CLONE_N).forEach(function (c) {
      track.appendChild(c.cloneNode(true));
    });

    var allCards = Array.from(track.children);
    var current  = CLONE_N;

    function visibleCount() {
      var vw = viewport.offsetWidth;
      if (vw >= 1100) return 4;
      if (vw >= 720)  return 3;
      if (vw >= 480)  return 2;
      return 1;
    }

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
