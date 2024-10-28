<?php
/**
 * Contacts List class
 *
 * @since 1.0.0
 */

namespace Phone_Book\Classes;

defined( 'ABSPATH' ) || die();

if ( ! class_exists( '\\Phone_Book\\Classes\\ContactsList' ) ) {

	class ContactsList extends \WP_List_Table {
		
		protected static $_instance = null;
		
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		protected function __construct() {					
			parent::__construct(
				[
					'singular' => 'contact',
					'plural'   => 'contacts',
					'ajax'     => false,
				]
			);
		}

		public function column_default( $item, $column_name ) {
			if ( ! is_object( $item ) ) {
				return;
			}
			
			switch ( $column_name ) {
				case 'first_name':
					echo $item->first_name;
					break;
				case 'last_name':
					echo $item->last_name;
					break;
				case 'company':
					echo $item->company;
					break;
				case 'email':
					if ( ! empty( $item->email ) ) {
						echo '<a href="mailto:'.$item->email.'">' . $item->email . '</a>';
					}
					break;
				case 'phone_number':
					$dial_codes = Phone_Book()->countryCodes->get_dial_codes();
					if ( ! empty( $item->phone_number ) ) {
						if ( ! empty( $item->country_code ) && isset( $dial_codes[$item->country_code] ) && substr( $item->phone_number, 0, 1 ) !== "+" && substr( $item->phone_number, 0, 2 ) !== "00" ) {
							$phone_number = $dial_codes[$item->country_code] . $item->phone_number;
						} else {
							$phone_number = $item->phone_number;
						}
						echo '<a href="tel:'.$phone_number.'">' . $phone_number . '</a>';
					}
					break;
				case 'actions':
					foreach ( [ 'edit', 'delete' ] as $type ) {
						$title = $type === 'edit' ? __( 'Edit', Phone_Book()->slug ) : __( 'Delete', Phone_Book()->slug );
						$class = $type === 'edit' ? "button button-primary {$type}" : "button {$type}";
						$url   = admin_url( 'admin.php?page='.Phone_Book()->slug.'_'.$type.'-contact&action='.$type.'&id=' . $item->id );
						echo '<a href="'.esc_url( add_query_arg( '_wpnonce', wp_create_nonce( "{$type}_contact" ), $url ) ).'" class="'.$class.'">'.$title.'</a>';
					}
					break;
				default:
					return;
			}
		}

		public function get_columns() {
			$columns = [
				'first_name'   => __( 'First name', Phone_Book()->slug ),
				'last_name'    => __( 'Last name', Phone_Book()->slug ),
				'company'      => __( 'Company', Phone_Book()->slug ),
				'email'        => __( 'Email', Phone_Book()->slug ),
				'phone_number' => __( 'Phone number', Phone_Book()->slug ),
				'actions'      => '',
			];

			return apply_filters( 'phone_book_contact_list_columns', $columns );
		}
		
		public function get_sortable_columns() {
			$sortable = [
				'first_name' => [ 'first_name', true ],
				'last_name'  => [ 'last_name', true ],
				'company'    => [ 'company', true ],
				'email'      => [ 'email', true ],
			];

			return apply_filters( 'phone_book_contact_list_sortable_columns', $sortable );
		}
		
		public function process_bulk_action() {
			// TODO: bulk edit and delete
		}

		public function prepare_items() {
			$settings     = Phone_Book()->settings->get_data();
			$per_page     = ! empty( $settings['results_per_page'] ) ? intval( $settings['results_per_page'] ) : 20;
			$search       = ! empty( $_GET['s'] ) ? esc_attr( $_GET['s'] ) : false;
			$search_types = [ 'first_name', 'last_name', 'company', 'email', 'phone' ];
			$columns      = $this->get_columns();
			$hidden       = [];
			$sortable     = $this->get_sortable_columns();
			$current_page = $this->get_pagenum();
			
			$this->_column_headers = [ $columns, $hidden, $sortable ];

			$this->process_bulk_action();
			
			$args = [
				'limit'    => $per_page,
				'offset'   => $per_page * ( $current_page - 1 ),
				'order_by' => 'date_created',
				'order'    => 'DESC',
			];
			
			if ( ! empty( $search ) ) {
				foreach ( $search_types as $type ) {
					if ( strpos( $search, "{$type}:" ) !== false ) {
						$args[$type] = trim( str_replace( "{$type}:", '', $search ) );
					}
				}
			}
			
			$this->items = Phone_Book()->contacts->database->get_entries( $args );
			unset( $args['limit'] );
			unset( $args['offset'] );
			$total_items = Phone_Book()->contacts->database->get_entries( $args );
			
			$this->set_pagination_args(
				[
					'total_items' => count( $total_items ),
					'per_page'    => $per_page,
					'total_pages' => ceil( count( $total_items ) / $per_page ),
				]
			);
		}

		public function no_items() {
			_e( 'No contacts yet! Please add one.', Phone_Book()->slug );
		}

	}

}