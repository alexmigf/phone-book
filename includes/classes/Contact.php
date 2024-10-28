<?php
/**
 * Contact class
 *
 * @since 1.0.0
 */

namespace Phone_Book\Classes;

use Alexmigf\Forma;

defined( 'ABSPATH' ) || die();

if ( ! class_exists( '\\Phone_Book\\Classes\\Contact' ) ) {

	class Contact {

		public $database;
		protected static $_instance = null;
		
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		protected function __construct() {
			Phone_Book()->settings_api->set_submenu(
				__( 'New contact', Phone_Book()->slug ),
				__( 'New contact', Phone_Book()->slug ),
				Phone_Book()->slug.'_new-contact',
				[ $this, 'new_contact_callback' ]
			);
			Phone_Book()->settings_api->set_submenu(
				__( 'Edit contact', Phone_Book()->slug ),
				__( 'Edit contact', Phone_Book()->slug ),
				Phone_Book()->slug.'_edit-contact',
				[ $this, 'edit_contact_callback' ],
				null,
				false
			);
			Phone_Book()->settings_api->set_submenu(
				__( 'Delete contact', Phone_Book()->slug ),
				__( 'Delete contact', Phone_Book()->slug ),
				Phone_Book()->slug.'_delete-contact',
				[ $this, 'delete_contact_callback' ],
				null,
				false
			);			
		}
		
		public function new_contact_callback() {
			$forma = new Forma(
				'new-contact',
				[
					'title'    => '',
					'callback' => [ $this, 'new_contact_process_callback' ],
					'nonce'    => true,
				]
			);
			$forma->add_section( 'new_contact' );
			$forma->add_fields( $this->contact_forma_fields(), 'new_contact' );
			$forma->render();
		}
		
		public function new_contact_process_callback( $request ) {
			$output = false;
			$data   = [];
			
			foreach ( $this->contact_forma_fields() as $field ) {
				if ( isset( $request[$field['id']] ) ) {
					if ( $field['id'] == 'birthday' ) {
						$data[$field['id']] = date( 'Y-m-d H:i:s', strtotime( esc_attr( $request[$field['id']] ) ) );
					} else {
						$data[$field['id']] = esc_attr( $request[$field['id']] );
					}
				}
				$data['date_created'] = $data['date_modified'] = date( 'Y-m-d H:i:s', time() );
			}
			
			if ( ! empty( $data ) ) {
				$output = $this->database->create_entry( $data );	
			}
			
			return $output;
		}
		
		public function edit_contact_callback() {
			if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'edit_contact' ) ) {
				return;
			}
			
			if ( ! isset( $_REQUEST['action'] ) && ! isset( $_REQUEST['id'] ) && $_REQUEST['action'] != 'edit' ) {
				return;
			}

			$contact_id = intval( esc_attr( $_REQUEST['id'] ) );
			$contact    = $this->database->get_entries( [ 'id' => $contact_id ] );
			$contact    = reset( $contact );
			
			if ( ! is_object( $contact ) ) {
				return;
			}

			$data = [];
			foreach ( $this->contact_forma_fields() as $field ) {
				$data[$field['id']] = $contact->{$field['id']};
			}
			
			if ( empty( $data ) ) {
				return;
			}
			
			$forma = new Forma(
				'edit-contact',
				[
					'title'       => '',
					'callback'    => [ $this, 'edit_contact_process_callback' ],
					'nonce'       => true,
					'button_text' => __( 'Save', Phone_Book()->slug ),
				]
			);
			$forma->add_section( 'edit_contact' );
			$forma->add_fields( $this->contact_forma_fields( $data ), 'edit_contact' );
			$forma->add_hidden_field( 'id', $contact->id );
			$forma->render();
		}
		
		public function edit_contact_process_callback( $request ) {
			$output = false;
			$data   = [];
			
			foreach ( $this->contact_forma_fields() as $field ) {
				if ( isset( $request[$field['id']] ) ) {
					if ( $field['id'] == 'birthday' ) {
						$data[$field['id']] = date( 'Y-m-d H:i:s', strtotime( esc_attr( $request[$field['id']] ) ) );
					} else {
						$data[$field['id']] = esc_attr( $request[$field['id']] );
					}
				}
				$data['date_modified'] = date( 'Y-m-d H:i:s', time() );
			}
			
			if ( ! empty( $data ) && ! empty( $request['id'] ) ) {
				$where = [
					'id' => $request['id'],
				];
				$output = $this->database->update_entry( $data, $where );
			}

			return $output;
		}
		
		public function delete_contact_callback() {
			if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'delete_contact' ) ) {
				return;
			}
			
			if ( ! isset( $_REQUEST['action'] ) && ! isset( $_REQUEST['id'] ) && $_REQUEST['action'] != 'delete' ) {
				return;
			}

			$contact_id = intval( esc_attr( $_REQUEST['id'] ) );
			$contact    = $this->database->get_entries( [ 'id' => $contact_id ] );
			$contact    = reset( $contact );
			
			if ( ! is_object( $contact ) ) {
				return;
			}
			
			$forma = new Forma(
				'delete-contact',
				[
					'title'        => "{$contact->first_name} {$contact->last_name} (#{$contact->id})",
					'callback'     => [ $this, 'delete_contact_process_callback' ],
					'nonce'        => true,
					'button_text'  => __( 'Delete', Phone_Book()->slug ),
					'redirect_uri' => admin_url( 'admin.php?page='.Phone_Book()->slug ),
				]
			);
			$forma->add_hidden_field( 'id', $contact->id );
			$forma->render();
		}
		
		public function delete_contact_process_callback( $request ) {
			$output = false;
			if ( ! empty( $request['id'] ) ) {
				$output = $this->database->delete_entry( [ 'id' => $request['id'] ] );
			}
			return $output;
		}
		
		public function contact_forma_fields( $data = [] ) {
			return [
				[
					'type'     => 'text',
					'id'       => 'first_name',
					'label'    => __( 'First name', Phone_Book()->slug ),
					'value'    => isset( $data['first_name'] ) ? $data['first_name'] : '',
					'required' => true,
				],
				[
					'type'     => 'text',
					'id'       => 'last_name',
					'label'    => __( 'Last name', Phone_Book()->slug ),
					'value'    => isset( $data['last_name'] ) ? $data['last_name'] : '',
					'required' => true,
				],
				[
					'type'     => 'text',
					'id'       => 'company',
					'label'    => __( 'Company', Phone_Book()->slug ),
					'value'    => isset( $data['company'] ) ? $data['company'] : '',
					'required' => false,
				],
				[
					'type'     => 'text',
					'id'       => 'position',
					'label'    => __( 'Position', Phone_Book()->slug ),
					'value'    => isset( $data['position'] ) ? $data['position'] : '',
					'required' => false,
				],
				[
					'type'     => 'text',
					'id'       => 'department',
					'label'    => __( 'Department', Phone_Book()->slug ),
					'value'    => isset( $data['department'] ) ? $data['department'] : '',
					'required' => false,
				],
				[
					'type'     => 'email',
					'id'       => 'email',
					'label'    => __( 'Email', Phone_Book()->slug ),
					'value'    => isset( $data['email'] ) ? $data['email'] : '',
					'required' => false,
				],
				[
					'type'     => 'select',
					'id'       => 'country_code',
					'label'    => __( 'Country', Phone_Book()->slug ),
					'options'  => Phone_Book()->countryCodes->get_countries(),
					'current'  => isset( $data['country_code'] ) ? $data['country_code'] : '',
					'required' => false,
					'desc'     => __( 'Required to get the dial country code.', Phone_Book()->slug ),
				],
				[
					'type'     => 'tel',
					'id'       => 'phone_number',
					'label'    => __( 'Phone number', Phone_Book()->slug ),
					'value'    => isset( $data['phone_number'] ) ? $data['phone_number'] : '',
					'required' => false,
				],
				[
					'type'     => 'textarea',
					'id'       => 'notes',
					'label'    => __( 'Notes', Phone_Book()->slug ),
					'value'    => isset( $data['notes'] ) ? $data['notes'] : '',
					'required' => false,
				],
				[
					'type'     => 'date',
					'id'       => 'birthday',
					'label'    => __( 'Birthday', Phone_Book()->slug ),
					'value'    => isset( $data['birthday'] ) ? $data['birthday'] : '',
					'required' => false,
				],
				[
					'type'     => 'url',
					'id'       => 'website',
					'label'    => __( 'Website', Phone_Book()->slug ),
					'value'    => isset( $data['website'] ) ? $data['website'] : '',
					'required' => false,
				],
			];
		}

	}

}

return Contact::instance();