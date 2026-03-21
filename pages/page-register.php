<?php
/**
 * Template Name: Registro
 */
if ( is_user_logged_in() ) {
    wp_redirect( home_url() );
    exit;
}
if ( ! get_option('users_can_register') ) {
    wp_redirect( home_url() );
    exit;
}

$error   = '';
$success = '';

if ( isset( $_POST['pce_register_nonce'] ) && wp_verify_nonce( $_POST['pce_register_nonce'], 'pce_register' ) ) {
    $username  = sanitize_user( $_POST['user_login'] ?? '' );
    $email     = sanitize_email( $_POST['user_email'] ?? '' );
    $password  = $_POST['user_pass'] ?? '';
    $password2 = $_POST['user_pass2'] ?? '';
    $firstname = sanitize_text_field( $_POST['first_name'] ?? '' );

    if ( ! $username || ! $email || ! $password ) {
        $error = 'Completa todos los campos obligatorios.';
    } elseif ( $password !== $password2 ) {
        $error = 'Las contraseñas no coinciden.';
    } elseif ( strlen( $password ) < 8 ) {
        $error = 'La contraseña debe tener al menos 8 caracteres.';
    } elseif ( username_exists( $username ) ) {
        $error = 'Ese nombre de usuario ya está en uso.';
    } elseif ( email_exists( $email ) ) {
        $error = 'Ya existe una cuenta con ese email.';
    } else {
        $user_id = wp_create_user( $username, $password, $email );
        if ( is_wp_error( $user_id ) ) {
            $error = $user_id->get_error_message();
        } else {
            if ( $firstname ) wp_update_user(['ID' => $user_id, 'first_name' => $firstname]);
            // Save activity preferences
            $activities = isset($_POST['activities']) ? (array) $_POST['activities'] : [];
            $allowed_acts = ['hiking','road-cycling','mtb','moto','car','4x4','camping','gastronomy'];
            $activities = array_intersect( $activities, $allowed_acts );
            update_user_meta( $user_id, 'pce_activity_prefs', $activities );
            // Save chosen avatar
            $chosen_avatar = (int) ( $_POST['pce_avatar'] ?? 0 );
            if ( $chosen_avatar ) {
                $pool = function_exists('PCE_Settings') ? PCE_Settings::get_avatar_pool() :
                        ( class_exists('PCE_Settings') ? PCE_Settings::get_avatar_pool() : get_option('pce_avatar_pool', []) );
                if ( in_array( $chosen_avatar, $pool ) ) {
                    update_user_meta( $user_id, 'pce_avatar_id', $chosen_avatar );
                }
            }

            // Auto-login
            wp_set_current_user( $user_id );
            wp_set_auth_cookie( $user_id );
            wp_new_user_notification( $user_id, null, 'user' );
            wp_redirect( home_url() );
            exit;
        }
    }
}

get_template_part('parts/header');
?>

