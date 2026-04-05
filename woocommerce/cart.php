<?php
/**
 * WooCommerce cart — "Load Manifest" layout
 * Desktop: sidebar Mission_Summary + main Load_Manifest items
 * Mobile: stacked, sticky Execute_Protocol CTA
 */
defined('ABSPATH') || exit;

// Ensure WC cart is available
if (!function_exists('WC') || !WC()->cart) {
    wp_redirect(home_url('/'));
    exit;
}

get_template_part('parts/header');
?>

<div class="lm-wrap">

  <!-- ══ HERO ════════════════════════════════════════════════════ -->
  <section class="lm-hero">
    <div class="lm-hero__inner">
      <p class="lm-hero__eyebrow">
        <span class="material-symbols-outlined">inventory_2</span>
        QUARTERMASTER
      </p>
      <h1 class="lm-hero__title">LOAD_MANIFEST</h1>
    </div>
  </section>

  <?php if (wc_notice_count() > 0) : ?>
  <div class="lm-notices">
    <?php wc_print_notices(); ?>
  </div>
  <?php endif; ?>

  <?php if (WC()->cart->is_empty()) : ?>

  <!-- ══ EMPTY ════════════════════════════════════════════════════ -->
  <div class="lm-empty">
    <span class="material-symbols-outlined lm-empty__icon">remove_shopping_cart</span>
    <p class="lm-empty__msg">MISSION MANIFEST IS EMPTY</p>
    <a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>" class="lm-empty__btn">
      <span class="material-symbols-outlined">storefront</span>
      BROWSE QUARTERMASTER
    </a>
  </div>

  <?php else : ?>

  <!-- ══ MAIN LAYOUT ══════════════════════════════════════════════ -->
  <div class="lm-layout container">

    <!-- ── Items column ──────────────────────────────────────────── -->
    <div class="lm-items">
      <div class="lm-items__hd">
        <span class="material-symbols-outlined">checklist</span>
        <h2 class="lm-items__title">EQUIPMENT MANIFEST</h2>
        <span class="lm-items__count"><?php echo WC()->cart->get_cart_contents_count(); ?> ITEMS</span>
      </div>

      <form class="woocommerce-cart-form" action="<?php echo esc_url(wc_get_cart_url()); ?>" method="post">

        <?php foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) :
          $product   = $cart_item['data'];
          $product_id = $cart_item['product_id'];
          $qty        = $cart_item['quantity'];
          $img_id     = get_post_thumbnail_id($product_id);
          $img_url    = $img_id ? wp_get_attachment_image_url($img_id, 'thumbnail') : '';
          $cats       = get_the_terms($product_id, 'product_cat');
          $cat_name   = $cats ? strtoupper($cats[0]->name) : 'GEAR';
          $line_price = WC()->cart->get_product_subtotal($product, $qty);
          $permalink  = get_permalink($product_id);
        ?>
        <div class="lm-row" data-key="<?php echo esc_attr($cart_item_key); ?>">

          <!-- Image -->
          <a href="<?php echo esc_url($permalink); ?>" class="lm-row__img-wrap">
            <?php if ($img_url) : ?>
            <img src="<?php echo esc_url($img_url); ?>"
                 alt="<?php echo esc_attr($product->get_name()); ?>"
                 class="lm-row__img" loading="lazy">
            <?php else : ?>
            <div class="lm-row__img-placeholder">
              <span class="material-symbols-outlined">inventory_2</span>
            </div>
            <?php endif; ?>
          </a>

          <!-- Info -->
          <div class="lm-row__info">
            <p class="lm-row__cat"><?php echo esc_html($cat_name); ?></p>
            <a href="<?php echo esc_url($permalink); ?>" class="lm-row__name">
              <?php echo esc_html(strtoupper($product->get_name())); ?>
            </a>
            <?php if ($product->is_on_sale()) : ?>
            <span class="lm-row__badge">SALE</span>
            <?php endif; ?>
          </div>

          <!-- Qty stepper -->
          <div class="lm-row__qty">
            <button type="button" class="lm-qty-btn js-lm-minus" aria-label="Decrease quantity">
              <span class="material-symbols-outlined">remove</span>
            </button>
            <input
              type="number"
              class="lm-qty-input"
              name="cart[<?php echo esc_attr($cart_item_key); ?>][qty]"
              value="<?php echo esc_attr($qty); ?>"
              min="0"
              step="1"
              aria-label="Quantity for <?php echo esc_attr($product->get_name()); ?>"
              data-key="<?php echo esc_attr($cart_item_key); ?>"
            >
            <button type="button" class="lm-qty-btn js-lm-plus" aria-label="Increase quantity">
              <span class="material-symbols-outlined">add</span>
            </button>
          </div>

          <!-- Price -->
          <div class="lm-row__price"><?php echo $line_price; ?></div>

          <!-- Remove -->
          <a href="<?php echo esc_url(wc_get_cart_remove_url($cart_item_key)); ?>"
             class="lm-row__remove"
             aria-label="Remove <?php echo esc_attr($product->get_name()); ?>">
            <span class="material-symbols-outlined">delete_outline</span>
          </a>

        </div>
        <?php endforeach; ?>

        <?php wp_nonce_field('woocommerce-cart', 'woocommerce-cart-nonce'); ?>

      </form>

      <!-- Continue shopping -->
      <a href="<?php echo esc_url(apply_filters('woocommerce_continue_shopping_redirect', wc_get_raw_referer() ?: wc_get_page_permalink('shop'))); ?>"
         class="lm-continue">
        <span class="material-symbols-outlined">arrow_back</span>
        CONTINUE BROWSING
      </a>
    </div>

    <!-- ── Mission Summary sidebar ──────────────────────────────── -->
    <aside class="lm-summary">
      <div class="lm-summary__hd">
        <span class="material-symbols-outlined">summarize</span>
        <h2 class="lm-summary__title">MISSION_SUMMARY</h2>
      </div>

      <dl class="lm-summary__lines">
        <div class="lm-summary__line">
          <dt>SUBTOTAL</dt>
          <dd><?php echo WC()->cart->get_cart_subtotal(); ?></dd>
        </div>

        <?php foreach (WC()->cart->get_fees() as $fee) : ?>
        <div class="lm-summary__line">
          <dt><?php echo esc_html(strtoupper($fee->name)); ?></dt>
          <dd><?php echo wc_price($fee->total); ?></dd>
        </div>
        <?php endforeach; ?>

        <?php foreach (WC()->cart->get_coupons() as $code => $coupon) : ?>
        <div class="lm-summary__line lm-summary__line--discount">
          <dt>
            <span class="material-symbols-outlined">local_offer</span>
            <?php echo esc_html(strtoupper($code)); ?>
          </dt>
          <dd>-<?php echo wc_price(WC()->cart->get_coupon_discount_amount($code, WC()->cart->display_cart_ex_tax)); ?></dd>
        </div>
        <?php endforeach; ?>

        <?php if (WC()->cart->needs_shipping() && WC()->cart->show_shipping()) : ?>
        <div class="lm-summary__line lm-summary__line--shipping">
          <dt>SHIPPING</dt>
          <dd><?php woocommerce_shipping_calculator(); ?></dd>
        </div>
        <?php endif; ?>

        <?php if (wc_coupons_enabled()) : ?>
        <div class="lm-summary__coupon">
          <form class="lm-coupon-form" action="<?php echo esc_url(wc_get_cart_url()); ?>" method="post">
            <input type="text"
                   name="coupon_code"
                   class="lm-coupon-input"
                   placeholder="PROMO CODE"
                   aria-label="Coupon code">
            <?php wp_nonce_field('woocommerce-cart', 'woocommerce-cart-nonce'); ?>
            <button type="submit" name="apply_coupon" value="Apply coupon" class="lm-coupon-btn">
              APPLY
            </button>
          </form>
          <?php foreach (WC()->cart->get_applied_coupons() as $code) : ?>
          <div class="lm-coupon-applied">
            <span class="material-symbols-outlined">local_offer</span>
            <?php echo esc_html(strtoupper($code)); ?>
            <a href="<?php echo esc_url(add_query_arg('remove_coupon', rawurlencode($code), wc_get_cart_url())); ?>"
               class="lm-coupon-remove" aria-label="Remove coupon">×</a>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="lm-summary__line lm-summary__line--total">
          <dt>TOTAL</dt>
          <dd><?php echo WC()->cart->get_total(); ?></dd>
        </div>
      </dl>

      <a href="<?php echo esc_url(wc_get_checkout_url()); ?>" class="lm-execute-btn">
        <span class="material-symbols-outlined">rocket_launch</span>
        EXECUTE_PROTOCOL
      </a>

      <!-- Tax note -->
      <?php if (WC()->cart->get_taxes_total() > 0) : ?>
      <p class="lm-summary__tax-note">
        <?php echo sprintf(__('Includes %s tax', 'woocommerce'), wc_price(WC()->cart->get_taxes_total())); ?>
      </p>
      <?php endif; ?>
    </aside>

  </div>

  <!-- Mobile sticky CTA -->
  <div class="lm-mobile-cta">
    <div class="lm-mobile-cta__total">
      <span>TOTAL</span>
      <span><?php echo WC()->cart->get_total(); ?></span>
    </div>
    <a href="<?php echo esc_url(wc_get_checkout_url()); ?>" class="lm-execute-btn lm-execute-btn--mobile">
      <span class="material-symbols-outlined">rocket_launch</span>
      EXECUTE_PROTOCOL
    </a>
  </div>

  <?php endif; ?>

