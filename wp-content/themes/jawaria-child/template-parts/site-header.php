<?php
/**
 * Two-row site header (replaces Astra's header):
 *   Row 1: logo · top menu (About, Patient Guide, Contact) · call + Book buttons
 *   Row 2: services menu with dropdowns / mega menu
 * Menus are edited in Appearance → Menus ("Header – top row", "Header – services row").
 *
 * @package jawaria-child
 */

defined( 'ABSPATH' ) || exit;

$jr_has = function_exists( 'jr_opt' );
?>
<header class="jr-header" id="masthead" data-jr-header>
	<div class="jr-header__main">
		<div class="jr-header__brand">
			<?php if ( has_custom_logo() ) : ?>
				<?php the_custom_logo(); ?>
			<?php else : ?>
				<a class="jr-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
					<span class="jr-logo__mark" aria-hidden="true">JR</span>
					<span class="jr-logo__text">
						<span class="jr-logo__name"><?php echo esc_html( $jr_has ? jr_opt( 'doctor_name' ) : get_bloginfo( 'name' ) ); ?></span>
						<span class="jr-logo__tag"><?php echo esc_html( get_bloginfo( 'description' ) ); ?></span>
					</span>
				</a>
			<?php endif; ?>
		</div>

		<nav class="jr-nav jr-nav--top" aria-label="<?php esc_attr_e( 'Main', 'jawaria-child' ); ?>">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'jr_top',
					'container'      => false,
					'menu_class'     => 'jr-menu',
					'fallback_cb'    => false,
					'depth'          => 2,
				)
			);
			?>
		</nav>

		<div class="jr-header__actions">
			<?php if ( $jr_has && jr_opt( 'phone' ) ) : ?>
				<a class="jr-header__call" href="<?php echo esc_url( jr_tel_url() ); ?>">
					<span class="jr-header__call-label"><?php esc_html_e( 'Call us', 'jawaria-child' ); ?></span>
					<span class="jr-header__call-num"><?php echo esc_html( jr_opt( 'phone' ) ); ?></span>
				</a>
			<?php endif; ?>
			<?php if ( $jr_has && jr_opt( 'video_consult_url' ) ) : ?>
				<a class="jr-btn jr-btn--ghost jr-btn--sm" href="<?php echo esc_url( jr_opt( 'video_consult_url' ) ); ?>"><?php esc_html_e( 'Video Consultation', 'jawaria-child' ); ?></a>
			<?php endif; ?>
			<a class="jr-btn jr-btn--primary jr-btn--sm" href="<?php echo esc_url( home_url( '/book-appointment/' ) ); ?>"><?php esc_html_e( 'Book', 'jawaria-child' ); ?><span class="jr-hide-sm"><?php esc_html_e( 'An Appointment', 'jawaria-child' ); ?></span></a>
			<button class="jr-burger" type="button" aria-expanded="false" aria-controls="jr-services-nav" data-jr-burger>
				<span></span><span></span><span></span>
				<span class="screen-reader-text"><?php esc_html_e( 'Menu', 'jawaria-child' ); ?></span>
			</button>
		</div>
	</div>

	<div class="jr-header__services" id="jr-services-nav">
		<nav class="jr-nav jr-nav--services" aria-label="<?php esc_attr_e( 'Services', 'jawaria-child' ); ?>">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'jr_services',
					'container'      => false,
					'menu_class'     => 'jr-menu',
					'fallback_cb'    => false,
					'depth'          => 3,
				)
			);
			?>
		</nav>
	</div>
</header>
