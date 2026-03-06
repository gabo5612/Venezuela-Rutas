<?php
/*
 * Template Name: Sugerir Ruta
 * Description: Página de formulario para sugerir una nueva ruta
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
        <span class="material-symbols-outlined">add_location</span>
        Comunidad
      </span>
      <h1 class="cat-hero__title">SUGIERE UNA RUTA</h1>
      <p class="cat-hero__desc">¿Conoces un camino que merece ser explorado? Comparte los detalles y lo revisaremos para publicarlo.</p>
      <div class="cat-hero__actions">
        <a href="<?php echo esc_url( home_url('/nuevo-poi') ); ?>" class="btn btn--outline">
          <span class="material-symbols-outlined">location_on</span>
          ¿Tienes un POI?
        </a>
      </div>
    </div>
  </section>

  <div class="page-suggest__form" data-animate="fade-up">
    <?php
    // Cambia el ID por el de tu formulario en Fluent Forms > Formularios
    echo do_shortcode('[fluentform id="3"]');
    ?>
  </div>

</div>

<?php get_template_part('parts/footer'); ?>
