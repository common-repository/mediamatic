<?php
/**
 * Plugin Name: Mediamatic Lite
 * Plugin URI:  https://frenify.net/envato/themedo/plugin/mediamatic/1/
 * Description: Get organized with thousands of images. Organize media into folders.
 * Version:     2.8.1
 * Author:      plugincraft
 * Author URI:  https://frenify.net/envato/themedo/plugin/mediamatic/1/
 * Text Domain: mediamatic
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path: /languages/
 */


if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'MEDIAMATIC__FILE__', __FILE__ );
define( 'MEDIAMATIC_FOLDER', 'mediamatic_wpfolder' );
define( 'MEDIAMATIC_VERSION', '2.8.1' );
define( 'MEDIAMATIC_PATH', plugin_dir_path( MEDIAMATIC__FILE__ ) );
define( 'MEDIAMATIC_URL', plugins_url( '/', MEDIAMATIC__FILE__ ) );
define( 'MEDIAMATIC_ASSETS_URL', MEDIAMATIC_URL . 'assets/' );
define( 'MEDIAMATIC_TEXT_DOMAIN', 'mediamatic' );
define( 'MEDIAMATIC_PLUGIN_BASE', plugin_basename( MEDIAMATIC__FILE__ ) );
define( 'MEDIAMATIC_PLUGIN_NAME', 'Mediamatic Lite' );


function mediamatic_plugins_loaded()
{
	// include main plugin file
	include_once ( MEDIAMATIC_PATH . 'inc/plugin.php' );
	load_plugin_textdomain(MEDIAMATIC_TEXT_DOMAIN, false, plugin_basename(__DIR__) . '/languages/');
}

add_action('plugins_loaded', 'mediamatic_plugins_loaded');