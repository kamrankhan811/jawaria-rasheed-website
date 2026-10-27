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
		'qualifications'   => "MBBS, FCPS (Obstetrics & Gynaecology)\nICMT, PGD, DipLM, & PGDip Sexual and Reproductive Medicine",
		'experience_years' => '15',
		'city'             => 'Bahawalpur',
		'address'          => 'House No. 1/1, Gulzar-e-Sadiq Road, Bahawalpur',
		'map_embed'        => '',
		'map_link'         => 'https://www.google.com/maps/search/?api=1&query=House+No.+1%2F1+Gulzar-e-Sadiq+Road+Bahawalpur',
		'whatsapp'         => '0300 0610903',
		'email'            => 'info@drjawariarasheed.com',
		'notify_email'     => '',
		'hours'            => "Monday – Friday | 3:00 pm – 7:00 pm\nSaturday – Sunday | Closed",
		'hours_short'      => 'Mon – Fri, 3:00 pm – 7:00 pm',
		'time_slots'       => "Any time (3:00 pm – 7:00 pm)\n3:00 pm – 4:00 pm\n4:00 pm – 5:00 pm\n5:00 pm – 6:00 pm\n6:00 pm – 7:00 pm",
		'wa_message'       => 'Assalam o Alaikum, I would like to book an appointment with Dr. Jawaria Rasheed.',
		'wa_float'         => '1',
		'slider_seconds'   => '6',
		'stats'            => "15+ | Years of experience\nFCPS | Qualified gynaecologist\nIUI Lab | Semen analysis on site\n3–7 pm | Monday to Friday",
		'google_rating'    => '',
		'google_reviews'   => '',
		'google_reviews_count' => '',
		'areas'            => "Bahawalpur\nMultan\nRahim Yar Khan\nBahawalnagar\nLodhran\nVehari\nAhmedpur East\nHasilpur\nKhanpur\nLiaquatpur",
		'facebook'         => '',
		'instagram'        => '',
		'youtube'          => 'https://www.youtube.com/@jawariainfertilityclinic3964',
		'tiktok'           => '',
		'videos'           => "https://www.youtube.com/watch?v=ymCmeTBZ0AI | Tubal Factor Infertility | Causes & Treatment of infertility\nhttps://www.youtube.com/watch?v=NdDlTrg_EA0 | What is Polycystic ovarian Syndrome? | Causes & Treatment\nhttps://www.youtube.com/watch?v=ymCmeTBZ0AI | Tubal Factor Infertility | Causes & Treatment of infertility",
		'recaptcha_site'   => '',
		'recaptcha_secret' => '',
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
 * Clinic number in international format (+923…), from the WhatsApp number.
 * The website has no call buttons; this is only used in search-engine data.
 *
 * @return string
 */
function jr_intl_number() {
	$d = jr_digits( jr_opt( 'whatsapp' ) );
	if ( ! $d ) {
		return '';
	}
	if ( 0 === strpos( $d, '0' ) ) {
		$d = '92' . substr( $d, 1 );
	}
	return '+' . $d;
}

/**
 * Google Maps embed URL: the one set in Clinic Info, otherwise a map of the address.
 *
 * @return string
 */
function jr_map_src() {
	$src = jr_opt( 'map_embed' );
	if ( $src ) {
		return $src;
	}
	$addr = trim( preg_replace( '/\s+/', ' ', jr_opt( 'address' ) ) );
	return $addr ? 'https://maps.google.com/maps?q=' . rawurlencode( $addr ) . '&z=16&output=embed' : '';
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
	$choices[] = __( 'Semen analysis (Andrology & IUI Lab)', 'jawaria-clinic' );
	$choices[] = __( 'Other / not sure', 'jawaria-clinic' );
	return array_values( array_unique( $choices ) );
}
