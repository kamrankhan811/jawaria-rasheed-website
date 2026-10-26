<?php
/**
 * Jawaria Rasheed Clinic — Astra child theme.
 *
 * @package jawaria-child
 */

defined( 'ABSPATH' ) || exit;

define( 'JR_CHILD_VERSION', '1.0.0' );

/**
 * Load fonts, parent style and the site design system.
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
	},
	20
);

/**
 * Make the same design visible inside the Elementor editor preview.
 */
add_action(
	'elementor/preview/enqueue_styles',
	function () {
		wp_enqueue_style( 'jr-site-editor', get_stylesheet_directory_uri() . '/assets/css/site.css', array(), JR_CHILD_VERSION );
	}
);

/**
 * Theme supports.
 */
add_action(
	'after_setup_theme',
	function () {
		add_theme_support( 'custom-logo', array( 'height' => 80, 'width' => 260, 'flex-width' => true, 'flex-height' => true ) );
		add_post_type_support( 'page', 'excerpt' );
		register_nav_menus( array( 'jr_footer' => __( 'Footer quick links', 'jawaria-child' ) ) );
	}
);

/**
 * Footer: clinic details, quick links, disclaimer. Replaces Astra's default footer bar.
 */
add_action(
	'astra_footer',
	function () {
		get_template_part( 'template-parts/site-footer' );
	},
	5
);
add_filter( 'astra_footer_copyright', '__return_false' );
add_action(
	'wp',
	function () {
		if ( class_exists( 'Astra_Builder_Footer' ) ) {
			remove_action( 'astra_footer', array( Astra_Builder_Footer::get_instance(), 'footer_markup' ), 10 );
		}
	},
	20
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
