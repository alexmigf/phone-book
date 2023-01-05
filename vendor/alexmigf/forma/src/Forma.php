<?php
/**
 * Main Class file for creating a form.
 *
 * @package Alexmigf\Forma
 */

 namespace Alexmigf;

/**
 * Class Forma
 *
 * @package alexmigf\forma
 *
 * @property string     $package
 * @property string     $id
 * @property string     $slug
 * @property array      $args
 * @property array      $sections
 * @property array      $fields
 * @property array      $hidden
 * @property array|null $nonce
 */

class Forma {

	/**
	 * The package name.
	 *
	 * @var string
	 */
	protected $package = 'alexmigf/forma';

	/**
	 * The form ID.
	 *
	 * @var string
	 */
	protected $id;

	/**
	 * The form slug.
	 *
	 * @var string
	 */
	public $slug;

	/**
	 * The form args.
	 *
	 * @var array
	 */
	protected $args = [];

	/**
	 * The form sections.
	 *
	 * @var array
	 */
	protected $sections = [];

	/**
	 * The form fields.
	 *
	 * @var array
	 */
	protected $fields = [];

	/**
	 * The form hidden fields.
	 *
	 * @var array
	 */
	protected $hidden = [];

	/**
	 * The form nonce.
	 *
	 * @var array|null
	 */
	protected $nonce = null;

	/**
	 * Form constructor.
	 *
	 * @param string $id   The ID of the form.
	 * @param array  $args The form options.
	 */
	public function __construct( $id, $args = array() ) {
		$this->id   = ! empty( $id ) ? esc_attr( $id ) : wp_rand( 1, 16 );
		$this->slug = "{$this->package}/{$this->id}";
		$this->args = [
			'title'       => isset( $args['title'] ) ? esc_attr( $args['title'] ) : '',
			'classes'     => isset( $args['classes'] ) ? esc_attr( $args['classes'] ) : '',
			'action'      => isset( $args['action'] ) ? esc_attr( $args['action'] ) : '',
			'method'      => isset( $args['method'] ) && in_array( strtolower( $args['method'] ), [ 'post', 'get' ] ) ? esc_attr( strtolower( $args['method'] ) ) : 'post',
			'callback'    => isset( $args['callback'] ) && is_array( $args['callback'] ) && count( $args['callback'] ) == 2 ? $args['callback'] : null,
			'nonce'       => isset( $args['nonce'] ) && true === $args['nonce'] ? $this->add_nonce_field() : false,
			'button_text' => isset( $args['button_text'] ) ? esc_attr( $args['button_text'] ) : __( 'Send', $this->package ),
		];
	}

	/**
	 * Render the form.
	 */
	public function render() {
		if ( empty( $this->args['action'] ) ) {
			$this->process();
			$this->is_submitted();
		}
		echo $this->build();
	}

	/**
	 * Build the form markup.
	 */
	public function build() {
		ob_start();
		do_action( "{$this->package}/before/render", $this );
		?>
		<div id="<?= "{$this->slug}/form"; ?>" class="<?= "{$this->package}/form" ?>">
			<?php if ( ! empty( $this->args['title'] ) ) : ?>
			<h3><?= $this->args['title']; ?></h3>
			<?php endif; ?>
			<?php do_action( "{$this->package}/before/form", $this ); ?>
			<form id="<?= $this->slug; ?>" class="<?= $this->args['classes']; ?>" action="<?= $this->args['action']; ?>" method="<?= $this->args['method']; ?>">
				<input type="hidden" name="<?= "{$this->slug}/submitted"; ?>" value="1"></input>
				<?php
					do_action( "{$this->package}/before/form/fields", $this );
					$this->build_sections();
					$this->build_orphan_fields();
					$this->build_hidden_fields();
					$this->build_nonce_field();
					$this->build_submit_field();
					do_action( "{$this->package}/after/form/fields", $this );
				?>
			</form>
			<?php do_action( "{$this->package}/after/form", $this ); ?>
		</div>
		<?php
		do_action( "{$this->package}/after/render", $this );
		return ob_get_clean();
	}

	/**
	 * Add a form section
	 *
	 * @param string $id
	 * @param string $class
	 */
	public function add_section( $id, $class = '' ) {
		if ( ! empty( $id ) ) {
			$this->sections[] = [
				'id'    => esc_attr( $id ),
				'class' => esc_attr( $class ),
			];
		}
	}

