<?php
/**
 * Site footer: clinic details, quick links, services, medical disclaimer.
 *
 * @package jawaria-child
 */

defined( 'ABSPATH' ) || exit;

$jr_has = function_exists( 'jr_opt' );
?>
<footer class="jr-footer" role="contentinfo">
	<div class="jr-footer__grid">
		<div class="jr-footer__col jr-footer__brand">
			<p class="jr-footer__name"><?php echo esc_html( $jr_has ? jr_opt( 'doctor_name' ) : get_bloginfo( 'name' ) ); ?></p>
			<p class="jr-footer__tag"><?php echo esc_html( get_bloginfo( 'description' ) ); ?></p>
			<?php if ( $jr_has ) : ?>
				<p><?php echo esc_html( jr_opt( 'qualifications' ) ); ?></p>
			<?php endif; ?>
		</div>

		<div class="jr-footer__col">
			<p class="jr-footer__heading"><?php esc_html_e( 'Quick links', 'jawaria-child' ); ?></p>
			<?php
			if ( has_nav_menu( 'jr_footer' ) ) {
				wp_nav_menu(
					array(
						'theme_location' => 'jr_footer',
						'container'      => false,
						'menu_class'     => 'jr-footer__links',
						'depth'          => 1,
					)
				);
			}
			?>
		</div>

		<?php if ( $jr_has ) : ?>
		<div class="jr-footer__col">
			<p class="jr-footer__heading"><?php esc_html_e( 'Visit the clinic', 'jawaria-child' ); ?></p>
			<?php echo jr_clinic_info_html(); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside. ?>
		</div>
		<?php endif; ?>
	</div>

	<div class="jr-footer__legal">
		<p>
			<?php esc_html_e( 'Information on this website is for general education and does not replace a consultation. In an emergency, go to the nearest hospital emergency department.', 'jawaria-child' ); ?>
		</p>
		<p>
			&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php echo esc_html( get_bloginfo( 'name' ) ); ?> &middot;
			<a href="<?php echo esc_url( home_url( '/privacy-policy/' ) ); ?>"><?php esc_html_e( 'Privacy policy', 'jawaria-child' ); ?></a> &middot;
			<a href="<?php echo esc_url( home_url( '/medical-disclaimer/' ) ); ?>"><?php esc_html_e( 'Medical disclaimer', 'jawaria-child' ); ?></a>
		</p>
	</div>
</footer>
