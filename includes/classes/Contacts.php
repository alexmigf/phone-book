<?php
/**
 * Contacts class
 *
 * @since 1.0.0
 */

namespace Phone_Book\Classes;

defined( 'ABSPATH' ) || die();

if( ! class_exists( '\\Phone_Book\\Classes\\Contacts' ) ) {

	class Contacts extends \Phone_Book\Classes\Base
	{

		protected function __construct()
		{
			add_action( 'admin_menu', array( $this, 'admin_menu_page' ) );
		}

		public function admin_menu_page()
		{			
			add_submenu_page(
				'phone-book',
				__( 'Contacts', 'phone-book' ),
				__( 'Contacts', 'phone-book' ),
				'edit_posts',
				'phone-book',
				array( $this, 'admin_menu_page_callback' ),
			);
			
			add_submenu_page(
				'phone-book',
				__( 'New contact', 'phone-book' ),
				__( 'New contact', 'phone-book' ),
				'edit_posts',
				'phone-book-new-contact',
				array( $this, 'admin_menu_page_callback' ),
			);
		}

		public function admin_menu_page_callback()
		{
			
		}

	}

}

return \Phone_Book\Classes\Contacts::instance();