	/**
	 * Add a form field
	 *
	 * @param array  $field
	 * @param string $section_id
	 */
	public function add_field( $field, $section_id = '' ) {
		if ( ! empty( $field ) ) {
			if ( ! empty( $section_id ) ) {
				$this->fields[$section_id][] = $field;
			} else {
				$this->fields['orphan'][] = $field;
			}
		}
	}

	/**
	 * Add multiple form fields
	 *
	 * @param array  $fields
	 * @param string $section_id
	 */
	public function add_fields( $fields, $section_id = '' ) {
		if ( ! empty( $fields ) ) {
			foreach ( $fields as $field ) {
				$this->add_field( $field, $section_id );
			}
		}
	}

	/**
	 * Add a form hidden field
	 *
	 * @param string $name
	 * @param string $value
	 */
	public function add_hidden_field( $name, $value ) {
		if ( ! empty( $name ) && ! empty( $value ) ) {
			$this->hidden[] = [
				'name'  => esc_attr( $name ),
				'value' => esc_attr( $value ),
			];
		}

	}

	/**
	 * Add a form nonce field
	 *
	 * @param string $action
	 */
	public function add_nonce_field( $action = '' ) {
		$this->nonce = [
			'action' => ! empty( $action ) ? esc_attr( $action ) : $this->id
		];
		return true;
	}

	/**
	 * Display anotice when the form is submitted
	 */
	public function is_submitted() {
		$notice = [];
		foreach ( [ 'error', 'success' ] as $type ) {
			if ( isset( $_REQUEST["{$this->package}/process/{$type}"] ) ) {
				$notice['message'] = sanitize_text_field( $_REQUEST["{$this->package}/process/{$type}"] );
				$notice['type']    = $type;
				break;
			}
		}
		if ( ! empty( $notice ) ) {
			?>
			<div class="notice notice-<?= $notice['type']; ?> inline">
				<p><?= $notice['message']; ?></p>
			</div>
			<?php
			echo "<meta http-equiv='refresh' content='2'>";
		}
	}

	/**
	 * Process the form data
	 */
	protected function process() {
		if ( ! isset( $_REQUEST["{$this->slug}/submitted"] ) ) {
			return;
		}

		if ( empty( $_REQUEST["{$this->package}/nonce"] ) ) {
			$_REQUEST["{$this->package}/process/error"] = __( 'Nonce is missing!', $this->package );
			return;
		}

		if ( ! wp_verify_nonce( $_REQUEST["{$this->package}/nonce"], esc_attr( $this->nonce['action'] ) ) ) {
			$_REQUEST["{$this->package}/process/error"] = __( 'Invalid nonce!', $this->package );
			return;
		}

		if ( ! is_null( $this->args['callback'] ) && is_callable( $this->args['callback'] ) ) {
			$response = call_user_func( $this->args['callback'], stripslashes_deep( $_REQUEST ) );
		} else {
			$response = false;
		}

		if ( $response ) {
			$_REQUEST["{$this->package}/process/success"] = __( 'Form successfully submitted!', $this->package );
		} else {
			$_REQUEST["{$this->package}/process/error"] = __( 'An error occurred while processing the form data.', $this->package );
		}
	}

	/**
	 * Build form sections
	 */
	protected function build_sections() {
		if ( ! empty( $this->sections ) ) {
			foreach ( $this->sections as $section ) {
				echo '<fieldset id="'.$section['id'].'-wrapper" class="section-wrapper '.$section['class'].'">';
				if ( isset( $this->fields[$section['id']] ) ) {
					foreach ( $this->fields[$section['id']] as $field ) {
						$this->build_field( $field );
					}
				}
				echo '</fieldset>';
			}
		}
	}

	/**
	 * Build form orphan fields
	 */
	protected function build_orphan_fields() {
		if ( ! empty( $this->fields['orphan'] ) ) {
			foreach ( $this->fields['orphan'] as $field ) {
				$this->build_field( $field );
			}
		}
	}

