<?php
/**
 * Database class
 *
 * @since 1.0.0
 */

namespace Phone_Book\Classes;

defined( 'ABSPATH' ) || die();

if( ! class_exists( '\\Phone_Book\\Classes\\Database' ) ) {

	class Database extends \Phone_Book\Classes\Base
	{

		public $wpdb;
		public $prefix;
		public $mysql_version;
		public $charset;

		protected function __construct()
		{
			global $wpdb;
			$this->wpdb				= $wpdb;
			$this->prefix			= $wpdb->prefix;
			$this->mysql_version	= $wpdb->db_version();
			$this->charset			= $wpdb->get_charset_collate();
		}

	}

}