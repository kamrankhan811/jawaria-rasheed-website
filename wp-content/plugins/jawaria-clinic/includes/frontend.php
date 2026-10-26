<?php
/**
 * Front-end extras: floating WhatsApp button and Physician structured data.
 *
 * @package jawaria-clinic
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'wp_footer',
	function () {
		if ( '1' !== jr_opt( 'wa_float' ) || ! jr_whatsapp_url() ) {
			return;
		}
		printf(
			'<a class="jr-wa-float" href="%s" target="_blank" rel="noopener" aria-label="%s">%s<span>%s</span></a>',
			esc_url( jr_whatsapp_url() ),
			esc_attr__( 'Chat with the clinic on WhatsApp', 'jawaria-clinic' ),
			jr_wa_icon(), // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG.
			esc_html__( 'WhatsApp us', 'jawaria-clinic' )
		);
	}
);

/**
 * Physician schema on the home and About pages (only facts entered in Clinic Info).
 */
add_action(
	'wp_head',
	function () {
		if ( ! ( is_front_page() || is_page( 'about' ) ) ) {
			return;
		}
		$data = array(
			'@context'        => 'https://schema.org',
			'@type'           => 'Physician',
			'name'            => jr_opt( 'doctor_name' ),
			'description'     => jr_opt( 'title' ),
			'medicalSpecialty' => 'Gynecologic',
			'url'             => home_url( '/' ),
		);
		if ( jr_opt( 'phone' ) ) {
			$data['telephone'] = jr_opt( 'phone' );
		}
		if ( jr_opt( 'address' ) ) {
			$data['address'] = array(
				'@type'           => 'PostalAddress',
				'streetAddress'   => preg_replace( '/\s+/', ' ', jr_opt( 'address' ) ),
				'addressLocality' => jr_opt( 'city' ),
				'addressCountry'  => 'PK',
			);
		}
		if ( has_custom_logo() ) {
			$data['image'] = wp_get_attachment_image_url( get_theme_mod( 'custom_logo' ), 'full' );
		}
		echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
	}
);