	/**
	 * Build form field
	 *
	 * @param array $field
	 */
	protected function build_field( $field ) {
		$build = '';
		if ( isset( $field['type'] ) && in_array( $field['type'], $this->get_field_types() ) ) {
			$callback = "{$field['type']}_field_callback";
			if ( is_callable( [ $this, $callback ] ) ) {
				$build = call_user_func( [ $this, $callback ], $field );
			}
		}
		echo apply_filters( "{$this->package}/build/field", $build, $field, $this );
	}

	/**
	 * Build form hidden fields
	 */
	protected function build_hidden_fields() {
		if ( ! empty( $this->hidden ) ) {
			foreach ( $this->hidden as $hidden ) {
				$field         = $hidden;
				$field['type'] = 'hidden';
				$this->build_field( $field );
			}
		}
	}

	/**
	 * Build form nonce field
	 */
	protected function build_nonce_field() {
		if ( true === $this->args['nonce'] && ! empty( $this->nonce ) ) {
			$field         = $this->nonce;
			$field['type'] = 'nonce';
			$this->build_field( $field );
		}
	}

	/**
	 * Build form submit field
	 */
	protected function build_submit_field() {
		$this->build_field( [
			'type'  => 'submit',
			'value' => $this->args['button_text'],
		] );
	}

	/**
	 * Get field types
	 */
	protected function get_field_types() {
		$field_types = [
			'hidden',
			'nonce',
			'submit',
			'checkbox',
			'tel',
			'number',
			'email',
			'date',
			'select',
			'textarea',
			'url',
			'text',
		];
		return apply_filters( "{$this->package}/field/types", $field_types );
	}

	/**
	 * $field = [
	 *   'type'  => 'hidden', (required)
	 *   'name'  => '',       (required)
	 *   'value' => '',       (required)
	 * ];
	 *
	 * @param array $field
	 */
	protected function hidden_field_callback( $field ) {
		if ( empty( $field ) ) {
			return '';
		}

		extract( $field );

		if ( empty( $name ) || empty( $value ) ) {
			return '';
		}

		return sprintf( '<input type="%1$s" name="%2$s" value="%3$s">', esc_attr( $type ), esc_attr( $name ), esc_attr( $value ) );
	}

	/**
	 * $field = [
	 *   'type'   => 'nonce', (required)
	 *   'action' => '',      (required)
	 *   'name'   => '',      (required)
	 * ];
	 *
	 * @param array $field
	 */
	protected function nonce_field_callback( $field ) {
		if ( empty( $field ) ) {
			return '';
		}

		extract( $field );

		return wp_nonce_field( esc_attr( $action ), "{$this->package}/nonce", true, false );
	}

	/**
	 * $field = [
	 *   'type'  => 'submit', (required)
	 *   'value' => '',
	 * ];
	 *
	 * @param array $field
	 */
	protected function submit_field_callback( $field ) {
		if ( empty( $field ) ) {
			return '';
		}

		extract( $field );

		return sprintf( '<div id="submit-wrapper"><input type="%1$s" id="%1$s" value="%2$s" class="button button-primary"></div>', esc_attr( $type ), esc_attr( $value ) );
	}

	/**
	 * $field = [
	 *   'type'    => 'checkbox', (required)
	 *   'id'      => '',         (required)
	 *   'label'   => '',
	 *   'current' => '',
	 *   'style'   => '',
	 *   'desc'    => '',
	 *   'required => false,
	 * ];
	 *
	 * @param array $field
	 */
	protected function checkbox_field_callback( $field ) {
		if ( empty( $field ) ) {
			return '';
		}

		extract( $field );

		if ( empty( $id ) ) {
			return '';
		}

		$current = ! empty( $current ) ? esc_attr( $current ) : 'off';

		$html = '<div id="'.esc_attr( $id ).'-wrapper" class="field-wrapper">';
		if ( ! empty( $label ) ) {
			$html .= sprintf( '<label for="%1$s">%2$s</label>', esc_attr( $id ), esc_attr( $label ) );
		}
		$html .= sprintf( '<input type="hidden" name="%1$s" value="off" />', esc_attr( $id ) );
		$html .= sprintf(
			'<input type="checkbox" id="%1$s" name="%1$s" value="on" style="%2$s" %3$s %4$s />',
			esc_attr( $id ),
			! empty( $style ) ? esc_attr( $style ) : '',
			checked( $current, 'on', false ),
			! empty( $required ) && true === $required ? 'required' : ''
		);
		$html .= ! empty( $desc ) ? sprintf( '<span class="description">%s</span>', esc_html( $desc ) ) : '';
		$html .= '</div>';

		return $html;
	}

