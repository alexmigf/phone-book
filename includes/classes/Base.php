<?php
/**
 * Base abstract class
 *
 * @since 1.0.0
 */

namespace Phone_Book\Classes;

defined( 'ABSPATH' ) || die();

if( ! class_exists( '\\Phone_Book\\Classes\\Base' ) ) {

	abstract class Base
	{
		
		protected static $instances = array();

		abstract protected function __construct();

		public static function instance() {
			$class = get_called_class();
			if( ! array_key_exists( $class, self::$instances ) ) {
				self::$instances[$class] = new $class();
			}
			return self::$instances[$class];
		}

	}

}