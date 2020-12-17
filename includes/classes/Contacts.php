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
		protected $db_table_name;
		protected $_data;
		protected $database;

		protected function __construct( array $data = [] )
		{
			$this->_data			= $data;
			$this->database			= \Phone_Book\Classes\Database::instance();
			$this->db_table_name	= $this->database->prefix . str_replace( '-', '_', PHONE_BOOK_PLUGIN_SLUG . '-contacts' );

			$this->db_table_create();

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

		private function db_table_exists()
		{
			if ( $this->database->wpdb->get_var( $this->database->wpdb->prepare( "SHOW TABLES LIKE %s", $this->db_table_name ) ) !== $this->db_table_name ) {
				return false;
			} else {
				return true;
			}
		}

		private function db_table_create()
		{
			// if we have the table already finish here
			if( $this->db_table_exists() ) return;

			// attempt to create the table
			$sql = "CREATE TABLE {$this->db_table_name} (";
			$sql .= "id bigint(20) NOT NULL AUTO_INCREMENT,";
			$sql .=	"first_name varchar(200) NULL,";
			$sql .=	"last_name varchar(200) NULL,";
			$sql .=	"company varchar(200) NULL,";
			$sql .=	"position varchar(200) NULL,";
			$sql .=	"department varchar(200) NULL,";
			$sql .=	"email varchar(200) NULL,";
			$sql .=	"country_code smallint(5) NULL,";
			$sql .=	"phone_number mediumint(9) NOT NULL,";
			$sql .=	"notes longtext NULL,";
			$sql .=	"tag varchar(200) NULL,";
			$sql .= "birthday datetime NULL,";
			$sql .=	"website varchar(200) NULL,";
			$sql .=	"PRIMARY KEY  (id)";
			$sql .= ") {$this->database->charset};";

			// result
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			$result = dbDelta( $sql );

			// log errors
			$logger = new \WC_Logger();
			$context = array( 'source' => 'phone-book-dbdelta-log' );
			if( $this->database->wpdb->last_error !== '' ) {
				$logger->log( 'error', $this->database->wpdb->last_error, $context );
			}

			return $result;
		}

	}

}

return \Phone_Book\Classes\Contacts::instance();