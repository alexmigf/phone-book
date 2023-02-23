<?php
/**
 * Database class
 *
 * @since 1.0.0
 */

namespace Phone_Book\Classes;

defined( 'ABSPATH' ) || die();

if ( ! class_exists( '\\Phone_Book\\Classes\\Database' ) ) {

	class Database {

		public           $wpdb;
		public           $prefix;
		public           $table_name;
		public           $mysql_version;
		public           $charset;
		public           $date_keys      = [];
		public           $special_keys   = [ 'search', 'limit', 'offset', 'order', 'order_by' ];
		protected static $_instance      = null;
		
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		protected function __construct() {
			global $wpdb;
			$this->wpdb          = $wpdb;
			$this->prefix        = $wpdb->prefix;
			$this->mysql_version = $wpdb->db_version();
			$this->charset       = $wpdb->get_charset_collate();
		}

		public function set_name( $name ) {
			if ( empty( $name ) ) {
				return;
			}

			$this->table_name = $this->prefix . str_replace( '-', '_', PHONE_BOOK_PLUGIN_SLUG . "_{$name}" );
		}

		public function table_exists() {
			if ( $this->wpdb->get_var( $this->wpdb->prepare( "SHOW TABLES LIKE %s", $this->table_name ) ) !== $this->table_name ) {
				return false;
			} else {
				return true;
			}
		}

		public function create_entry( $data = [] ) {
			if ( is_array( $data ) ) {
				return $this->wpdb->insert(
					$this->table_name,
					$data
				);
			} else {
				return false;
			}
		}

		public function update_entry( $data = [], $where = [] ) {
			if ( is_array( $data ) && is_array( $where ) ) {
				return $this->wpdb->update(
					$this->table_name,
					$data,
					$where
				);
			} else {
				return false;
			}
		}

		public function delete_entry( $where = [] ) {
			if ( is_array( $where ) ) {
				return $this->wpdb->delete(
					$this->table_name,
					$where
				);
			} else {
				return false;
			}
		}

		public function get_entries( $args ) {
			if ( empty( $args ) || ! is_array( $args ) ) {
				return null;
			}

			// parse dates
			if ( ! empty( $this->date_keys ) ) {
				foreach ( $this->date_keys as $date ) {
					$args = ! empty( $args[$date] ) ? $this->parse_date_for_db_query( $args[$date], $date, $args ) : $args;
				}
			}
			
			// select
			if ( ! empty( $args['select'] ) ) {
				$select = esc_attr( $args['select'] );
				unset( $args['select'] );
			} else {
				$select = '*';
			}

			$sql         = "SELECT {$select} FROM {$this->table_name}";
			$sql_params  = [];
			$c           = 0;
			$next_clause = 'WHERE';

			foreach ( $args as $key => $value ) {
				if ( empty( $value ) ) {
					continue;
				}

				if ( in_array( $key, $this->special_keys ) ) {
					continue;
				}

				$key = sanitize_text_field( $key );

				// date args
				if ( $key == 'date_query' && is_array( $value ) ) {
					foreach ( $value as $date ) {
						$inclusive = ! empty( $date['inclusive'] ) ? '=' : '';
						if ( ! empty( $date['column'] ) ) {
							$date_column = sanitize_text_field( $date['column'] );
							$sql        .= " {$next_clause} {$date_column}";
							if ( ! empty( $date['after'] ) && ! empty( $date['before'] ) ) {
								$sql         .= " BETWEEN '%s' AND '%s'";
								$sql_params[] = $date['after'];
								$sql_params[] = $date['before'];
							} elseif ( ! empty( $date['after'] ) ) {
								$sql         .= " >{$inclusive} '%s'";
								$sql_params[] = $date['after'];
							} elseif ( ! empty( $date['before'] ) ) {
								$sql         .= " <{$inclusive} '%s'";
								$sql_params[] = $date['before'];
							} elseif ( ! empty( $date['equal'] ) ) {
								$sql         .= " = '%s'";
								$sql_params[] = $date['equal'];
							}
						}
					}
				// array args
				} elseif ( is_array( $value ) ) {
					$output = [];
					$value  = array_walk_recursive( $value, function( $v ) use ( &$output ) {
						$output[] = is_null( $v ) ? '' : (string) $v;
					} );
					$separated_comma = "('" . implode( "','", $output ) . "')";
					$sql            .= " {$next_clause} {$key} IN {$separated_comma}";
				// string args
				} elseif ( is_string( $value ) ) {
					$sql         .= " {$next_clause} {$key} = '%s'";
					$sql_params[] = $value;
				// integer args
				} elseif ( is_int( $value ) ) {
					$sql         .= " {$next_clause} {$key} = '%d'";
					$sql_params[] = $value;
				}

				$c++;
				if ( $c == 1 ) {
					$next_clause = 'AND';
				}
			}

			// prepare SQL query
			$sql  = $this->prepare_sql_query( $sql, $sql_params );
			// special clauses
			$sql .= $this->get_special_clauses( $next_clause, $args );

			return $this->wpdb->get_results( $sql );
		}

		public function set_date_keys( $keys ) {
			$this->date_keys = ! empty( $keys ) && is_array( $keys ) ? $keys : $this->date_keys;
		}

		public function get_special_clauses( $next_clause, $args ) {
			$sql        = '';
			$sql_params = [];

			// search
			if ( ! empty( $args['search'] ) && is_array( $args['search'] ) ) {
				if ( ! empty( $args['search']['keys'] ) && is_array( $args['search']['keys'] ) && ! empty( $args['search']['term'] ) ) {
					if ( ! empty( $args['search']['type'] ) ) {
						switch ( $args['search']['type'] ) {
							case 'exact':
								$operator = '=';
								$term     = $args['search']['term'];
								break;
							case 'pattern':
								$operator = 'LIKE';
								$term     = "%{$args['search']['term']}%";
								break;
						}
					} else {
						$operator = '=';
						$term     = $args['search']['term'];
					}

					$search_clause = $next_clause;
					$c             = 0;
					foreach ( $args['search']['keys'] as $key ) {
						$key          = sanitize_text_field( $key );
						$sql         .= " {$search_clause} {$key} {$operator} '%s'";
						$sql_params[] = $term;

						$c++;
						if ( $c == 1 ) {
							$search_clause = 'OR';
						}
					}
				}
			}

			// orderby/order
			if ( ! empty( $args['order_by'] ) && ! empty( $args['order'] ) ) {
				$order_by = sanitize_text_field( $args['order_by'] );
				$order    = sanitize_text_field( $args['order'] );
				$sql     .= " ORDER BY {$order_by} {$order}";
			}

			// limit/offset
			if ( ! empty( $args['limit'] ) && $args['limit'] > 0 ) {
				$sql         .= " LIMIT %d";
				$sql_params[] = $args['limit'];
				if ( ! empty( $args['offset'] ) && $args['offset'] > 0 ) {
					$sql         .= ", %d";
					$sql_params[] = $args['offset'];
				}
			}

			$sql = $this->prepare_sql_query( $sql, $sql_params );

			return $sql;
		}

		/**
		 * Map a valid date query var to DB arguments.
		 * Valid date formats: YYYY-MM-DD or timestamp, possibly combined with an operator from $valid_operators.
		 * Also accepts a WC_DateTime object.
		 * 
		 * Source: https://github.com/woocommerce/woocommerce/blob/3611d4643791bad87a0d3e6e73e031bb80447417/plugins/woocommerce/includes/data-stores/class-wc-data-store-wp.php#L334
		 *
		 * @since 3.2.0
		 * @param mixed  $query_var A valid date format.
		 * @param string $key meta or db column key.
		 * @param array  $query_args WP_Query args.
		 * @return array Modified $query_args
		 */
		public function parse_date_for_db_query( $query_var, $key, $query_args = [] ) {
			$query_parse_regex = '/([^.<>]*)(>=|<=|>|<|\.\.\.)([^.<>]+)/';
			$valid_operators   = [ '>', '>=', '=', '<=', '<', '...' ];
	
			// YYYY-MM-DD queries have 'day' precision. Timestamp/WC_DateTime queries have 'second' precision.
			$precision = 'second';
	
			$dates    = [];
			$operator = '=';
	
			try {
				// Specific time query with a WC_DateTime.
				if ( is_a( $query_var, 'WC_DateTime' ) ) {
					$dates[] = $query_var;
				} elseif ( is_numeric( $query_var ) ) { // Specific time query with a timestamp.
					$dates[] = new \DateTime( "@{$query_var}", new \DateTimeZone( 'UTC' ) );
				} elseif ( preg_match( $query_parse_regex, $query_var, $sections ) ) { // Query with operators and possible range of dates.
					if ( ! empty( $sections[1] ) ) {
						$dates[] = is_numeric( $sections[1] ) ? new \DateTime( "@{$sections[1]}", new \DateTimeZone( 'UTC' ) ) : getDate( strtotime( $sections[1] ) );
					}
	
					$operator = in_array( $sections[2], $valid_operators, true ) ? $sections[2] : '';
					$dates[]  = is_numeric( $sections[3] ) ? new \DateTime( "@{$sections[3]}", new \DateTimeZone( 'UTC' ) ) : getDate( strtotime( $sections[3] ) );
	
					if ( ! is_numeric( $sections[1] ) && ! is_numeric( $sections[3] ) ) {
						$precision = 'day';
					}
				} else { // Specific time query with a string.
					$dates[]   = getDate( strtotime( $query_var ) );
					$precision = 'day';
				}
			} catch ( \Exception $e ) {
				return $query_args;
			}
	
			// Check for valid inputs.
			if ( ! $operator || empty( $dates ) || ( '...' === $operator && count( $dates ) < 2 ) ) {
				return $query_args;
			}
	
			// Build date query for date keys.
			if ( in_array( $key, $this->date_keys ) ) {
				if ( ! isset( $query_args['date_query'] ) ) {
					$query_args['date_query'] = [];
				}
	
				$query_arg = [
					'column'    => 'day' === $precision ? $key : $key,
					'inclusive' => '>' !== $operator && '<' !== $operator,
				];
	
				// Add 'after'/'before' query args.
				$comparisons = [];
				if ( '>' === $operator || '>=' === $operator || '...' === $operator ) {
					$comparisons[] = 'after';
				}
				if ( '<' === $operator || '<=' === $operator || '...' === $operator ) {
					$comparisons[] = 'before';
				}
	
				foreach ( $comparisons as $index => $comparison ) {
					if ( 'day' === $precision ) {
						$time = '00:00:00';
						if ( $comparison == 'before' ) {
							$time = '23:59:59';
						}
						$query_arg[ $comparison ] = gmdate( "Y-m-d {$time}", $dates[ $index ]->getTimestamp() );
					} else {
						$query_arg[ $comparison ] = gmdate( 'Y-m-d H:i:s', $dates[ $index ]->getTimestamp() );
					}
				}
	
				if ( empty( $comparisons ) ) {
					if ( strpos( $query_var, '-' ) === false ) {
						$query_arg['equal']  = gmdate( 'Y-m-d H:i:s', $dates[0]->getTimestamp() );
					} else {
						$query_arg['after']  = gmdate( 'Y-m-d 00:00:00', $dates[0]->getTimestamp() );
						$query_arg['before'] = gmdate( 'Y-m-d 23:59:59', $dates[0]->getTimestamp() );
					}
				}
	
				$query_args['date_query'][] = $query_arg;
				unset( $query_args[$key] );
			}
	
			return $query_args;
		}

		public function prepare_sql_query( $sql, $sql_params = [] ) {
			if ( ! empty( $sql_params ) ) {
				return $this->wpdb->prepare( $sql, $sql_params );
			} else {
				return $sql;
			}
		}

		public function catch_object_errors() {
			global $EZSQL_ERROR;
		
			$errors = [];
		
			// using '$wpdb->queries'
			if ( ! empty( $this->wpdb->queries ) && is_array( $this->wpdb->queries ) ) {
				foreach ( $this->wpdb->queries as $query ) {
					$result = isset( $query['result'] ) ? $query['result'] : null;
		
					if ( is_wp_error( $result ) && is_array( $result->errors ) ) {
						foreach ( $result->errors as $error ) {
							$errors[] = reset( $error );
						}
					}
				}
			} 
		
			// fallback to '$EZSQL_ERROR'
			if ( empty( $errors ) && ! empty( $EZSQL_ERROR ) && is_array( $EZSQL_ERROR ) ) {
				foreach ( $EZSQL_ERROR as $error ) {
					$errors[] = $error['error_str'];
				}
			}
		
			// log errors
			if ( ! empty( $errors ) ) {
				foreach ( $errors as $error_message ) {
					Phone_Book()->logger->log( $error_message );
				}
			}
		
			return $errors;
		}

	}

}