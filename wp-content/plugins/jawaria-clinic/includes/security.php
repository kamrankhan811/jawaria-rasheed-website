<?php
/**
 * Security hardening that is safe for every site. Works alongside Wordfence
 * (firewall, malware scan, login limits) — see docs/security.md.
 *
 * Disable everything here with: add_filter( 'jr_security_hardening', '__return_false' );
 *
 * @package jawaria-clinic
 */

defined( 'ABSPATH' ) || exit;

// Block editing theme/plugin code from the dashboard (a common attack path).
if ( ! defined( 'DISALLOW_FILE_EDIT' ) ) {
	define( 'DISALLOW_FILE_EDIT', true );
}

add_action(
	'plugins_loaded',
	function () {
		if ( ! apply_filters( 'jr_security_hardening', true ) ) {
			return;
		}

		// XML-RPC is not needed (no mobile app / Jetpack) and is used for brute-force attacks.
		add_filter( 'xmlrpc_enabled', '__return_false' );
		add_filter(
			'wp_headers',
			function ( $headers ) {
				unset( $headers['X-Pingback'] );
				return $headers;
			}
		);

		// Do not reveal the WordPress version or legacy endpoints.
		remove_action( 'wp_head', 'wp_generator' );
		remove_action( 'wp_head', 'rsd_link' );
		remove_action( 'wp_head', 'wlwmanifest_link' );
		add_filter( 'the_generator', '__return_empty_string' );

		// Security headers.
		add_action(
			'send_headers',
			function () {
				if ( headers_sent() ) {
					return;
				}
				header( 'X-Content-Type-Options: nosniff' );
				header( 'X-Frame-Options: SAMEORIGIN' );
				header( 'Referrer-Policy: strict-origin-when-cross-origin' );
				header( 'Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()' );
				if ( is_ssl() ) {
					header( 'Strict-Transport-Security: max-age=31536000' );
				}
			}
		);

		// Stop username discovery via /?author=1 and the public users API.
		add_action(
			'template_redirect',
			function () {
				if ( ! is_user_logged_in() && ( is_author() || isset( $_GET['author'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					wp_safe_redirect( home_url( '/' ), 301 );
					exit;
				}
			},
			1
		);
		add_filter(
			'rest_endpoints',
			function ( $endpoints ) {
				if ( ! is_user_logged_in() ) {
					unset( $endpoints['/wp/v2/users'], $endpoints['/wp/v2/users/(?P<id>[\d]+)'] );
				}
				return $endpoints;
			}
		);
		add_filter( 'oembed_response_data', function ( $data ) {
			unset( $data['author_name'], $data['author_url'] );
			return $data;
		} );

		// Same message for wrong username or wrong password.
		add_filter(
			'login_errors',
			function () {
				return __( 'Incorrect login details. Please try again.', 'jawaria-clinic' );
			}
		);

		// Appointment & review records never appear in search or sitemaps.
		add_filter(
			'wp_sitemaps_post_types',
			function ( $types ) {
				unset( $types['jr_appointment'], $types['jr_review'], $types['jr_photo'] );
				return $types;
			}
		);
	}
);
