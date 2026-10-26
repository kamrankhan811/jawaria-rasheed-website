<?php
/**
 * Shared helpers: clinic options, contact URLs.
 *
 * @package jawaria-clinic
 */

defined( 'ABSPATH' ) || exit;

define( 'JR_OPTION', 'jr_clinic' );

/**
 * Default clinic settings. Empty values are filled in under Settings → Clinic Info.
 *
 * @return array
 */
function jr_default_options() {
	return array(
		'doctor_name'      => 'Dr. Jawaria Rasheed',
		'title'            => 'Consultant Gynecologist & IUI Specialist',
		'qualifications'   => '',
		'experience_years' => '15',
		'city'             => 'Bahawalpur',
		'address'          => '',
		'map_embed'        => '',
		'map_link'         => '',
		'phone'            => '',
		'whatsapp'         => '',
		'email'            => '',
		'notify_email'     => '',
		'hours'            => "Monday – Saturday | Add timings\nSunday | Closed",
		'hours_short'      => 'Mon – Sat, by appointment',
		'time_slots'       => "Any time\nMorning\nAfternoon\nEvening",
		'wa_message'       => 'Assalam o Alaikum, I would like to book an appointment with Dr. Jawaria Rasheed.',
		'wa_float'         => '1',
		'call_float'       => '1',
		'stats'            => "15+ | Years of experience\nIUI | Fertility treatment offered\nPCOS | Specialised care\n1:1 | Private consultations",
		'google_rating'    => '',
		'google_reviews'   => '',
		'google_reviews_count' => '',
		'areas'            => "Bahawalpur\nMultan\nRahim Yar Khan\nBahawalnagar\nLodhran\nVehari\nAhmedpur East\nHasilpur\nKhanpur\nLiaquatpur",
		'facebook'         => '',
		'instagram'        => '',
		'youtube'          => '',
		'tiktok'           => '',
		'videos'           => '',
		'video_consult_url' => '',
	);
}

/**
 * Get one clinic setting.
 *
 * @param string $key Setting key.
 * @return string
 */
function jr_opt( $key ) {
	$opts = wp_parse_args( (array) get_option( JR_OPTION, array() ), jr_default_options() );
	return isset( $opts[ $key ] ) ? (string) $opts[ $key ] : '';
}

/**
 * Digits-only phone, e.g. "923001234567".
 *
 * @param string $number Raw number.
 * @return string
 */
function jr_digits( $number ) {
	return preg_replace( '/\D+/', '', (string) $number );
}

/**
 * tel: link for the clinic phone.
 *
 * @return string
 */
function jr_tel_url() {
	$d = jr_digits( jr_opt( 'phone' ) );
	return $d ? 'tel:+' . ltrim( $d, '+' ) : '';
}

/**
 * wa.me link with the default greeting.
 *
 * @param string $message Optional message override.
 * @return string
 */
function jr_whatsapp_url( $message = '' ) {
	$d = jr_digits( jr_opt( 'whatsapp' ) );
	if ( ! $d ) {
		return '';
	}
	// Local Pakistani format 03xx… → international 923xx….
	if ( 0 === strpos( $d, '0' ) ) {
		$d = '92' . substr( $d, 1 );
	}
	$text = $message ? $message : jr_opt( 'wa_message' );
	return 'https://wa.me/' . $d . ( $text ? '?text=' . rawurlencode( $text ) : '' );
}

/**
 * Parse "Label | Value" lines into pairs.
 *
 * @param string $text Multi-line text.
 * @return array<int, array{0:string,1:string}>
 */
function jr_pairs( $text ) {
	$out = array();
	foreach ( preg_split( '/\r\n|\r|\n/', (string) $text ) as $line ) {
		$line = trim( $line );
		if ( '' === $line ) {
			continue;
		}
		$parts = array_map( 'trim', explode( '|', $line, 2 ) );
		$out[] = array( $parts[0], isset( $parts[1] ) ? $parts[1] : '' );
	}
	return $out;
}

/**
 * Non-empty lines of a textarea.
 *
 * @param string $text Multi-line text.
 * @return string[]
 */
function jr_lines( $text ) {
	return array_values( array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) $text ) ) ) );
}

/**
 * The parent "Services" page, if it exists.
 *
 * @return WP_Post|null
 */
function jr_services_parent() {
	$page = get_page_by_path( 'services' );
	return $page instanceof WP_Post ? $page : null;
}

/**
 * Published service pages (children of /services/), in menu order.
 *
 * @param int $limit 0 = all.
 * @return WP_Post[]
 */
function jr_service_pages( $limit = 0 ) {
	$parent = jr_services_parent();
	if ( ! $parent ) {
		return array();
	}
	return get_posts(
		array(
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'post_parent'    => $parent->ID,
			'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
			'posts_per_page' => $limit > 0 ? $limit : -1,
		)
	);
}

/**
 * Choices for the "service" field on the appointment form.
 *
 * @return string[]
 */
function jr_service_choices() {
	$choices = array( __( 'General gynecology consultation', 'jawaria-clinic' ) );
	foreach ( jr_service_pages() as $p ) {
		$choices[] = get_the_title( $p );
	}
	$choices[] = __( 'Other / not sure', 'jawaria-clinic' );
	return array_values( array_unique( $choices ) );
}