</div>

<script>
(function () {
  var form = document.querySelector('.woocommerce-cart-form');
  if (!form) return;

  var cartUrl   = form.getAttribute('action');
  var updateTimer = null;

  // ── Stepper buttons ──────────────────────────────────────────────
  document.querySelectorAll('.lm-row').forEach(function (row) {
    var input = row.querySelector('.lm-qty-input');
    var minus = row.querySelector('.js-lm-minus');
    var plus  = row.querySelector('.js-lm-plus');
    if (!input || !minus || !plus) return;

    minus.addEventListener('click', function () {
      var v = Math.max(0, parseInt(input.value, 10) - 1);
      input.value = v;
      scheduleUpdate(row);
    });
    plus.addEventListener('click', function () {
      input.value = parseInt(input.value, 10) + 1;
      scheduleUpdate(row);
    });
    input.addEventListener('change', function () { scheduleUpdate(row); });
  });

  // ── Debounced update: 2s, then POST via fetch ────────────────────
  function scheduleUpdate(activeRow) {
    activeRow.classList.add('lm-row--updating');
    clearTimeout(updateTimer);
    updateTimer = setTimeout(function () { submitCart(); }, 2000);
  }

  function submitCart() {
    // Collect cart form fields
    var params = new URLSearchParams();
    new FormData(form).forEach(function (val, key) {
      params.append(key, val);
    });
    // Tell WC this is a qty update (not a coupon apply)
    params.set('update_cart', '1');

    // Dim all rows while updating
    document.querySelectorAll('.lm-row').forEach(function (r) {
      r.classList.add('lm-row--updating');
    });

    fetch(cartUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: params.toString(),
      redirect: 'follow',
      credentials: 'same-origin'
    })
    .then(function () { window.location.reload(); })
    .catch(function ()  { form.submit(); });
  }
}());
</script>

<?php get_template_part('parts/footer'); ?>
