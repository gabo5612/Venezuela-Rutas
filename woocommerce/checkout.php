<?php
/**
 * WooCommerce Checkout
 */
defined('ABSPATH') || exit;

if (!function_exists('WC') || !WC()->cart) {
    wp_redirect(home_url('/'));
    exit;
}

if (WC()->cart->is_empty()) {
    wp_redirect(wc_get_page_permalink('shop'));
    exit;
}

$checkout = WC()->checkout();

get_template_part('parts/header');
?>

<div class="co-wrap">

  <section class="cat-hero">
    <div class="cat-hero__bg"></div>
    <div class="cat-hero__inner">
      <span class="cat-hero__eyebrow">
        <span class="material-symbols-outlined">shopping_cart_checkout</span>
        Shop
      </span>
      <h1 class="cat-hero__title">Checkout</h1>
    </div>
  </section>

  <?php if (wc_notice_count() > 0) : ?>
  <div class="co-notices container">
    <?php wc_print_notices(); ?>
  </div>
  <?php endif; ?>

  <div class="co-layout container">

    <!-- Form -->
    <div class="co-form-col">
      <form name="checkout" method="post"
            class="checkout woocommerce-checkout co-form"
            action="<?php echo esc_url(wc_get_checkout_url()); ?>"
            enctype="multipart/form-data">

        <?php if ($checkout->is_registration_required() && !is_user_logged_in()) : ?>
        <div class="co-login-note">
          <?php echo esc_html__('You must be logged in to checkout.', 'woocommerce'); ?>
          <a href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>">
            <?php esc_html_e('Login', 'woocommerce'); ?>
          </a>
        </div>
        <?php endif; ?>

        <!-- Contact details -->
        <section class="co-section">
          <div class="co-section__hd">
            <span class="material-symbols-outlined">person</span>
            <h2 class="co-section__title">Contact details</h2>
          </div>
          <div class="co-section__body">
            <div class="co-fields-grid co-fields-grid--2">
              <?php
              $billing_fields = $checkout->get_checkout_fields('billing');
              $contact_keys   = ['billing_first_name','billing_last_name','billing_email','billing_phone'];
              foreach ($contact_keys as $key) :
                if (!isset($billing_fields[$key])) continue;
                $field = $billing_fields[$key];
              ?>
              <div class="co-field<?php echo $key === 'billing_email' ? ' co-field--full' : ''; ?>">
                <label class="co-field__label" for="<?php echo esc_attr($key); ?>">
                  <?php echo esc_html($field['label'] ?? $key); ?>
                  <?php if (!empty($field['required'])) echo '<span class="co-required">*</span>'; ?>
                </label>
                <?php woocommerce_form_field($key, $field, $checkout->get_value($key)); ?>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
        </section>

        <!-- Billing address -->
        <section class="co-section">
          <div class="co-section__hd">
            <span class="material-symbols-outlined">location_on</span>
            <h2 class="co-section__title">Billing address</h2>
          </div>
          <div class="co-section__body">
            <div class="co-fields-grid co-fields-grid--1">
              <?php
              $address_keys = ['billing_company','billing_address_1','billing_address_2','billing_city','billing_postcode','billing_state','billing_country'];
              foreach ($address_keys as $key) :
                if (!isset($billing_fields[$key])) continue;
                $field = $billing_fields[$key];
                $full  = in_array($key, ['billing_address_1','billing_address_2','billing_country']);
              ?>
              <div class="co-field<?php echo $full ? ' co-field--full' : ''; ?>">
                <label class="co-field__label" for="<?php echo esc_attr($key); ?>">
                  <?php echo esc_html($field['label'] ?? $key); ?>
                  <?php if (!empty($field['required'])) echo '<span class="co-required">*</span>'; ?>
                </label>
                <?php woocommerce_form_field($key, $field, $checkout->get_value($key)); ?>
              </div>
              <?php endforeach; ?>
            </div>

            <?php if (WC()->cart->needs_shipping() && wc_ship_to_billing_address_only() === false) : ?>
            <div class="co-ship-toggle">
              <label class="co-toggle-label">
                <input type="checkbox" id="ship-to-different-address-checkbox"
                       name="ship_to_different_address" value="1"
                       <?php checked(1, $checkout->get_value('ship_to_different_address')); ?>>
                <span><?php esc_html_e('Ship to a different address?', 'woocommerce'); ?></span>
              </label>
            </div>
            <div class="co-shipping-fields" id="ship-to-different-address">
              <div class="co-fields-grid co-fields-grid--1">
                <?php foreach ($checkout->get_checkout_fields('shipping') as $key => $field) : ?>
                <div class="co-field">
                  <label class="co-field__label" for="<?php echo esc_attr($key); ?>">
                    <?php echo esc_html($field['label'] ?? $key); ?>
                    <?php if (!empty($field['required'])) echo '<span class="co-required">*</span>'; ?>
                  </label>
                  <?php woocommerce_form_field($key, $field, $checkout->get_value($key)); ?>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
            <?php endif; ?>
          </div>
        </section>

        <!-- Shipping method -->
        <?php if (WC()->cart->needs_shipping()) : ?>
        <section class="co-section">
          <div class="co-section__hd">
            <span class="material-symbols-outlined">local_shipping</span>
            <h2 class="co-section__title">Shipping method</h2>
          </div>
          <div class="co-section__body">
            <div class="co-shipping-methods" id="order_review">
              <?php do_action('woocommerce_checkout_before_order_review'); ?>
              <?php woocommerce_order_review(); ?>
              <?php do_action('woocommerce_checkout_after_order_review'); ?>
            </div>
          </div>
        </section>
        <?php endif; ?>

        <!-- Payment -->
        <section class="co-section">
          <div class="co-section__hd">
            <span class="material-symbols-outlined">payments</span>
            <h2 class="co-section__title">Payment</h2>
          </div>
          <div class="co-section__body co-payment-wrap" id="order_review_payment">
            <?php do_action('woocommerce_checkout_before_order_review'); ?>
            <?php woocommerce_checkout_payment(); ?>
          </div>
        </section>

        <!-- Order notes -->
        <?php foreach ($checkout->get_checkout_fields('order') as $key => $field) : ?>
        <div class="co-section co-section--notes">
          <div class="co-field co-field--full">
            <label class="co-field__label" for="<?php echo esc_attr($key); ?>">
              <?php echo esc_html($field['label'] ?? $key); ?>
            </label>
            <?php woocommerce_form_field($key, $field, $checkout->get_value($key)); ?>
          </div>
        </div>
        <?php endforeach; ?>

        <?php do_action('woocommerce_checkout_after_customer_details'); ?>

      </form>
    </div>

    <!-- Order Summary -->
    <aside class="co-summary">
      <div class="co-summary__inner">

        <div class="co-summary__hd">
          <span class="material-symbols-outlined">receipt_long</span>
          <h2 class="co-summary__title">Order Summary</h2>
          <span class="co-summary__count"><?php echo WC()->cart->get_cart_contents_count(); ?> items</span>
        </div>

        <div class="co-summary__items">
          <?php foreach (WC()->cart->get_cart() as $item) :
            $prod    = $item['data'];
            $prod_id = $item['product_id'];
            $img_id  = get_post_thumbnail_id($prod_id);
            $img_url = $img_id ? wp_get_attachment_image_url($img_id, 'thumbnail') : '';
          ?>
          <div class="co-summary__item">
            <div class="co-summary__item-img">
              <?php if ($img_url) : ?>
              <img src="<?php echo esc_url($img_url); ?>" alt="<?php echo esc_attr($prod->get_name()); ?>">
              <?php else : ?>
              <span class="material-symbols-outlined">inventory_2</span>
              <?php endif; ?>
            </div>
            <div class="co-summary__item-info">
              <p class="co-summary__item-name"><?php echo esc_html($prod->get_name()); ?></p>
              <p class="co-summary__item-qty">Qty: <?php echo esc_html($item['quantity']); ?></p>
              <p class="co-summary__item-price"><?php echo WC()->cart->get_product_subtotal($prod, $item['quantity']); ?></p>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <div class="co-summary__totals">
          <div class="co-summary__total-row">
            <span>Subtotal</span>
            <span><?php echo WC()->cart->get_cart_subtotal(); ?></span>
          </div>

          <?php foreach (WC()->cart->get_coupons() as $code => $coupon) : ?>
          <div class="co-summary__total-row co-summary__total-row--discount">
            <span>
              <span class="material-symbols-outlined">local_offer</span>
              <?php echo esc_html($code); ?>
            </span>
            <span>-<?php echo wc_price(WC()->cart->get_coupon_discount_amount($code, WC()->cart->display_cart_ex_tax)); ?></span>
          </div>
          <?php endforeach; ?>

          <?php foreach (WC()->cart->get_fees() as $fee) : ?>
          <div class="co-summary__total-row">
            <span><?php echo esc_html($fee->name); ?></span>
            <span><?php echo wc_price($fee->total); ?></span>
          </div>
          <?php endforeach; ?>

          <?php if (WC()->cart->get_taxes_total()) : ?>
          <div class="co-summary__total-row">
            <span>Tax</span>
            <span><?php echo wc_price(WC()->cart->get_taxes_total()); ?></span>
          </div>
          <?php endif; ?>

          <?php if (WC()->cart->needs_shipping()) : ?>
          <div class="co-summary__total-row">
            <span>Shipping</span>
            <span><?php echo WC()->cart->get_cart_shipping_total() ?: '—'; ?></span>
          </div>
          <?php endif; ?>

          <div class="co-summary__total-row co-summary__total-row--final">
            <span>Total</span>
            <span><?php echo WC()->cart->get_total(); ?></span>
          </div>
        </div>

      </div>
    </aside>

  </div>

</div>

<script>
(function () {
  var toggle = document.getElementById('ship-to-different-address-checkbox');
  var panel  = document.getElementById('ship-to-different-address');
  if (toggle && panel) {
    function syncPanel() { panel.classList.toggle('active', toggle.checked); }
    toggle.addEventListener('change', syncPanel);
    syncPanel();
  }
}());
</script>

<?php get_template_part('parts/footer'); ?>