	/**
	 * $field = [
	 *   'type'     => 'tel', (required)
	 *   'id'       => '',    (required)
	 *   'label'    => '',
	 *   'value'    => '',
	 *   'style'    => '',
	 *   'pattern'  => '',
	 *   'required' => false,
	 *   'desc'     => '',
	 * ];
	 *
	 * @param array $field
	 */
	protected function tel_field_callback( $field ) {
		if ( empty( $field ) ) {
			return '';
		}

		extract( $field );

		if ( empty( $id ) ) {
			return '';
		}

		$html = '<div id="'.esc_attr( $id ).'-wrapper" class="field-wrapper">';
		if ( ! empty( $label ) ) {
			$html .= sprintf( '<label for="%1$s">%2$s</label>', esc_attr( $id ), esc_attr( $label ) );
		}
		$html .= sprintf(
			'<input type="tel" id="%1$s" name="%1$s" value="%2$s" style="%3$s" %4$s %5$s />',
			esc_attr( $id ),
			! empty( $value ) ? esc_attr( $value ) : '',
			! empty( $style ) ? esc_attr( $style ) : '',
			! empty( $pattern ) ? 'pattern="'.esc_attr( $pattern ).'"' : '',
			! empty( $required ) && true === $required ? 'required' : ''
		);
		$html .= ! empty( $desc ) ? sprintf( '<span class="description">%s</span>', esc_html( $desc ) ) : '';
		$html .= '</div>';

		return $html;
	}

	/**
	 * $field = [
	 *   'type'     => 'number', (required)
	 *   'id'       => '',       (required)
	 *   'label'    => '',
	 *   'value'    => '',
	 *   'style'    => '',
	 *   'pattern'  => '',
	 *   'required' => false,
	 *   'desc'     => '',
	 * ];
	 *
	 * @param array $field
	 */
	protected function number_field_callback( $field ) {
		if ( empty( $field ) ) {
			return '';
		}

		extract( $field );

		if ( empty( $id ) ) {
			return '';
		}

		$html = '<div id="'.esc_attr( $id ).'-wrapper" class="field-wrapper">';
		if ( ! empty( $label ) ) {
			$html .= sprintf( '<label for="%1$s">%2$s</label>', esc_attr( $id ), esc_attr( $label ) );
		}
		$html .= sprintf(
			'<input type="number" id="%1$s" name="%1$s" value="%2$s" style="%3$s" %4$s %5$s />',
			esc_attr( $id ),
			! empty( $value ) ? esc_attr( $value ) : '',
			! empty( $style ) ? esc_attr( $style ) : '',
			! empty( $pattern ) ? 'pattern="'.esc_attr( $pattern ).'"' : '',
			! empty( $required ) && true === $required ? 'required' : ''
		);
		$html .= ! empty( $desc ) ? sprintf( '<span class="description">%s</span>', esc_html( $desc ) ) : '';
		$html .= '</div>';

		return $html;
	}

	/**
	 * $field = [
	 *   'type'     => 'email', (required)
	 *   'id'       => '',      (required)
	 *   'label'    => '',
	 *   'value'    => '',
	 *   'style'    => '',
	 *   'required' => false,
	 *   'desc'     => '',
	 * ];
	 *
	 * @param array $field
	 */
	protected function email_field_callback( $field ) {
		if ( empty( $field ) ) {
			return '';
		}

		extract( $field );

		if ( empty( $id ) ) {
			return '';
		}

		$html = '<div id="'.esc_attr( $id ).'-wrapper" class="field-wrapper">';
		if ( ! empty( $label ) ) {
			$html .= sprintf( '<label for="%1$s">%2$s</label>', esc_attr( $id ), esc_attr( $label ) );
		}
		$html .= sprintf(
			'<input type="email" id="%1$s" name="%1$s" value="%2$s" style="%3$s" %4$s />',
			esc_attr( $id ),
			! empty( $value ) ? esc_attr( $value ) : '',
			! empty( $style ) ? esc_attr( $style ) : '',
			! empty( $required ) && true === $required ? 'required' : ''
		);
		$html .= ! empty( $desc ) ? sprintf( '<span class="description">%s</span>', esc_html( $desc ) ) : '';
		$html .= '</div>';

		return $html;
	}

