<?php
/**
 * Logger class
 *
 * @since 1.0.0
 */

namespace Phone_Book\Classes;

defined( 'ABSPATH' ) || die();

if ( ! class_exists( '\\Phone_Book\\Classes\\Logger' ) ) {

	class Logger {

		private          $debug_file = PHONE_BOOK_PLUGIN_DIR.'debug.log';
		protected static $_instance  = null;
		
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		protected function __construct() {
			$this->create_file();
		}

		public function log( $message ) {
			if ( ! empty( $message ) && $this->file_exists() ) {
				error_log( $message.PHP_EOL, 3, $this->debug_file );
			}
		}

		public function create_file() {
			if ( ! $this->file_exists() ) {
				$file = fopen( $this->debug_file, 'w' ) or die( "Can't open file" );
				fclose( $file );
			}
		}

		public function file_exists() {
			if ( file_exists( $this->debug_file ) ) {
				return true;
			} else {
				return false;
			}
		}

	}

}

return Logger::instance();