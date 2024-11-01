<?php
/**
 * Plugin Name: Wilapp
 * Plugin URI:  https://wilapp.com
 * Description: Make appointments for your shop with Wilapp.
 * Version:     1.3.1
 * Author:      wilapp
 * Author URI:  https://close.technology
 * Text Domain: wilapp
 * Domain Path: /languages
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Requires at least: 5.4
 * Requires PHP: 7.4
 *
 * @package     WordPress
 * @author      Closetechnology
 * @copyright   2021 Closetechnology
 * @license     GPL-2.0+
 *
 * @wordpress-plugin
 *
 * Prefix:      wilapp
 */

defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

define( 'WILAPP_VERSION', '1.3.1' );
define( 'WILAPP_PLUGIN', __FILE__ );
define( 'WILAPP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WILAPP_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'WILAPP_MAXDAYS', 15 );

add_action( 'plugins_loaded', 'wilapp_plugin_init' );
/**
 * Load localization files
 *
 * @return void
 */
function wilapp_plugin_init() {
	load_plugin_textdomain( 'wilapp', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

require_once WILAPP_PLUGIN_PATH . 'includes/class-helpers-wilapp.php';
require_once WILAPP_PLUGIN_PATH . 'includes/class-wilapp-admin-settings.php';
require_once WILAPP_PLUGIN_PATH . 'includes/class-wilapp-wizard.php';
