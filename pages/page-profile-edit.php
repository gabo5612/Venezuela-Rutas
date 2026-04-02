<?php
/**
 * Template Name: Edit Profile
 */
if ( ! is_user_logged_in() ) {
    wp_redirect( get_permalink( get_page_by_path('login') ) ?: wp_login_url( get_permalink() ) );
    exit;
}

$user    = wp_get_current_user();
$user_id = $user->ID;
$error   = '';
$success = '';

$hfa_roles = class_exists('HFA_Roles') ? HFA_Roles::get_user_roles( $user_id ) : [];
$is_guide  = in_array( 'hfa_guide', $hfa_roles );
$is_org    = in_array( 'hfa_organizer', $hfa_roles );

// ── Handle form submission ───────────────────────────────────
if ( isset( $_POST['hfa_profile_nonce'] ) && wp_verify_nonce( $_POST['hfa_profile_nonce'], 'hfa_profile_edit' ) ) {

    $display_name = sanitize_text_field( $_POST['display_name'] ?? '' );
    $first_name   = sanitize_text_field( $_POST['first_name']   ?? '' );
    $last_name    = sanitize_text_field( $_POST['last_name']    ?? '' );
    $description  = sanitize_textarea_field( $_POST['description'] ?? '' );
    $email        = sanitize_email( $_POST['user_email'] ?? '' );
    $new_pass     = $_POST['new_pass']  ?? '';
    $new_pass2    = $_POST['new_pass2'] ?? '';

    // Activities
    $activities   = isset($_POST['activities']) ? (array)$_POST['activities'] : [];
    $allowed_acts = array_keys( class_exists('HFA_Roles') ? HFA_Roles::activity_types() : [] ) ?: ['hiking','road-cycling','mtb','moto','car','4x4','camping','gastronomy'];
    $activities   = array_values( array_intersect( $activities, $allowed_acts ) );

    // Avatar
    $chosen_avatar = (int) ( $_POST['hfa_avatar'] ?? 0 );

    // Validate
    if ( $email && $email !== $user->user_email && email_exists( $email ) ) {
        $error = 'That email is already in use by another account.';
    } elseif ( $new_pass && $new_pass !== $new_pass2 ) {
        $error = 'Passwords do not match.';
    } elseif ( $new_pass && strlen($new_pass) < 8 ) {
        $error = 'Password must be at least 8 characters.';
    } else {
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
            update_user_meta( $user_id, 'hfa_activity_prefs', $activities );

            if ( $chosen_avatar ) {
                $pool = get_option('hfa_avatar_pool', []);
                if ( in_array( $chosen_avatar, $pool ) ) update_user_meta( $user_id, 'hfa_avatar_id', $chosen_avatar );
            }

            // Guide / organizer fields
            if ( $is_guide || $is_org ) {
                $phone    = sanitize_text_field( $_POST['hfa_phone'] ?? '' );
                $ec_name  = sanitize_text_field( $_POST['hfa_ec_name'] ?? '' );
                $ec_phone = sanitize_text_field( $_POST['hfa_ec_phone'] ?? '' );
                if ( $phone ) update_user_meta( $user_id, 'hfa_phone', $phone );
                if ( $ec_name || $ec_phone ) update_user_meta( $user_id, 'hfa_emergency_contact', ['name' => $ec_name, 'phone' => $ec_phone] );
                update_user_meta( $user_id, 'hfa_first_aid', isset($_POST['hfa_first_aid']) ? 1 : 0 );
            }

            if ( $is_guide ) {
                $whatsapp  = sanitize_text_field( $_POST['hfa_whatsapp']  ?? '' );
                $instagram = sanitize_text_field( str_replace('@','', $_POST['hfa_instagram'] ?? '') );
                $guide_bio = sanitize_textarea_field( $_POST['hfa_guide_bio'] ?? '' );
                update_user_meta( $user_id, 'hfa_whatsapp',  $whatsapp );
                update_user_meta( $user_id, 'hfa_instagram', $instagram );
                update_user_meta( $user_id, 'hfa_guide_bio', $guide_bio );
            }

            $success = 'Profile updated successfully.';
            $user    = get_userdata( $user_id );
        }
    }
}

// Current values
$activity_prefs  = get_user_meta( $user_id, 'hfa_activity_prefs', true ) ?: [];
$current_avatar  = (int) get_user_meta( $user_id, 'hfa_avatar_id', true );
$avatar_pool     = get_option('hfa_avatar_pool', []);
$activity_labels = class_exists('HFA_Roles') ? HFA_Roles::activity_types() : [];

