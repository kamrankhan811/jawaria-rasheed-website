<?php
/**
 * Four-column footer + clinic map + bottom bar (floating WhatsApp button comes from the plugin).
 *   1. Brand, short intro, social links
 *   2. Our Services   (menu: "Footer – Our Services")
 *   3. Quick Links    (menu: "Footer – Quick Links")
 *   4. Get In Touch   (Settings → Clinic Info)
 *
 * @package jawaria-child
 */

defined( 'ABSPATH' ) || exit;

$jr_has = function_exists( 'jr_opt' );
?>
<footer class="jr-footer" role="contentinfo">
	<div class="jr-footer__grid">
		<div class="jr-footer__col jr-footer__brand">
			<div class="jr-footer__brandrow">
				<img class="jr-footer__logo" src="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/images/logo-mark-white.png' ); ?>" width="64" height="64" alt="" loading="lazy">
				<p class="jr-footer__name"><?php echo esc_html( $jr_has ? jr_opt( 'doctor_name' ) : get_bloginfo( 'name' ) ); ?></p>
			</div>
			<p class="jr-footer__tag"><?php echo esc_html( get_bloginfo( 'description' ) ); ?></p>
			<p class="jr-footer__about"><?php esc_html_e( 'Compassionate, personalised care in gynecology, women’s health and fertility, including IUI — at every stage of life.', 'jawaria-child' ); ?></p>
			<?php
			if ( $jr_has ) {
				echo jr_social_links_html(); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside.
			}
			?>
		</div>

		<div class="jr-footer__col">
			<p class="jr-footer__heading"><?php esc_html_e( 'Our Services', 'jawaria-child' ); ?></p>
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'jr_footer_services',
					'container'      => false,
					'menu_class'     => 'jr-footer__links',
					'depth'          => 1,
					'fallback_cb'    => false,
				)
			);
			?>
		</div>

		<div class="jr-footer__col">
			<p class="jr-footer__heading"><?php esc_html_e( 'Quick Links', 'jawaria-child' ); ?></p>
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'jr_footer',
					'container'      => false,
					'menu_class'     => 'jr-footer__links',
					'depth'          => 1,
					'fallback_cb'    => false,
				)
			);
			?>
		</div>

		<div class="jr-footer__col">
			<p class="jr-footer__heading"><?php esc_html_e( 'Get In Touch', 'jawaria-child' ); ?></p>
			<?php if ( $jr_has ) : ?>
				<?php echo jr_clinic_info_html( true ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside. ?>
				<?php echo jr_map_html( 'jr-map--footer' ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside. ?>
			<?php endif; ?>
		</div>
	</div>

	<div class="jr-footer__legal">
		<p class="jr-footer__note"><?php esc_html_e( 'Information on this website is for general education and does not replace a consultation. In an emergency, go to the nearest hospital emergency department.', 'jawaria-child' ); ?></p>
		<div class="jr-footer__bottom">
			<span>&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php echo esc_html( get_bloginfo( 'name' ) ); ?>. <?php esc_html_e( 'All rights reserved.', 'jawaria-child' ); ?></span>
			<span class="jr-footer__policies">
				<a href="<?php echo esc_url( home_url( '/privacy-policy/' ) ); ?>"><?php esc_html_e( 'Privacy Policy', 'jawaria-child' ); ?></a>
				<a href="<?php echo esc_url( home_url( '/medical-disclaimer/' ) ); ?>"><?php esc_html_e( 'Medical Disclaimer', 'jawaria-child' ); ?></a>
				<a href="<?php echo esc_url( home_url( '/sitemap_index.xml' ) ); ?>"><?php esc_html_e( 'Sitemap', 'jawaria-child' ); ?></a>
			</span>
		</div>
	</div>
</footer>