	/**
	 * $field = [
	 *   'type'     => 'date',      (required)
	 *   'id'       => '',          (required)
	 *   'label'    => '',
	 *   'value'    => 'yyyy-mm-dd',
	 *   'style'    => '',
	 *   'required' => false,
	 *   'desc'     => '',
	 * ];
	 *
	 * @param array $field
	 */
	protected function date_field_callback( $field ) {
		if ( empty( $field ) ) {
			return '';
		}

		extract( $field );

		if ( empty( $id ) ) {
			return '';
		}

		$html = '<div id="'.esc_attr( $id ).'-wrapper" class="field-wrapper">';
		if ( ! empty( $label ) ) {
			$html .= sprintf( '<label for="%1$s">%2$s</label>', esc_attr( $id ), esc_attr( $label ) );
		}
		$html .= sprintf(
			'<input type="date" id="%1$s" name="%1$s" value="%2$s" style="%3$s" %4$s />',
			esc_attr( $id ),
			! empty( $value ) ? date( 'Y-m-d', strtotime( esc_attr( $value ) ) ) : '',
			! empty( $style ) ? esc_attr( $style ) : '',
			! empty( $required ) && true === $required ? 'required' : ''
		);
		$html .= ! empty( $desc ) ? sprintf( '<span class="description">%s</span>', esc_html( $desc ) ) : '';
		$html .= '</div>';

		return $html;
	}

	/**
	 * $field = [
	 *   'type'     => 'select', (required)
	 *   'id'       => '',       (required)
	 *   'label'    => '',
	 *   'options'  => [],       (accepts callback function)
	 *   'style'    => '',
	 *   'current'  => '',
	 *   'required' => false,
	 *   'multiple' => false,
	 *   'desc'     => '',
	 * ];
	 *
	 * @param array $field
	 */
	protected function select_field_callback( $field ) {
		if ( empty( $field ) ) {
			return '';
		}

		extract( $field );

		if ( empty( $id ) ) {
			return '';
		}

		$html = '<div id="'.esc_attr( $id ).'-wrapper" class="field-wrapper">';
		if ( ! empty( $label ) ) {
			$html .= sprintf( '<label for="%1$s">%2$s</label>', esc_attr( $id ), esc_attr( $label ) );
		}
		$html .= sprintf(
			'<select id="%1$s" name="%1$s" style="%2$s" %3$s %4$s>',
			esc_attr( $id ),
			! empty( $style ) ? esc_attr( $style ) : '',
			! empty( $required ) && true === $required ? 'required' : '',
			! empty( $multiple ) && true === $multiple ? 'multiple' : ''
		);
		$html .= sprintf( '<option value="">%s</option>', esc_html( __( 'Select...', $this->package ) ) );
		if ( ! empty( $options ) ) {
			if ( is_callable( $options ) ) {
				$options = call_user_func( $options );
			}
			if ( is_array( $options ) ) {
				foreach ( $options as $key => $option ) {
					if ( ! empty( $multiple ) && is_array( $current ) ) {
						$selected = in_array( $key, $current ) ? ' selected' : '';
						$html    .= sprintf( '<option value="%s"%s>%s</option>', esc_attr( $key ), esc_attr( $selected ), esc_html( $option ) );
					} else {
						$current = ! empty( $current ) ? esc_attr( $current ) : '';
						$html   .= sprintf( '<option value="%s"%s>%s</option>', esc_attr( $key ), esc_attr( selected( $current, $key, false ) ), esc_html( $option ) );
					}
				}
			}
		}
		$html .= '</select>';
		$html .= ! empty( $desc ) ? sprintf( '<span class="description">%s</span>', esc_html( $desc ) ) : '';
		$html .= '</div>';

		return $html;
	}

