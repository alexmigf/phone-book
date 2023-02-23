<?php
/**
 * Export class
 *
 * @since 1.0.0
 */

namespace Phone_Book\Classes;

use Alexmigf\Forma;

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
				Phone_Book()->slug.'_export',
				[ $this, 'export_contacts_callback' ]
			);
			
			add_action( 'alexmigf/forma/after/form', [ $this, 'display_exported_files' ] );
		}
		
		public function export_contacts_callback() {
			$forma = new Forma(
				'export_contacts',
				[
					'title'       => __( 'Contacts', Phone_Book()->slug ),
					'callback'    => [ $this, 'export_contacts_process_callback' ],
					'nonce'       => true,
					'button_text' => __( 'Export', Phone_Book()->slug ),
				]
			);
			$forma->render();
		}
		
		public function export_contacts_process_callback( $request ) {
			$filename = sprintf( 'phone_book_contacts-%s.csv', date( 'Y-m-d_H-m-s' ) );
			$file     = fopen( PHONE_BOOK_PLUGIN_EXPORTS_DIR.'contacts/'.$filename, 'w' );
			$contacts = $this->get_contacts();
			return $this->write_file_data( $file, $contacts );
		}
		
		public function get_contacts() {
			$columns = apply_filters( 'phone_book_contacts_export_csv_columns', [
				'first_name',
				'last_name',
				'company',
				'position',
				'department',
				'email',
				'country_code',
				'phone_number',
				'birthday',
				'website',
			] );
			
			$args = [
				'select'   => implode( ', ', $columns ),
				'order_by' => 'date_created',
				'order'    => 'DESC',
			];
			$contacts = Phone_Book()->contacts->database->get_entries( $args );
			if ( empty( $contacts ) ) {
				return [];
			}

			// csv headers
			$first_row = reset( $contacts );
			$headers   = array_keys( (array) $first_row );

			// clean contact rows
			foreach ( $contacts as &$row ) {
				$row = array_values( array_map( 'htmlspecialchars_decode', (array) $row ) );
			}

			// prepend headers
			array_unshift( $contacts, $headers );

			return $contacts;
		}
		
		public function write_file_data( $file, $contacts ) {
			if ( empty( $file ) || empty( $contacts ) ) {
				return false;
			}
			
			$delimiter = apply_filters( 'phone_book_contacts_export_csv_delimiter', "," );
			$enclosure = apply_filters( 'phone_book_contacts_export_csv_enclosure', '"' );
			$escape    = apply_filters( 'phone_book_contacts_export_csv_escape',    "\\" );
			
			// add BOM to fix UTF-8 in Excel: https://www.php.net/manual/de/function.fputcsv.php#121950
			fputs( $file, chr(0xEF) . chr(0xBB) . chr(0xBF) );
			foreach ( $contacts as $row ) {
				fputcsv( $file, (array) $row, $delimiter, $enclosure, $escape );
			}
			
			fclose( $file );
			return $file;
		}
		
		public function display_exported_files() {
			$files   = scandir( PHONE_BOOK_PLUGIN_EXPORTS_DIR.'contacts/', SCANDIR_SORT_DESCENDING );
			$exclude = [
				'..',
				'.',
				'index.php',
			];
			
			$files = array_diff( $files, $exclude );
			if ( empty( $files ) ) {
				return;
			}
			
			echo '<div id="contacts-exported-files">';
			foreach ( $files as $file ) {
				echo '<a href="'.PHONE_BOOK_PLUGIN_URL.'exports/contacts/'.$file.'">'.$file.'</a>';
			}
			echo '</div>';
		}

	}

}

return Export::instance();