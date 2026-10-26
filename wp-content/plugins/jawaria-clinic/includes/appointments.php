<?php
/**
 * Appointment requests.
 *
 * Phase 1: patients send a *request* (name, phone, service, preferred date/time).
 * Staff see them under "Appointments" in the dashboard, call/WhatsApp the patient
 * and set the status to Confirmed with the final date and time.
 * Phase 3 (WhatsApp + HMIS) can read and write the same records.
 *
 * No medical history is collected here on purpose.
 *
 * @package jawaria-clinic
 */

defined( 'ABSPATH' ) || exit;

/**
 * Statuses for a request.
 *
 * @return array
 */
function jr_appt_statuses() {
	return array(
		'new'       => __( 'New', 'jawaria-clinic' ),
		'contacted' => __( 'Contacted', 'jawaria-clinic' ),
		'confirmed' => __( 'Confirmed', 'jawaria-clinic' ),
		'cancelled' => __( 'Cancelled', 'jawaria-clinic' ),
		'no_reply'  => __( 'No reply', 'jawaria-clinic' ),
	);
}

/**
 * Register the private Appointment record type.
 */
function jr_register_appointment_type() {
	register_post_type(
		'jr_appointment',
		array(
			'labels'          => array(
				'name'          => __( 'Appointments', 'jawaria-clinic' ),
				'singular_name' => __( 'Appointment request', 'jawaria-clinic' ),
				'edit_item'     => __( 'Appointment request', 'jawaria-clinic' ),
				'add_new_item'  => __( 'Add appointment (phone/walk-in)', 'jawaria-clinic' ),
				'search_items'  => __( 'Search appointments', 'jawaria-clinic' ),
				'not_found'     => __( 'No appointment requests yet.', 'jawaria-clinic' ),
			),
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => true,
			'menu_position'   => 3,
			'menu_icon'       => 'dashicons-calendar-alt',
			'supports'        => array( 'title' ),
			'capability_type' => 'page',
			'map_meta_cap'    => true,
			'show_in_rest'    => false,
			'rewrite'         => false,
			'query_var'       => false,
		)
	);
}
add_action( 'init', 'jr_register_appointment_type' );

/* -------------------------------------------------------------------------
 * Front-end form
 * ---------------------------------------------------------------------- */

add_shortcode( 'jr_appointment_form', 'jr_render_appointment_form' );

/**
 * Render the request form.
 *
 * @return string
 */
