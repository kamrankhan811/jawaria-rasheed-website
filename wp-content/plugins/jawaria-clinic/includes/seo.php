<?php
/**
 * SEO / AEO / GEO helpers.
 *
 * - Structured data (JSON-LD): Physician + MedicalClinic with areas served,
 *   MedicalWebPage, BreadcrumbList and FAQPage built from each page's FAQ toggles.
 * - /llms.txt: a plain-text summary of the site for AI assistants and answer engines.
 * - robots.txt sitemap line; meta title/description fallback when Rank Math is not active.
 * - [jr_areas] shortcode listing the cities served.
 *
 * Rank Math remains the main SEO plugin (titles, sitemaps, Open Graph). In Rank Math,
 * keep "Local SEO" off so the clinic is not described twice.
 *
 * @package jawaria-clinic
 */

defined( 'ABSPATH' ) || exit;

/**
 * Cities / areas served, from Clinic Info.
 *
 * @return string[]
 */
function jr_areas() {
	return jr_lines( jr_opt( 'areas' ) );
}

add_shortcode(
	'jr_areas',
	function () {
		$areas = jr_areas();
		if ( ! $areas ) {
			return '';
		}
		$html = '<ul class="jr-areas">';
		foreach ( $areas as $a ) {
			$html .= '<li>' . esc_html( $a ) . '</li>';
		}
		return $html . '</ul>';
	}
);

/* ---------------------------------------------------------------- JSON-LD */

/**
 * Collect FAQ question/answer pairs from Elementor toggle/accordion widgets on a page.
 *
 * @param int $post_id Page ID.
 * @return array<int, array{0:string,1:string}>
 */
function jr_page_faqs( $post_id ) {
	$raw = get_post_meta( $post_id, '_elementor_data', true );
	if ( ! $raw ) {
		return array();
	}
	$data = is_array( $raw ) ? $raw : json_decode( $raw, true );
	$out  = array();
	$walk = function ( $els ) use ( &$walk, &$out ) {
		foreach ( (array) $els as $el ) {
			if ( isset( $el['widgetType'] ) && in_array( $el['widgetType'], array( 'toggle', 'accordion' ), true ) && ! empty( $el['settings']['tabs'] ) ) {
				foreach ( $el['settings']['tabs'] as $tab ) {
					if ( ! empty( $tab['tab_title'] ) && ! empty( $tab['tab_content'] ) ) {
						$out[] = array( wp_strip_all_tags( $tab['tab_title'] ), wp_strip_all_tags( $tab['tab_content'] ) );
					}
				}
			}
			if ( ! empty( $el['elements'] ) ) {
				$walk( $el['elements'] );
			}
		}
	};
	$walk( $data );
	return $out;
}

/**
 * Build the schema graph for the current request.
 *
 * @return array
 */
