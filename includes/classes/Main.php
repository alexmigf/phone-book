<?php
/**
 * Main class
 *
 * @since 1.0.0
 */

namespace Phone_Book\Classes;

use Eighteen73\SettingsApi\SettingsApi;

defined( 'ABSPATH' ) || die();

if ( ! class_exists( '\\Phone_Book\\Classes\\Main' ) ) {

	class Main {

		public           $logger;
		public           $countryCodes;
		public           $contacts;
		public           $settings;
		public           $import;
		public           $export;
		public           $settings_api;
		public           $slug;
		protected static $_instance = null;
		
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		protected function __construct() {
			$this->slug = 'phone-book';
			
			$this->settings_api = new SettingsApi(
				__( 'Contacts', $this->slug ),
				__( 'Phone Book', $this->slug ),
				'edit_posts',
				$this->slug,
				[ $this, 'contacts_list_callback' ],
				3,
				true,
				'dashicons-phone'
			);
			
			add_action( 'plugins_loaded', [ $this, 'load_classes' ], 9 );
			add_action( 'admin_enqueue_scripts', [ $this, 'load_admin_assets' ] );
		}
		
		/**
		 * Loads contacts list
		 */
		public function contacts_list_callback() {
			$post_type    = ! empty( $_REQUEST['post_type'] ) ? esc_attr( $_REQUEST['post_type'] ) : 'contact';
			$contact_list = ContactsList::instance();
			include_once( PHONE_BOOK_PLUGIN_TEMPLATES_DIR . 'ContactsList.php' );
		}

		/**
		 * Load plugin classes
		 */
		public function load_classes() {
			$this->logger	    = include_once( PHONE_BOOK_PLUGIN_DIR.'includes/classes/Logger.php' );
			$this->countryCodes	= include_once( PHONE_BOOK_PLUGIN_DIR.'includes/classes/CountryCodes.php' );
			$this->contacts	    = include_once( PHONE_BOOK_PLUGIN_DIR.'includes/classes/Contacts.php' );
			$this->settings	    = include_once( PHONE_BOOK_PLUGIN_DIR.'includes/classes/Settings.php' );
			$this->import	    = include_once( PHONE_BOOK_PLUGIN_DIR.'includes/classes/Import.php' );
			$this->export	    = include_once( PHONE_BOOK_PLUGIN_DIR.'includes/classes/Export.php' );
			
			include_once( PHONE_BOOK_PLUGIN_DIR.'includes/Functions.php' );
		}
		
		public function load_admin_assets() {
			if ( is_admin() ) {
				wp_enqueue_style( Phone_Book()->slug.'-forma', PHONE_BOOK_PLUGIN_URL . 'assets/css/forma.css', [], PHONE_BOOK_VERSION );
				wp_enqueue_style( Phone_Book()->slug.'-settings', PHONE_BOOK_PLUGIN_URL . 'assets/css/settings.css', [], PHONE_BOOK_VERSION );
			}
		}

	}

}