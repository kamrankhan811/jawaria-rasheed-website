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
			<?php echo jr_icon( 'clock' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?>
			<?php
			$jr_lines = function_exists( 'jr_hours_lines' ) ? jr_hours_lines() : array();
			foreach ( $jr_lines as $jr_i => $jr_line ) :
				?>
				<?php if ( $jr_i ) : ?><span class="jr-topbar__sep" aria-hidden="true">|</span><?php endif; ?>
				<span class="jr-topbar__hours"><?php echo esc_html( $jr_line ); ?></span>
			<?php endforeach; ?>
		</span>
		<span class="jr-topbar__links">
			<?php if ( jr_opt( 'whatsapp' ) ) : ?>
				<a class="jr-topbar__wa" href="<?php echo esc_url( jr_whatsapp_url() ); ?>" target="_blank" rel="noopener"><?php echo jr_wa_icon(); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?> <?php echo esc_html( jr_opt( 'whatsapp' ) ); ?></a>
			<?php endif; ?>
			<?php if ( jr_opt( 'email' ) ) : ?>
				<a href="mailto:<?php echo esc_attr( antispambot( jr_opt( 'email' ) ) ); ?>"><?php echo jr_icon( 'mail' ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?> <?php echo esc_html( antispambot( jr_opt( 'email' ) ) ); ?></a>
			<?php endif; ?>
		</span>
	</div>
</div>