function jr_schema_graph() {
	$home   = home_url( '/' );
	$doc_id = $home . '#doctor';
	$cl_id  = $home . '#clinic';
	$same   = array_values( array_filter( array( jr_opt( 'facebook' ), jr_opt( 'instagram' ), jr_opt( 'youtube' ), jr_opt( 'tiktok' ), jr_opt( 'google_reviews' ) ) ) );
	$areas  = array();
	foreach ( jr_areas() as $a ) {
		$areas[] = array( '@type' => 'City', 'name' => $a );
	}
	$address = null;
	if ( jr_opt( 'address' ) ) {
		$address = array(
			'@type'           => 'PostalAddress',
			'streetAddress'   => preg_replace( '/\s+/', ' ', jr_opt( 'address' ) ),
			'addressLocality' => jr_opt( 'city' ),
			'addressRegion'   => 'Punjab',
			'addressCountry'  => 'PK',
		);
	}
	$logo = has_custom_logo() ? wp_get_attachment_image_url( get_theme_mod( 'custom_logo' ), 'full' ) : '';

	$doctor = array_filter(
		array(
			'@type'            => 'Physician',
			'@id'              => $doc_id,
			'name'             => jr_opt( 'doctor_name' ),
			'description'      => jr_opt( 'title' ) . ( jr_opt( 'experience_years' ) ? ' with over ' . jr_opt( 'experience_years' ) . ' years of experience in gynecology, women’s health and IUI.' : '' ),
			'medicalSpecialty' => array( 'Gynecologic', 'Obstetric' ),
			'url'              => home_url( '/about/' ),
			'telephone'        => jr_intl_number(),
			'address'          => $address,
			'areaServed'       => $areas,
			'image'            => $logo,
			'sameAs'           => $same,
			'knowsAbout'       => array( 'Gynecology', 'Infertility', 'Intrauterine insemination (IUI)', 'PCOS', 'Pregnancy care', 'Menopause', 'Endometriosis', 'Uterine fibroids' ),
			'hasCredential'    => jr_opt( 'qualifications' ) ? jr_opt( 'qualifications' ) : null,
			'worksFor'         => array( '@id' => $cl_id ),
		)
	);
	$clinic = array_filter(
		array(
			'@type'            => array( 'MedicalClinic', 'LocalBusiness' ),
			'@id'              => $cl_id,
			'name'             => jr_opt( 'doctor_name' ) . ' — Gynecology & IUI Clinic',
			'url'              => $home,
			'telephone'        => jr_intl_number(),
			'email'            => jr_opt( 'email' ),
			'address'          => $address,
			'hasMap'           => jr_opt( 'map_link' ),
			'areaServed'       => $areas,
			'medicalSpecialty' => array( 'Gynecologic', 'Obstetric' ),
			'image'            => $logo,
			'logo'             => $logo,
			'sameAs'           => $same,
			'employee'         => array( '@id' => $doc_id ),
		)
	);
	if ( jr_opt( 'google_rating' ) && jr_opt( 'google_reviews_count' ) ) {
		$clinic['aggregateRating'] = array(
			'@type'       => 'AggregateRating',
			'ratingValue' => jr_opt( 'google_rating' ),
			'reviewCount' => (int) jr_opt( 'google_reviews_count' ),
		);
	}

	$graph = array( $doctor, $clinic );

	if ( is_singular( 'page' ) && ! is_front_page() ) {
		$id     = get_queried_object_id();
		$url    = get_permalink( $id );
		$crumbs = array( array( 'Home', $home ) );
		foreach ( array_reverse( get_post_ancestors( $id ) ) as $anc ) {
			$crumbs[] = array( get_the_title( $anc ), get_permalink( $anc ) );
		}
		$crumbs[] = array( get_the_title( $id ), $url );
		$items    = array();
		foreach ( $crumbs as $i => $c ) {
			$items[] = array( '@type' => 'ListItem', 'position' => $i + 1, 'name' => $c[0], 'item' => $c[1] );
		}
		$graph[] = array( '@type' => 'BreadcrumbList', '@id' => $url . '#breadcrumb', 'itemListElement' => $items );

		$parent = wp_get_post_parent_id( $id );
		if ( $parent && 'services' === get_post_field( 'post_name', $parent ) ) {
			$graph[] = array(
				'@type'         => 'MedicalWebPage',
				'@id'           => $url . '#webpage',
				'url'           => $url,
				'name'          => get_the_title( $id ),
				'description'   => wp_strip_all_tags( get_the_excerpt( $id ) ),
				'about'         => array( '@type' => 'MedicalEntity', 'name' => get_the_title( $id ) ),
				'audience'      => array( '@type' => 'PeopleAudience', 'audienceType' => 'Patient' ),
				'author'        => array( '@id' => $doc_id ),
				'publisher'     => array( '@id' => $cl_id ),
				'dateModified'  => get_the_modified_date( 'c', $id ),
				'inLanguage'    => 'en',
				'breadcrumb'    => array( '@id' => $url . '#breadcrumb' ),
			);
		}

		$faqs = jr_page_faqs( $id );
		if ( $faqs ) {
			$qa = array();
			foreach ( $faqs as $f ) {
				$qa[] = array(
					'@type'          => 'Question',
					'name'           => $f[0],
					'acceptedAnswer' => array( '@type' => 'Answer', 'text' => $f[1] ),
				);
			}
			$graph[] = array( '@type' => 'FAQPage', '@id' => $url . '#faq', 'mainEntity' => $qa );
		}
	}
	return $graph;
}

add_action(
	'wp_head',
	function () {
		if ( is_admin() || is_404() || ! apply_filters( 'jr_output_schema', true ) ) {
			return;
		}
		$data = array(
			'@context' => 'https://schema.org',
			'@graph'   => jr_schema_graph(),
		);
		echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
	},
	20
);

/* ---------------------------------------------------------------- meta fallback (no Rank Math) */

/**
 * Whether Rank Math is active.
 *
 * @return bool
 */
function jr_has_rank_math() {
	return defined( 'RANK_MATH_VERSION' ) || class_exists( 'RankMath' );
}

add_filter(
	'pre_get_document_title',
	function ( $title ) {
		if ( jr_has_rank_math() || ! is_singular() ) {
			return $title;
		}
		$custom = get_post_meta( get_queried_object_id(), 'rank_math_title', true );
		return $custom ? wp_strip_all_tags( $custom ) : $title;
	}
);

