<?php
/**
 * Tools → Clinic Site Setup.
 *
 * One click creates every page (as editable Elementor pages), the menus,
 * home/blog page settings, permalinks and draft articles from /content/*.json.
 * Safe to run again: existing published pages are left alone unless
 * "Overwrite" is ticked.
 *
 * @package jawaria-clinic
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'admin_menu',
	function () {
		add_management_page(
			__( 'Clinic Site Setup', 'jawaria-clinic' ),
			__( 'Clinic Site Setup', 'jawaria-clinic' ),
			'manage_options',
			'jr-site-setup',
			'jr_render_setup_page'
		);
	}
);

/**
 * Setup screen.
 */
function jr_render_setup_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$report = get_transient( 'jr_setup_report_' . get_current_user_id() );
	delete_transient( 'jr_setup_report_' . get_current_user_id() );
	$astra     = 'astra' === get_template();
	$elementor = defined( 'ELEMENTOR_VERSION' );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Clinic Site Setup', 'jawaria-clinic' ); ?></h1>

		<h2><?php esc_html_e( 'Checklist', 'jawaria-clinic' ); ?></h2>
		<ul>
			<li><?php echo $astra ? '✅' : '❌'; ?> <?php esc_html_e( 'Astra theme installed and "Jawaria Rasheed Clinic" child theme active', 'jawaria-clinic' ); ?></li>
			<li><?php echo $elementor ? '✅' : '❌'; ?> <?php esc_html_e( 'Elementor plugin active', 'jawaria-clinic' ); ?></li>
			<li><?php echo jr_opt( 'phone' ) && jr_opt( 'whatsapp' ) ? '✅' : '⚠️'; ?> <a href="<?php echo esc_url( admin_url( 'options-general.php?page=jr-clinic-info' ) ); ?>"><?php esc_html_e( 'Clinic Info filled in (can be done later)', 'jawaria-clinic' ); ?></a></li>
		</ul>

		<?php if ( is_array( $report ) ) : ?>
			<div class="notice notice-success"><p><strong><?php esc_html_e( 'Setup finished.', 'jawaria-clinic' ); ?></strong></p>
				<ul style="list-style:disc;padding-left:20px">
					<?php foreach ( $report as $line ) : ?>
						<li><?php echo esc_html( $line ); ?></li>
					<?php endforeach; ?>
				</ul>
				<p><a class="button button-primary" href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank"><?php esc_html_e( 'View website', 'jawaria-clinic' ); ?></a></p>
			</div>
		<?php endif; ?>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="jr_run_setup">
			<?php wp_nonce_field( 'jr_run_setup', 'jr_setup_nonce' ); ?>
			<p><label><input type="checkbox" name="jr_overwrite" value="1"> <?php esc_html_e( 'Overwrite pages that already exist (your edits to those pages will be replaced)', 'jawaria-clinic' ); ?></label></p>
			<p><label><input type="checkbox" name="jr_clean" value="1" checked> <?php esc_html_e( 'Remove WordPress sample content ("Sample Page", "Hello world!")', 'jawaria-clinic' ); ?></label></p>
			<?php submit_button( __( 'Create website pages & menus', 'jawaria-clinic' ), 'primary', 'submit', true, $elementor ? array() : array( 'disabled' => 'disabled' ) ); ?>
			<?php if ( ! $elementor ) : ?>
				<p class="description"><?php esc_html_e( 'Activate Elementor first.', 'jawaria-clinic' ); ?></p>
			<?php endif; ?>
		</form>
	</div>
	<?php
}

add_action( 'admin_post_jr_run_setup', 'jr_run_setup' );

/**
 * Run the setup.
 */
