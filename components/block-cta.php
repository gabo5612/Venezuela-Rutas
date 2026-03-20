<?php
$is_guide_context = is_post_type_archive('guide') || is_tax('guide-zone') || (is_singular() && get_post_type() === 'guide');

if ($is_guide_context) :
  $heading = '¿Eres Guía?';
  $subtext = 'Únete al directorio de PataCaliente y conecta con exploradores que buscan guías locales.';
  $mailto  = 'mailto:patacaliente@gmail.com?subject=' . rawurlencode('Quiero ser guia PataCaliente');
?>
<section class="block-cta" id="newsletter">
  <div class="block-cta__inner" data-animate="fade-up">
    <h2 class="block-cta__title"><?php echo esc_html($heading); ?></h2>
    <p class="block-cta__text"><?php echo esc_html($subtext); ?></p>
    <div class="block-cta__form">
      <a href="<?php echo esc_url($mailto); ?>" class="btn btn--primary">
        <span class="material-symbols-outlined">mail</span>
        Quiero ser guía
      </a>
    </div>
  </div>
</section>
<?php else :
  $heading = get_sub_field('heading')   ?: '¿Tienes una Ruta?';
  $subtext = get_sub_field('subtext')   ?: 'Comparte tu aventura con la comunidad venezolana.';
  $cta_lbl = get_sub_field('cta_label') ?: 'Enviar Ruta';
?>
<section class="block-cta" id="newsletter">
  <div class="block-cta__inner" data-animate="fade-up">
    <h2 class="block-cta__title"><?php echo esc_html($heading); ?></h2>
    <p class="block-cta__text"><?php echo esc_html($subtext); ?></p>
    <div class="block-cta__form">
      <input type="email" placeholder="tu@correo.com" aria-label="Email">
      <button type="button"><?php echo esc_html($cta_lbl); ?></button>
    </div>
  </div>
</section>
<?php endif; ?>
