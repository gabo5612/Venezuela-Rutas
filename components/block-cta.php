<?php
$is_guide_context = is_post_type_archive('guide') || is_tax('guide-zone') || (is_singular() && get_post_type() === 'guide');

if ($is_guide_context) :
  $heading = 'Are you a Guide?';
  $subtext = 'Join the HotFoot Adventure directory and connect with explorers looking for local guides.';
  $mailto  = 'mailto:hotfootadventure@gmail.com?subject=' . rawurlencode('I want to be a HotFoot Adventure guide');
?>
<section class="block-cta" id="newsletter">
  <div class="block-cta__inner" data-animate="fade-up">
    <h2 class="block-cta__title"><?php echo esc_html($heading); ?></h2>
    <p class="block-cta__text"><?php echo esc_html($subtext); ?></p>
    <div class="block-cta__form">
      <a href="<?php echo esc_url($mailto); ?>" class="btn btn--primary">
        <span class="material-symbols-outlined">mail</span>
        I want to be a guide
      </a>
    </div>
  </div>
</section>
<?php else :
  $heading = get_sub_field('heading')   ?: 'Have a Route?';
  $subtext = get_sub_field('subtext')   ?: 'Share your adventure with the Venezuelan community.';
  $cta_lbl = get_sub_field('cta_label') ?: 'Submit Route';
?>
<section class="block-cta" id="newsletter">
  <div class="block-cta__inner" data-animate="fade-up">
    <h2 class="block-cta__title"><?php echo esc_html($heading); ?></h2>
    <p class="block-cta__text"><?php echo esc_html($subtext); ?></p>
    <div class="block-cta__form">
      <input type="email" placeholder="your@email.com" aria-label="Email">
      <button type="button"><?php echo esc_html($cta_lbl); ?></button>
    </div>
  </div>
</section>
<?php endif; ?>
