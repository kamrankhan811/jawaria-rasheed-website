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
?>
<div class="jr-topbar" role="complementary" aria-label="<?php esc_attr_e( 'Clinic contact information', 'jawaria-child' ); ?>">
	<div class="jr-topbar__inner">
		<span class="jr-topbar__item">
			<?php if ( jr_opt( 'city' ) ) : ?>
				<?php echo esc_html( jr_opt( 'city' ) ); ?> &middot;
			<?php endif; ?>
			<?php echo esc_html( jr_opt( 'hours_short' ) ); ?>
		</span>
		<span class="jr-topbar__links">
			<?php if ( jr_opt( 'phone' ) ) : ?>
				<a href="<?php echo esc_url( jr_tel_url() ); ?>"><span aria-hidden="true">&#9742;</span> <?php esc_html_e( 'Call', 'jawaria-child' ); ?> <?php echo esc_html( jr_opt( 'phone' ) ); ?></a>
			<?php endif; ?>
			<?php if ( jr_opt( 'whatsapp' ) ) : ?>
				<a href="<?php echo esc_url( jr_whatsapp_url() ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'WhatsApp', 'jawaria-child' ); ?> <?php echo esc_html( jr_opt( 'whatsapp' ) ); ?></a>
			<?php endif; ?>
			<?php if ( jr_opt( 'email' ) ) : ?>
				<a href="mailto:<?php echo esc_attr( antispambot( jr_opt( 'email' ) ) ); ?>"><?php echo esc_html( antispambot( jr_opt( 'email' ) ) ); ?></a>
			<?php endif; ?>
		</span>
	</div>
</div>
