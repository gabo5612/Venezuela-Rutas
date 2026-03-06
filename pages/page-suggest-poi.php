<?php
/*
 * Template Name: Sugerir Punto de Interés
 * Description: Página de formulario para sugerir un nuevo punto de interés
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
        Comunidad
      </span>
      <h1 class="cat-hero__title">SUGIERE UN POI</h1>
      <p class="cat-hero__desc">¿Hay un lugar especial que vale la pena marcar en el mapa? Cuéntanos sobre este punto de interés.</p>
      <div class="cat-hero__actions">
        <a href="<?php echo esc_url( home_url('/nueva-ruta') ); ?>" class="btn btn--outline">
          <span class="material-symbols-outlined">add_location</span>
          ¿Tienes una Ruta?
        </a>
      </div>
    </div>
  </section>

  <div class="page-suggest__form" data-animate="fade-up">
    <?php
    // Cambia el ID por el de tu formulario en Fluent Forms > Formularios
    echo do_shortcode('[fluentform id="4"]');
    ?>
  </div>

</div>

<?php get_template_part('parts/footer'); ?>
