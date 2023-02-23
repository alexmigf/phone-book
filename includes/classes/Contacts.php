<?php
/**
 * Contacts class
 *
 * @since 1.0.0
 */

namespace Phone_Book\Classes;

defined( 'ABSPATH' ) || die();

if ( ! class_exists( '\\Phone_Book\\Classes\\Contacts' ) ) {

	class Contacts {

		public           $slug      = 'contacts';
		public           $database;
		public           $contact;
		protected static $_instance = null;
		
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		protected function __construct() {
			$this->database = Database::instance();
			$this->database->set_name( $this->slug );
			$this->database->set_date_keys( [ 'date_created', 'date_modified', 'birthday' ] );

			$this->create_db_table();

			Phone_Book()->settings_api->set_submenu(
				__( 'Contacts', Phone_Book()->slug ),
				__( 'Contacts', Phone_Book()->slug ),
				Phone_Book()->slug
			);
			
			$this->contact = Contact::instance();
			$this->contact->database = $this->database;
		}

		private function create_db_table() {
			// if we have the table already finish here
			if ( $this->database->table_exists() ) {
				return;
			}

			// attempt to create the table
			$sql  = "CREATE TABLE {$this->database->table_name} (";
			$sql .= "id bigint(20) NOT NULL AUTO_INCREMENT,";
			$sql .= "date_created datetime DEFAULT '1000-01-01 00:00:00' NOT NULL,";
			$sql .= "date_modified datetime DEFAULT '1000-01-01 00:00:00' NULL,";
			$sql .=	"first_name varchar(200) NULL,";
			$sql .=	"last_name varchar(200) NULL,";
			$sql .=	"company varchar(200) NULL,";
			$sql .=	"position varchar(200) NULL,";
			$sql .=	"department varchar(200) NULL,";
			$sql .=	"email varchar(200) NULL,";
			$sql .=	"country_code varchar(200) NULL,";
			$sql .=	"phone_number varchar(200) NULL,";
			$sql .=	"notes longtext NULL,";
			$sql .= "birthday datetime DEFAULT '1000-01-01 00:00:00' NULL,";
			$sql .=	"website varchar(200) NULL,";
			$sql .=	"PRIMARY KEY  (id)";
			$sql .= ") {$this->database->charset};";

			// result
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql );

			$this->database->catch_object_errors();

			return;
		}

	}

}

return Contacts::instance();