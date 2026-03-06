<?php
$heading  = get_sub_field('heading')   ?: '¿Tienes una Ruta?';
$subtext  = get_sub_field('subtext')   ?: 'Comparte tu aventura con la comunidad venezolana.';
$cta_lbl  = get_sub_field('cta_label') ?: 'Enviar Ruta';
$cta_url  = get_sub_field('cta_url')   ?: '#newsletter';
?>

<section class="block-cta" id="newsletter">
  <div class="block-cta__inner">
    <h2 class="block-cta__title"><?php echo esc_html($heading); ?></h2>
    <p class="block-cta__text"><?php echo esc_html($subtext); ?></p>
    <div class="block-cta__form">
      <input type="email" placeholder="tu@correo.com" aria-label="Email">
      <button type="button"><?php echo esc_html($cta_lbl); ?></button>
    </div>
  </div>
</section>
