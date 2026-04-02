<?php
/*
 * Template Name: Suggest Point of Interest
 * Description: Form page to suggest a new point of interest
 */
?>
<?php get_template_part('parts/header'); ?>

<style>
.page-suggest__form {
  max-width: 720px;
  margin: 0 auto;
  padding: 4rem 1.5rem 6rem;
}
</style>

<div class="page-suggest">

  <section class="cat-hero">
    <div class="cat-hero__bg"></div>
    <div class="cat-hero__inner">
      <span class="cat-hero__eyebrow">
        <span class="material-symbols-outlined">location_on</span>
        Community
      </span>
      <h1 class="cat-hero__title">SUGGEST A POI</h1>
      <p class="cat-hero__desc">Is there a special place worth marking on the map? Tell us about this point of interest.</p>
      <div class="cat-hero__actions">
        <a href="<?php echo esc_url( home_url('/nueva-ruta') ); ?>" class="btn btn--outline">
          <span class="material-symbols-outlined">add_location</span>
          Have a Route?
        </a>
      </div>
    </div>
  </section>

  <div class="page-suggest__form" data-animate="fade-up">
    <?php
    // Change the ID to your form's ID in Fluent Forms > Forms
    echo do_shortcode('[fluentform id="4"]');
    ?>
  </div>

</div>

<?php get_template_part('parts/footer'); ?>
