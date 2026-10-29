<?php
/**
 * Settings → Clinic Info: one place for phone, WhatsApp, address, hours, etc.
 * Every page, the header bar, footer, form and WhatsApp button read from here.
 *
 * @package jawaria-clinic
 */

defined( 'ABSPATH' ) || exit;

/**
 * Field definitions: key => [label, type, help].
 *
 * @return array
 */
function jr_settings_fields() {
	return array(
		'Doctor'          => array(
			'doctor_name'      => array( 'Doctor name', 'text', '' ),
			'title'            => array( 'Title / specialty line', 'text', 'Shown under the name.' ),
			'qualifications'   => array( 'Qualifications', 'textarea', 'One line per row, e.g. MBBS, FCPS (Obstetrics & Gynaecology). Shown wherever Dr. Jawaria is introduced.' ),
			'experience_years' => array( 'Years of experience', 'text', 'Number only, e.g. 15.' ),
		),
		'Clinic & contact' => array(
			'city'         => array( 'City', 'text', '' ),
			'address'      => array( 'Clinic address', 'textarea', 'Full address. Use one line per clinic if she sits at more than one.' ),
			'whatsapp'     => array( 'WhatsApp number', 'text', 'The clinic number shown on the website (WhatsApp only — there are no call buttons). e.g. 0300 0620903.' ),
			'email'        => array( 'Public email', 'text', '' ),
			'map_link'     => array( 'Google Maps link', 'text', 'The "Share → Copy link" URL from Google Maps (used for "Get directions").' ),
			'map_embed'    => array( 'Google Maps embed URL', 'text', 'Optional. Only the src="…" part of Google Maps "Share → Embed a map". If empty, the map shows the clinic address.' ),
		),
		'Timings'         => array(
			'clinics'     => array( 'Clinic locations', 'textarea', 'One line per clinic: Name | City | Address | Hours | Map link | Slot minutes. Hours use day codes Mo Tu We Th Fr Sa Su with 24-hour times, comma between groups — for example "Mo-Fr 15:00-19:00, Sa 10:00-13:00". Adding a new city later means adding one more line here; the top bar, contact pages, booking form and Google schema all follow automatically. Slot minutes is optional and defaults to the setting below.' ),
			'slot_minutes' => array( 'Booking slot length (minutes)', 'text', 'Gap between the time choices on the booking form. 15 gives 3:00 PM – 3:15 PM, 3:15 PM – 3:30 PM, and so on.' ),
			'hours'       => array( 'Clinic timings (legacy)', 'textarea', 'Not used while Clinic locations above is filled in.' ),
			'hours_short' => array( 'Short timings (legacy)', 'text', 'Not used while Clinic locations above is filled in.' ),
			'opening_hours' => array( 'Opening hours for Google', 'text', 'Not used while Clinic locations above is filled in.' ),
			'time_slots'  => array( 'Time choices (legacy)', 'textarea', 'Not used while Clinic locations above is filled in — slots are generated from each clinic’s hours.' ),
		),
		'Appointments & WhatsApp' => array(
			'notify_email' => array( 'Send new appointment requests to', 'text', 'Staff email. Defaults to the site admin email.' ),
			'wa_message'   => array( 'WhatsApp greeting', 'textarea', 'Pre-filled text when a patient taps the WhatsApp button.' ),
			'wa_float'     => array( 'Show floating WhatsApp button', 'checkbox', '' ),
			'chatbot'      => array( 'Show website chat assistant', 'checkbox', 'FAQ assistant: timings, location, services, booking links. No medical advice.' ),
			'video_consult_url' => array( 'Video consultation link', 'url', 'Optional. If filled, a "Video Consultation" button appears in the header.' ),
		),
		'Google reCAPTCHA (booking form)' => array(
			'recaptcha_site'   => array( 'reCAPTCHA site key', 'text', 'Create free keys at https://www.google.com/recaptcha/admin — choose "reCAPTCHA v2 → I\'m not a robot" and add your domain. The tick box appears on the booking form once both keys are filled.' ),
			'recaptcha_secret' => array( 'reCAPTCHA secret key', 'password', 'Kept private; never shown on the website.' ),
		),
		'Homepage'        => array(
			'slider_seconds' => array( 'Banner slider: seconds per slide', 'text', 'Default 6. Slides themselves are edited under Home Slides.' ),
			'stats'          => array( 'Highlight numbers (hero cards)', 'textarea', 'One per line: Number | Label. Use only real, verified numbers, e.g. 1500+ | IUI procedures.' ),
			'google_rating'  => array( 'Google rating', 'text', 'e.g. 4.9 — only if it is her real Google rating. Leave empty to hide.' ),
			'google_reviews' => array( 'Google reviews link', 'url', '' ),
			'google_reviews_count' => array( 'Number of Google reviews', 'text', 'Only the real number from Google. Used with the rating for search results.' ),
			'areas'          => array( 'Areas served (local SEO)', 'textarea', 'One city per line. Used in search-engine data and the "Patients from" list.' ),
			'videos'         => array( 'YouTube videos', 'textarea', 'One per line: YouTube link | Title. Shown in the Videos section.' ),
		),
		'Social media'    => array(
			'facebook'  => array( 'Facebook page', 'url', '' ),
			'instagram' => array( 'Instagram', 'url', '' ),
			'youtube'   => array( 'YouTube channel', 'url', '' ),
			'tiktok'    => array( 'TikTok', 'url', '' ),
		),
	);
}

