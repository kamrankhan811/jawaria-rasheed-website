<?php
/**
 * Shortcodes used inside Elementor pages (Shortcode or Text Editor widgets).
 *
 * [jr_doctor_name] [jr_qualifications] [jr_experience] [jr_phone] (WhatsApp number)
 * [jr_cta_buttons book="1" whatsapp="1" style="default|light" align="left|center"]
 * [jr_clinic_info] [jr_hours] [jr_map] [jr_stats]
 * [jr_services_grid limit="0"] [jr_latest_posts count="3"]
 * [jr_appointment_form]  (see appointments.php)
 *
 * @package jawaria-clinic
 */

defined( 'ABSPATH' ) || exit;

add_shortcode( 'jr_doctor_name', fn() => esc_html( jr_opt( 'doctor_name' ) ) );
add_shortcode( 'jr_experience', fn() => esc_html( jr_opt( 'experience_years' ) ) );
add_shortcode(
	'jr_qualifications',
	function () {
		$q = jr_opt( 'qualifications' );
		return $q ? esc_html( $q ) : '';
	}
);
add_shortcode(
	'jr_phone',
	function () {
		$p = jr_opt( 'whatsapp' );
		return $p ? '<a href="' . esc_url( jr_whatsapp_url() ) . '" target="_blank" rel="noopener">' . esc_html( $p ) . '</a>' : '';
	}
);

/**
 * WhatsApp icon (simple original glyph: chat bubble with handset).
 *
 * @return string
 */
function jr_wa_icon() {
	return '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm0 18.2c-1.5 0-3-.4-4.3-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8-.2-.1-.4-.1-.6.1l-.8 1c-.1.2-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.3-.4.2-.4.7-1.3.1-.2 0-.3 0-.4l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.2 5.2 0 0 0 1.1 2.7 11.8 11.8 0 0 0 4.5 4c1.7.7 2.3.8 3.2.6.5-.1 1.5-.6 1.7-1.2.2-.6.2-1.1.2-1.2-.1-.1-.3-.2-.5-.3z"/></svg>';
}

/**
 * Small inline SVG icon (decorative; text beside it carries the meaning).
 *
 * @param string $name pin|mail|clock|user|calendar.
 * @return string
 */
function jr_icon( $name ) {
	$paths = array(
		'pin'      => 'M12 2a7 7 0 0 0-7 7c0 5.2 7 13 7 13s7-7.8 7-13a7 7 0 0 0-7-7zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5z',
		'mail'     => 'M3 5h18a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1zm1 2.3V17h16V7.3l-8 5.2-8-5.2zM5.2 7l6.8 4.4L18.8 7H5.2z',
		'clock'    => 'M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zm0 18a8 8 0 1 1 0-16 8 8 0 0 1 0 16zm1-13h-2v6l5 3 1-1.7-4-2.4V7z',
		'user'     => 'M12 12a5 5 0 1 0 0-10 5 5 0 0 0 0 10zm0 2c-4.4 0-8 2.2-8 5v3h16v-3c0-2.8-3.6-5-8-5z',
		'calendar' => 'M7 2h2v2h6V2h2v2h3a1 1 0 0 1 1 1v15a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1h3V2zm12 8H5v9h14v-9zM7 12h2v2H7v-2zm4 0h2v2h-2v-2zm4 0h2v2h-2v-2zm-8 4h2v2H7v-2zm4 0h2v2h-2v-2z',
	);
	return isset( $paths[ $name ] ) ? '<svg class="jr-ico" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="' . $paths[ $name ] . '"/></svg>' : '';
}

