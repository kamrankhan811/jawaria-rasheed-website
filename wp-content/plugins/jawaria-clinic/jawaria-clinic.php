<?php
/**
 * Plugin Name:       Jawaria Clinic Toolkit
 * Description:       Clinic info settings, appointment requests, WhatsApp button, shortcodes and one-click page setup for Dr. Jawaria Rasheed's website.
 * Version:           1.2.0
 * Requires at least: 6.3
 * Requires PHP:      7.4
 * Author:            Kamran Khan
 * Text Domain:       jawaria-clinic
 *
 * @package jawaria-clinic
 */

defined( 'ABSPATH' ) || exit;

define( 'JR_CLINIC_VERSION', '1.2.0' );
define( 'JR_CLINIC_FILE', __FILE__ );
define( 'JR_CLINIC_DIR', plugin_dir_path( __FILE__ ) );
define( 'JR_CLINIC_URL', plugin_dir_url( __FILE__ ) );

require_once JR_CLINIC_DIR . 'includes/helpers.php';
require_once JR_CLINIC_DIR . 'includes/settings.php';
require_once JR_CLINIC_DIR . 'includes/shortcodes.php';
require_once JR_CLINIC_DIR . 'includes/appointments.php';
require_once JR_CLINIC_DIR . 'includes/seo.php';
require_once JR_CLINIC_DIR . 'includes/security.php';
require_once JR_CLINIC_DIR . 'includes/extras.php';
require_once JR_CLINIC_DIR . 'includes/setup.php';

register_activation_hook(
	__FILE__,
	function () {
		jr_register_appointment_type();
		jr_register_review_type();
		if ( false === get_option( JR_OPTION ) ) {
			add_option( JR_OPTION, jr_default_options() );
		}
		add_rewrite_rule( '^llms\\.txt$', 'index.php?jr_llms=1', 'top' );
		flush_rewrite_rules();
	}
);