	/**
	 * $field = [
	 *   'type'     => 'textarea', (required)
	 *   'id'       => '',         (required)
	 *   'label'    => '',
	 *   'cols'     => '',
	 *   'rows'     => '',
	 *   'value'    => '',
	 *   'style'    => '',
	 *   'required' => false,
	 *   'desc'     => '',
	 * ];
	 *
	 * @param array $field
	 */
	protected function textarea_field_callback( $field ) {
		if ( empty( $field ) ) {
			return '';
		}

		extract( $field );

		if ( empty( $id ) ) {
			return '';
		}

		$html = '<div id="'.esc_attr( $id ).'-wrapper" class="field-wrapper">';
		if ( ! empty( $label ) ) {
			$html .= sprintf( '<label for="%1$s">%2$s</label>', esc_attr( $id ), esc_attr( $label ) );
		}
		$html .= sprintf(
			'<textarea id="%1$s" name="%1$s" cols="%2$s" rows="%3$s" style="%4$s" %5$s>%6$s</textarea>',
			esc_attr( $id ),
			! empty( $cols ) ? esc_attr( $cols ) : '',
			! empty( $rows ) ? esc_attr( $rows ) : '',
			! empty( $style ) ? esc_attr( $style ) : '',
			! empty( $required ) && true === $required ? 'required' : '',
			! empty( $value ) ? esc_attr( $value ) : ''
		);
		$html .= ! empty( $desc ) ? sprintf( '<span class="description">%s</span>', esc_html( $desc ) ) : '';
		$html .= '</div>';

		return $html;
	}

	/**
	 * $field = [
	 *   'type'     => 'url', (required)
	 *   'id'       => '',    (required)
	 *   'label'    => '',
	 *   'value'    => '',
	 *   'style'    => '',
	 *   'pattern'  => '',
	 *   'required' => false,
	 *   'desc'     => '',
	 * ];
	 *
	 * @param array $field
	 */
	protected function url_field_callback( $field ) {
		if ( empty( $field ) ) {
			return '';
		}

		extract( $field );

		if ( empty( $id ) ) {
			return '';
		}

		$html = '<div id="'.esc_attr( $id ).'-wrapper" class="field-wrapper">';
		if ( ! empty( $label ) ) {
			$html .= sprintf( '<label for="%1$s">%2$s</label>', esc_attr( $id ), esc_attr( $label ) );
		}
		$html .= sprintf(
			'<input type="url" id="%1$s" name="%1$s" value="%2$s" style="%3$s" %4$s %5$s />',
			esc_attr( $id ),
			! empty( $value ) ? esc_attr( $value ) : '',
			! empty( $style ) ? esc_attr( $style ) : '',
			! empty( $pattern ) ? 'pattern="'.esc_attr( $pattern ).'"' : 'pattern="https://.*"',
			! empty( $required ) && true === $required ? 'required' : ''
		);
		$html .= ! empty( $desc ) ? sprintf( '<span class="description">%s</span>', esc_html( $desc ) ) : '';
		$html .= '</div>';

		return $html;
	}

	/**
	 * $field = [
	 *   'type'     => 'text', (required)
	 *   'id'       => '',     (required)
	 *   'label'    => '',
	 *   'value'    => '',
	 *   'style'    => '',
	 *   'pattern'  => '',
	 *   'required' => false,
	 *   'desc'     => '',
	 * ];
	 *
	 * @param array $field
	 */
	protected function text_field_callback( $field ) {
		if ( empty( $field ) ) {
			return '';
		}

		extract( $field );

		if ( empty( $id ) ) {
			return '';
		}

		$html = '<div id="'.esc_attr( $id ).'-wrapper" class="field-wrapper">';
		if ( ! empty( $label ) ) {
			$html .= sprintf( '<label for="%1$s">%2$s</label>', esc_attr( $id ), esc_attr( $label ) );
		}
		$html .= sprintf(
			'<input type="text" id="%1$s" name="%1$s" value="%2$s" style="%3$s" %4$s %5$s />',
			esc_attr( $id ),
			! empty( $value ) ? esc_attr( $value ) : '',
			! empty( $style ) ? esc_attr( $style ) : '',
			! empty( $pattern ) ? 'pattern="'.esc_attr( $pattern ).'"' : '',
			! empty( $required ) && true === $required ? 'required' : ''
		);
		$html .= ! empty( $desc ) ? sprintf( '<span class="description">%s</span>', esc_html( $desc ) ) : '';
		$html .= '</div>';

		return $html;
	}

}
