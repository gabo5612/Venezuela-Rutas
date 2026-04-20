<?php
/**
 * Template Name: Registro
 */
if ( is_user_logged_in() ) { wp_redirect( home_url() ); exit; }
if ( ! get_option('users_can_register') ) { wp_redirect( home_url() ); exit; }

$error   = '';
$success = '';
$step    = 1; // default view

// ── Process form submission ────────────────────────────────────────────────
if ( isset( $_POST['hfa_register_nonce'] ) && wp_verify_nonce( $_POST['hfa_register_nonce'], 'hfa_register' ) ) {

    $username  = sanitize_user( $_POST['user_login'] ?? '' );
    $email     = sanitize_email( $_POST['user_email'] ?? '' );
    $password  = $_POST['user_pass'] ?? '';
    $password2 = $_POST['user_pass2'] ?? '';
    $firstname = sanitize_text_field( $_POST['first_name'] ?? '' );

    // Selected roles
    $allowed_roles   = array_keys( HFA_Roles::definitions() );
    $selected_roles  = isset( $_POST['hfa_roles'] ) ? array_intersect( (array) $_POST['hfa_roles'], $allowed_roles ) : [];
    if ( empty( $selected_roles ) ) $selected_roles = [ 'hfa_explorer' ];

    // ── Validation ─────────────────────────────────────────────────
    if ( ! $username || ! $email || ! $password ) {
        $error = 'Please fill in all required fields.';
    } elseif ( $password !== $password2 ) {
        $error = 'Passwords do not match.';
    } elseif ( strlen( $password ) < 8 ) {
        $error = 'Password must be at least 8 characters.';
    } elseif ( username_exists( $username ) ) {
        $error = 'That username is already taken.';
    } elseif ( email_exists( $email ) ) {
        $error = 'An account with that email already exists.';
    } else {
        // ── Create user ────────────────────────────────────────────
        $user_id = wp_create_user( $username, $password, $email );

        if ( is_wp_error( $user_id ) ) {
            $error = $user_id->get_error_message();
        } else {
            if ( $firstname ) wp_update_user( [ 'ID' => $user_id, 'first_name' => $firstname, 'display_name' => $firstname ] );

            // Assign WP role (primary = first selected)
            $primary = reset( $selected_roles );
            wp_update_user( [ 'ID' => $user_id, 'role' => $primary ] );

            // Store all selected roles in meta
            update_user_meta( $user_id, 'hfa_roles', array_values( $selected_roles ) );

            // Needs approval?
            $defs          = HFA_Roles::definitions();
            $needs_approval = false;
            foreach ( $selected_roles as $r ) {
                if ( ! empty( $defs[$r]['approval'] ) ) { $needs_approval = true; break; }
            }
            $approval_status = $needs_approval ? 'pending' : 'approved';
            HFA_Roles::set_approval_status( $user_id, $approval_status );

            // ── Role-specific meta ──────────────────────────────────
            $blood_type = sanitize_text_field( $_POST['hfa_blood_type'] ?? '' );
            if ( $blood_type ) update_user_meta( $user_id, 'hfa_blood_type', $blood_type );

            $phone = sanitize_text_field( $_POST['hfa_phone'] ?? '' );
            if ( $phone ) update_user_meta( $user_id, 'hfa_phone', $phone );

            $ec_name  = sanitize_text_field( $_POST['hfa_ec_name'] ?? '' );
            $ec_phone = sanitize_text_field( $_POST['hfa_ec_phone'] ?? '' );
            if ( $ec_name || $ec_phone ) update_user_meta( $user_id, 'hfa_emergency_contact', [ 'name' => $ec_name, 'phone' => $ec_phone ] );

            // Organizer fields
            $has_first_aid = isset( $_POST['hfa_first_aid'] ) ? 1 : 0;
            update_user_meta( $user_id, 'hfa_first_aid', $has_first_aid );

            // Guide fields
            $whatsapp = sanitize_text_field( $_POST['hfa_whatsapp'] ?? '' );
            if ( $whatsapp ) update_user_meta( $user_id, 'hfa_whatsapp', $whatsapp );

            $instagram = sanitize_text_field( str_replace('@', '', $_POST['hfa_instagram'] ?? '') );
            if ( $instagram ) update_user_meta( $user_id, 'hfa_instagram', $instagram );

            $guide_bio = sanitize_textarea_field( $_POST['hfa_guide_bio'] ?? '' );
            if ( $guide_bio ) update_user_meta( $user_id, 'hfa_guide_bio', $guide_bio );

            // Experience fields
            $biz_phone   = sanitize_text_field( $_POST['hfa_biz_phone'] ?? '' );
            if ( $biz_phone ) update_user_meta( $user_id, 'hfa_biz_phone', $biz_phone );

            $biz_instagram = sanitize_text_field( str_replace('@', '', $_POST['hfa_biz_instagram'] ?? '') );
            if ( $biz_instagram ) update_user_meta( $user_id, 'hfa_biz_instagram', $biz_instagram );

            $biz_desc = sanitize_textarea_field( $_POST['hfa_biz_desc'] ?? '' );
            if ( $biz_desc ) update_user_meta( $user_id, 'hfa_biz_desc', $biz_desc );

            $sell_method = in_array( $_POST['hfa_sell_method'] ?? '', [ 'woo', 'contact' ] ) ? $_POST['hfa_sell_method'] : 'contact';
            update_user_meta( $user_id, 'hfa_sell_method', $sell_method );

            // Activities
            $allowed_acts = array_keys( HFA_Roles::activity_types() );
            $activities   = isset( $_POST['activities'] ) ? array_intersect( (array) $_POST['activities'], $allowed_acts ) : [];
            update_user_meta( $user_id, 'hfa_activity_prefs', array_values( $activities ) );

            // Avatar
            $chosen_avatar = (int) ( $_POST['hfa_avatar'] ?? 0 );
            if ( $chosen_avatar ) {
                $pool = get_option( 'hfa_avatar_pool', [] );
                if ( in_array( $chosen_avatar, $pool ) ) update_user_meta( $user_id, 'hfa_avatar_id', $chosen_avatar );
            }

            // ID photo upload (organizer/guide)
            if ( in_array( 'hfa_organizer', $selected_roles ) || in_array( 'hfa_guide', $selected_roles ) ) {
                HFA_Roles::handle_id_photo_upload( $user_id );
            }

            // ── Redirect ────────────────────────────────────────────
            if ( $needs_approval ) {
                // Partial login — show pending page (don't grant full session yet)
                wp_redirect( add_query_arg( 'hfa_pending', '1', home_url( '/registro' ) ) );
                exit;
            }

            wp_set_current_user( $user_id );
            wp_set_auth_cookie( $user_id );
            wp_new_user_notification( $user_id, null, 'user' );
            wp_redirect( home_url() );
            exit;
        }
    }
}

