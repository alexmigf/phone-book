<?php
/**
 * Settings class
 *
 * @since 1.0.0
 */

namespace Phone_Book\Classes;

defined( 'ABSPATH' ) || die();

if ( ! class_exists( '\\Phone_Book\\Classes\\Settings' ) ) {

	class Settings {

		public $settings;
		public $slug;
		protected static $_instance = null;
		
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		protected function __construct() {
			$this->slug = Phone_Book()->slug.'_settings';
			
			Phone_Book()->settings_api->set_submenu(
				__( 'Settings', Phone_Book()->slug ),
				__( 'Settings', Phone_Book()->slug ),
				$this->slug
			);
			
			Phone_Book()->settings_api->add_section(
				[
					'id'      => Phone_Book()->slug.'-settings',
					'title'   => __( 'Settings', Phone_Book()->slug ),
					'submenu' => $this->slug,
				]
			);
			
			Phone_Book()->settings_api->add_field(
				Phone_Book()->slug.'-settings',
				[
					'id'      => 'results_per_page',
					'type'    => 'number',
					'name'    => __( 'Results per page', Phone_Book()->slug ),
					'desc'    => __( 'Number of list results per page.', Phone_Book()->slug ),
					'default' => 20,
				]
			);
		}
		
		public function get_data() {
			return get_option( Phone_Book()->slug.'-settings' );
		}

	}

}

return Settings::instance();