<?php
/**
 * Plugin Name: WooCommerce Shipping Label Printer
 * Plugin URI:  https://yourwebsite.com/
 * Description: Allows admin to print multiple shipping labels on A4 pages with customizable layouts.
 * Version:     1.0.0
 * Author:      Amit Chauhan
 * Author URI:  https://yourwebsite.com/
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: wsplp
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define Constants.
define( 'WSPLP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WSPLP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WSPLP_VERSION', '1.0.0' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing hooks.
 */
require_once WSPLP_PLUGIN_DIR . 'includes/class-wsplp-settings.php';
require_once WSPLP_PLUGIN_DIR . 'includes/class-wsplp-label-generator.php';
require_once WSPLP_PLUGIN_DIR . 'admin/class-wsplp-admin.php';


/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is loaded into classes, we just need to
 * activate this class to run the plugin.
 *
 * @since    1.0.0
 */
function run_wsplp() {
	$wsplp_admin = new WSPLP_Admin();
	$wsplp_admin->run(); // Initialize admin hooks.
}
run_wsplp();

// Activation Hook (Optional: for setting default options or flushing rewrite rules).
register_activation_hook( __FILE__, 'wsplp_activate_plugin' );
function wsplp_activate_plugin() {
    // You can set default options here if needed, but WSPLP_Settings handles defaults.
}

// Deactivation Hook (Optional: for cleaning up options).
register_deactivation_hook( __FILE__, 'wsplp_deactivate_plugin' );
function wsplp_deactivate_plugin() {
    // Optionally delete plugin options on deactivation
    // delete_option( 'wsplp_options' );
}