<?php
/**
 * Plugin Name:			Phone Book
 * Description:			A WordPress Phone Book plugin
 * Version:				1.0.0
 * Requires at least:	4.9
 * Requires PHP:		7.2
 * Author:				Alexandre Faustino
 * Author URI:			https://alexmigf.dev
 * License:           	GPL v2 or later
 * License URI:       	https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:			phone-book
 * Domain Path:			/languages
 */

defined( 'ABSPATH' ) || die();

// constants
define( 'PHONE_BOOK_VERSION', '1.0.0' );
define( 'PHONE_BOOK_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'PHONE_BOOK_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PHONE_BOOK_PLUGIN_TEMPLATES_DIR', plugin_dir_path( __FILE__ ).'templates/' );
define( 'PHONE_BOOK_PLUGIN_EXPORTS_DIR', plugin_dir_path( __FILE__ ).'exports/' );
define( 'PHONE_BOOK_PLUGIN_BASE_NAME', plugin_basename( __FILE__ ) );
define( 'PHONE_BOOK_PLUGIN_FILE', basename( __FILE__ ) );
define( 'PHONE_BOOK_PLUGIN_SLUG', substr( basename( __FILE__ ), 0, strrpos( basename( __FILE__ ), '.' ) ) );
define( 'PHONE_BOOK_PLUGIN_FULL_PATH', __FILE__ );

// autoload
require_once( PHONE_BOOK_PLUGIN_DIR . 'vendor/autoload.php' );

// main instance
function Phone_Book() {
	return \Phone_Book\Classes\Main::instance();
}
Phone_Book();