<?php
/**
 * Plugin Name: Byteflows Travel & Hotel Booking
 * Description: Turn WordPress into a complete travel & hotel booking platform — trip packages, hotels, a smart booking engine, search, reviews, wishlist & compare and bank-transfer checkout.
 * Version: 1.0.0
 * Author: Byteflows
 * Author URI: https://byteflows.net/
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: byteflows-travel-hotel-booking
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 *
 * @package Byteflows_Travel_Hotel_Booking
 */

// Prevent direct access.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Plugin constants.
 */
define('WPTM_VERSION', '1.0.0');
define('WPTM_PLUGIN_FILE', __FILE__);
define('WPTM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPTM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WPTM_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('WPTM_DB_VERSION', '1.0.0');

/**
 * Autoloader.
 */
require_once WPTM_PLUGIN_DIR . 'includes/class-autoloader.php';
\JourneyLoom\Autoloader::register();

/**
 * Global helper functions (non-class).
 */
require_once WPTM_PLUGIN_DIR . 'includes/helpers/class-functions.php';

/**
 * Plugin activation hook.
 */
register_activation_hook(__FILE__, function () {
    \JourneyLoom\Activator::activate();
});

/**
 * Plugin deactivation hook.
 */
register_deactivation_hook(__FILE__, function () {
    \JourneyLoom\Deactivator::deactivate();
});

/**
 * Initialize the plugin.
 */
function wptm_init()
{
    return \JourneyLoom\Plugin::get_instance();
}

// Boot the plugin.
add_action('plugins_loaded', 'wptm_init');
