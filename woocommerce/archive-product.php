<?php
defined('ABSPATH') || exit;

get_template_part('parts/header');
wc_print_notices();

$categories = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => true, 'parent' => 0]);
?>

<div class="qs-wrap">

  <header class="qs-header">
    <div class="qs-header__inner container">
      <div class="qs-header__bottom">
        <div>
          <h1 class="qs-header__title"><?php woocommerce_page_title(); ?></h1>
        </div>
        <div class="qs-search-wrap">
          <span class="material-symbols-outlined qs-search-icon">search</span>
          <input class="qs-search" type="text"
                 placeholder="Search products…"
                 oninput="qsSearch(this.value)">
        </div>
      </div>
    </div>
  </header>

  <div class="qs-filters-bar">
    <div class="qs-filters-bar__inner container">
      <details class="qs-filters" id="qsFilters">
        <summary class="qs-filters__summary">
          <div class="qs-filters__summary-left">
            <span class="material-symbols-outlined qs-filters__icon">tune</span>
            <span class="qs-filters__label">Filter</span>
          </div>
          <span class="material-symbols-outlined qs-filters__chevron">expand_more</span>
        </summary>

        <div class="qs-filters__panel">

          <?php if (!empty($categories) && !is_wp_error($categories)) : ?>
          <div>
            <p class="qs-filters__group-label">Category</p>
            <div class="qs-filters__cats">
              <a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>"
                 class="qs-cat-btn <?php echo is_shop() && !is_product_category() ? 'qs-cat-btn--active' : ''; ?>">
                All
              </a>
              <?php foreach ($categories as $cat) : ?>
              <a href="<?php echo esc_url(get_term_link($cat)); ?>"
                 class="qs-cat-btn <?php echo is_product_category($cat->slug) ? 'qs-cat-btn--active' : ''; ?>">
                <?php echo esc_html($cat->name); ?>
              </a>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endif; ?>

          <div>
            <p class="qs-filters__group-label">Sort by</p>
            <div class="qs-filters__order-wrap">
              <?php woocommerce_catalog_ordering(); ?>
            </div>
          </div>

          <div class="qs-filters__group--cta">
            <button class="qs-execute-btn"
                    onclick="document.getElementById('qsFilters').removeAttribute('open')">
              Apply filters
            </button>
          </div>

        </div>
      </details>
    </div>
  </div>

  <div class="qs-grid-wrap container">

    <?php if (woocommerce_product_loop()) : ?>

    <ul class="qs-grid products" id="qsGrid">
      <?php
      $loop_i = 0;
      while (have_posts()) :
        the_post();
        global $product;
        $product = wc_get_product(get_the_ID());
        if (!$product) { $loop_i++; continue; }

        $is_hero    = ($loop_i === 0);
        $img_id     = get_post_thumbnail_id();
        $img_src    = $img_id ? wp_get_attachment_image_url($img_id, $is_hero ? 'large' : 'woocommerce_thumbnail') : '';
        $price_html = $product->get_price_html();
        $on_sale    = $product->is_on_sale();
        $in_stock   = $product->is_in_stock();
        $cats       = get_the_terms(get_the_ID(), 'product_cat') ?: [];
        $loop_i++;
      ?>
      <li class="qs-card<?php echo $is_hero ? ' qs-card--hero' : ''; ?> product type-product">

        <a href="<?php the_permalink(); ?>" class="qs-card__img-wrap">
          <?php if ($on_sale) : ?>
          <span class="qs-badge qs-badge--sale">Sale</span>
          <?php endif; ?>
          <span class="qs-badge qs-badge--stock <?php echo $in_stock ? 'qs-badge--in' : 'qs-badge--out'; ?>">
            <?php echo $in_stock ? 'In stock' : 'Out of stock'; ?>
          </span>
          <?php if ($img_src) : ?>
          <img src="<?php echo esc_url($img_src); ?>"
               alt="<?php the_title_attribute(); ?>"
               loading="<?php echo $loop_i <= 3 ? 'eager' : 'lazy'; ?>">
          <?php else : ?>
          <div class="qs-card__img-placeholder">
            <span class="material-symbols-outlined">inventory_2</span>
          </div>
          <?php endif; ?>
          <div class="qs-card__img-overlay"></div>
        </a>

        <div class="qs-card__body">

          <?php if ($is_hero) : ?>
          <div class="qs-card__hero-top">
            <h2 class="qs-card__title qs-card__title--hero woocommerce-loop-product__title">
              <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
            </h2>
            <div class="qs-card__price-row">
              <span class="qs-card__price price"><?php echo $price_html; ?></span>
            </div>
            <?php if ($cats) : ?>
            <div class="qs-card__terms">
              <?php foreach (array_slice($cats, 0, 2) as $c) : ?>
              <span class="qs-tag"><?php echo esc_html($c->name); ?></span>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
          </div>
          <div class="qs-card__hero-actions">
            <?php woocommerce_template_loop_add_to_cart(); ?>
            <a href="<?php the_permalink(); ?>" class="qs-view-btn">
              View product
              <span class="material-symbols-outlined">arrow_forward</span>
            </a>
          </div>

          <?php else : ?>
          <h2 class="qs-card__title woocommerce-loop-product__title">
            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
          </h2>
          <div class="qs-card__price price"><?php echo $price_html; ?></div>
          <div class="qs-card__foot">
            <?php woocommerce_template_loop_add_to_cart(); ?>
            <a href="<?php the_permalink(); ?>" class="qs-view-btn qs-view-btn--sm">
              <span class="material-symbols-outlined">open_in_new</span>
            </a>
          </div>
          <?php endif; ?>

        </div>
      </li>
      <?php endwhile; ?>
    </ul>

    <div class="qs-pagination">
      <?php woocommerce_pagination(); ?>
    </div>

    <?php else : ?>
    <div class="qs-empty">
      <span class="material-symbols-outlined">inventory_2</span>
      <?php wc_get_template('loop/no-products-found.php'); ?>
    </div>
    <?php endif; ?>

  </div>

</div>

<script>
function qsSearch(val) {
  var term = val.toLowerCase().trim();
  document.querySelectorAll('#qsGrid .qs-card').forEach(function(card) {
    var title = card.querySelector('.qs-card__title');
    if (!title) return;
    card.style.display = (!term || title.textContent.toLowerCase().includes(term)) ? '' : 'none';
  });
}
</script>

<?php get_template_part('parts/footer'); ?>
