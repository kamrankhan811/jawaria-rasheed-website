<?php
/**
 * Homepage extras: highlight numbers, Google rating, patient reviews,
 * YouTube videos, social links and floating WhatsApp button.
 *
 * [jr_stats] [jr_rating] [jr_reviews count="3"] [jr_videos count="3"] [jr_social]
 *
 * @package jawaria-clinic
 */

defined( 'ABSPATH' ) || exit;

/* ---------------------------------------------------------------- stats */

add_shortcode(
	'jr_stats',
	function () {
		$rows = jr_pairs( jr_opt( 'stats' ) );
		if ( ! $rows ) {
			return '';
		}
		$icons = array( 'award', 'heart', 'flower', 'shield' ); // one per card, in order.
		$html  = '<div class="jr-stats">';
		foreach ( $rows as $i => $r ) {
			$html .= '<div class="jr-stat"><span class="jr-stat__icon">' . jr_icon( $icons[ $i % 4 ] ) . '</span><span class="jr-stat__num">' . esc_html( $r[0] ) . '</span><span class="jr-stat__label">' . esc_html( $r[1] ) . '</span></div>';
		}
		return $html . '</div>';
	}
);

/* ---------------------------------------------------------------- rating */

add_shortcode(
	'jr_rating',
	function () {
		$rating = jr_opt( 'google_rating' );
		if ( '' === $rating ) {
			return '';
		}
		$inner = '<span class="jr-rating__stars" aria-hidden="true">★★★★★</span> <strong>' . esc_html( $rating ) . '</strong> ' . esc_html__( 'rating on Google', 'jawaria-clinic' );
		if ( jr_opt( 'google_reviews' ) ) {
			return '<a class="jr-rating" href="' . esc_url( jr_opt( 'google_reviews' ) ) . '" target="_blank" rel="noopener">' . $inner . '</a>';
		}
		return '<span class="jr-rating">' . $inner . '</span>';
	}
);

/* ---------------------------------------------------------------- social */

/**
 * Social icon links from Clinic Info.
 *
 * @return string
 */
function jr_social_links_html() {
	$nets = array(
		'facebook'  => array( 'Facebook', 'fab fa-facebook-f' ),
		'instagram' => array( 'Instagram', 'fab fa-instagram' ),
		'youtube'   => array( 'YouTube', 'fab fa-youtube' ),
		'tiktok'    => array( 'TikTok', 'fab fa-tiktok' ),
	);
	$html = '';
	foreach ( $nets as $key => $n ) {
		if ( jr_opt( $key ) ) {
			$html .= '<a class="jr-social__link jr-social__link--' . esc_attr( $key ) . '" href="' . esc_url( jr_opt( $key ) ) . '" target="_blank" rel="noopener me" aria-label="' . esc_attr( $n[0] ) . '"><i class="' . esc_attr( $n[1] ) . '" aria-hidden="true"></i></a>';
		}
	}
	if ( ! $html && current_user_can( 'manage_options' ) ) {
		return '<p class="jr-empty">' . esc_html__( 'Add Facebook, Instagram, YouTube and TikTok links in Settings → Clinic Info to show the icons here.', 'jawaria-clinic' ) . '</p>';
	}
	return $html ? '<div class="jr-social">' . $html . '</div>' : '';
}

/**
 * Brand icons come from the Font Awesome library bundled with Elementor.
 */
add_action(
	'wp_enqueue_scripts',
	function () {
		if ( wp_style_is( 'elementor-icons-fa-brands', 'registered' ) ) {
			wp_enqueue_style( 'elementor-icons-fa-brands' );
		} elseif ( defined( 'ELEMENTOR_URL' ) ) {
			// Elementor 4.x no longer registers the handle up front; load its bundled files directly.
			$fa = ELEMENTOR_URL . 'assets/lib/font-awesome/css/';
			wp_enqueue_style( 'jr-fa-core', $fa . 'fontawesome.min.css', array(), '5.15.3' );
			wp_enqueue_style( 'jr-fa-brands', $fa . 'brands.min.css', array( 'jr-fa-core' ), '5.15.3' );
		}
	},
	30
);
add_shortcode( 'jr_social', 'jr_social_links_html' );

/* ---------------------------------------------------------------- reviews */

/**
 * Patient reviews: entered by staff, shown only when the patient gave permission.
 */
function jr_register_review_type() {
	register_post_type(
		'jr_review',
		array(
			'labels'        => array(
				'name'          => __( 'Testimonials', 'jawaria-clinic' ),
				'singular_name' => __( 'Testimonial', 'jawaria-clinic' ),
				'add_new_item'  => __( 'Add testimonial', 'jawaria-clinic' ),
				'edit_item'     => __( 'Edit testimonial', 'jawaria-clinic' ),
			),
			'public'        => false,
			'show_ui'       => true,
			'menu_position' => 4,
			'menu_icon'     => 'dashicons-format-quote',
			'supports'      => array( 'title', 'editor' ),
			'capability_type' => 'page',
			'map_meta_cap'  => true,
			'rewrite'       => false,
		)
	);
}
add_action( 'init', 'jr_register_review_type' );

