<?php
/**
 * Plugin Name: WP Travel Machine - AI powered Travel & Hotel Booking Plugin
 * Plugin URI: https://wptravelmachine.com
 * Description: A modern, AI-powered travel & hotel booking system for WordPress. Features trip packages, hotel management, smart booking engine, and AI-driven recommendations.
 * Version: 1.0.1
 * Author: WP Travel Machine
 * Author URI: https://wptravelmachine.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: wp-travel-machine
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 *
 * @package WPTravelMachine
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Plugin constants.
 */
define( 'WPTM_VERSION', '1.0.1' );
define( 'WPTM_PLUGIN_FILE', __FILE__ );
define( 'WPTM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPTM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WPTM_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'WPTM_DB_VERSION', '1.0.0' );

/**
 * Autoloader.
 */
require_once WPTM_PLUGIN_DIR . 'includes/class-autoloader.php';
\WPTravelMachine\Autoloader::register();

/**
 * Global helper functions (non-class).
 */
require_once WPTM_PLUGIN_DIR . 'includes/helpers/class-functions.php';

/**
 * Plugin activation hook.
 */
register_activation_hook( __FILE__, function () {
    \WPTravelMachine\Activator::activate();
} );

/**
 * Plugin deactivation hook.
 */
register_deactivation_hook( __FILE__, function () {
    \WPTravelMachine\Deactivator::deactivate();
} );

/**
 * Initialize the plugin.
 */
function wptm_init() {
    return \WPTravelMachine\Plugin::get_instance();
}

// Boot the plugin.
add_action( 'plugins_loaded', 'wptm_init' );
