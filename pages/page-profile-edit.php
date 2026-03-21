<?php
/**
 * Template Name: Editar Perfil
 */
if ( ! is_user_logged_in() ) {
    wp_redirect( get_permalink( get_page_by_path('login') ) ?: wp_login_url( get_permalink() ) );
    exit;
}

$user    = wp_get_current_user();
$user_id = $user->ID;
$error   = '';
$success = '';

// ── Handle form submission ───────────────────────────────────
if ( isset( $_POST['pce_profile_nonce'] ) && wp_verify_nonce( $_POST['pce_profile_nonce'], 'pce_profile_edit' ) ) {

    $display_name = sanitize_text_field( $_POST['display_name'] ?? '' );
    $first_name   = sanitize_text_field( $_POST['first_name']   ?? '' );
    $last_name    = sanitize_text_field( $_POST['last_name']    ?? '' );
    $description  = sanitize_textarea_field( $_POST['description'] ?? '' );
    $email        = sanitize_email( $_POST['user_email'] ?? '' );
    $new_pass     = $_POST['new_pass']  ?? '';
    $new_pass2    = $_POST['new_pass2'] ?? '';

    // Activities
    $activities   = isset($_POST['activities']) ? (array)$_POST['activities'] : [];
    $allowed_acts = ['hiking','road-cycling','mtb','moto','car','4x4','camping','gastronomy'];
    $activities   = array_values( array_intersect( $activities, $allowed_acts ) );

    // Avatar
    $chosen_avatar = (int) ( $_POST['pce_avatar'] ?? 0 );

    // Validate email
    if ( $email && $email !== $user->user_email && email_exists( $email ) ) {
        $error = 'Ese email ya está en uso por otra cuenta.';
    } elseif ( $new_pass && $new_pass !== $new_pass2 ) {
        $error = 'Las contraseñas no coinciden.';
    } elseif ( $new_pass && strlen($new_pass) < 8 ) {
        $error = 'La contraseña debe tener al menos 8 caracteres.';
    } else {
        // Update user
        $update_data = [
            'ID'           => $user_id,
            'display_name' => $display_name ?: $user->display_name,
            'first_name'   => $first_name,
            'last_name'    => $last_name,
            'description'  => $description,
        ];
        if ( $email ) $update_data['user_email'] = $email;
        if ( $new_pass ) $update_data['user_pass'] = $new_pass;

        $result = wp_update_user( $update_data );

        if ( is_wp_error($result) ) {
            $error = $result->get_error_message();
        } else {
            update_user_meta( $user_id, 'pce_activity_prefs', $activities );

            // Avatar
            if ( $chosen_avatar ) {
                $pool = get_option('pce_avatar_pool', []);
                if ( in_array( $chosen_avatar, $pool ) ) {
                    update_user_meta( $user_id, 'pce_avatar_id', $chosen_avatar );
                }
            }

            $success = 'Perfil actualizado correctamente.';
            // Refresh user object
            $user = get_userdata( $user_id );
        }
    }
}

// Current values
$activity_prefs = get_user_meta( $user_id, 'pce_activity_prefs', true ) ?: [];
$current_avatar = (int) get_user_meta( $user_id, 'pce_avatar_id', true );
$avatar_pool    = get_option('pce_avatar_pool', []);

$activity_labels = [
    'hiking'       => ['hiking',         'Senderismo'],
    'road-cycling' => ['directions_bike','Bici de ruta'],
    'mtb'          => ['forest',         'MTB'],
    'moto'         => ['two_wheeler',    'Moto'],
    'car'          => ['directions_car', 'Carro'],
    '4x4'          => ['terrain',        'Offroad 4x4'],
    'camping'      => ['camping',        'Campismo'],
    'gastronomy'   => ['restaurant',     'Gastronomía'],
];

get_template_part('parts/header');
?>

