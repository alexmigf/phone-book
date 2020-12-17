<?php
/**
 * Settings class
 *
 * @since 1.0.0
 */

namespace Phone_Book\Classes;

defined( 'ABSPATH' ) || die();

if( ! class_exists( '\\Phone_Book\\Classes\\Settings' ) ) {

	class Settings extends \Phone_Book\Classes\Base
	{

		protected function __construct()
		{
			add_action( 'admin_menu', array( $this, 'admin_menu_page' ) );
		}

		public function admin_menu_page()
		{			
			add_submenu_page(
				'phone-book',
				__( 'Settings', 'phone-book' ),
				__( 'Settings', 'phone-book' ),
				'edit_posts',
				'phone-book-settings',
				array( $this, 'admin_menu_page_callback' ),
			);
		}

		public function admin_menu_page_callback()
		{
			$settings_tabs = apply_filters( 'phone_book_settings_tabs', array (
				'Settings'	=> __('Settings', 'phone-book'),
				'import'	=> __('Import', 'phone-book'),
				'export'	=> __('Export', 'phone-book'),
			));

			$active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'settings';

			// template
			include_once( PHONE_BOOK_PLUGIN_TEMPLATES_DIR.'Settings.php' );
		}

	}

}

return \Phone_Book\Classes\Settings::instance();