add_filter(
	'enter_title_here',
	function ( $text, $post ) {
		return 'jr_review' === $post->post_type ? __( 'Name to display (e.g. "Ayesha K." or "Patient from Multan")', 'jawaria-clinic' ) : $text;
	},
	10,
	2
);

add_action(
	'add_meta_boxes_jr_review',
	function () {
		add_meta_box(
			'jr_review_meta',
			__( 'Review details', 'jawaria-clinic' ),
			function ( $post ) {
				wp_nonce_field( 'jr_review_save', 'jr_review_nonce' );
				$perm   = get_post_meta( $post->ID, '_jr_permission', true );
				$source = get_post_meta( $post->ID, '_jr_source', true );
				$head   = get_post_meta( $post->ID, '_jr_headline', true );
				?>
				<p><label><strong><?php esc_html_e( 'Short headline', 'jawaria-clinic' ); ?></strong><br>
					<input type="text" name="_jr_headline" value="<?php echo esc_attr( $head ); ?>" class="widefat" placeholder="<?php esc_attr_e( 'e.g. Very kind and patient doctor', 'jawaria-clinic' ); ?>"></label></p>
				<p><label><strong><?php esc_html_e( 'Source', 'jawaria-clinic' ); ?></strong><br>
					<input type="text" name="_jr_source" value="<?php echo esc_attr( $source ); ?>" class="widefat" placeholder="<?php esc_attr_e( 'Google / Facebook / Written feedback', 'jawaria-clinic' ); ?>"></label></p>
				<p><label><input type="checkbox" name="_jr_permission" value="1" <?php checked( $perm, '1' ); ?>>
					<?php esc_html_e( 'The patient gave permission to publish this review. (Reviews without this tick are never shown.)', 'jawaria-clinic' ); ?></label></p>
				<?php
			},
			'jr_review',
			'side'
		);
	}
);

