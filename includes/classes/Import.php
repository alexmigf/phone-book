<?php
/**
 * Import class
 *
 * @since 1.0.0
 */

namespace Phone_Book\Classes;

defined( 'ABSPATH' ) || die();

if ( ! class_exists( '\\Phone_Book\\Classes\\Import' ) ) {

	class Import {

		protected static $_instance = null;
		
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		protected function __construct() {
			Phone_Book()->settings_api->set_submenu(
				__( 'Import', Phone_Book()->slug ),
				__( 'Import', Phone_Book()->slug ),
				Phone_Book()->slug.'_import'
			);
		}

	}

}

return Import::instance();