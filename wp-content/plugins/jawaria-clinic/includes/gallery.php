<?php
/**
 * Photo Gallery: add/remove photos in Dashboard → Photo Gallery (title + featured image).
 * Shown with [jr_gallery count="0"] (0 = all), newest order first by "Order" then date.
 *
 * @package jawaria-clinic
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the gallery photo type.
 */
function jr_register_photo_type() {
	register_post_type(
		'jr_photo',
		array(
			'labels'          => array(
				'name'               => __( 'Photo Gallery', 'jawaria-clinic' ),
				'singular_name'      => __( 'Photo', 'jawaria-clinic' ),
				'add_new'            => __( 'Add photo', 'jawaria-clinic' ),
				'add_new_item'       => __( 'Add photo', 'jawaria-clinic' ),
				'edit_item'          => __( 'Edit photo', 'jawaria-clinic' ),
				'featured_image'     => __( 'Photo', 'jawaria-clinic' ),
				'set_featured_image' => __( 'Choose photo', 'jawaria-clinic' ),
			),
			'public'          => false,
			'show_ui'         => true,
			'menu_position'   => 5,
			'menu_icon'       => 'dashicons-format-gallery',
			'supports'        => array( 'title', 'thumbnail', 'page-attributes' ),
			'capability_type' => 'page',
			'map_meta_cap'    => true,
			'rewrite'         => false,
		)
	);
}
add_action( 'init', 'jr_register_photo_type' );

add_filter(
	'enter_title_here',
	function ( $text, $post ) {
		return 'jr_photo' === $post->post_type ? __( 'Caption, e.g. "Consultation room" (also used as the image description)', 'jawaria-clinic' ) : $text;
	},
	10,
	2
);

add_shortcode(
	'jr_gallery',
	function ( $atts ) {
		$a      = shortcode_atts( array( 'count' => '0' ), $atts );
		$photos = get_posts(
			array(
				'post_type'      => 'jr_photo',
				'post_status'    => 'publish',
				'posts_per_page' => (int) $a['count'] > 0 ? (int) $a['count'] : -1,
				'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
				'meta_key'       => '_thumbnail_id', // phpcs:ignore WordPress.DB.SlowDBQuery -- small list; skips photos with no image.
			)
		);
		if ( ! $photos ) {
			return current_user_can( 'edit_pages' ) ? '<p class="jr-empty jr-center">' . esc_html__( 'Add photos under Photo Gallery and they will appear here.', 'jawaria-clinic' ) . '</p>' : '';
		}
		wp_enqueue_script( 'jr-gallery', JR_CLINIC_URL . 'assets/js/gallery.js', array(), JR_CLINIC_VERSION, array( 'strategy' => 'defer', 'in_footer' => true ) );
		$html = '<div class="jr-gallery" data-jr-gallery>';
		foreach ( $photos as $p ) {
			$id      = (int) get_post_thumbnail_id( $p );
			$caption = get_the_title( $p );
			$html   .= sprintf(
				'<figure class="jr-gallery__item"><a href="%1$s" data-jr-gallery-link>%2$s</a>%3$s</figure>',
				esc_url( wp_get_attachment_image_url( $id, 'large' ) ),
				wp_get_attachment_image( $id, 'medium_large', false, array( 'loading' => 'lazy', 'alt' => $caption ) ),
				$caption ? '<figcaption>' . esc_html( $caption ) . '</figcaption>' : ''
			);
		}
		return $html . '</div>';
	}
);