add_shortcode(
	'jr_cta_buttons',
	function ( $atts ) {
		$a     = shortcode_atts(
			array(
				'book'     => '1',
				'whatsapp' => '1',
				'style'    => 'default',
				'align'    => 'left',
			),
			$atts
		);
		$light = 'light' === $a['style'];
		$html  = '<div class="jr-btn-row' . ( 'center' === $a['align'] ? ' jr-center' : '' ) . '">';
		if ( '1' === $a['book'] ) {
			$html .= '<a class="jr-btn ' . ( $light ? 'jr-btn--light' : 'jr-btn--primary' ) . '" href="' . esc_url( home_url( '/book-appointment/' ) ) . '">' . esc_html__( 'Book an Appointment', 'jawaria-clinic' ) . '</a>';
		}
		if ( '1' === $a['whatsapp'] && jr_whatsapp_url() ) {
			$html .= '<a class="jr-btn jr-btn--whatsapp" href="' . esc_url( jr_whatsapp_url() ) . '" target="_blank" rel="noopener">' . jr_wa_icon() . esc_html__( 'WhatsApp', 'jawaria-clinic' ) . '</a>';
		}
		return $html . '</div>';
	}
);

/**
 * Address / WhatsApp / email / timings list. Also used in the footer.
 *
 * @param bool $icons Footer style: an icon per row instead of a text label.
 * @return string
 */
function jr_clinic_info_html( $icons = false ) {
	if ( $icons ) {
		$rows = array();
		if ( jr_opt( 'address' ) ) {
			$rows[] = array( 'pin', esc_html( preg_replace( '/\s+/', ' ', jr_opt( 'address' ) ) ) );
		}
		if ( jr_opt( 'whatsapp' ) ) {
			$rows[] = array( 'wa', '<a href="' . esc_url( jr_whatsapp_url() ) . '" target="_blank" rel="noopener">' . esc_html( jr_opt( 'whatsapp' ) ) . '</a>' );
		}
		if ( jr_opt( 'email' ) ) {
			$rows[] = array( 'mail', '<a href="mailto:' . esc_attr( antispambot( jr_opt( 'email' ) ) ) . '">' . esc_html( antispambot( jr_opt( 'email' ) ) ) . '</a>' );
		}
		if ( jr_opt( 'hours_short' ) ) {
			$rows[] = array( 'clock', esc_html( jr_opt( 'hours_short' ) ) );
		}
		$html = '<ul class="jr-info jr-info--icons">';
		foreach ( $rows as $r ) {
			$html .= '<li><span class="jr-info__ico">' . ( 'wa' === $r[0] ? jr_wa_icon() : jr_icon( $r[0] ) ) . '</span><span>' . $r[1] . '</span></li>';
		}
		return $html . '</ul>';
	}
	$rows = array();
	if ( jr_opt( 'address' ) ) {
		$addr = nl2br( esc_html( jr_opt( 'address' ) ) );
		if ( jr_opt( 'map_link' ) ) {
			$addr .= '<br><a href="' . esc_url( jr_opt( 'map_link' ) ) . '" target="_blank" rel="noopener">' . esc_html__( 'Get directions', 'jawaria-clinic' ) . '</a>';
		}
		$rows[] = array( __( 'Address', 'jawaria-clinic' ), $addr );
	}
	if ( jr_opt( 'whatsapp' ) ) {
		$rows[] = array( __( 'WhatsApp', 'jawaria-clinic' ), '<a class="jr-info__wa" href="' . esc_url( jr_whatsapp_url() ) . '" target="_blank" rel="noopener">' . jr_wa_icon() . esc_html( jr_opt( 'whatsapp' ) ) . '</a>' );
	}
	if ( jr_opt( 'email' ) ) {
		$rows[] = array( __( 'Email', 'jawaria-clinic' ), '<a href="mailto:' . esc_attr( antispambot( jr_opt( 'email' ) ) ) . '">' . esc_html( antispambot( jr_opt( 'email' ) ) ) . '</a>' );
	}
	if ( jr_opt( 'hours_short' ) ) {
		$rows[] = array( __( 'Timings', 'jawaria-clinic' ), esc_html( jr_opt( 'hours_short' ) ) );
	}
	if ( ! $rows ) {
		return current_user_can( 'manage_options' ) ? '<p class="jr-empty">' . esc_html__( 'Add clinic details under Settings → Clinic Info.', 'jawaria-clinic' ) . '</p>' : '';
	}
	$html = '<ul class="jr-info">';
	foreach ( $rows as $r ) {
		$html .= '<li><strong>' . esc_html( $r[0] ) . '</strong>' . $r[1] . '</li>';
	}
	return $html . '</ul>';
}
add_shortcode( 'jr_clinic_info', fn() => jr_clinic_info_html() );

