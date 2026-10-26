<?php
/**
 * Shortcodes used inside Elementor pages (Shortcode or Text Editor widgets).
 *
 * [jr_doctor_name] [jr_qualifications] [jr_experience] [jr_phone]
 * [jr_cta_buttons book="1" call="1" whatsapp="1" style="default|light" align="left|center"]
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
		$p = jr_opt( 'phone' );
		return $p ? '<a href="' . esc_url( jr_tel_url() ) . '">' . esc_html( $p ) . '</a>' : '';
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

add_shortcode(
	'jr_cta_buttons',
	function ( $atts ) {
		$a     = shortcode_atts(
			array(
				'book'     => '1',
				'call'     => '1',
				'whatsapp' => '1',
				'style'    => 'default',
				'align'    => 'left',
			),
			$atts
		);
		$light = 'light' === $a['style'];
		$html  = '<div class="jr-btn-row' . ( 'center' === $a['align'] ? ' jr-center' : '' ) . '">';
		if ( '1' === $a['book'] ) {
			$html .= '<a class="jr-btn ' . ( $light ? 'jr-btn--light' : 'jr-btn--primary' ) . '" href="' . esc_url( home_url( '/book-appointment/' ) ) . '">' . esc_html__( 'Book an appointment', 'jawaria-clinic' ) . '</a>';
		}
		if ( '1' === $a['whatsapp'] && jr_whatsapp_url() ) {
			$html .= '<a class="jr-btn jr-btn--whatsapp" href="' . esc_url( jr_whatsapp_url() ) . '" target="_blank" rel="noopener">' . jr_wa_icon() . esc_html__( 'WhatsApp', 'jawaria-clinic' ) . '</a>';
		}
		if ( '1' === $a['call'] && jr_tel_url() ) {
			$html .= '<a class="jr-btn ' . ( $light ? 'jr-btn--light' : 'jr-btn--ghost' ) . '" href="' . esc_url( jr_tel_url() ) . '">' . esc_html__( 'Call', 'jawaria-clinic' ) . ' ' . esc_html( jr_opt( 'phone' ) ) . '</a>';
		}
		return $html . '</div>';
	}
);

/**
 * Address / phone / WhatsApp / email list. Also used in the footer.
 *
 * @return string
 */
function jr_clinic_info_html() {
	$rows = array();
	if ( jr_opt( 'address' ) ) {
		$addr = nl2br( esc_html( jr_opt( 'address' ) ) );
		if ( jr_opt( 'map_link' ) ) {
			$addr .= '<br><a href="' . esc_url( jr_opt( 'map_link' ) ) . '" target="_blank" rel="noopener">' . esc_html__( 'Get directions', 'jawaria-clinic' ) . '</a>';
		}
		$rows[] = array( __( 'Address', 'jawaria-clinic' ), $addr );
	}
	if ( jr_opt( 'phone' ) ) {
		$rows[] = array( __( 'Phone', 'jawaria-clinic' ), '<a href="' . esc_url( jr_tel_url() ) . '">' . esc_html( jr_opt( 'phone' ) ) . '</a>' );
	}
	if ( jr_opt( 'whatsapp' ) ) {
		$rows[] = array( __( 'WhatsApp', 'jawaria-clinic' ), '<a href="' . esc_url( jr_whatsapp_url() ) . '" target="_blank" rel="noopener">' . esc_html( jr_opt( 'whatsapp' ) ) . '</a>' );
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
add_shortcode( 'jr_clinic_info', 'jr_clinic_info_html' );

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

add_shortcode(
	'jr_map',
	function () {
		$src = jr_opt( 'map_embed' );
		if ( ! $src ) {
			return '';
		}
		return '<div class="jr-map"><iframe src="' . esc_url( $src ) . '" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="' . esc_attr__( 'Clinic location map', 'jawaria-clinic' ) . '"></iframe></div>';
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
			$img   = get_the_post_thumbnail( $p, 'medium_large', array( 'loading' => 'lazy' ) );
			$html .= '<article class="jr-post-card">' . $img . '<div class="jr-post-card__body"><h3 class="jr-post-card__title"><a href="' . esc_url( get_permalink( $p ) ) . '">' . esc_html( get_the_title( $p ) ) . '</a></h3><p class="jr-post-card__meta">' . esc_html( get_the_date( '', $p ) ) . '</p><p>' . esc_html( wp_trim_words( get_the_excerpt( $p ), 22 ) ) . '</p></div></article>';
		}
		return $html . '</div>';
	}
);