<main class="auth-page">
  <div class="auth-page__bg"></div>

  <div class="auth-container">

    <a href="<?php echo esc_url( home_url() ); ?>" class="auth-brand">
      <?php
        $logo = get_theme_mod('custom_logo');
        if ( $logo ) :
          echo wp_get_attachment_image( $logo, 'full', false, ['class' => 'auth-brand__logo'] );
        else :
      ?>
      <span class="auth-brand__name"><?php bloginfo('name'); ?></span>
      <?php endif; ?>
    </a>

    <div class="auth-card auth-card--wide">
      <h1 class="auth-card__title">Crea tu cuenta</h1>
      <p class="auth-card__subtitle">Únete a la comunidad de exploradores venezolanos</p>

      <?php if ( $error ) : ?>
      <div class="auth-alert auth-alert--error">
        <span class="material-symbols-outlined">error</span>
        <?php echo esc_html( $error ); ?>
      </div>
      <?php endif; ?>

      <form class="auth-form" method="post" action="">
        <?php wp_nonce_field( 'pce_register', 'pce_register_nonce' ); ?>

        <div class="auth-form__grid">
          <div class="auth-form__field">
            <label class="auth-form__label" for="first_name">Nombre <span class="auth-form__optional">(opcional)</span></label>
            <div class="auth-form__input-wrap">
              <span class="material-symbols-outlined auth-form__icon">badge</span>
              <input type="text" id="first_name" name="first_name" class="auth-form__input"
                     placeholder="Gabriel" autocomplete="given-name"
                     value="<?php echo isset($_POST['first_name']) ? esc_attr($_POST['first_name']) : ''; ?>">
            </div>
          </div>

          <div class="auth-form__field">
            <label class="auth-form__label" for="user_login">Usuario *</label>
            <div class="auth-form__input-wrap">
              <span class="material-symbols-outlined auth-form__icon">alternate_email</span>
              <input type="text" id="user_login" name="user_login" class="auth-form__input"
                     placeholder="explorador_gabo" autocomplete="username" required
                     value="<?php echo isset($_POST['user_login']) ? esc_attr($_POST['user_login']) : ''; ?>">
            </div>
          </div>
        </div>

        <div class="auth-form__field">
          <label class="auth-form__label" for="user_email">Email *</label>
          <div class="auth-form__input-wrap">
            <span class="material-symbols-outlined auth-form__icon">mail</span>
            <input type="email" id="user_email" name="user_email" class="auth-form__input"
                   placeholder="gabo@ejemplo.com" autocomplete="email" required
                   value="<?php echo isset($_POST['user_email']) ? esc_attr($_POST['user_email']) : ''; ?>">
          </div>
        </div>

        <div class="auth-form__grid">
          <div class="auth-form__field">
            <label class="auth-form__label" for="user_pass">Contraseña *</label>
            <div class="auth-form__input-wrap">
              <span class="material-symbols-outlined auth-form__icon">lock</span>
              <input type="password" id="user_pass" name="user_pass" class="auth-form__input"
                     placeholder="Mín. 8 caracteres" autocomplete="new-password" required>
              <button type="button" class="auth-form__toggle-pw js-toggle-pw" tabindex="-1" aria-label="Mostrar">
                <span class="material-symbols-outlined">visibility</span>
              </button>
            </div>
          </div>

          <div class="auth-form__field">
            <label class="auth-form__label" for="user_pass2">Confirmar contraseña *</label>
            <div class="auth-form__input-wrap">
              <span class="material-symbols-outlined auth-form__icon">lock</span>
              <input type="password" id="user_pass2" name="user_pass2" class="auth-form__input"
                     placeholder="Repite la contraseña" autocomplete="new-password" required>
            </div>
          </div>
        </div>

        <!-- Avatar picker -->
        <?php
        $avatar_pool = class_exists('PCE_Settings') ? PCE_Settings::get_avatar_pool() : get_option('pce_avatar_pool', []);
        if ( ! empty($avatar_pool) ) :
        ?>
        <div class="auth-form__field">
          <label class="auth-form__label">
            Elige tu avatar
            <span class="auth-form__optional">Opcional</span>
          </label>
          <div class="auth-avatar-grid">
            <?php foreach ( $avatar_pool as $att_id ) :
              $url = wp_get_attachment_image_url( $att_id, 'thumbnail' );
              if ( ! $url ) continue;
              $checked = ( isset($_POST['pce_avatar']) && (int)$_POST['pce_avatar'] === $att_id ) ? 'checked' : '';
            ?>
            <label class="auth-avatar-option <?php echo $checked ? 'is-selected' : ''; ?>">
              <input type="radio" name="pce_avatar" value="<?php echo (int)$att_id; ?>" <?php echo $checked; ?>>
              <img src="<?php echo esc_url($url); ?>" alt="">
              <span class="auth-avatar-option__check material-symbols-outlined">check_circle</span>
            </label>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

        <!-- Activity preferences -->
        <div class="auth-form__field">
          <label class="auth-form__label">
            ¿Qué actividades te interesan?
            <span class="auth-form__optional">Puedes elegir varias</span>
          </label>
          <div class="auth-activities">
            <?php
            $acts = [
              'hiking'       => ['hiking',        'Senderismo'],
              'road-cycling' => ['directions_bike','Bici de ruta'],
              'mtb'          => ['forest',         'MTB'],
              'moto'         => ['two_wheeler',    'Moto'],
              'car'          => ['directions_car', 'Carro'],
              '4x4'          => ['terrain',        'Offroad 4x4'],
              'camping'      => ['camping',        'Campismo'],
              'gastronomy'   => ['restaurant',     'Gastronomía'],
            ];
            $selected = isset($_POST['activities']) ? (array)$_POST['activities'] : [];
            foreach ($acts as $val => [$icon, $label]) :
              $checked = in_array($val, $selected) ? 'checked' : '';
            ?>
            <label class="auth-activity-chip <?php echo $checked ? 'is-checked' : ''; ?>">
              <input type="checkbox" name="activities[]" value="<?php echo esc_attr($val); ?>" <?php echo $checked; ?>>
              <span class="material-symbols-outlined"><?php echo esc_html($icon); ?></span>
              <span><?php echo esc_html($label); ?></span>
            </label>
            <?php endforeach; ?>
          </div>
        </div>

        <button type="submit" class="auth-btn auth-btn--primary">
          Crear cuenta
          <span class="material-symbols-outlined">arrow_forward</span>
        </button>

        <p class="auth-form__terms">
          Al registrarte aceptas explorar con responsabilidad y respetar a los demás miembros de la comunidad.
        </p>
      </form>

      <div class="auth-divider"><span>¿Ya tienes cuenta?</span></div>

      <a href="<?php echo esc_url( get_permalink( get_page_by_path('login') ) ?: wp_login_url() ); ?>" class="auth-btn auth-btn--outline">
        <span class="material-symbols-outlined">login</span>
        Iniciar sesión
      </a>

    </div>

    <p class="auth-footer-note">
      Al registrarte aceptas explorar Venezuela con respeto y responsabilidad.
    </p>

  </div>
</main>

<script>
document.querySelectorAll('.js-toggle-pw').forEach(function(btn) {
  btn.addEventListener('click', function() {
    var input = this.closest('.auth-form__input-wrap').querySelector('input');
    var icon  = this.querySelector('.material-symbols-outlined');
    if (input.type === 'password') {
      input.type = 'text';
      icon.textContent = 'visibility_off';
    } else {
      input.type = 'password';
      icon.textContent = 'visibility';
    }
  });
});
// Activity chip toggle visual
document.querySelectorAll('.auth-activity-chip input').forEach(function(cb) {
  cb.addEventListener('change', function() {
    this.closest('.auth-activity-chip').classList.toggle('is-checked', this.checked);
  });
});
// Avatar picker
document.querySelectorAll('.auth-avatar-option input[type=radio]').forEach(function(radio) {
  radio.addEventListener('change', function() {
    document.querySelectorAll('.auth-avatar-option').forEach(function(el) {
      el.classList.remove('is-selected');
    });
    if (this.checked) this.closest('.auth-avatar-option').classList.add('is-selected');
  });
});
</script>

<?php get_template_part('parts/footer'); ?>
