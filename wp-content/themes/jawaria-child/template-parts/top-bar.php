<?php
/**
 * Slim information bar above the header. Values come from Settings → Clinic Info.
 *
 * @package jawaria-child
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'jr_opt' ) ) {
	return;
}
$jr_phone = jr_opt( 'phone' );
$jr_hours = jr_opt( 'hours_short' );
$jr_city  = jr_opt( 'city' );
?>
<div class="jr-topbar" role="complementary" aria-label="<?php esc_attr_e( 'Clinic contact information', 'jawaria-child' ); ?>">
	<div class="jr-topbar__inner">
		<span class="jr-topbar__item">
			<?php if ( $jr_city ) : ?>
				<?php echo esc_html( $jr_city ); ?> &middot;
			<?php endif; ?>
			<?php echo esc_html( $jr_hours ); ?>
		</span>
		<span class="jr-topbar__links">
			<?php if ( $jr_phone ) : ?>
				<a href="<?php echo esc_url( jr_tel_url() ); ?>"><?php echo esc_html( $jr_phone ); ?></a>
			<?php endif; ?>
			<?php if ( jr_opt( 'whatsapp' ) ) : ?>
				<a href="<?php echo esc_url( jr_whatsapp_url() ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'WhatsApp', 'jawaria-child' ); ?></a>
			<?php endif; ?>
			<a class="jr-topbar__cta" href="<?php echo esc_url( home_url( '/book-appointment/' ) ); ?>"><?php esc_html_e( 'Book appointment', 'jawaria-child' ); ?></a>
		</span>
	</div>
</div>