function jr_render_appointment_form() {
	$state = array(
		'errors' => array(),
		'old'    => array(),
	);
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only display state.
	if ( isset( $_GET['jr_booked'] ) ) {
		$msg = '<div class="jr-alert jr-alert--ok" role="status"><strong>' . esc_html__( 'Thank you — your request has been received.', 'jawaria-clinic' ) . '</strong><br>' .
			esc_html__( 'This is not yet a confirmed appointment. Our team will call or WhatsApp you to confirm the date and time.', 'jawaria-clinic' ) . '</div>';
		return $msg . do_shortcode( '[jr_cta_buttons book="0"]' );
	}
	if ( isset( $_GET['jr_t'] ) ) {
		$saved = get_transient( 'jr_form_' . sanitize_key( wp_unslash( $_GET['jr_t'] ) ) );
		if ( is_array( $saved ) ) {
			$state = $saved;
		}
	}
	// phpcs:enable

	$old   = $state['old'];
	$val   = fn( $k ) => isset( $old[ $k ] ) ? $old[ $k ] : '';
	$today = current_time( 'Y-m-d' );
	$max   = gmdate( 'Y-m-d', strtotime( $today . ' +90 days' ) );

	ob_start();
	?>
	<form class="jr-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" novalidate>
		<?php if ( $state['errors'] ) : ?>
			<div class="jr-alert jr-alert--err" role="alert">
				<strong><?php esc_html_e( 'Please check the form:', 'jawaria-clinic' ); ?></strong>
				<ul>
					<?php foreach ( $state['errors'] as $e ) : ?>
						<li><?php echo esc_html( $e ); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>

		<input type="hidden" name="action" value="jr_book">
		<input type="hidden" name="jr_return" value="<?php echo esc_url( get_permalink() ); ?>">
		<?php wp_nonce_field( 'jr_book', 'jr_nonce' ); ?>
		<div class="jr-hp" aria-hidden="true"><label>Website <input type="text" name="jr_website" tabindex="-1" autocomplete="off"></label></div>

		<div class="jr-form__grid">
			<div>
				<label for="jr_name"><?php esc_html_e( 'Patient name', 'jawaria-clinic' ); ?> <span class="jr-req">*</span></label>
				<input type="text" id="jr_name" name="jr_name" required maxlength="80" autocomplete="name" value="<?php echo esc_attr( $val( 'name' ) ); ?>">
			</div>
			<div>
				<label for="jr_phone"><?php esc_html_e( 'Mobile / WhatsApp number', 'jawaria-clinic' ); ?> <span class="jr-req">*</span></label>
				<input type="tel" id="jr_phone" name="jr_phone" required maxlength="20" autocomplete="tel" inputmode="tel" placeholder="03xx xxxxxxx" value="<?php echo esc_attr( $val( 'phone' ) ); ?>">
			</div>
			<div class="jr-form__full">
				<label for="jr_service"><?php esc_html_e( 'Reason for visit', 'jawaria-clinic' ); ?></label>
				<select id="jr_service" name="jr_service">
					<?php foreach ( jr_service_choices() as $choice ) : ?>
						<option value="<?php echo esc_attr( $choice ); ?>" <?php selected( $val( 'service' ), $choice ); ?>><?php echo esc_html( $choice ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div>
				<label for="jr_date"><?php esc_html_e( 'Preferred date', 'jawaria-clinic' ); ?> <span class="jr-req">*</span></label>
				<input type="date" id="jr_date" name="jr_date" required min="<?php echo esc_attr( $today ); ?>" max="<?php echo esc_attr( $max ); ?>" value="<?php echo esc_attr( $val( 'date' ) ); ?>">
			</div>
			<div>
				<label for="jr_time"><?php esc_html_e( 'Preferred time', 'jawaria-clinic' ); ?></label>
				<select id="jr_time" name="jr_time">
					<?php foreach ( jr_lines( jr_opt( 'time_slots' ) ) as $slot ) : ?>
						<option value="<?php echo esc_attr( $slot ); ?>" <?php selected( $val( 'time' ), $slot ); ?>><?php echo esc_html( $slot ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="jr-form__full">
				<label for="jr_note"><?php esc_html_e( 'Anything we should know to arrange the visit? (optional)', 'jawaria-clinic' ); ?></label>
				<textarea id="jr_note" name="jr_note" maxlength="300"><?php echo esc_textarea( $val( 'note' ) ); ?></textarea>
				<p class="jr-form__hint"><?php esc_html_e( 'e.g. first visit or follow-up, best time to call. Please do not share medical details here — the doctor will discuss them with you in private.', 'jawaria-clinic' ); ?></p>
			</div>
			<div class="jr-form__full">
				<label class="jr-form__consent">
					<input type="checkbox" name="jr_consent" value="1" required <?php checked( $val( 'consent' ), '1' ); ?>>
					<span><?php esc_html_e( 'I agree that the clinic may contact me by phone or WhatsApp about this appointment.', 'jawaria-clinic' ); ?> <span class="jr-req">*</span></span>
				</label>
			</div>
			<div class="jr-form__full">
				<button type="submit" class="jr-btn jr-btn--primary"><?php esc_html_e( 'Send appointment request', 'jawaria-clinic' ); ?></button>
				<p class="jr-form__hint"><?php esc_html_e( 'Your appointment is confirmed only after the clinic contacts you.', 'jawaria-clinic' ); ?></p>
			</div>
		</div>
	</form>
	<?php
	return ob_get_clean();
}

add_action( 'admin_post_jr_book', 'jr_handle_booking' );
add_action( 'admin_post_nopriv_jr_book', 'jr_handle_booking' );

/**
 * Validate and store a request, then redirect back.
 */
function jr_handle_booking() {
	$return = isset( $_POST['jr_return'] ) ? esc_url_raw( wp_unslash( $_POST['jr_return'] ) ) : home_url( '/book-appointment/' );
	$return = wp_validate_redirect( $return, home_url( '/book-appointment/' ) );

	if ( ! isset( $_POST['jr_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['jr_nonce'] ) ), 'jr_book' ) ) {
		wp_safe_redirect( $return );
		exit;
	}

	// Honeypot: bots fill hidden fields. Pretend success.
	if ( ! empty( $_POST['jr_website'] ) ) {
		wp_safe_redirect( add_query_arg( 'jr_booked', '1', $return ) );
		exit;
	}

	$old = array(
		'name'    => isset( $_POST['jr_name'] ) ? sanitize_text_field( wp_unslash( $_POST['jr_name'] ) ) : '',
		'phone'   => isset( $_POST['jr_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['jr_phone'] ) ) : '',
		'service' => isset( $_POST['jr_service'] ) ? sanitize_text_field( wp_unslash( $_POST['jr_service'] ) ) : '',
		'date'    => isset( $_POST['jr_date'] ) ? sanitize_text_field( wp_unslash( $_POST['jr_date'] ) ) : '',
		'time'    => isset( $_POST['jr_time'] ) ? sanitize_text_field( wp_unslash( $_POST['jr_time'] ) ) : '',
		'note'    => isset( $_POST['jr_note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['jr_note'] ) ) : '',
		'consent' => empty( $_POST['jr_consent'] ) ? '' : '1',
	);

	$errors = jr_validate_booking( $old );

	// Simple rate limit: 5 requests per hour per visitor.
	$ip_key = 'jr_rl_' . md5( isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'x' );
	$count  = (int) get_transient( $ip_key );
	if ( $count >= 5 ) {
		$errors[] = __( 'Too many requests from this device. Please call or WhatsApp the clinic instead.', 'jawaria-clinic' );
	}

	if ( $errors ) {
		$token = wp_generate_password( 12, false );
		set_transient( 'jr_form_' . strtolower( $token ), array( 'errors' => $errors, 'old' => $old ), 10 * MINUTE_IN_SECONDS );
		wp_safe_redirect( add_query_arg( 'jr_t', strtolower( $token ), $return ) . '#jr_name' );
		exit;
	}

	set_transient( $ip_key, $count + 1, HOUR_IN_SECONDS );

	$id = wp_insert_post(
		array(
			'post_type'   => 'jr_appointment',
			'post_status' => 'publish',
			'post_title'  => $old['name'] . ' — ' . $old['date'],
		),
		true
	);
	if ( is_wp_error( $id ) ) {
		wp_safe_redirect( $return );
		exit;
	}
	update_post_meta( $id, '_jr_phone', jr_normalize_phone( $old['phone'] ) );
	update_post_meta( $id, '_jr_name', $old['name'] );
	update_post_meta( $id, '_jr_service', $old['service'] );
	update_post_meta( $id, '_jr_pref_date', $old['date'] );
	update_post_meta( $id, '_jr_pref_time', $old['time'] );
	update_post_meta( $id, '_jr_note', $old['note'] );
	update_post_meta( $id, '_jr_status', 'new' );
	update_post_meta( $id, '_jr_source', 'website' );
	update_post_meta( $id, '_jr_consent_at', current_time( 'mysql' ) );

	jr_notify_staff( $id );

	/**
	 * Fires after a website appointment request is saved. Phase 3 (HMIS / WhatsApp) can hook here.
	 *
	 * @param int $id Appointment record ID.
	 */
	do_action( 'jr_appointment_created', $id );

	wp_safe_redirect( add_query_arg( 'jr_booked', '1', $return ) );
	exit;
}

/**
 * Validation rules.
 *
 * @param array $d Submitted data.
 * @return string[] Error messages.
 */
function jr_validate_booking( array $d ) {
	$e = array();
	if ( mb_strlen( $d['name'] ) < 2 || mb_strlen( $d['name'] ) > 80 ) {
		$e[] = __( 'Please enter the patient’s name.', 'jawaria-clinic' );
	}
	$digits = jr_digits( $d['phone'] );
	if ( strlen( $digits ) < 10 || strlen( $digits ) > 15 ) {
		$e[] = __( 'Please enter a valid mobile number, e.g. 0300 1234567.', 'jawaria-clinic' );
	}
	$date = DateTime::createFromFormat( '!Y-m-d', $d['date'], wp_timezone() );
	$now  = new DateTime( 'today', wp_timezone() );
	if ( ! $date || $date->format( 'Y-m-d' ) !== $d['date'] ) {
		$e[] = __( 'Please choose a preferred date.', 'jawaria-clinic' );
	} elseif ( $date < $now || $date > ( clone $now )->modify( '+90 days' ) ) {
		$e[] = __( 'Please choose a date within the next 90 days.', 'jawaria-clinic' );
	}
	if ( $d['service'] && ! in_array( $d['service'], jr_service_choices(), true ) ) {
		$e[] = __( 'Please choose a reason for visit from the list.', 'jawaria-clinic' );
	}
	$slots = jr_lines( jr_opt( 'time_slots' ) );
	if ( $d['time'] && $slots && ! in_array( $d['time'], $slots, true ) ) {
		$e[] = __( 'Please choose a preferred time from the list.', 'jawaria-clinic' );
	}
	if ( '1' !== $d['consent'] ) {
		$e[] = __( 'Please tick the box so the clinic can contact you.', 'jawaria-clinic' );
	}
	return $e;
}

/**
 * Normalize Pakistani mobile numbers to 92XXXXXXXXXX where possible.
 *
 * @param string $phone Raw.
 * @return string
 */
function jr_normalize_phone( $phone ) {
	$d = jr_digits( $phone );
	if ( 0 === strpos( $d, '0092' ) ) {
		$d = substr( $d, 2 );
	} elseif ( 0 === strpos( $d, '03' ) && 11 === strlen( $d ) ) {
		$d = '92' . substr( $d, 1 );
	}
	return $d;
}

/**
 * Email staff. Keeps the message minimal (no service/reason) because email is not a secure channel.
 *
 * @param int $id Record ID.
 */
function jr_notify_staff( $id ) {
	$to = jr_opt( 'notify_email' ) ? jr_opt( 'notify_email' ) : get_option( 'admin_email' );
	if ( ! $to ) {
		return;
	}
	$subject = sprintf( /* translators: %d: request number */ __( 'New appointment request #%d', 'jawaria-clinic' ), $id );
	$body    = sprintf(
		"A new appointment request was received on the website.\n\nName: %s\nPhone: %s\nPreferred date: %s (%s)\n\nOpen it in the dashboard to see the details and update the status:\n%s\n",
		get_post_meta( $id, '_jr_name', true ),
		get_post_meta( $id, '_jr_phone', true ),
		get_post_meta( $id, '_jr_pref_date', true ),
		get_post_meta( $id, '_jr_pref_time', true ),
		admin_url( 'post.php?post=' . $id . '&action=edit' )
	);
	wp_mail( $to, $subject, $body );
}

/* -------------------------------------------------------------------------
 * Dashboard: list columns, details box, status
 * ---------------------------------------------------------------------- */

add_filter(
	'manage_jr_appointment_posts_columns',
	function () {
		return array(
			'cb'           => '<input type="checkbox">',
			'title'        => __( 'Patient', 'jawaria-clinic' ),
			'jr_phone'     => __( 'Phone', 'jawaria-clinic' ),
			'jr_service'   => __( 'Reason', 'jawaria-clinic' ),
			'jr_pref'      => __( 'Preferred', 'jawaria-clinic' ),
			'jr_status'    => __( 'Status', 'jawaria-clinic' ),
			'jr_confirmed' => __( 'Confirmed for', 'jawaria-clinic' ),
			'date'         => __( 'Received', 'jawaria-clinic' ),
		);
	}
);

add_action(
	'manage_jr_appointment_posts_custom_column',
	function ( $col, $id ) {
		switch ( $col ) {
			case 'jr_phone':
				$p = get_post_meta( $id, '_jr_phone', true );
				if ( $p ) {
					printf( '<a href="tel:+%1$s">+%1$s</a> &middot; <a href="https://wa.me/%1$s" target="_blank" rel="noopener">WhatsApp</a>', esc_attr( $p ) );
				}
				break;
			case 'jr_service':
				echo esc_html( get_post_meta( $id, '_jr_service', true ) );
				break;
			case 'jr_pref':
				echo esc_html( trim( get_post_meta( $id, '_jr_pref_date', true ) . ' · ' . get_post_meta( $id, '_jr_pref_time', true ), ' ·' ) );
				break;
			case 'jr_status':
				$s = get_post_meta( $id, '_jr_status', true );
				$l = jr_appt_statuses();
				echo esc_html( isset( $l[ $s ] ) ? $l[ $s ] : $s );
				break;
			case 'jr_confirmed':
				echo esc_html( get_post_meta( $id, '_jr_confirmed_at', true ) );
				break;
		}
	},
	10,
	2
);

// Filter list by status.
add_action(
	'restrict_manage_posts',
	function ( $type ) {
		if ( 'jr_appointment' !== $type ) {
			return;
		}
		$cur = isset( $_GET['jr_status'] ) ? sanitize_key( wp_unslash( $_GET['jr_status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		echo '<select name="jr_status"><option value="">' . esc_html__( 'All statuses', 'jawaria-clinic' ) . '</option>';
		foreach ( jr_appt_statuses() as $k => $label ) {
			printf( '<option value="%s" %s>%s</option>', esc_attr( $k ), selected( $cur, $k, false ), esc_html( $label ) );
		}
		echo '</select>';
	}
);
add_action(
	'pre_get_posts',
	function ( $q ) {
		if ( ! is_admin() || ! $q->is_main_query() || 'jr_appointment' !== $q->get( 'post_type' ) ) {
			return;
		}
		$cur = isset( $_GET['jr_status'] ) ? sanitize_key( wp_unslash( $_GET['jr_status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $cur ) {
			$q->set( 'meta_query', array( array( 'key' => '_jr_status', 'value' => $cur ) ) );
		}
	}
);

add_action(
	'add_meta_boxes_jr_appointment',
	function () {
		add_meta_box( 'jr_appt_details', __( 'Request details', 'jawaria-clinic' ), 'jr_render_appt_box', 'jr_appointment', 'normal', 'high' );
	}
);

/**
 * Details + status editor.
 *
 * @param WP_Post $post Record.
 */
function jr_render_appt_box( $post ) {
	wp_nonce_field( 'jr_appt_save', 'jr_appt_nonce' );
	$m = fn( $k ) => get_post_meta( $post->ID, $k, true );
	$fields = array(
		'_jr_name'      => array( __( 'Patient name', 'jawaria-clinic' ), 'text' ),
		'_jr_phone'     => array( __( 'Phone (92…)', 'jawaria-clinic' ), 'text' ),
		'_jr_service'   => array( __( 'Reason for visit', 'jawaria-clinic' ), 'text' ),
		'_jr_pref_date' => array( __( 'Preferred date', 'jawaria-clinic' ), 'date' ),
		'_jr_pref_time' => array( __( 'Preferred time', 'jawaria-clinic' ), 'text' ),
	);
	echo '<table class="form-table" role="presentation">';
	foreach ( $fields as $key => $f ) {
		printf(
			'<tr><th><label for="%1$s">%2$s</label></th><td><input type="%3$s" id="%1$s" name="%1$s" value="%4$s" class="regular-text"></td></tr>',
			esc_attr( $key ),
			esc_html( $f[0] ),
			esc_attr( $f[1] ),
			esc_attr( $m( $key ) )
		);
	}
	printf(
		'<tr><th><label for="_jr_note">%s</label></th><td><textarea id="_jr_note" name="_jr_note" rows="3" class="large-text">%s</textarea></td></tr>',
		esc_html__( 'Note', 'jawaria-clinic' ),
		esc_textarea( $m( '_jr_note' ) )
	);
	echo '<tr><th><label for="_jr_status">' . esc_html__( 'Status', 'jawaria-clinic' ) . '</label></th><td><select id="_jr_status" name="_jr_status">';
	foreach ( jr_appt_statuses() as $k => $label ) {
		printf( '<option value="%s" %s>%s</option>', esc_attr( $k ), selected( $m( '_jr_status' ), $k, false ), esc_html( $label ) );
	}
	echo '</select></td></tr>';
	printf(
		'<tr><th><label for="_jr_confirmed_at">%s</label></th><td><input type="datetime-local" id="_jr_confirmed_at" name="_jr_confirmed_at" value="%s"><p class="description">%s</p></td></tr>',
		esc_html__( 'Confirmed date & time', 'jawaria-clinic' ),
		esc_attr( str_replace( ' ', 'T', $m( '_jr_confirmed_at' ) ) ),
		esc_html__( 'Fill in after confirming with the patient.', 'jawaria-clinic' )
	);
	printf( '<tr><th>%s</th><td>%s</td></tr>', esc_html__( 'Source', 'jawaria-clinic' ), esc_html( $m( '_jr_source' ) ? $m( '_jr_source' ) : 'manual' ) );
	echo '</table>';
}

add_action(
	'save_post_jr_appointment',
	function ( $id ) {
		if ( ! isset( $_POST['jr_appt_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['jr_appt_nonce'] ) ), 'jr_appt_save' ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $id ) ) {
			return;
		}
		foreach ( array( '_jr_name', '_jr_service', '_jr_pref_date', '_jr_pref_time' ) as $k ) {
			if ( isset( $_POST[ $k ] ) ) {
				update_post_meta( $id, $k, sanitize_text_field( wp_unslash( $_POST[ $k ] ) ) );
			}
		}
		if ( isset( $_POST['_jr_phone'] ) ) {
			update_post_meta( $id, '_jr_phone', jr_normalize_phone( sanitize_text_field( wp_unslash( $_POST['_jr_phone'] ) ) ) );
		}
		if ( isset( $_POST['_jr_note'] ) ) {
			update_post_meta( $id, '_jr_note', sanitize_textarea_field( wp_unslash( $_POST['_jr_note'] ) ) );
		}
		if ( isset( $_POST['_jr_status'] ) && array_key_exists( sanitize_key( wp_unslash( $_POST['_jr_status'] ) ), jr_appt_statuses() ) ) {
			update_post_meta( $id, '_jr_status', sanitize_key( wp_unslash( $_POST['_jr_status'] ) ) );
		}
		if ( isset( $_POST['_jr_confirmed_at'] ) ) {
			$v = str_replace( 'T', ' ', sanitize_text_field( wp_unslash( $_POST['_jr_confirmed_at'] ) ) );
			update_post_meta( $id, '_jr_confirmed_at', preg_match( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $v ) ? $v : '' );
		}
		if ( ! get_post_meta( $id, '_jr_source', true ) ) {
			update_post_meta( $id, '_jr_source', 'manual' );
		}
		if ( ! get_post_meta( $id, '_jr_status', true ) ) {
			update_post_meta( $id, '_jr_status', 'new' );
		}
	}
);