// Guide / organizer meta
$hfa_phone  = get_user_meta( $user_id, 'hfa_phone', true );
$hfa_ec     = get_user_meta( $user_id, 'hfa_emergency_contact', true );
$first_aid  = get_user_meta( $user_id, 'hfa_first_aid', true );
$whatsapp   = get_user_meta( $user_id, 'hfa_whatsapp',  true );
$instagram  = get_user_meta( $user_id, 'hfa_instagram', true );
$guide_bio  = get_user_meta( $user_id, 'hfa_guide_bio', true );

get_template_part('parts/header');
?>

<main class="auth-page auth-page--edit">
  <div class="auth-page__bg"></div>

  <div class="auth-container" style="max-width:600px">

    <a href="<?php echo esc_url( get_author_posts_url($user_id) ); ?>" class="auth-back-link">
      <span class="material-symbols-outlined">arrow_back</span>
      Back to my profile
    </a>

    <div class="auth-card auth-card--wide">
      <h1 class="auth-card__title">Edit profile</h1>
      <p class="auth-card__subtitle">Changes will be reflected on your public profile</p>

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
        <?php wp_nonce_field('hfa_profile_edit', 'hfa_profile_nonce'); ?>

        <!-- Avatar picker -->
        <?php if ( ! empty($avatar_pool) ) : ?>
        <div class="auth-form__field">
          <label class="auth-form__label">Your avatar</label>
          <div class="auth-avatar-grid">
            <?php foreach ($avatar_pool as $att_id) :
              $url = wp_get_attachment_image_url($att_id, 'thumbnail');
              if (!$url) continue;
              $selected = $current_avatar === $att_id ? 'is-selected' : '';
              $checked  = $current_avatar === $att_id ? 'checked' : '';
            ?>
            <label class="auth-avatar-option <?php echo $selected; ?>">
              <input type="radio" name="hfa_avatar" value="<?php echo (int)$att_id; ?>" <?php echo $checked; ?>>
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
            <label class="auth-form__label" for="first_name">First name</label>
            <div class="auth-form__input-wrap">
              <span class="material-symbols-outlined auth-form__icon">badge</span>
              <input type="text" id="first_name" name="first_name" class="auth-form__input"
                     value="<?php echo esc_attr($user->first_name); ?>">
            </div>
          </div>
          <div class="auth-form__field">
            <label class="auth-form__label" for="last_name">Last name</label>
            <div class="auth-form__input-wrap">
              <span class="material-symbols-outlined auth-form__icon">badge</span>
              <input type="text" id="last_name" name="last_name" class="auth-form__input"
                     value="<?php echo esc_attr($user->last_name); ?>">
            </div>
          </div>
        </div>

        <div class="auth-form__field">
          <label class="auth-form__label" for="display_name">Public name</label>
          <div class="auth-form__input-wrap">
            <span class="material-symbols-outlined auth-form__icon">person</span>
            <input type="text" id="display_name" name="display_name" class="auth-form__input"
                   value="<?php echo esc_attr($user->display_name); ?>">
          </div>
        </div>

        <div class="auth-form__field">
          <label class="auth-form__label" for="description">Bio <span class="auth-form__optional">Tell us about yourself as an explorer</span></label>
          <textarea id="description" name="description" class="auth-form__input" rows="3"
                    style="padding-left:.85rem"
                    placeholder="e.g. MTB enthusiast and nature photographer..."><?php echo esc_textarea($user->description); ?></textarea>
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
            Activities of interest
            <span class="auth-form__optional">You can change your selection</span>
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

        <!-- ── Guide / Organizer fields ────────────────────── -->
        <?php if ( $is_guide || $is_org ) : ?>
        <div class="auth-section-divider">
          <span class="material-symbols-outlined"><?php echo $is_guide ? 'explore' : 'flag'; ?></span>
          <?php echo $is_guide ? 'Guide Profile' : 'Organizer Profile'; ?>
        </div>

        <div class="auth-form__grid">
          <div class="auth-form__field">
            <label class="auth-form__label" for="hfa_phone">Phone</label>
            <div class="auth-form__input-wrap">
              <span class="material-symbols-outlined auth-form__icon">phone</span>
              <input type="tel" id="hfa_phone" name="hfa_phone" class="auth-form__input"
                     placeholder="+58 412 000 0000"
                     value="<?php echo esc_attr($hfa_phone); ?>">
            </div>
          </div>
          <div class="auth-form__field">
            <label class="auth-form__label">First aid</label>
            <label class="auth-toggle-label" style="margin-top:.5rem">
              <input type="checkbox" name="hfa_first_aid" value="1" <?php checked($first_aid, 1); ?>>
              <span class="auth-toggle-track"></span>
              <span>I have first aid knowledge</span>
            </label>
          </div>
        </div>

        <div class="auth-form__grid">
          <div class="auth-form__field">
            <label class="auth-form__label" for="hfa_ec_name">Emergency contact — Name</label>
            <div class="auth-form__input-wrap">
              <span class="material-symbols-outlined auth-form__icon">person_alert</span>
              <input type="text" id="hfa_ec_name" name="hfa_ec_name" class="auth-form__input"
                     placeholder="María Arias"
                     value="<?php echo esc_attr(is_array($hfa_ec) ? ($hfa_ec['name'] ?? '') : ''); ?>">
            </div>
          </div>
          <div class="auth-form__field">
            <label class="auth-form__label" for="hfa_ec_phone">Emergency contact — Phone</label>
            <div class="auth-form__input-wrap">
              <span class="material-symbols-outlined auth-form__icon">call</span>
              <input type="tel" id="hfa_ec_phone" name="hfa_ec_phone" class="auth-form__input"
                     placeholder="+58 212 000 0000"
                     value="<?php echo esc_attr(is_array($hfa_ec) ? ($hfa_ec['phone'] ?? '') : ''); ?>">
            </div>
          </div>
        </div>

        <?php if ( $is_guide ) : ?>
        <div class="auth-form__grid">
          <div class="auth-form__field">
            <label class="auth-form__label" for="hfa_whatsapp">WhatsApp</label>
            <div class="auth-form__input-wrap">
              <span class="material-symbols-outlined auth-form__icon">chat</span>
              <input type="tel" id="hfa_whatsapp" name="hfa_whatsapp" class="auth-form__input"
                     placeholder="+58 412 000 0000"
                     value="<?php echo esc_attr($whatsapp); ?>">
            </div>
          </div>
          <div class="auth-form__field">
            <label class="auth-form__label" for="hfa_instagram">Instagram</label>
            <div class="auth-form__input-wrap">
              <span class="material-symbols-outlined auth-form__icon">photo_camera</span>
              <input type="text" id="hfa_instagram" name="hfa_instagram" class="auth-form__input"
                     placeholder="@tu_usuario"
                     value="<?php echo esc_attr($instagram ? '@'.$instagram : ''); ?>">
            </div>
          </div>
        </div>

        <div class="auth-form__field">
          <label class="auth-form__label" for="hfa_guide_bio">
            Experience and certifications
            <span class="auth-form__optional">Visible on your public profile</span>
          </label>
          <textarea id="hfa_guide_bio" name="hfa_guide_bio"
                    class="auth-form__input auth-form__textarea"
                    rows="4"
                    placeholder="Tell us about your experience, routes you know, certifications, years guiding…"><?php echo esc_textarea($guide_bio); ?></textarea>
        </div>
        <?php endif; ?>
        <?php endif; ?>

        <!-- Change password (optional) -->
        <details class="auth-form__details">
          <summary class="auth-form__details-trigger">
            <span class="material-symbols-outlined">lock</span>
            Change password <span class="auth-form__optional">(optional)</span>
          </summary>
          <div class="auth-form__details-body">
            <div class="auth-form__grid">
              <div class="auth-form__field">
                <label class="auth-form__label" for="new_pass">New password</label>
                <div class="auth-form__input-wrap">
                  <span class="material-symbols-outlined auth-form__icon">lock</span>
                  <input type="password" id="new_pass" name="new_pass" class="auth-form__input"
                         placeholder="Min. 8 characters" autocomplete="new-password">
                  <button type="button" class="auth-form__toggle-pw js-toggle-pw" tabindex="-1">
                    <span class="material-symbols-outlined">visibility</span>
                  </button>
                </div>
              </div>
              <div class="auth-form__field">
                <label class="auth-form__label" for="new_pass2">Confirm password</label>
                <div class="auth-form__input-wrap">
                  <span class="material-symbols-outlined auth-form__icon">lock</span>
                  <input type="password" id="new_pass2" name="new_pass2" class="auth-form__input"
                         placeholder="Repeat password" autocomplete="new-password">
                </div>
              </div>
            </div>
          </div>
        </details>

        <button type="submit" class="auth-btn auth-btn--primary" style="margin-top:1.25rem">
          Save changes
          <span class="material-symbols-outlined">save</span>
        </button>

      </form>
    </div>

    <p class="auth-footer-note">
      Your username (<?php echo esc_html($user->user_login); ?>) cannot be changed.
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