add_shortcode(
	'jr_hours',
	function () {
		$rows = jr_pairs( jr_opt( 'hours' ) );
		if ( ! $rows ) {
			return '';
		}
		$html = '<table class="jr-hours"><tbody>';
		foreach ( $rows as $r ) {
			$html .= '<tr><td>' . esc_html( $r[0] ) . '</td><td>' . esc_html( $r[1] ) . '</td></tr>';
		}
		return $html . '</tbody></table>';
	}
);

/**
 * Google Map of the clinic (Clinic Info embed link, or the address).
 *
 * @param string $class Extra CSS class.
 * @return string
 */
function jr_map_html( $class = '' ) {
	$src = jr_map_src();
	if ( ! $src ) {
		return '';
	}
	return '<div class="jr-map ' . esc_attr( $class ) . '"><iframe src="' . esc_url( $src ) . '" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="' . esc_attr__( 'Clinic location map', 'jawaria-clinic' ) . '"></iframe></div>';
}

add_shortcode(
	'jr_map',
	function () {
		return jr_map_html();
	}
);

add_shortcode(
	'jr_services_grid',
	function ( $atts ) {
		$a     = shortcode_atts( array( 'limit' => '0' ), $atts );
		$pages = jr_service_pages( (int) $a['limit'] );
		if ( ! $pages ) {
			return current_user_can( 'edit_pages' ) ? '<p class="jr-empty">' . esc_html__( 'Service pages appear here automatically — add child pages under "Services".', 'jawaria-clinic' ) . '</p>' : '';
		}
		$html = '<div class="jr-services-grid">';
		foreach ( $pages as $p ) {
			$icon = get_post_meta( $p->ID, '_jr_icon', true );
			if ( ! $icon ) {
				$icon = mb_strtoupper( mb_substr( get_the_title( $p ), 0, 1 ) );
			}
			$html .= sprintf(
				'<a class="jr-service-card" href="%1$s"><span class="jr-service-card__icon" aria-hidden="true">%2$s</span><h3 class="jr-service-card__title">%3$s</h3><p class="jr-service-card__text">%4$s</p><span class="jr-service-card__more">%5$s &rarr;</span></a>',
				esc_url( get_permalink( $p ) ),
				esc_html( $icon ),
				esc_html( get_the_title( $p ) ),
				esc_html( wp_strip_all_tags( get_the_excerpt( $p ) ) ),
				esc_html__( 'Learn more', 'jawaria-clinic' )
			);
		}
		return $html . '</div>';
	}
);

add_shortcode(
	'jr_latest_posts',
	function ( $atts ) {
		$a     = shortcode_atts( array( 'count' => '3' ), $atts );
		$posts = get_posts(
			array(
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'posts_per_page' => max( 1, (int) $a['count'] ),
			)
		);
		if ( ! $posts ) {
			return '<p class="jr-empty">' . esc_html__( 'Health articles are coming soon.', 'jawaria-clinic' ) . '</p>';
		}
		$html = '<div class="jr-posts-grid">';
		foreach ( $posts as $p ) {
			$link  = esc_url( get_permalink( $p ) );
			$img   = has_post_thumbnail( $p )
				? get_the_post_thumbnail( $p, 'medium_large', array( 'loading' => 'lazy', 'alt' => '' ) )
				: '<img src="' . esc_url( JR_CLINIC_URL . 'assets/images/post-placeholder.jpg' ) . '" alt="" loading="lazy" width="800" height="450">';
			$html .= '<article class="jr-post-card"><a class="jr-post-card__img" href="' . $link . '" tabindex="-1" aria-hidden="true">' . $img . '</a>'
				. '<h3 class="jr-post-card__title"><a href="' . $link . '">' . esc_html( get_the_title( $p ) ) . '</a></h3>'
				. '<p class="jr-post-card__meta"><span>' . jr_icon( 'user' ) . esc_html( get_the_author_meta( 'display_name', $p->post_author ) ) . '</span>'
				. '<span>' . jr_icon( 'calendar' ) . esc_html( get_the_date( '', $p ) ) . '</span></p></article>';
		}
		return $html . '</div>';
	}
);
