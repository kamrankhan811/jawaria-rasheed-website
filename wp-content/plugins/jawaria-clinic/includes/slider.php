<?php
/**
 * Home page banner slider — light and fast.
 *
 * - Slides are managed in WordPress: Dashboard → Home Slides (title, text, image, buttons, order).
 * - No jQuery or slider library: ~2 KB of plain JavaScript, CSS fade transition.
 * - First slide image loads immediately (good for Google speed score); the rest load lazily.
 * - Autoplay pauses on hover, focus and in background tabs; respects "reduce motion".
 *
 * Shortcode: [jr_slider]
 *
 * @package jawaria-clinic
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the Home Slides post type.
 */
function jr_register_slide_type() {
	register_post_type(
		'jr_slide',
		array(
			'labels'          => array(
				'name'          => __( 'Home Slides', 'jawaria-clinic' ),
				'singular_name' => __( 'Home Slide', 'jawaria-clinic' ),
				'add_new_item'  => __( 'Add slide', 'jawaria-clinic' ),
				'edit_item'     => __( 'Edit slide', 'jawaria-clinic' ),
				'featured_image'     => __( 'Slide image (1920 × 800 px recommended)', 'jawaria-clinic' ),
				'set_featured_image' => __( 'Set slide image', 'jawaria-clinic' ),
			),
			'public'          => false,
			'show_ui'         => true,
			'menu_position'   => 5,
			'menu_icon'       => 'dashicons-images-alt2',
			'supports'        => array( 'title', 'excerpt', 'thumbnail', 'page-attributes' ),
			'capability_type' => 'page',
			'map_meta_cap'    => true,
			'rewrite'         => false,
		)
	);
	add_theme_support( 'post-thumbnails', array( 'jr_slide', 'post', 'page' ) );
}
add_action( 'init', 'jr_register_slide_type' );

add_filter(
	'enter_title_here',
	function ( $text, $post ) {
		return 'jr_slide' === $post->post_type ? __( 'Slide heading (big text)', 'jawaria-clinic' ) : $text;
	},
	10,
	2
);

/**
 * Slide fields: label, buttons.
 *
 * @return array
 */
function jr_slide_fields() {
	return array(
		'_jr_eyebrow'  => __( 'Small label above heading', 'jawaria-clinic' ),
		'_jr_b1_label' => __( 'Button 1 text', 'jawaria-clinic' ),
		'_jr_b1_url'   => __( 'Button 1 link', 'jawaria-clinic' ),
		'_jr_b2_label' => __( 'Button 2 text (optional)', 'jawaria-clinic' ),
		'_jr_b2_url'   => __( 'Button 2 link (type "whatsapp" for the WhatsApp chat)', 'jawaria-clinic' ),
	);
}

add_action(
	'add_meta_boxes_jr_slide',
	function () {
		add_meta_box(
			'jr_slide_meta',
			__( 'Slide label & buttons', 'jawaria-clinic' ),
			function ( $post ) {
				wp_nonce_field( 'jr_slide_save', 'jr_slide_nonce' );
				echo '<p class="description">' . esc_html__( 'The "Excerpt" box below is the short text under the heading. Set the order of slides in "Page Attributes → Order" (0 = first).', 'jawaria-clinic' ) . '</p><table class="form-table" role="presentation">';
				foreach ( jr_slide_fields() as $key => $label ) {
					printf(
						'<tr><th><label for="%1$s">%2$s</label></th><td><input type="text" class="widefat" id="%1$s" name="%1$s" value="%3$s"></td></tr>',
						esc_attr( $key ),
						esc_html( $label ),
						esc_attr( get_post_meta( $post->ID, $key, true ) )
					);
				}
				echo '</table>';
			},
			'jr_slide',
			'normal',
			'high'
		);
	}
);

