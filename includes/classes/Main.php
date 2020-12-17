<?php
/**
 * Main class
 *
 * @since 1.0.0
 */

namespace Phone_Book\Classes;

defined( 'ABSPATH' ) || die();

if( ! class_exists( '\\Phone_Book\\Classes\\Main' ) ) {

	class Main extends \Phone_Book\Classes\Base
	{

		public $contacts;
		public $settings;

		protected function __construct()
		{
			add_action( 'plugins_loaded', array( $this, 'load_classes' ), 9 );
			add_action( 'admin_menu', array( $this, 'admin_menu_page' ) );
		}

		public function admin_menu_page()
		{
			add_menu_page(
				__('Phone Book', 'phone-book'),
				__('Phone Book', 'phone-book'),
				'edit_posts',
				'phone-book',
				array( $this, 'admin_menu_page_callback' ),
				'dashicons-phone',
				3
			);
		}

		public function admin_menu_page_callback()
		{
			
		}

		/**
		 * Load plugin classes
		 */
		public function load_classes()
		{
			$this->contacts	= include_once( PHONE_BOOK_PLUGIN_DIR.'includes/classes/Contacts.php' );
			$this->settings	= include_once( PHONE_BOOK_PLUGIN_DIR.'includes/classes/Settings.php' );
			
			include_once( PHONE_BOOK_PLUGIN_DIR.'includes/Functions.php' );
		}

	}

}