add_action(
	'save_post_jr_review',
	function ( $id ) {
		if ( ! isset( $_POST['jr_review_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['jr_review_nonce'] ) ), 'jr_review_save' ) || ! current_user_can( 'edit_post', $id ) ) {
			return;
		}
		update_post_meta( $id, '_jr_headline', isset( $_POST['_jr_headline'] ) ? sanitize_text_field( wp_unslash( $_POST['_jr_headline'] ) ) : '' );
		update_post_meta( $id, '_jr_source', isset( $_POST['_jr_source'] ) ? sanitize_text_field( wp_unslash( $_POST['_jr_source'] ) ) : '' );
		update_post_meta( $id, '_jr_permission', empty( $_POST['_jr_permission'] ) ? '' : '1' );
	}
);

add_shortcode(
	'jr_reviews',
	function ( $atts ) {
		$a     = shortcode_atts( array( 'count' => '3' ), $atts );
		$posts = get_posts(
			array(
				'post_type'      => 'jr_review',
				'post_status'    => 'publish',
				'posts_per_page' => (int) $a['count'] > 0 ? (int) $a['count'] : -1,
				'meta_key'       => '_jr_permission', // phpcs:ignore WordPress.DB.SlowDBQuery
				'meta_value'     => '1', // phpcs:ignore WordPress.DB.SlowDBQuery
			)
		);
		if ( ! $posts ) {
			if ( jr_opt( 'google_reviews' ) ) {
				return '<p class="jr-center"><a class="jr-btn jr-btn--ghost" href="' . esc_url( jr_opt( 'google_reviews' ) ) . '" target="_blank" rel="noopener">' . esc_html__( 'Read patient reviews on Google', 'jawaria-clinic' ) . '</a></p>';
			}
			// Visitors: an empty marker lets CSS hide the whole home section until real testimonials exist.
			return current_user_can( 'edit_pages' ) ? '<p class="jr-empty jr-center">' . esc_html__( 'Add testimonials under Testimonials (only with the patient’s permission) and they will appear here.', 'jawaria-clinic' ) . '</p>' : '<span class="jr-reviews-empty" hidden></span><style>.jr-section:has(.jr-reviews-empty){display:none}</style>'; // style here too so it works without a theme update
		}
		$html = '<div class="jr-reviews">';
		foreach ( $posts as $p ) {
			$head   = get_post_meta( $p->ID, '_jr_headline', true );
			$source = get_post_meta( $p->ID, '_jr_source', true );
			$html  .= '<figure class="jr-review"><span class="jr-review__stars" aria-hidden="true">★★★★★</span>';
			if ( $head ) {
				$html .= '<p class="jr-review__head">' . esc_html( $head ) . '</p>';
			}
			$html .= '<blockquote class="jr-review__text">' . wp_kses_post( wpautop( $p->post_content ) ) . '</blockquote>';
			$html .= '<figcaption class="jr-review__name">' . esc_html( get_the_title( $p ) ) . ( $source ? ' <span>· ' . esc_html( $source ) . '</span>' : '' ) . '</figcaption></figure>';
		}
		return $html . '</div>';
	}
);

/* ---------------------------------------------------------------- videos */

/**
 * YouTube video ID from a URL.
 *
 * @param string $url URL.
 * @return string
 */
function jr_youtube_id( $url ) {
	if ( preg_match( '~(?:youtu\.be/|v=|/embed/|/shorts/)([A-Za-z0-9_-]{11})~', $url, $m ) ) {
		return $m[1];
	}
	return '';
}

add_shortcode(
	'jr_videos',
	function ( $atts ) {
		$a      = shortcode_atts( array( 'count' => '3' ), $atts );
		$videos = array();
		foreach ( jr_pairs( jr_opt( 'videos' ) ) as $row ) {
			$id = jr_youtube_id( $row[0] );
			if ( $id ) {
				$videos[] = array( $id, $row[1] );
			}
		}
		if ( (int) $a['count'] > 0 ) {
			$videos = array_slice( $videos, 0, (int) $a['count'] );
		}
		$html = '';
		if ( $videos ) {
			$html .= '<div class="jr-videos">';
			foreach ( $videos as $v ) {
				$html .= sprintf(
					'<a class="jr-video" href="%1$s" target="_blank" rel="noopener"><span class="jr-video__thumb"><img src="%2$s" alt="" loading="lazy"><span class="jr-video__play" aria-hidden="true">▶</span></span><span class="jr-video__title">%3$s</span></a>',
					esc_url( 'https://www.youtube.com/watch?v=' . $v[0] ),
					esc_url( 'https://i.ytimg.com/vi/' . $v[0] . '/hqdefault.jpg' ),
					esc_html( $v[1] ? $v[1] : __( 'Watch on YouTube', 'jawaria-clinic' ) )
				);
			}
			$html .= '</div>';
		} elseif ( current_user_can( 'edit_pages' ) ) {
			$html .= '<p class="jr-empty jr-center">' . esc_html__( 'Add YouTube links under Settings → Clinic Info → YouTube videos.', 'jawaria-clinic' ) . '</p>';
		}
		if ( jr_opt( 'youtube' ) ) {
			$html .= '<p class="jr-center"><a class="jr-btn jr-btn--ghost" href="' . esc_url( jr_opt( 'youtube' ) ) . '" target="_blank" rel="noopener">' . esc_html__( 'Visit our YouTube channel', 'jawaria-clinic' ) . '</a></p>';
		}
		return $html;
	}
);

/* ---------------------------------------------------------------- floating buttons */

add_action(
	'wp_footer',
	function () {
		$html = '';
		if ( '1' === jr_opt( 'wa_float' ) && jr_whatsapp_url() ) {
			$html .= '<a class="jr-float jr-float--wa" href="' . esc_url( jr_whatsapp_url() ) . '" target="_blank" rel="noopener" aria-label="' . esc_attr__( 'Chat with the clinic on WhatsApp', 'jawaria-clinic' ) . '">' . jr_wa_icon() . '</a>';
		}
		if ( $html ) {
			echo '<div class="jr-floats">' . $html . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput -- built from escaped parts.
		}
	}
);

/**
 * Show the doctor's name as the post author (the login name "drjawaria_admin" is not for visitors).
 */
add_filter( 'the_author', 'jr_author_display_name' );
add_filter( 'get_the_author_display_name', 'jr_author_display_name' );
function jr_author_display_name( $name ) {
	return is_admin() ? $name : jr_opt( 'doctor_name' );
}

/**
 * Single posts: show the featured image at the top of the article (Astra was not printing it).
 */
add_filter(
	'the_content',
	function ( $content ) {
		if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() || ! has_post_thumbnail() ) {
			return $content;
		}
		return '<figure class="jr-post-hero">' . get_the_post_thumbnail( null, 'large', array( 'alt' => get_post_meta( get_post_thumbnail_id(), '_wp_attachment_image_alt', true ) ) ) . '</figure>' . $content;
	},
	5
);

/**
 * Default social-share image (WhatsApp/Facebook link preview) when a page has no featured image.
 */
add_filter( 'rank_math/opengraph/facebook/image', 'jr_og_default_image' );
add_filter( 'rank_math/opengraph/twitter/image', 'jr_og_default_image' );
function jr_og_default_image( $img ) {
	return $img ? $img : JR_CLINIC_URL . 'assets/images/og-card.jpg';
}
add_filter( 'rank_math/opengraph/facebook/image_dimensions', function () { return array( 1200, 630 ); } );