// Pending confirmation screen
$is_pending_screen = ! empty( $_GET['hfa_pending'] );

get_template_part('parts/header');
?>

<main class="auth-page">
  <div class="auth-page__bg"></div>
  <div class="auth-container">

    <a href="<?php echo esc_url( home_url() ); ?>" class="auth-brand">
      <?php $logo = get_theme_mod('custom_logo');
        if ( $logo ) echo wp_get_attachment_image( $logo, 'full', false, ['class' => 'auth-brand__logo'] );
        else echo '<span class="auth-brand__name">' . esc_html( get_bloginfo('name') ) . '</span>';
      ?>
    </a>

    <?php if ( $is_pending_screen ) : ?>
    <!-- ── Pending screen ──────────────────────────────────────── -->
    <div class="auth-card auth-pending">
      <span class="auth-pending__icon material-symbols-outlined">hourglass_top</span>
      <h1 class="auth-card__title">Request submitted</h1>
      <p class="auth-card__subtitle">We will review your information and notify you by email when your account is approved. This usually takes less than 48 hours.</p>
      <a href="<?php echo esc_url( home_url() ); ?>" class="auth-btn auth-btn--outline">
        <span class="material-symbols-outlined">home</span> Back to home
      </a>
    </div>

    <?php else : ?>
    <!-- ── Registration form ──────────────────────────────────── -->
    <div class="auth-card auth-card--wide" id="hfa-register-card">
      <h1 class="auth-card__title">Create your account</h1>
      <p class="auth-card__subtitle">Join the Venezuelan explorers community</p>

      <?php if ( $error ) : ?>
      <div class="auth-alert auth-alert--error">
        <span class="material-symbols-outlined">error</span>
        <?php echo esc_html( $error ); ?>
      </div>
      <?php endif; ?>

      <!-- Step indicators -->
      <div class="auth-steps">
        <div class="auth-step active" data-step="1"><span>1</span> Account type</div>
        <div class="auth-step" data-step="2"><span>2</span> Basic info</div>
        <div class="auth-step" data-step="3"><span>3</span> Profile</div>
      </div>

      <form class="auth-form" method="post" action="" enctype="multipart/form-data" id="hfa-register-form">
        <?php wp_nonce_field( 'hfa_register', 'hfa_register_nonce' ); ?>

        <!-- ══ STEP 1 — Role selection ══════════════════════════════ -->
        <div class="auth-step-content active" id="step-1">
          <p class="auth-step-hint">You can select multiple roles if applicable.</p>
          <div class="auth-role-grid">
            <?php foreach ( HFA_Roles::definitions() as $slug => $def ) :
              if ( $slug === 'hfa_experience' ) continue; // hidden until ready
              $checked = ( isset( $_POST['hfa_roles'] ) && in_array( $slug, (array)$_POST['hfa_roles'] ) );
            ?>
            <label class="auth-role-card <?php echo $checked ? 'is-selected' : ''; ?>">
              <input type="checkbox" name="hfa_roles[]" value="<?php echo esc_attr($slug); ?>" <?php echo $checked ? 'checked' : ''; ?>>
              <span class="auth-role-card__icon material-symbols-outlined"><?php echo esc_html($def['icon']); ?></span>
              <span class="auth-role-card__label"><?php echo esc_html($def['label']); ?></span>
              <span class="auth-role-card__desc"><?php echo esc_html($def['description']); ?></span>
              <?php if ( $def['approval'] ) : ?>
              <span class="auth-role-card__badge"><span class="material-symbols-outlined">verified</span> Manual approval</span>
              <?php endif; ?>
            </label>
            <?php endforeach; ?>
          </div>
          <button type="button" class="auth-btn auth-btn--primary js-next-step" data-next="2">
            Continue <span class="material-symbols-outlined">arrow_forward</span>
          </button>
        </div>

        <!-- ══ STEP 2 — Base data ══════════════════════════════════ -->
        <div class="auth-step-content" id="step-2">
          <div class="auth-form__grid">
            <div class="auth-form__field">
              <label class="auth-form__label" for="first_name">Name <span class="auth-form__optional">(optional)</span></label>
              <div class="auth-form__input-wrap">
                <span class="material-symbols-outlined auth-form__icon">badge</span>
                <input type="text" id="first_name" name="first_name" class="auth-form__input"
                       placeholder="Gabriel" autocomplete="given-name"
                       value="<?php echo isset($_POST['first_name']) ? esc_attr($_POST['first_name']) : ''; ?>">
              </div>
            </div>
            <div class="auth-form__field">
              <label class="auth-form__label" for="user_login">Username *</label>
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
              <label class="auth-form__label" for="user_pass">Password *</label>
              <div class="auth-form__input-wrap">
                <span class="material-symbols-outlined auth-form__icon">lock</span>
                <input type="password" id="user_pass" name="user_pass" class="auth-form__input"
                       placeholder="Min. 8 characters" autocomplete="new-password" required>
                <button type="button" class="auth-form__toggle-pw js-toggle-pw" tabindex="-1">
                  <span class="material-symbols-outlined">visibility</span>
                </button>
              </div>
            </div>
            <div class="auth-form__field">
              <label class="auth-form__label" for="user_pass2">Confirm password *</label>
              <div class="auth-form__input-wrap">
                <span class="material-symbols-outlined auth-form__icon">lock</span>
                <input type="password" id="user_pass2" name="user_pass2" class="auth-form__input"
                       placeholder="Repeat password" autocomplete="new-password" required>
              </div>
            </div>
          </div>

          <!-- Avatar -->
          <?php $avatar_pool = get_option( 'hfa_avatar_pool', [] ); if ( ! empty($avatar_pool) ) : ?>
          <div class="auth-form__field">
            <label class="auth-form__label">Choose your avatar <span class="auth-form__optional">Optional</span></label>
            <div class="auth-avatar-grid">
              <?php foreach ( $avatar_pool as $att_id ) :
                $url = wp_get_attachment_image_url( $att_id, 'thumbnail' );
                if ( ! $url ) continue;
                $checked = isset($_POST['hfa_avatar']) && (int)$_POST['hfa_avatar'] === $att_id;
              ?>
              <label class="auth-avatar-option <?php echo $checked ? 'is-selected' : ''; ?>">
                <input type="radio" name="hfa_avatar" value="<?php echo (int)$att_id; ?>" <?php echo $checked ? 'checked' : ''; ?>>
                <img src="<?php echo esc_url($url); ?>" alt="">
                <span class="auth-avatar-option__check material-symbols-outlined">check_circle</span>
              </label>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endif; ?>

          <div class="auth-form__nav">
            <button type="button" class="auth-btn auth-btn--ghost js-prev-step" data-prev="1">
              <span class="material-symbols-outlined">arrow_back</span> Back
            </button>
            <button type="button" class="auth-btn auth-btn--primary js-next-step" data-next="3">
              Continue <span class="material-symbols-outlined">arrow_forward</span>
            </button>
          </div>
        </div>

        <!-- ══ STEP 3 — Role-specific fields ═══════════════════════ -->
        <div class="auth-step-content" id="step-3">

          <!-- Shared: explorer / organizer / guide ──────────────── -->
          <div class="auth-role-fields" data-roles="hfa_explorer,hfa_organizer,hfa_guide">
            <h3 class="auth-section-title"><span class="material-symbols-outlined">medical_information</span> Safety information</h3>

            <div class="auth-form__grid">
              <div class="auth-form__field">
                <label class="auth-form__label" for="hfa_blood_type">Blood type *</label>
                <div class="auth-form__input-wrap">
                  <span class="material-symbols-outlined auth-form__icon">bloodtype</span>
                  <select id="hfa_blood_type" name="hfa_blood_type" class="auth-form__input">
                    <option value="">Select…</option>
                    <?php foreach ( HFA_Roles::blood_types() as $bt ) : ?>
                    <option value="<?php echo $bt; ?>" <?php selected( $_POST['hfa_blood_type'] ?? '', $bt ); ?>><?php echo $bt; ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
              <div class="auth-form__field">
                <label class="auth-form__label" for="hfa_phone">Phone *</label>
                <div class="auth-form__input-wrap">
                  <span class="material-symbols-outlined auth-form__icon">phone</span>
                  <input type="tel" id="hfa_phone" name="hfa_phone" class="auth-form__input"
                         placeholder="+58 412 000 0000"
                         value="<?php echo esc_attr( $_POST['hfa_phone'] ?? '' ); ?>">
                </div>
              </div>
            </div>

            <div class="auth-form__grid">
              <div class="auth-form__field">
                <label class="auth-form__label" for="hfa_ec_name">Emergency contact — Name *</label>
                <div class="auth-form__input-wrap">
                  <span class="material-symbols-outlined auth-form__icon">person_alert</span>
                  <input type="text" id="hfa_ec_name" name="hfa_ec_name" class="auth-form__input"
                         placeholder="María Arias"
                         value="<?php echo esc_attr( $_POST['hfa_ec_name'] ?? '' ); ?>">
                </div>
              </div>
              <div class="auth-form__field">
                <label class="auth-form__label" for="hfa_ec_phone">Emergency contact — Phone *</label>
                <div class="auth-form__input-wrap">
                  <span class="material-symbols-outlined auth-form__icon">call</span>
                  <input type="tel" id="hfa_ec_phone" name="hfa_ec_phone" class="auth-form__input"
                         placeholder="+58 212 000 0000"
                         value="<?php echo esc_attr( $_POST['hfa_ec_phone'] ?? '' ); ?>">
                </div>
              </div>
            </div>
          </div>

          <!-- Organizer only ─────────────────────────────────────── -->
          <div class="auth-role-fields" data-roles="hfa_organizer">
            <h3 class="auth-section-title"><span class="material-symbols-outlined">flag</span> Organizer</h3>
            <div class="auth-form__field">
              <label class="auth-toggle-label">
                <input type="checkbox" name="hfa_first_aid" value="1" <?php checked( isset($_POST['hfa_first_aid']) ); ?>>
                <span class="auth-toggle-track"></span>
                <span>I have first aid knowledge</span>
              </label>
            </div>
          </div>

          <!-- Guide only ─────────────────────────────────────────── -->
          <div class="auth-role-fields" data-roles="hfa_guide">
            <h3 class="auth-section-title"><span class="material-symbols-outlined">explore</span> Guide</h3>
            <div class="auth-form__grid">
              <div class="auth-form__field">
                <label class="auth-form__label" for="hfa_whatsapp">WhatsApp</label>
                <div class="auth-form__input-wrap">
                  <span class="material-symbols-outlined auth-form__icon">chat</span>
                  <input type="tel" id="hfa_whatsapp" name="hfa_whatsapp" class="auth-form__input"
                         placeholder="+58 412 000 0000"
                         value="<?php echo esc_attr( $_POST['hfa_whatsapp'] ?? '' ); ?>">
                </div>
              </div>
              <div class="auth-form__field">
                <label class="auth-form__label" for="hfa_instagram">Instagram</label>
                <div class="auth-form__input-wrap">
                  <span class="material-symbols-outlined auth-form__icon">photo_camera</span>
                  <input type="text" id="hfa_instagram" name="hfa_instagram" class="auth-form__input"
                         placeholder="@tu_usuario"
                         value="<?php echo esc_attr( $_POST['hfa_instagram'] ?? '' ); ?>">
                </div>
              </div>
            </div>
            <div class="auth-form__field">
              <label class="auth-form__label" for="hfa_guide_bio">Experience and certifications</label>
              <textarea id="hfa_guide_bio" name="hfa_guide_bio" class="auth-form__input auth-form__textarea"
                        placeholder="Tell us about your experience as a guide, routes you know, certifications, etc."
                        rows="4"><?php echo esc_textarea( $_POST['hfa_guide_bio'] ?? '' ); ?></textarea>
            </div>
          </div>

          <!-- ID photo — shared for organizer AND guide (single input) -->
          <div class="auth-role-fields" id="hfa-id-photo-block" data-roles="hfa_organizer,hfa_guide">
            <div class="auth-form__field">
              <label class="auth-form__label">ID photo *</label>
              <p class="auth-form__help">Required for verification. Visible to administrators only.</p>
              <label class="auth-upload-area js-upload-area">
                <input type="file" name="hfa_id_photo" id="hfa_id_photo" accept="image/jpeg,image/png,image/webp" class="auth-upload-area__input">
                <span class="material-symbols-outlined">id_card</span>
                <span class="auth-upload-area__label">Select ID image</span>
                <span class="auth-upload-area__hint">JPG, PNG or WEBP · max. 5 MB</span>
              </label>
            </div>
          </div>

          <?php /* Experience only — hidden until ready
          <div class="auth-role-fields" data-roles="hfa_experience">
            <h3 class="auth-section-title"><span class="material-symbols-outlined">storefront</span> Experience / Operator</h3>
            <div class="auth-form__grid">
              <div class="auth-form__field">
                <label class="auth-form__label" for="hfa_biz_phone">WhatsApp / Business phone</label>
                <div class="auth-form__input-wrap">
                  <span class="material-symbols-outlined auth-form__icon">chat</span>
                  <input type="tel" id="hfa_biz_phone" name="hfa_biz_phone" class="auth-form__input"
                         placeholder="+58 412 000 0000"
                         value="<?php echo esc_attr( $_POST['hfa_biz_phone'] ?? '' ); ?>">
                </div>
              </div>
              <div class="auth-form__field">
                <label class="auth-form__label" for="hfa_biz_instagram">Instagram</label>
                <div class="auth-form__input-wrap">
                  <span class="material-symbols-outlined auth-form__icon">photo_camera</span>
                  <input type="text" id="hfa_biz_instagram" name="hfa_biz_instagram" class="auth-form__input"
                         placeholder="@tu_negocio"
                         value="<?php echo esc_attr( $_POST['hfa_biz_instagram'] ?? '' ); ?>">
                </div>
              </div>
            </div>
            <div class="auth-form__field">
              <label class="auth-form__label" for="hfa_biz_desc">What do you offer?</label>
              <textarea id="hfa_biz_desc" name="hfa_biz_desc" class="auth-form__input auth-form__textarea"
                        placeholder="Describe your packages, experiences, tourism services…"
                        rows="4"></textarea>
            </div>
            <div class="auth-form__field">
              <label class="auth-form__label">How do you prefer to receive orders?</label>
              <div class="auth-radio-group">
                <label class="auth-radio-option">
                  <input type="radio" name="hfa_sell_method" value="contact">
                  <span class="material-symbols-outlined">chat</span>
                  <span>Direct contact (WhatsApp / email)</span>
                </label>
                <label class="auth-radio-option">
                  <input type="radio" name="hfa_sell_method" value="woo">
                  <span class="material-symbols-outlined">shopping_cart</span>
                  <span>Online payment (WooCommerce)</span>
                </label>
              </div>
            </div>
          </div>
          */ ?>

          <!-- Activities (all roles) ─────────────────────────────── -->
          <div class="auth-form__field">
            <label class="auth-form__label">What activities interest you? <span class="auth-form__optional">You can choose multiple</span></label>
            <div class="auth-activities">
              <?php $selected_acts = isset($_POST['activities']) ? (array)$_POST['activities'] : [];
              foreach ( HFA_Roles::activity_types() as $val => [$icon, $label] ) :
                $checked = in_array($val, $selected_acts) ? 'checked' : '';
              ?>
              <label class="auth-activity-chip <?php echo $checked ? 'is-checked' : ''; ?>">
                <input type="checkbox" name="activities[]" value="<?php echo esc_attr($val); ?>" <?php echo $checked; ?>>
                <span class="material-symbols-outlined"><?php echo esc_html($icon); ?></span>
                <span><?php echo esc_html($label); ?></span>
              </label>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="auth-form__nav">
            <button type="button" class="auth-btn auth-btn--ghost js-prev-step" data-prev="2">
              <span class="material-symbols-outlined">arrow_back</span> Back
            </button>
            <button type="submit" class="auth-btn auth-btn--primary">
              Create account <span class="material-symbols-outlined">check</span>
            </button>
          </div>

        </div><!-- /step-3 -->

      </form>

      <div class="auth-divider"><span>Already have an account?</span></div>
      <a href="<?php echo esc_url( get_permalink( get_page_by_path('login') ) ?: wp_login_url() ); ?>" class="auth-btn auth-btn--outline">
        <span class="material-symbols-outlined">login</span> Sign in
      </a>
    </div>
    <?php endif; ?>

  </div>
