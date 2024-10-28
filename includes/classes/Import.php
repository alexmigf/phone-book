<?php
/**
 * Import class
 *
 * @since 1.0.0
 */

namespace Phone_Book\Classes;

use Alexmigf\Forma;

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
				Phone_Book()->slug.'_import',
				[ $this, 'import_contacts_callback' ]
			);
		}
		
		public function import_contacts_callback() {
			$forma = new Forma(
				'import_contacts',
				[
					'title'       => __( 'Contacts', Phone_Book()->slug ),
					'enctype'     => 'multipart/form-data',
					'callback'    => [ $this, 'import_contacts_process_callback' ],
					'nonce'       => true,
					'button_text' => __( 'Import', Phone_Book()->slug ),
				]
			);
			$forma->add_field( [
				'type'     => 'file',
				'id'       => 'csv_file',
				'label'    => __( 'CSV File', Phone_Book()->slug ),
				'accept'   => '.csv',
				'required' => false,
			] );
			$forma->render();
		}
		
		public function import_contacts_process_callback( $request ) {
			if ( ! empty( $_FILES['csv_file'] ) && isset( $_FILES['csv_file']['tmp_name'] ) ) {
				$file = fopen( $_FILES['csv_file']['tmp_name'], 'r' );
				if ( false === $file ) {
					Phone_Book()->logger->log( 'Failed to open uploaded CSV file!' );
					return false;
				}
			}
			
			$csv_specs = phone_book_csv_specs();
			$batch     = phone_book_csv_import_batch_number();
			$header    = fgetcsv( $file, null, $csv_specs['delimiter'], $csv_specs['enclosure'], $csv_specs['escape'] );
			
			if ( count( phone_book_csv_columns() ) === count( $header ) ) {
				$buffer = [];
				while ( ( $row = fgetcsv( $file, null, $csv_specs['delimiter'], $csv_specs['enclosure'], $csv_specs['escape'] ) ) !== false ) {
					$buffer[] = $row;
					if ( count( $buffer ) >= $batch ) {
						$this->parse_csv_rows( $buffer );
						$buffer = [];
					}
				}
				
				if ( ! empty( $buffer ) ) {
					$this->parse_csv_rows( $buffer );
				}
				
				return fclose( $file );	
			} else {
				Phone_Book()->logger->log( 'The columns in the CSV file do not match the defaults.' );
				return false;
			}
		}
		
		public function parse_csv_rows( $rows ) {
			foreach ( $rows as $row ) {
				$data         = [];
				$country_code = '';
				$duplicate    = false;
				
				foreach ( phone_book_csv_columns() as $key => $column ) {
					switch ( $column ) {
						default:
						case 'first_name':
						case 'last_name':
						case 'company':
						case 'position':
						case 'department':
							$data[$column] = sanitize_text_field( $row[$key] );
							break;
						case 'email':
							$email    = sanitize_email( $row[$key] );
							$contacts = Phone_Book()->contacts->database->get_entries( [ 'email' => $email ] );
							if ( empty( $contacts ) ) {
								$data[$column] = $email;
							} else {
								$duplicate = true;
							}
							break;
						case 'country_code':
							$code = esc_attr( $row[$key] );
							if ( empty( $country_code ) && array_key_exists( $code, Phone_Book()->countryCodes->get_countries() ) ) {
								$country_code = $code;
							}
							break;
						case 'phone_number':
							$phone = esc_attr( $row[$key] );
							foreach ( Phone_Book()->countryCodes->get_dial_codes() as $code => $dial ) {
								if ( strpos( $phone, $dial ) !== false ) {
									$phone         = str_replace( $dial, '', $phone );
									$country_code  = $code;
									break;
								}
							}
							if ( empty( $country_code ) ) {
								$phone = preg_replace( '/[^0-9]/', '', $phone );
							}
							$contacts = Phone_Book()->contacts->database->get_entries( [ 'phone_number' => $phone ] );
							if ( empty( $contacts ) ) {
								$data[$column] = $phone;
							} else {
								$duplicate = true;
							}
							break;
						case 'birthday':
							$birthday = esc_attr( $row[$key] );
							if ( ! empty( $birthday ) ) {
								$data[$column] = date( 'Y-m-d H:i:s', strtotime( $birthday ) );
							} else {
								$data[$column] = '';
							}
							break;
						case 'website':
							$data[$column] = sanitize_url( $row[$key] );
							break;
					}
				}
				
				// don't store if duplicate
				if ( $duplicate ) {
					continue;
				}
				
				// don't store if empty email and phone
				if ( empty( $data['email'] ) && empty( $data['phone_number'] ) && apply_filters( 'phone_book_csv_import_disallow_empty_email_and_phone', true ) ) {
					continue;
				}
				
				$data['country_code'] = $country_code;
				$data['date_created'] = $data['date_modified'] = date( 'Y-m-d H:i:s', time() );
				$data = apply_filters( 'phone_book_csv_row_data', $data, $row );
					
				if ( ! empty( $data ) ) {
					Phone_Book()->contacts->database->create_entry( $data );	
				} else {
					Phone_Book()->logger->log( 'CSV row data is empty!' );
				}
			}
		}

	}

}

return Import::instance();