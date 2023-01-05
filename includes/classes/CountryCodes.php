<?php
/**
 * CountryCodes class
 *
 * @since 1.0.0
 */

namespace Phone_Book\Classes;

use megastruktur\PhoneCountryCodes;

defined( 'ABSPATH' ) || die();

if ( ! class_exists( '\\Phone_Book\\Classes\\CountryCodes' ) ) {

	class CountryCodes {

		protected static $_instance = null;
		
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		protected function __construct() {
			
		}
		
		public function get_countries() {
			$countries = [];
			foreach ( PhoneCountryCodes::getCodesFullList() as $countryCode ) {
				$countries[$countryCode['code']] = $countryCode['name'] . " ({$countryCode['dial_code']})";
			}
			return $countries;
		}
		
		public function get_dial_codes() {
			$phoneCodes = [];
			foreach ( PhoneCountryCodes::getCodesFullList() as $countryCode ) {
				$phoneCodes[$countryCode['code']] = $countryCode['dial_code'];
			}
			return $phoneCodes;
		}

	}

}

return CountryCodes::instance();