</main>

<script>
(function() {
  // ── Step navigation ────────────────────────────────────────────
  var form  = document.getElementById('hfa-register-form');
  var steps = document.querySelectorAll('.auth-step-content');
  var indicators = document.querySelectorAll('.auth-step');
  var currentStep = 1;

  // Error recovery: if PHP returned an error, jump to the step where data was
  <?php if ($error) : ?>
  currentStep = <?php echo empty($_POST['hfa_roles']) ? 1 : (empty($_POST['user_login']) ? 1 : (empty($_POST['hfa_blood_type']) ? 2 : 3)); ?>;
  showStep(currentStep);
  <?php endif; ?>

  // ── Inline error helper ────────────────────────────────────────
  function showError(container, message) {
    clearError(container);
    var el = document.createElement('p');
    el.className = 'auth-field-error';
    el.textContent = message;
    container.appendChild(el);
    container.querySelector('input,select,textarea')?.classList.add('is-invalid');
  }

  function clearError(container) {
    var el = container.querySelector('.auth-field-error');
    if (el) el.remove();
    container.querySelector('.is-invalid')?.classList.remove('is-invalid');
  }

  function showStepAlert(stepId, message) {
    var step = document.getElementById(stepId);
    var existing = step.querySelector('.auth-step-alert');
    if (existing) existing.remove();
    var el = document.createElement('div');
    el.className = 'auth-alert auth-alert--error auth-step-alert';
    el.innerHTML = '<span class="material-symbols-outlined">error</span> ' + message;
    step.insertBefore(el, step.firstChild);
    step.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  function clearStepAlert(stepId) {
    var el = document.getElementById(stepId)?.querySelector('.auth-step-alert');
    if (el) el.remove();
  }

  // ── Step 2 validation ──────────────────────────────────────────
  function validateStep2() {
    var ok = true;
    clearStepAlert('step-2');

    var login = form.querySelector('#user_login');
    var loginWrap = login.closest('.auth-form__field');
    clearError(loginWrap);
    if (!login.value.trim()) { showError(loginWrap, 'Username is required.'); ok = false; }

    var email = form.querySelector('#user_email');
    var emailWrap = email.closest('.auth-form__field');
    clearError(emailWrap);
    if (!email.value.trim()) { showError(emailWrap, 'Email is required.'); ok = false; }
    else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) { showError(emailWrap, 'Enter a valid email.'); ok = false; }

    var pass  = form.querySelector('#user_pass');
    var pass2 = form.querySelector('#user_pass2');
    var passWrap  = pass.closest('.auth-form__field');
    var pass2Wrap = pass2.closest('.auth-form__field');
    clearError(passWrap); clearError(pass2Wrap);

    if (!pass.value) { showError(passWrap, 'Password is required.'); ok = false; }
    else if (pass.value.length < 8) { showError(passWrap, 'Minimum 8 characters.'); ok = false; }

    if (!pass2.value) { showError(pass2Wrap, 'Confirm your password.'); ok = false; }
    else if (pass.value && pass.value !== pass2.value) { showError(pass2Wrap, 'Passwords do not match.'); ok = false; }

    if (!ok) showStepAlert('step-2', 'Please fix the highlighted fields to continue.');
    return ok;
  }

  // ── Live password match indicator ─────────────────────────────
  var pass2Input = form.querySelector('#user_pass2');
  pass2Input.addEventListener('input', function() {
    var pass  = form.querySelector('#user_pass').value;
    var wrap  = this.closest('.auth-form__field');
    clearError(wrap);
    if (this.value && pass !== this.value) {
      showError(wrap, 'Passwords do not match.');
    }
  });

  document.querySelectorAll('.js-next-step').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var next = parseInt(this.dataset.next);

      if (next === 2) {
        clearStepAlert('step-1');
        var checked = form.querySelectorAll('input[name="hfa_roles[]"]:checked');
        if (!checked.length) {
          showStepAlert('step-1', 'Select at least one account type.');
          return;
        }
        updateRoleFields();
      }

      if (next === 3) {
        if (!validateStep2()) return;
      }

      showStep(next);
    });
  });

  document.querySelectorAll('.js-prev-step').forEach(function(btn) {
    btn.addEventListener('click', function() {
      showStep(parseInt(this.dataset.prev));
    });
  });

  function showStep(n) {
    currentStep = n;
    steps.forEach(function(s) { s.classList.remove('active'); });
    indicators.forEach(function(s) { s.classList.remove('active','done'); });
    var target = document.getElementById('step-' + n);
    if (target) target.classList.add('active');
    indicators.forEach(function(ind) {
      var sn = parseInt(ind.dataset.step);
      if (sn < n) ind.classList.add('done');
      if (sn === n) ind.classList.add('active');
    });
  }

  // ── Show/hide role-specific fields based on selected roles ─────
  function updateRoleFields() {
    var checked = Array.from(form.querySelectorAll('input[name="hfa_roles[]"]:checked')).map(function(i){return i.value;});
    document.querySelectorAll('.auth-role-fields').forEach(function(block) {
      var roles = block.dataset.roles.split(',');
      var show  = roles.some(function(r){ return checked.includes(r.trim()); });
      block.style.display = show ? '' : 'none';
    });
  }

  // Trigger on role card toggle
  form.querySelectorAll('input[name="hfa_roles[]"]').forEach(function(cb) {
    cb.addEventListener('change', function() {
      this.closest('.auth-role-card').classList.toggle('is-selected', this.checked);
    });
  });

  // ── Password toggle ────────────────────────────────────────────
  document.querySelectorAll('.js-toggle-pw').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var input = this.closest('.auth-form__input-wrap').querySelector('input');
      var icon  = this.querySelector('.material-symbols-outlined');
      input.type = input.type === 'password' ? 'text' : 'password';
      icon.textContent = input.type === 'password' ? 'visibility' : 'visibility_off';
    });
  });

  // ── Activity chips ─────────────────────────────────────────────
  document.querySelectorAll('.auth-activity-chip input').forEach(function(cb) {
    cb.addEventListener('change', function() {
      this.closest('.auth-activity-chip').classList.toggle('is-checked', this.checked);
    });
  });

  // ── Avatar picker ──────────────────────────────────────────────
  document.querySelectorAll('.auth-avatar-option input[type=radio]').forEach(function(radio) {
    radio.addEventListener('change', function() {
      document.querySelectorAll('.auth-avatar-option').forEach(function(el){ el.classList.remove('is-selected'); });
      if (this.checked) this.closest('.auth-avatar-option').classList.add('is-selected');
    });
  });

  // ── File upload label update ───────────────────────────────────
  document.querySelectorAll('.auth-upload-area__input').forEach(function(inp) {
    inp.addEventListener('change', function() {
      var label = this.closest('.auth-upload-area').querySelector('.auth-upload-area__label');
      if (this.files && this.files[0]) label.textContent = this.files[0].name;
    });
  });

  // ── Step 3 validation on submit ───────────────────────────────
  form.addEventListener('submit', function(e) {
    var ok = true;
    clearStepAlert('step-3');

    var roles = Array.from(form.querySelectorAll('input[name="hfa_roles[]"]:checked')).map(function(i){ return i.value; });
    var needsSafety = roles.some(function(r){ return ['hfa_explorer','hfa_organizer','hfa_guide'].includes(r); });

    if (needsSafety) {
      var blood = form.querySelector('#hfa_blood_type');
      var bloodWrap = blood.closest('.auth-form__field');
      clearError(bloodWrap);
      if (!blood.value) { showError(bloodWrap, 'Select your blood type.'); ok = false; }

      var phone = form.querySelector('#hfa_phone');
      var phoneWrap = phone.closest('.auth-form__field');
      clearError(phoneWrap);
      if (!phone.value.trim()) { showError(phoneWrap, 'Phone is required.'); ok = false; }

      var ecName = form.querySelector('#hfa_ec_name');
      var ecNameWrap = ecName.closest('.auth-form__field');
      clearError(ecNameWrap);
      if (!ecName.value.trim()) { showError(ecNameWrap, 'Emergency contact name is required.'); ok = false; }

      var ecPhone = form.querySelector('#hfa_ec_phone');
      var ecPhoneWrap = ecPhone.closest('.auth-form__field');
      clearError(ecPhoneWrap);
      if (!ecPhone.value.trim()) { showError(ecPhoneWrap, 'Emergency contact phone is required.'); ok = false; }
    }

    var needsId = roles.includes('hfa_organizer') || roles.includes('hfa_guide');
    if (needsId) {
      var idPhoto = form.querySelector('#hfa_id_photo');
      var idWrap  = idPhoto.closest('.auth-form__field');
      clearError(idWrap);
      if (!idPhoto.files || !idPhoto.files[0]) {
        showError(idWrap, 'ID photo is required for this role.');
        ok = false;
      }
    }

    if (!ok) {
      e.preventDefault();
      showStepAlert('step-3', 'Corrige los campos marcados para continuar.');
    }
  });

  // ── Init: run on load if error caused reload ───────────────────
  updateRoleFields();
})();
</script>

<?php get_template_part('parts/footer'); ?>