add_action(
	'admin_menu',
	function () {
		add_options_page(
			__( 'Clinic Info', 'jawaria-clinic' ),
			__( 'Clinic Info', 'jawaria-clinic' ),
			'manage_options',
			'jr-clinic-info',
			'jr_render_settings_page'
		);
	}
);

add_action(
	'admin_init',
	function () {
		register_setting(
			'jr_clinic_group',
			JR_OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => 'jr_sanitize_options',
				'default'           => jr_default_options(),
			)
		);
	}
);

/**
 * Sanitize settings on save.
 *
 * @param mixed $input Raw input.
 * @return array
 */
function jr_sanitize_options( $input ) {
	$input = is_array( $input ) ? $input : array();
	$out   = array();
	foreach ( jr_settings_fields() as $fields ) {
		foreach ( $fields as $key => $def ) {
			$val = isset( $input[ $key ] ) ? wp_unslash( $input[ $key ] ) : '';
			switch ( $def[1] ) {
				case 'checkbox':
					$out[ $key ] = $val ? '1' : '';
					break;
				case 'url':
					$out[ $key ] = esc_url_raw( $val );
					break;
				case 'textarea':
					$out[ $key ] = sanitize_textarea_field( $val );
					break;
				default:
					$out[ $key ] = sanitize_text_field( $val );
			}
		}
	}
	$out['email']        = sanitize_email( $out['email'] );
	$out['notify_email'] = sanitize_email( $out['notify_email'] );
	$out['map_link']     = esc_url_raw( $out['map_link'] );
	$out['map_embed']    = esc_url_raw( $out['map_embed'] );
	if ( $out['map_embed'] && ! preg_match( '#^https://(www\.|maps\.)?google\.[a-z.]+/maps(/embed|\?)#', $out['map_embed'] ) ) {
		add_settings_error( JR_OPTION, 'jr_map', __( 'The map embed URL must start with https://www.google.com/maps/embed — it was cleared.', 'jawaria-clinic' ) );
		$out['map_embed'] = '';
	}
	return $out;
}

/**
 * Render the settings screen.
 */
function jr_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$opts = wp_parse_args( (array) get_option( JR_OPTION, array() ), jr_default_options() );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Clinic Info', 'jawaria-clinic' ); ?></h1>
		<p><?php esc_html_e( 'Update these once — the header bar, footer, Contact page, booking form and WhatsApp button all use them.', 'jawaria-clinic' ); ?></p>
		<?php settings_errors( JR_OPTION ); ?>
		<form method="post" action="options.php">
			<?php settings_fields( 'jr_clinic_group' ); ?>
			<?php foreach ( jr_settings_fields() as $group => $fields ) : ?>
				<h2><?php echo esc_html( $group ); ?></h2>
				<table class="form-table" role="presentation">
					<?php foreach ( $fields as $key => $def ) : ?>
						<?php $id = 'jr_' . $key; ?>
						<tr>
							<th scope="row"><label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $def[0] ); ?></label></th>
							<td>
								<?php if ( 'textarea' === $def[1] ) : ?>
									<textarea id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( JR_OPTION . '[' . $key . ']' ); ?>" rows="4" class="large-text"><?php echo esc_textarea( $opts[ $key ] ); ?></textarea>
								<?php elseif ( 'password' === $def[1] ) : ?>
									<input type="password" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( JR_OPTION . '[' . $key . ']' ); ?>" value="<?php echo esc_attr( $opts[ $key ] ); ?>" class="regular-text" autocomplete="off">
								<?php elseif ( 'checkbox' === $def[1] ) : ?>
									<input type="checkbox" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( JR_OPTION . '[' . $key . ']' ); ?>" value="1" <?php checked( $opts[ $key ], '1' ); ?>>
								<?php else : ?>
									<input type="text" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( JR_OPTION . '[' . $key . ']' ); ?>" value="<?php echo esc_attr( $opts[ $key ] ); ?>" class="regular-text">
								<?php endif; ?>
								<?php if ( $def[2] ) : ?>
									<p class="description"><?php echo esc_html( $def[2] ); ?></p>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</table>
			<?php endforeach; ?>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}

/**
 * Remind admins about missing essentials.
 */
add_action(
	'admin_notices',
	function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$missing = array();
		foreach ( array( 'whatsapp' => 'WhatsApp number', 'address' => 'address', 'qualifications' => 'qualifications' ) as $k => $label ) {
			if ( '' === jr_opt( $k ) ) {
				$missing[] = $label;
			}
		}
		if ( ! $missing ) {
			return;
		}
		printf(
			'<div class="notice notice-warning"><p>%s <a href="%s">%s</a></p></div>',
			esc_html( sprintf( /* translators: %s: list of fields */ __( 'Clinic Info still needs: %s.', 'jawaria-clinic' ), implode( ', ', $missing ) ) ),
			esc_url( admin_url( 'options-general.php?page=jr-clinic-info' ) ),
			esc_html__( 'Fill it in', 'jawaria-clinic' )
		);
	}
);