function jr_run_setup() {
	if ( ! current_user_can( 'manage_options' ) || ! isset( $_POST['jr_setup_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['jr_setup_nonce'] ) ), 'jr_run_setup' ) ) {
		wp_die( esc_html__( 'Not allowed.', 'jawaria-clinic' ) );
	}
	$overwrite = ! empty( $_POST['jr_overwrite'] );
	$report    = array();

	if ( ! empty( $_POST['jr_clean'] ) ) {
		$report = array_merge( $report, jr_setup_remove_samples() );
	}

	$media  = jr_setup_media();
	$report = array_merge( $report, jr_setup_pages( $media, $overwrite ) );
	$report = array_merge( $report, jr_setup_site_options() );
	$report = array_merge( $report, jr_setup_menus( $overwrite ) );
	$report = array_merge( $report, jr_setup_posts( $media ) );

	if ( class_exists( '\Elementor\Plugin' ) ) {
		\Elementor\Plugin::$instance->files_manager->clear_cache();
	}
	flush_rewrite_rules();

	set_transient( 'jr_setup_report_' . get_current_user_id(), $report, 5 * MINUTE_IN_SECONDS );
	wp_safe_redirect( admin_url( 'tools.php?page=jr-site-setup' ) );
	exit;
}

/**
 * Load a JSON file from /content.
 *
 * @param string $name File name.
 * @param array  $media Token map for images.
 * @return array
 */
function jr_setup_load_json( $name, array $media = array() ) {
	$path = JR_CLINIC_DIR . 'content/' . $name;
	if ( ! is_readable( $path ) ) {
		return array();
	}
	$raw = (string) file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local file.
	foreach ( $media as $key => $att ) {
		$raw = str_replace( '"{{img:' . $key . ':id}}"', (string) (int) $att['id'], $raw );
		$raw = str_replace( '{{img:' . $key . ':url}}', addcslashes( $att['url'], '/' ), $raw );
	}
	$data = json_decode( $raw, true );
	return is_array( $data ) ? $data : array();
}

/**
 * Import bundled placeholder images once.
 *
 * @return array key => [id, url]
 */
function jr_setup_media() {
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$stored = (array) get_option( 'jr_media', array() );
	$files  = array(
		'doctor' => 'doctor-placeholder.jpg',
		'clinic' => 'clinic-placeholder.jpg',
	);
	$out = array();
	foreach ( $files as $key => $file ) {
		if ( ! empty( $stored[ $key ] ) && get_post( $stored[ $key ] ) ) {
			$out[ $key ] = array( 'id' => (int) $stored[ $key ], 'url' => wp_get_attachment_url( $stored[ $key ] ) );
			continue;
		}
		$src = JR_CLINIC_DIR . 'assets/images/' . $file;
		if ( ! is_readable( $src ) ) {
			continue;
		}
		$upload = wp_upload_bits( $file, null, (string) file_get_contents( $src ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( ! empty( $upload['error'] ) ) {
			continue;
		}
		$id = wp_insert_attachment(
			array(
				'post_mime_type' => 'image/jpeg',
				'post_title'     => ucfirst( $key ) . ' photo (placeholder — replace)',
				'post_status'    => 'inherit',
			),
			$upload['file']
		);
		wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $upload['file'] ) );
		update_post_meta( $id, '_wp_attachment_image_alt', 'clinic' === $key ? 'Clinic consultation room' : 'Dr. Jawaria Rasheed' );
		$stored[ $key ] = $id;
		$out[ $key ]    = array( 'id' => $id, 'url' => $upload['url'] );
	}
	update_option( 'jr_media', $stored, false );
	return $out;
}

/**
 * Create / update pages.
 *
 * @param array $media     Image tokens.
 * @param bool  $overwrite Overwrite published pages.
 * @return string[]
 */
function jr_setup_pages( array $media, $overwrite ) {
	$pages   = jr_setup_load_json( 'pages.json', $media );
	$report  = array();
	$created = 0;
	$updated = 0;
	$skipped = array();
	$ids     = array();

	foreach ( $pages as $def ) {
		$parent_id = 0;
		$path      = $def['slug'];
		if ( ! empty( $def['parent'] ) ) {
			$parent_id = isset( $ids[ $def['parent'] ] ) ? $ids[ $def['parent'] ] : 0;
			$path      = $def['parent'] . '/' . $def['slug'];
		}
		$existing = get_page_by_path( $path, OBJECT, 'page' );

		if ( $existing && 'publish' === $existing->post_status && ! $overwrite ) {
			$ids[ $def['slug'] ] = $existing->ID;
			$skipped[]           = $def['title'];
			continue;
		}

		$postarr = array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => $def['title'],
			'post_name'    => $def['slug'],
			'post_parent'  => $parent_id,
			'menu_order'   => isset( $def['order'] ) ? (int) $def['order'] : 0,
			'post_excerpt' => isset( $def['excerpt'] ) ? $def['excerpt'] : '',
			'post_content' => isset( $def['fallback'] ) ? $def['fallback'] : '',
		);
		if ( $existing ) {
			$postarr['ID'] = $existing->ID;
			$id            = wp_update_post( wp_slash( $postarr ), true );
			++$updated;
		} else {
			$id = wp_insert_post( wp_slash( $postarr ), true );
			++$created;
		}
		if ( is_wp_error( $id ) ) {
			$report[] = 'Error on ' . $def['title'] . ': ' . $id->get_error_message();
			continue;
		}
		$ids[ $def['slug'] ] = $id;

		if ( ! empty( $def['elementor'] ) ) {
			update_post_meta( $id, '_wp_page_template', 'elementor_header_footer' );
			update_post_meta( $id, '_elementor_edit_mode', 'builder' );
			update_post_meta( $id, '_elementor_template_type', 'wp-page' );
			update_post_meta( $id, '_elementor_version', defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : '3.0.0' );
			update_post_meta( $id, '_elementor_data', wp_slash( wp_json_encode( $def['elementor'] ) ) );
			delete_post_meta( $id, '_elementor_css' );
		}
		if ( ! empty( $def['icon'] ) ) {
			update_post_meta( $id, '_jr_icon', sanitize_text_field( $def['icon'] ) );
		}
		if ( ! empty( $def['seo_title'] ) ) {
			update_post_meta( $id, 'rank_math_title', sanitize_text_field( $def['seo_title'] ) );
		}
		if ( ! empty( $def['seo_description'] ) ) {
			update_post_meta( $id, 'rank_math_description', sanitize_text_field( $def['seo_description'] ) );
		}
	}

	update_option( 'jr_page_ids', $ids, false );
	$report[] = sprintf( 'Pages: %d created, %d updated, %d kept as they were.', $created, $updated, count( $skipped ) );
	if ( $skipped ) {
		$report[] = 'Kept (tick "Overwrite" to replace): ' . implode( ', ', $skipped );
	}
	return $report;
}

/**
 * Reading settings, permalinks, title, Elementor defaults.
 *
 * @return string[]
 */
function jr_setup_site_options() {
	$ids = (array) get_option( 'jr_page_ids', array() );
	if ( ! empty( $ids['home'] ) ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', (int) $ids['home'] );
	}
	if ( ! empty( $ids['blog'] ) ) {
		update_option( 'page_for_posts', (int) $ids['blog'] );
	}
	if ( ! empty( $ids['privacy-policy'] ) ) {
		update_option( 'wp_page_for_privacy_policy', (int) $ids['privacy-policy'] );
	}
	update_option( 'blogname', 'Dr. Jawaria Rasheed' );
	update_option( 'blogdescription', 'Consultant Gynecologist & IUI Specialist' );
	update_option( 'permalink_structure', '/%postname%/' );

	// Let the child theme's fonts and colours apply instead of Elementor defaults.
	update_option( 'elementor_disable_color_schemes', 'yes' );
	update_option( 'elementor_disable_typography_schemes', 'yes' );
	$support = (array) get_option( 'elementor_cpt_support', array( 'page', 'post' ) );
	update_option( 'elementor_cpt_support', array_values( array_unique( array_merge( $support, array( 'page', 'post' ) ) ) ) );

	return array( 'Home page, blog page, site title and permalinks set.' );
}

/**
 * Build menus.
 *
 * @param bool $overwrite Rebuild existing menus.
 * @return string[]
 */
function jr_setup_menus( $overwrite ) {
	$ids    = (array) get_option( 'jr_page_ids', array() );
	$report = array();

	$build = function ( $name, array $items ) use ( $ids, $overwrite, &$report ) {
		$menu = wp_get_nav_menu_object( $name );
		if ( $menu && ! $overwrite ) {
			$report[] = "Menu \"$name\" already exists — kept.";
			return (int) $menu->term_id;
		}
		$menu_id = $menu ? (int) $menu->term_id : wp_create_nav_menu( $name );
		if ( is_wp_error( $menu_id ) ) {
			return 0;
		}
		foreach ( (array) wp_get_nav_menu_items( $menu_id ) as $old ) {
			wp_delete_post( $old->ID, true );
		}
		$pos = 0;
		foreach ( $items as $item ) {
			$slug = $item[0];
			if ( empty( $ids[ $slug ] ) ) {
				continue;
			}
			$parent_item = wp_update_nav_menu_item(
				$menu_id,
				0,
				array(
					'menu-item-object-id' => $ids[ $slug ],
					'menu-item-object'    => 'page',
					'menu-item-type'      => 'post_type',
					'menu-item-status'    => 'publish',
					'menu-item-title'     => isset( $item[1] ) ? $item[1] : '',
					'menu-item-classes'   => isset( $item[2] ) ? $item[2] : '',
					'menu-item-position'  => ++$pos,
				)
			);
			if ( ! empty( $item[3] ) && 'children' === $item[3] ) {
				foreach ( jr_service_pages() as $child ) {
					wp_update_nav_menu_item(
						$menu_id,
						0,
						array(
							'menu-item-object-id' => $child->ID,
							'menu-item-object'    => 'page',
							'menu-item-type'      => 'post_type',
							'menu-item-status'    => 'publish',
							'menu-item-parent-id' => $parent_item,
							'menu-item-position'  => ++$pos,
						)
					);
				}
			}
		}
		$report[] = "Menu \"$name\" created.";
		return $menu_id;
	};

	$main = $build(
		'Main menu',
		array(
			array( 'home', 'Home' ),
			array( 'about', 'About Dr. Jawaria' ),
			array( 'services', 'Services', '', 'children' ),
			array( 'blog', 'Health Articles' ),
			array( 'contact', 'Contact' ),
			array( 'book-appointment', 'Book Appointment', 'jr-menu-book' ),
		)
	);
	$footer = $build(
		'Footer links',
		array(
			array( 'about', 'About Dr. Jawaria' ),
			array( 'services', 'Services' ),
			array( 'blog', 'Health Articles' ),
			array( 'book-appointment', 'Book Appointment' ),
			array( 'contact', 'Contact' ),
		)
	);

	$locations = (array) get_theme_mod( 'nav_menu_locations', array() );
	if ( $main ) {
		$locations['primary']     = $main;
		$locations['mobile_menu'] = $main;
	}
	if ( $footer ) {
		$locations['jr_footer'] = $footer;
	}
	set_theme_mod( 'nav_menu_locations', $locations );
	return $report;
}

/**
 * Draft starter articles (review before publishing).
 *
 * @param array $media Image tokens.
 * @return string[]
 */
function jr_setup_posts( array $media ) {
	$posts   = jr_setup_load_json( 'posts.json', $media );
	$created = 0;
	foreach ( $posts as $p ) {
		if ( get_page_by_path( $p['slug'], OBJECT, 'post' ) ) {
			continue;
		}
		$cat_ids = array();
		foreach ( (array) $p['categories'] as $cat ) {
			$term = term_exists( $cat, 'category' );
			if ( ! $term ) {
				$term = wp_insert_term( $cat, 'category' );
			}
			if ( ! is_wp_error( $term ) ) {
				$cat_ids[] = (int) ( is_array( $term ) ? $term['term_id'] : $term );
			}
		}
		$id = wp_insert_post(
			wp_slash(
				array(
					'post_type'     => 'post',
					'post_status'   => 'draft',
					'post_title'    => $p['title'],
					'post_name'     => $p['slug'],
					'post_excerpt'  => $p['excerpt'],
					'post_content'  => $p['content'],
					'post_category' => $cat_ids,
				)
			)
		);
		if ( $id && ! is_wp_error( $id ) ) {
			++$created;
			if ( ! empty( $p['seo_description'] ) ) {
				update_post_meta( $id, 'rank_math_description', $p['seo_description'] );
			}
		}
	}
	return array( sprintf( '%d starter articles added as drafts (Posts → Drafts). Ask Dr. Jawaria to review before publishing.', $created ) );
}

/**
 * Remove default WordPress sample content if untouched.
 *
 * @return string[]
 */
function jr_setup_remove_samples() {
	$out    = array();
	$sample = get_page_by_path( 'sample-page' );
	if ( $sample ) {
		wp_trash_post( $sample->ID );
		$out[] = 'Moved "Sample Page" to trash.';
	}
	$hello = get_page_by_path( 'hello-world', OBJECT, 'post' );
	if ( $hello ) {
		wp_trash_post( $hello->ID );
		$out[] = 'Moved "Hello world!" to trash.';
	}
	return $out;
}
