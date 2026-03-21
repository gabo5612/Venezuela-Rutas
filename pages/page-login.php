<?php
/**
 * Template Name: Login
 */
if ( is_user_logged_in() ) {
    wp_redirect( home_url() );
    exit;
}

// Handle login form submission
$error   = '';
$success = '';
if ( isset( $_POST['pce_login_nonce'] ) && wp_verify_nonce( $_POST['pce_login_nonce'], 'pce_login' ) ) {
    $creds = [
        'user_login'    => sanitize_text_field( $_POST['log'] ?? '' ),
        'user_password' => $_POST['pwd'] ?? '',
        'remember'      => isset( $_POST['rememberme'] ),
    ];
    $user = wp_signon( $creds, false );
    if ( is_wp_error( $user ) ) {
        $error = 'Usuario o contraseña incorrectos.';
    } else {
        $redirect = isset( $_POST['redirect_to'] ) ? esc_url( $_POST['redirect_to'] ) : home_url();
        wp_redirect( $redirect );
        exit;
    }
}

$redirect_to = isset( $_GET['redirect_to'] ) ? esc_url( $_GET['redirect_to'] ) : home_url();
get_template_part('parts/header');
?>

<main class="auth-page">
  <div class="auth-page__bg"></div>

  <div class="auth-container">

    <!-- Logo / Brand -->
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

    <div class="auth-card">
      <h1 class="auth-card__title">Bienvenido de nuevo</h1>
      <p class="auth-card__subtitle">Inicia sesión para organizar o unirte a expediciones</p>

      <?php if ( $error ) : ?>
      <div class="auth-alert auth-alert--error">
        <span class="material-symbols-outlined">error</span>
        <?php echo esc_html( $error ); ?>
      </div>
      <?php endif; ?>

      <form class="auth-form" method="post" action="">
        <?php wp_nonce_field( 'pce_login', 'pce_login_nonce' ); ?>
        <input type="hidden" name="redirect_to" value="<?php echo esc_attr( $redirect_to ); ?>">

        <div class="auth-form__field">
          <label class="auth-form__label" for="log">Usuario o email</label>
          <div class="auth-form__input-wrap">
            <span class="material-symbols-outlined auth-form__icon">person</span>
            <input type="text" id="log" name="log" class="auth-form__input"
                   placeholder="tu_usuario" autocomplete="username" required
                   value="<?php echo isset($_POST['log']) ? esc_attr($_POST['log']) : ''; ?>">
          </div>
        </div>

        <div class="auth-form__field">
          <label class="auth-form__label" for="pwd">Contraseña</label>
          <div class="auth-form__input-wrap">
            <span class="material-symbols-outlined auth-form__icon">lock</span>
            <input type="password" id="pwd" name="pwd" class="auth-form__input"
                   placeholder="••••••••" autocomplete="current-password" required>
            <button type="button" class="auth-form__toggle-pw js-toggle-pw" tabindex="-1" aria-label="Mostrar contraseña">
              <span class="material-symbols-outlined">visibility</span>
            </button>
          </div>
        </div>

        <div class="auth-form__row-split">
          <label class="auth-form__checkbox">
            <input type="checkbox" name="rememberme" value="forever">
            <span>Recuérdame</span>
          </label>
          <a href="<?php echo esc_url( wp_lostpassword_url() ); ?>" class="auth-form__link">
            ¿Olvidaste tu contraseña?
          </a>
        </div>

        <button type="submit" class="auth-btn auth-btn--primary">
          Iniciar sesión
          <span class="material-symbols-outlined">arrow_forward</span>
        </button>
      </form>

      <div class="auth-divider"><span>¿Primera vez aquí?</span></div>

      <a href="<?php echo esc_url( get_permalink( get_page_by_path('registro') ) ?: wp_registration_url() ); ?>" class="auth-btn auth-btn--outline">
        <span class="material-symbols-outlined">person_add</span>
        Crear cuenta gratis
      </a>

    </div><!-- /auth-card -->

    <p class="auth-footer-note">
      Al registrarte aceptas explorar Venezuela con respeto y responsabilidad.
    </p>

  </div><!-- /auth-container -->
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
</script>

<?php get_template_part('parts/footer'); ?>