<main class="auth-page auth-page--edit">
  <div class="auth-page__bg"></div>

  <div class="auth-container" style="max-width:600px">

    <a href="<?php echo esc_url( get_author_posts_url($user_id) ); ?>" class="auth-back-link">
      <span class="material-symbols-outlined">arrow_back</span>
      Volver a mi perfil
    </a>

    <div class="auth-card auth-card--wide">
      <h1 class="auth-card__title">Editar perfil</h1>
      <p class="auth-card__subtitle">Los cambios se reflejarán en tu perfil público</p>

      <?php if ($error) : ?>
      <div class="auth-alert auth-alert--error">
        <span class="material-symbols-outlined">error</span>
        <?php echo esc_html($error); ?>
      </div>
      <?php endif; ?>

      <?php if ($success) : ?>
      <div class="auth-alert auth-alert--success">
        <span class="material-symbols-outlined">check_circle</span>
        <?php echo esc_html($success); ?>
      </div>
      <?php endif; ?>

      <form class="auth-form" method="post" action="">
        <?php wp_nonce_field('pce_profile_edit', 'pce_profile_nonce'); ?>

        <!-- Avatar picker -->
        <?php if ( ! empty($avatar_pool) ) : ?>
        <div class="auth-form__field">
          <label class="auth-form__label">Tu avatar</label>
          <div class="auth-avatar-grid">
            <?php foreach ($avatar_pool as $att_id) :
              $url = wp_get_attachment_image_url($att_id, 'thumbnail');
              if (!$url) continue;
              $selected = $current_avatar === $att_id ? 'is-selected' : '';
              $checked  = $current_avatar === $att_id ? 'checked' : '';
            ?>
            <label class="auth-avatar-option <?php echo $selected; ?>">
              <input type="radio" name="pce_avatar" value="<?php echo (int)$att_id; ?>" <?php echo $checked; ?>>
              <img src="<?php echo esc_url($url); ?>" alt="">
              <span class="auth-avatar-option__check material-symbols-outlined">check_circle</span>
            </label>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

        <!-- Name fields -->
        <div class="auth-form__grid">
          <div class="auth-form__field">
            <label class="auth-form__label" for="first_name">Nombre</label>
            <div class="auth-form__input-wrap">
              <span class="material-symbols-outlined auth-form__icon">badge</span>
              <input type="text" id="first_name" name="first_name" class="auth-form__input"
                     value="<?php echo esc_attr($user->first_name); ?>">
            </div>
          </div>
          <div class="auth-form__field">
            <label class="auth-form__label" for="last_name">Apellido</label>
            <div class="auth-form__input-wrap">
              <span class="material-symbols-outlined auth-form__icon">badge</span>
              <input type="text" id="last_name" name="last_name" class="auth-form__input"
                     value="<?php echo esc_attr($user->last_name); ?>">
            </div>
          </div>
        </div>

        <div class="auth-form__field">
          <label class="auth-form__label" for="display_name">Nombre público</label>
          <div class="auth-form__input-wrap">
            <span class="material-symbols-outlined auth-form__icon">person</span>
            <input type="text" id="display_name" name="display_name" class="auth-form__input"
                   value="<?php echo esc_attr($user->display_name); ?>">
          </div>
        </div>

        <div class="auth-form__field">
          <label class="auth-form__label" for="description">Bio <span class="auth-form__optional">Cuéntanos sobre ti como explorador</span></label>
          <textarea id="description" name="description" class="auth-form__input" rows="3"
                    style="padding-left:.85rem"
                    placeholder="Ej: Amante del MTB y la fotografía de naturaleza..."><?php echo esc_textarea($user->description); ?></textarea>
        </div>

        <div class="auth-form__field">
          <label class="auth-form__label" for="user_email">Email</label>
          <div class="auth-form__input-wrap">
            <span class="material-symbols-outlined auth-form__icon">mail</span>
            <input type="email" id="user_email" name="user_email" class="auth-form__input"
                   value="<?php echo esc_attr($user->user_email); ?>">
          </div>
        </div>

        <!-- Activities -->
        <div class="auth-form__field">
          <label class="auth-form__label">
            Actividades de interés
            <span class="auth-form__optional">Puedes cambiar tu selección</span>
          </label>
          <div class="auth-activities">
            <?php foreach ($activity_labels as $val => [$icon, $label]) :
              $checked = in_array($val, $activity_prefs) ? 'checked' : '';
              $cls     = in_array($val, $activity_prefs) ? 'is-checked' : '';
            ?>
            <label class="auth-activity-chip <?php echo $cls; ?>">
              <input type="checkbox" name="activities[]" value="<?php echo esc_attr($val); ?>" <?php echo $checked; ?>>
              <span class="material-symbols-outlined"><?php echo esc_html($icon); ?></span>
              <span><?php echo esc_html($label); ?></span>
            </label>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Change password (optional) -->
        <details class="auth-form__details">
          <summary class="auth-form__details-trigger">
            <span class="material-symbols-outlined">lock</span>
            Cambiar contraseña <span class="auth-form__optional">(opcional)</span>
          </summary>
          <div class="auth-form__details-body">
            <div class="auth-form__grid">
              <div class="auth-form__field">
                <label class="auth-form__label" for="new_pass">Nueva contraseña</label>
                <div class="auth-form__input-wrap">
                  <span class="material-symbols-outlined auth-form__icon">lock</span>
                  <input type="password" id="new_pass" name="new_pass" class="auth-form__input"
                         placeholder="Mín. 8 caracteres" autocomplete="new-password">
                  <button type="button" class="auth-form__toggle-pw js-toggle-pw" tabindex="-1">
                    <span class="material-symbols-outlined">visibility</span>
                  </button>
                </div>
              </div>
              <div class="auth-form__field">
                <label class="auth-form__label" for="new_pass2">Confirmar contraseña</label>
                <div class="auth-form__input-wrap">
                  <span class="material-symbols-outlined auth-form__icon">lock</span>
                  <input type="password" id="new_pass2" name="new_pass2" class="auth-form__input"
                         placeholder="Repetir contraseña" autocomplete="new-password">
                </div>
              </div>
            </div>
          </div>
        </details>

        <button type="submit" class="auth-btn auth-btn--primary" style="margin-top:1.25rem">
          Guardar cambios
          <span class="material-symbols-outlined">save</span>
        </button>

      </form>
    </div>

    <p class="auth-footer-note">
      Tu nombre de usuario (<?php echo esc_html($user->user_login); ?>) no se puede cambiar.
    </p>

  </div>
</main>

<script>
document.querySelectorAll('.js-toggle-pw').forEach(function(btn) {
  btn.addEventListener('click', function() {
    var input = this.closest('.auth-form__input-wrap').querySelector('input');
    var icon  = this.querySelector('.material-symbols-outlined');
    input.type = input.type === 'password' ? 'text' : 'password';
    icon.textContent = input.type === 'password' ? 'visibility' : 'visibility_off';
  });
});
document.querySelectorAll('.auth-activity-chip input').forEach(function(cb) {
  cb.addEventListener('change', function() {
    this.closest('.auth-activity-chip').classList.toggle('is-checked', this.checked);
  });
});
document.querySelectorAll('.auth-avatar-option input[type=radio]').forEach(function(radio) {
  radio.addEventListener('change', function() {
    document.querySelectorAll('.auth-avatar-option').forEach(function(el) { el.classList.remove('is-selected'); });
    if (this.checked) this.closest('.auth-avatar-option').classList.add('is-selected');
  });
});
</script>

<?php get_template_part('parts/footer'); ?>
