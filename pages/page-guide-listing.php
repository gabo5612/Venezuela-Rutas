<?php
/**
 * Template Name: Guide Listing
 * Allows approved guides to create or update their public guide profile.
 */

// Must be logged in
if ( ! is_user_logged_in() ) {
    wp_redirect( get_permalink( get_page_by_path('login') ) ?: wp_login_url( get_permalink() ) );
    exit;
}

$user    = wp_get_current_user();
$user_id = $user->ID;
$error   = '';
$success = '';

// Must be an approved guide
$hfa_roles   = class_exists('HFA_Roles') ? HFA_Roles::get_user_roles( $user_id ) : [];
$is_guide    = in_array( 'hfa_guide', $hfa_roles );
$is_approved = class_exists('HFA_Roles') && HFA_Roles::get_approval_status( $user_id ) === 'approved';

if ( ! $is_guide || ! $is_approved ) {
    wp_redirect( get_author_posts_url( $user_id ) );
    exit;
}

// Existing guide post
$guide_post_id = (int) get_user_meta( $user_id, 'hfa_guide_post_id', true );
$guide_post    = $guide_post_id ? get_post( $guide_post_id ) : null;

// Only allow editing own guide post
if ( $guide_post && (int) $guide_post->post_author !== $user_id ) {
    $guide_post    = null;
    $guide_post_id = 0;
}

// Fetch guide zones taxonomy
$all_zones = get_terms(['taxonomy' => 'guide-zone', 'hide_empty' => false]);

$specialties = [
    'Hiking', 'Climbing', '4x4', 'Kayak', 'Camping',
    'Photography', 'Scuba Diving', 'Mountain Biking',
    'Road Cycling', 'Motorcycle', 'Gastronomy',
];

// ── Handle form submission ────────────────────────────────────
if ( isset( $_POST['hfa_guide_listing_nonce'] ) && wp_verify_nonce( $_POST['hfa_guide_listing_nonce'], 'hfa_guide_listing_' . $user_id ) ) {

    $title       = sanitize_text_field( $_POST['guide_title']   ?? '' );
    $bio         = sanitize_textarea_field( $_POST['guide_bio'] ?? '' );
    $whatsapp    = sanitize_text_field( $_POST['guide_whatsapp']    ?? '' );
    $email_val   = sanitize_email( $_POST['guide_email']            ?? '' );
    $instagram   = sanitize_text_field( $_POST['guide_instagram']   ?? '' );
    $price       = floatval( $_POST['guide_price_from']             ?? 0 );
    $raw_spec    = isset( $_POST['guide_specialty'] ) ? (array) $_POST['guide_specialty'] : [];
    $spec        = array_intersect( $raw_spec, $specialties );
    $zone_ids    = isset( $_POST['guide_zones'] ) ? array_map('intval', (array) $_POST['guide_zones'] ) : [];

    // Handle photo upload
    $new_photo_id = 0;
    if ( ! empty( $_FILES['guide_photo']['name'] ) ) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        $upload = wp_handle_upload( $_FILES['guide_photo'], [ 'test_form' => false ] );
        if ( isset( $upload['file'] ) && ! isset( $upload['error'] ) ) {
            $att_id = wp_insert_attachment([
                'post_title'     => sanitize_file_name( basename( $upload['file'] ) ),
                'post_mime_type' => $upload['type'],
                'post_status'    => 'inherit',
                'post_author'    => $user_id,
            ], $upload['file'] );
            if ( ! is_wp_error( $att_id ) ) {
                wp_update_attachment_metadata( $att_id, wp_generate_attachment_metadata( $att_id, $upload['file'] ) );
                $new_photo_id = $att_id;
            }
        }
    }

    if ( ! $title ) {
        $error = 'Your listing title is required.';
    } elseif ( ! $whatsapp && ! $email_val ) {
        $error = 'Please provide at least a WhatsApp number or email so explorers can contact you.';
    } else {
        // Create or update post
        $post_data = [
            'post_type'    => 'guide',
            'post_title'   => $title,
            'post_content' => $bio,
            'post_status'  => 'publish',
            'post_author'  => $user_id,
        ];

        if ( $guide_post ) {
            $post_data['ID'] = $guide_post_id;
            $result = wp_update_post( $post_data, true );
        } else {
            $result = wp_insert_post( $post_data, true );
        }

        if ( is_wp_error( $result ) ) {
            $error = $result->get_error_message();
        } else {
            $guide_post_id = $result;
            update_user_meta( $user_id, 'hfa_guide_post_id', $guide_post_id );

            // Save ACF fields
            if ( function_exists('update_field') ) {
                update_field( 'guide_whatsapp',   $whatsapp,  $guide_post_id );
                update_field( 'guide_email',      $email_val, $guide_post_id );
                update_field( 'guide_instagram',  ltrim( $instagram, '@' ), $guide_post_id );
                update_field( 'guide_price_from', $price ?: '',             $guide_post_id );
                update_field( 'guide_specialty',  $spec,                    $guide_post_id );
                if ( $new_photo_id ) {
                    update_field( 'guide_photo', $new_photo_id, $guide_post_id );
                }
            }

            // Set guide zones
            if ( ! empty( $zone_ids ) ) {
                wp_set_post_terms( $guide_post_id, $zone_ids, 'guide-zone' );
            } else {
                wp_set_post_terms( $guide_post_id, [], 'guide-zone' );
            }

            $guide_post = get_post( $guide_post_id );
            $success    = 'Your guide listing has been saved!';
        }
    }
}