add_action(
	'save_post_jr_slide',
	function ( $id ) {
		if ( ! isset( $_POST['jr_slide_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['jr_slide_nonce'] ) ), 'jr_slide_save' ) || ! current_user_can( 'edit_post', $id ) ) {
			return;
		}
		foreach ( array_keys( jr_slide_fields() ) as $key ) {
			$val = isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : '';
			update_post_meta( $id, $key, $val );
		}
	}
);

/**
 * Resolve a button link: "whatsapp", absolute or site-relative URL.
 *
 * @param string $url Raw value.
 * @return string
 */
function jr_slide_link( $url ) {
	$url = trim( (string) $url );
	if ( 'whatsapp' === strtolower( $url ) ) {
		return jr_whatsapp_url();
	}
	if ( 'call' === strtolower( $url ) ) {
		return jr_whatsapp_url(); // No call buttons on this site: "call" opens WhatsApp.
	}
	if ( 0 === strpos( $url, '/' ) ) {
		return home_url( $url );
	}
	return $url;
}

add_shortcode( 'jr_slider', 'jr_render_slider' );

/**
 * Render the slider.
 *
 * @return string
 */
function jr_render_slider() {
	$slides = get_posts(
		array(
			'post_type'      => 'jr_slide',
			'post_status'    => 'publish',
			'posts_per_page' => 6,
			'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
		)
	);
	if ( ! $slides ) {
		return current_user_can( 'edit_pages' ) ? '<p class="jr-empty">' . esc_html__( 'Add slides under Home Slides in the dashboard.', 'jawaria-clinic' ) . '</p>' : '';
	}

	wp_enqueue_script( 'jr-slider', JR_CLINIC_URL . 'assets/js/slider.js', array(), JR_CLINIC_VERSION, true );

	$interval = max( 3, (int) jr_opt( 'slider_seconds' ) ) * 1000;
	$count    = count( $slides );
	$html     = '<section class="jr-slider" data-jr-slider data-interval="' . esc_attr( $interval ) . '" aria-roledescription="carousel" aria-label="' . esc_attr__( 'Highlights', 'jawaria-clinic' ) . '">';
	$html    .= '<div class="jr-slider__track">';

	foreach ( $slides as $i => $s ) {
		$img = '';
		if ( has_post_thumbnail( $s ) ) {
			$img = get_the_post_thumbnail(
				$s,
				'full',
				array(
					'class'         => 'jr-slide__img',
					'alt'           => '',
					'loading'       => 0 === $i ? 'eager' : 'lazy',
					'fetchpriority' => 0 === $i ? 'high' : 'low',
					'decoding'      => 'async',
					'sizes'         => '100vw',
				)
			);
		}
		$eyebrow = get_post_meta( $s->ID, '_jr_eyebrow', true );
		$b1l     = get_post_meta( $s->ID, '_jr_b1_label', true );
		$b1u     = jr_slide_link( get_post_meta( $s->ID, '_jr_b1_url', true ) );
		$b2l     = get_post_meta( $s->ID, '_jr_b2_label', true );
		$b2raw   = get_post_meta( $s->ID, '_jr_b2_url', true );
		$b2u     = jr_slide_link( $b2raw );
		$heading = 0 === $i ? 'h1' : 'h2';

		$html .= '<div class="jr-slide' . ( 0 === $i ? ' is-active' : '' ) . '" role="group" aria-roledescription="slide" aria-label="' . esc_attr( ( $i + 1 ) . ' / ' . $count ) . '"' . ( 0 === $i ? '' : ' aria-hidden="true"' ) . '>';
		$html .= $img;
		$html .= '<div class="jr-slide__inner"><div class="jr-slide__content">';
		if ( $eyebrow ) {
			$html .= '<p class="jr-slide__eyebrow">' . esc_html( $eyebrow ) . '</p>';
		}
		$html .= '<' . $heading . ' class="jr-slide__title">' . esc_html( get_the_title( $s ) ) . '</' . $heading . '>';
		if ( has_excerpt( $s ) ) {
			$html .= '<p class="jr-slide__text">' . esc_html( get_the_excerpt( $s ) ) . '</p>';
		}
		if ( ( $b1l && $b1u ) || ( $b2l && $b2u ) ) {
			$html .= '<div class="jr-btn-row">';
			if ( $b1l && $b1u ) {
				$html .= '<a class="jr-btn jr-btn--primary" href="' . esc_url( $b1u ) . '"' . ( 0 === $i ? '' : ' tabindex="-1"' ) . '>' . esc_html( $b1l ) . '</a>';
			}
			if ( $b2l && $b2u ) {
				$wa    = 'whatsapp' === strtolower( trim( $b2raw ) );
				$html .= '<a class="jr-btn ' . ( $wa ? 'jr-btn--whatsapp' : 'jr-btn--light' ) . '" href="' . esc_url( $b2u ) . '"' . ( $wa ? ' target="_blank" rel="noopener"' : '' ) . ( 0 === $i ? '' : ' tabindex="-1"' ) . '>' . esc_html( $b2l ) . '</a>';
			}
			$html .= '</div>';
		}
		$html .= '</div></div></div>';
	}
	$html .= '</div>';

	if ( $count > 1 ) {
		$html .= '<button class="jr-slider__arrow jr-slider__arrow--prev" type="button" data-jr-prev aria-label="' . esc_attr__( 'Previous slide', 'jawaria-clinic' ) . '"><span aria-hidden="true">&#8249;</span></button>';
		$html .= '<button class="jr-slider__arrow jr-slider__arrow--next" type="button" data-jr-next aria-label="' . esc_attr__( 'Next slide', 'jawaria-clinic' ) . '"><span aria-hidden="true">&#8250;</span></button>';
		$html .= '<div class="jr-slider__dots">';
		for ( $i = 0; $i < $count; $i++ ) {
			$html .= '<button type="button" class="jr-slider__dot' . ( 0 === $i ? ' is-active' : '' ) . '" data-jr-dot="' . esc_attr( $i ) . '" aria-label="' . esc_attr( sprintf( /* translators: %d: slide number */ __( 'Go to slide %d', 'jawaria-clinic' ), $i + 1 ) ) . '"' . ( 0 === $i ? ' aria-current="true"' : '' ) . '></button>';
		}
		$html .= '</div>';
	}
	return $html . '</section>';
}

/**
 * Preload the first slide image on the home page (faster first paint).
 */
add_action(
	'wp_head',
	function () {
		if ( ! is_front_page() ) {
			return;
		}
		$first = get_posts(
			array(
				'post_type'      => 'jr_slide',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
				'fields'         => 'ids',
			)
		);
		if ( $first && has_post_thumbnail( $first[0] ) ) {
			$src = wp_get_attachment_image_url( get_post_thumbnail_id( $first[0] ), 'full' );
			if ( $src ) {
				echo '<link rel="preload" as="image" href="' . esc_url( $src ) . '" fetchpriority="high">' . "\n";
			}
		}
	},
	3
);