add_action(
	'wp_head',
	function () {
		if ( jr_has_rank_math() ) {
			return;
		}
		$desc = '';
		if ( is_singular() ) {
			$id   = get_queried_object_id();
			$desc = get_post_meta( $id, 'rank_math_description', true );
			if ( ! $desc ) {
				$desc = get_the_excerpt( $id );
			}
		} elseif ( is_home() || is_front_page() ) {
			$desc = get_bloginfo( 'description' );
		}
		if ( $desc ) {
			echo '<meta name="description" content="' . esc_attr( wp_trim_words( wp_strip_all_tags( $desc ), 32, '' ) ) . '">' . "\n";
		}
	},
	1
);

/* ---------------------------------------------------------------- robots.txt */

add_filter(
	'robots_txt',
	function ( $output, $public ) {
		if ( ! $public ) {
			return $output;
		}
		if ( false === stripos( $output, 'sitemap' ) ) {
			$output .= "\nSitemap: " . home_url( '/sitemap_index.xml' ) . "\n";
		}
		return $output;
	},
	20,
	2
);

/* ---------------------------------------------------------------- llms.txt (GEO) */

add_action(
	'init',
	function () {
		add_rewrite_rule( '^llms\.txt$', 'index.php?jr_llms=1', 'top' );
	}
);
add_filter(
	'query_vars',
	function ( $vars ) {
		$vars[] = 'jr_llms';
		return $vars;
	}
);
add_action(
	'template_redirect',
	function () {
		if ( ! get_query_var( 'jr_llms' ) ) {
			return;
		}
		header( 'Content-Type: text/plain; charset=utf-8' );
		echo jr_llms_txt(); // phpcs:ignore WordPress.Security.EscapeOutput -- plain text.
		exit;
	}
);

/**
 * Markdown summary of the clinic for AI assistants (llms.txt convention).
 *
 * @return string
 */
function jr_llms_txt() {
	$clean = fn( $s ) => trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( (string) $s ) ) );
	$lines = array();
	$lines[] = '# ' . jr_opt( 'doctor_name' ) . ' — ' . jr_opt( 'title' );
	$lines[] = '';
	$lines[] = '> ' . jr_opt( 'doctor_name' ) . ' is a consultant gynecologist' . ( jr_opt( 'city' ) ? ' in ' . jr_opt( 'city' ) . ', Punjab, Pakistan' : '' ) . ( jr_opt( 'experience_years' ) ? ', with over ' . jr_opt( 'experience_years' ) . ' years of experience' : '' ) . ' in gynecology, women’s health, fertility evaluation and IUI (intrauterine insemination).';
	$lines[] = '';
	$lines[] = '## Clinic';
	foreach (
		array(
			'Qualifications' => jr_opt( 'qualifications' ),
			'Address'        => $clean( jr_opt( 'address' ) ),
			'WhatsApp'       => jr_opt( 'whatsapp' ),
			'Email'          => jr_opt( 'email' ),
			'Timings'        => jr_opt( 'hours_short' ),
			'Areas served'   => implode( ', ', jr_areas() ),
			'Book online'    => home_url( '/book-appointment/' ),
		) as $label => $val
	) {
		if ( $val ) {
			$lines[] = '- ' . $label . ': ' . $val;
		}
	}
	$lines[] = '';
	$lines[] = '## Services';
	foreach ( jr_service_pages() as $p ) {
		$lines[] = '- [' . get_the_title( $p ) . '](' . get_permalink( $p ) . '): ' . $clean( get_the_excerpt( $p ) );
	}
	$lines[] = '';
	$lines[] = '## Key pages';
	foreach ( array( 'about', 'faqs', 'contact', 'book-appointment' ) as $slug ) {
		$p = get_page_by_path( $slug );
		if ( $p && 'publish' === $p->post_status ) {
			$lines[] = '- [' . get_the_title( $p ) . '](' . get_permalink( $p ) . ')';
		}
	}
	$posts = get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => 20 ) );
	if ( $posts ) {
		$lines[] = '';
		$lines[] = '## Articles';
		foreach ( $posts as $p ) {
			$lines[] = '- [' . get_the_title( $p ) . '](' . get_permalink( $p ) . '): ' . $clean( get_the_excerpt( $p ) );
		}
	}
	$lines[] = '';
	$lines[] = '## Note';
	$lines[] = 'Information on this website is general patient education and does not replace a medical consultation.';
	return implode( "\n", $lines ) . "\n";
}