// Pre-fill values from existing post
$val_title     = $guide_post ? $guide_post->post_title   : $user->display_name;
$val_bio       = $guide_post ? $guide_post->post_content : '';
$val_whatsapp  = $guide_post_id ? ( get_field('guide_whatsapp',   $guide_post_id) ?: '' ) : get_user_meta($user_id,'hfa_whatsapp',true);
$val_email     = $guide_post_id ? ( get_field('guide_email',      $guide_post_id) ?: '' ) : $user->user_email;
$val_instagram = $guide_post_id ? ( get_field('guide_instagram',  $guide_post_id) ?: '' ) : get_user_meta($user_id,'hfa_instagram',true);
$val_price     = $guide_post_id ? ( get_field('guide_price_from', $guide_post_id) ?: '' ) : '';
$val_spec      = $guide_post_id ? ( get_field('guide_specialty',  $guide_post_id) ?: [] ) : [];
$val_zones     = $guide_post_id ? wp_get_post_terms( $guide_post_id, 'guide-zone', ['fields'=>'ids'] ) : [];

get_template_part('parts/header');
?>

<main class="auth-page">
  <div class="auth-page__bg"></div>
  <div class="auth-container" style="max-width:680px">

    <a href="<?php echo esc_url( get_author_posts_url($user_id) ); ?>" class="auth-back-link">
      <span class="material-symbols-outlined">arrow_back</span> Back to profile
    </a>

    <div class="auth-card">
      <h1 class="auth-card__title">
        <?php echo $guide_post ? 'Edit guide listing' : 'Create your guide listing'; ?>
      </h1>
      <p class="auth-card__subtitle">
        This is your public profile visible to all explorers in the guide directory.
      </p>

      <?php if ( $error ) : ?>
      <div class="auth-alert auth-alert--error">
        <span class="material-symbols-outlined">error</span>
        <?php echo esc_html( $error ); ?>
      </div>
      <?php endif; ?>

      <?php if ( $success ) : ?>
      <div class="auth-alert auth-alert--success">
        <span class="material-symbols-outlined">check_circle</span>
        <?php echo esc_html( $success ); ?>
        <a href="<?php echo esc_url( get_permalink($guide_post_id) ); ?>" style="margin-left:.5rem;color:inherit;font-weight:700">View listing →</a>
      </div>
      <?php endif; ?>

      <form class="auth-form" method="post" action="" enctype="multipart/form-data">
        <?php wp_nonce_field( 'hfa_guide_listing_' . $user_id, 'hfa_guide_listing_nonce' ); ?>

        <!-- Profile photo -->
        <?php
        $current_photo_url = '';
        if ( $guide_post_id && function_exists('get_field') ) {
            $photo_field = get_field( 'guide_photo', $guide_post_id );
            if ( is_array($photo_field) ) $current_photo_url = $photo_field['url'] ?? '';
            elseif ( is_string($photo_field) ) $current_photo_url = $photo_field;
            elseif ( is_int($photo_field) ) $current_photo_url = wp_get_attachment_image_url($photo_field,'medium') ?: '';
        }
        ?>
        <div class="auth-form__field">
          <label class="auth-form__label">Profile photo <span class="auth-form__optional">Optional — max 2MB, JPG/PNG</span></label>
          <div class="guide-listing-photo-wrap">
            <?php if ( $current_photo_url ) : ?>
            <img id="guide-photo-preview" src="<?php echo esc_url($current_photo_url); ?>"
                 alt="Current photo" class="guide-listing-photo__preview">
            <?php else : ?>
            <div id="guide-photo-preview" class="guide-listing-photo__placeholder">
              <span class="material-symbols-outlined">person</span>
            </div>
            <?php endif; ?>
            <div class="guide-listing-photo__controls">
              <label class="auth-btn auth-btn--outline" style="cursor:pointer">
                <span class="material-symbols-outlined">upload</span>
                <?php echo $current_photo_url ? 'Change photo' : 'Upload photo'; ?>
                <input type="file" name="guide_photo" id="guide_photo" accept="image/jpeg,image/png,image/webp"
                       style="display:none">
              </label>
              <p class="auth-form__hint" style="margin:0">This photo appears on your guide card in the directory.</p>
            </div>
          </div>
        </div>

        <!-- Listing title -->
        <div class="auth-form__field">
          <label class="auth-form__label" for="guide_title">Listing name *</label>
          <div class="auth-form__input-wrap">
            <span class="material-symbols-outlined auth-form__icon">badge</span>
            <input type="text" id="guide_title" name="guide_title" class="auth-form__input"
                   placeholder="e.g. Gabriel Arias — Mountain Guide"
                   value="<?php echo esc_attr($val_title); ?>" required>
          </div>
          <p class="auth-form__hint">This is the name shown on your card in the guide directory.</p>
        </div>

        <!-- Bio -->
        <div class="auth-form__field">
          <label class="auth-form__label" for="guide_bio">About you / Bio</label>
          <textarea id="guide_bio" name="guide_bio" class="auth-form__input auth-form__textarea"
                    rows="4" placeholder="Describe your experience, certifications, and what makes you a great guide..."><?php echo esc_textarea($val_bio); ?></textarea>
        </div>

        <!-- Contact -->
        <div class="auth-form__field">
          <label class="auth-form__label">Contact <span class="auth-form__optional">At least one required</span></label>
          <div class="auth-form__grid">
            <div class="auth-form__input-wrap">
              <span class="material-symbols-outlined auth-form__icon">chat</span>
              <input type="text" name="guide_whatsapp" class="auth-form__input"
                     placeholder="+58 412 000 0000"
                     value="<?php echo esc_attr($val_whatsapp); ?>">
            </div>
            <div class="auth-form__input-wrap">
              <span class="material-symbols-outlined auth-form__icon">mail</span>
              <input type="email" name="guide_email" class="auth-form__input"
                     placeholder="your@email.com"
                     value="<?php echo esc_attr($val_email); ?>">
            </div>
          </div>
        </div>

        <!-- Instagram + Price -->
        <div class="auth-form__grid">
          <div class="auth-form__field">
            <label class="auth-form__label" for="guide_instagram">Instagram</label>
            <div class="auth-form__input-wrap">
              <span class="material-symbols-outlined auth-form__icon">photo_camera</span>
              <input type="text" id="guide_instagram" name="guide_instagram" class="auth-form__input"
                     placeholder="yourhandle"
                     value="<?php echo esc_attr(ltrim($val_instagram,'@')); ?>">
            </div>
          </div>
          <div class="auth-form__field">
            <label class="auth-form__label" for="guide_price_from">Rate from (USD/day)</label>
            <div class="auth-form__input-wrap">
              <span class="material-symbols-outlined auth-form__icon">payments</span>
              <input type="number" id="guide_price_from" name="guide_price_from" class="auth-form__input"
                     placeholder="50" min="0" step="1"
                     value="<?php echo esc_attr($val_price); ?>">
            </div>
          </div>
        </div>

        <!-- Specialties -->
        <div class="auth-form__field">
          <label class="auth-form__label">Specialties <span class="auth-form__optional">Choose all that apply</span></label>
          <div class="auth-activities">
            <?php foreach ( $specialties as $s ) :
              $checked = in_array($s, $val_spec) ? 'checked' : '';
            ?>
            <label class="auth-activity-chip <?php echo $checked ? 'is-checked' : ''; ?>">
              <input type="checkbox" name="guide_specialty[]" value="<?php echo esc_attr($s); ?>" <?php echo $checked; ?>>
              <span><?php echo esc_html($s); ?></span>
            </label>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Zones -->
        <?php if ( ! empty($all_zones) && ! is_wp_error($all_zones) ) : ?>
        <div class="auth-form__field">
          <label class="auth-form__label">Zone(s) you operate in <span class="auth-form__optional">Optional</span></label>
          <div class="auth-activities">
            <?php foreach ( $all_zones as $z ) :
              $checked = in_array($z->term_id, $val_zones) ? 'checked' : '';
            ?>
            <label class="auth-activity-chip <?php echo $checked ? 'is-checked' : ''; ?>">
              <input type="checkbox" name="guide_zones[]" value="<?php echo (int)$z->term_id; ?>" <?php echo $checked; ?>>
              <span class="material-symbols-outlined" style="font-size:1rem">location_on</span>
              <span><?php echo esc_html($z->name); ?></span>
            </label>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

        <div class="auth-form__nav" style="justify-content:flex-end">
          <button type="submit" class="auth-btn auth-btn--primary">
            <span class="material-symbols-outlined">save</span>
            <?php echo $guide_post ? 'Save changes' : 'Publish listing'; ?>
          </button>
        </div>

      </form>

    </div>
  </div>
</main>

<script>
document.querySelectorAll('.auth-activity-chip input[type=checkbox]').forEach(function(cb) {
  cb.addEventListener('change', function() {
    this.closest('.auth-activity-chip').classList.toggle('is-checked', this.checked);
  });
});
// Live photo preview
var photoInput = document.getElementById('guide_photo');
if (photoInput) {
  photoInput.addEventListener('change', function() {
    if (!this.files || !this.files[0]) return;
    var reader = new FileReader();
    reader.onload = function(e) {
      var preview = document.getElementById('guide-photo-preview');
      if (preview.tagName === 'IMG') {
        preview.src = e.target.result;
      } else {
        var img = document.createElement('img');
        img.id = 'guide-photo-preview';
        img.src = e.target.result;
        img.className = 'guide-listing-photo__preview';
        preview.replaceWith(img);
      }
    };
    reader.readAsDataURL(this.files[0]);
  });
}
</script>

<?php get_template_part('parts/footer'); ?>
