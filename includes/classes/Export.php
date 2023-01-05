<?php
/**
 * Export class
 *
 * @since 1.0.0
 */

namespace Phone_Book\Classes;

defined( 'ABSPATH' ) || die();

if ( ! class_exists( '\\Phone_Book\\Classes\\Export' ) ) {

	class Export {

		protected static $_instance = null;
		
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		protected function __construct() {
			Phone_Book()->settings_api->set_submenu(
				__( 'Export', Phone_Book()->slug ),
				__( 'Export', Phone_Book()->slug ),
				Phone_Book()->slug.'_export'
			);
		}

	}

}

return Export::instance();