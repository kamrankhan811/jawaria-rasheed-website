<?php
/**
 * Jawaria Rasheed Clinic — Astra child theme.
 *
 * Astra provides the base (page layouts, blog, accessibility). This child theme
 * replaces Astra's header and footer with the clinic's own two-row header,
 * mega menu and four-column footer.
 *
 * @package jawaria-child
 */

defined( 'ABSPATH' ) || exit;

define( 'JR_CHILD_VERSION', '1.1.0' );

/**
 * Fonts, parent style, design system, header script.
 */
add_action(
	'wp_enqueue_scripts',
	function () {
		wp_enqueue_style(
			'jr-fonts',
			'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap',
			array(),
			null
		);
		wp_enqueue_style( 'astra-parent', get_template_directory_uri() . '/style.css', array(), JR_CHILD_VERSION );
		wp_enqueue_style(
			'jr-site',
			get_stylesheet_directory_uri() . '/assets/css/site.css',
			array( 'astra-parent', 'jr-fonts' ),
			filemtime( get_stylesheet_directory() . '/assets/css/site.css' )
		);
		wp_enqueue_script(
			'jr-nav',
			get_stylesheet_directory_uri() . '/assets/js/nav.js',
			array(),
			filemtime( get_stylesheet_directory() . '/assets/js/nav.js' ),
			true
		);
	},
	20
);

/**
 * Same design inside the Elementor editor preview.
 */
add_action(
	'elementor/preview/enqueue_styles',
	function () {
		wp_enqueue_style( 'jr-site-editor', get_stylesheet_directory_uri() . '/assets/css/site.css', array(), JR_CHILD_VERSION );
	}
);

/**
 * Theme supports and menu locations.
 */
add_action(
	'after_setup_theme',
	function () {
		add_theme_support( 'custom-logo', array( 'height' => 80, 'width' => 260, 'flex-width' => true, 'flex-height' => true ) );
		add_post_type_support( 'page', 'excerpt' );
		register_nav_menus(
			array(
				'jr_top'             => __( 'Header – top row', 'jawaria-child' ),
				'jr_services'        => __( 'Header – services row', 'jawaria-child' ),
				'jr_footer_services' => __( 'Footer – Our Services', 'jawaria-child' ),
				'jr_footer'          => __( 'Footer – Quick Links', 'jawaria-child' ),
			)
		);
	},
	20
);

/**
 * Swap Astra's header and footer for ours.
 */
add_action(
	'wp',
	function () {
		remove_all_actions( 'astra_header' );
		remove_all_actions( 'astra_footer' );
		add_action(
			'astra_header',
			function () {
				get_template_part( 'template-parts/site-header' );
			}
		);
		add_action(
			'astra_footer',
			function () {
				get_template_part( 'template-parts/site-footer' );
			}
		);
	},
	99
);

/**
 * Top information bar above the header.
 */
add_action(
	'astra_header_before',
	function () {
		get_template_part( 'template-parts/top-bar' );
	}
);
