<?php
/**
 * Front-end: Physician structured data.
 *
 * @package jawaria-clinic
 */

defined( 'ABSPATH' ) || exit;

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
