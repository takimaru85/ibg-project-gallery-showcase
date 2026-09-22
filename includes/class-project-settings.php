<?php
/**
 * Settings -> Project Gallery admin page, built with the Settings API.
 *
 * @package WordPress_Project_Gallery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPG_Settings
 */
class WPG_Settings {

	/**
	 * Option name.
	 *
	 * @var string
	 */
	const OPTION_NAME = 'wpg_settings';

	/**
	 * Settings page slug.
	 *
	 * @var string
	 */
	const PAGE_SLUG = 'wpg-settings';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Get default settings values.
	 *
	 * @return array
	 */
	public static function get_defaults() {
		return array(
			'project_slug'        => 'project',
			'default_columns'     => 3,
			'default_limit'       => -1,
			'wall_per_page'       => 8,
			'enable_filter'       => 0,
			'enable_hover_anim'   => 1,
			'enable_lightbox'     => 1,
			'accent_color'        => '#e0972e',
		);
	}

	/**
	 * Add the Settings -> Project Gallery submenu page.
	 */
	public function add_settings_page() {
		add_options_page(
			__( 'Project Gallery Settings', 'wordpress-project-gallery' ),
			__( 'Project Gallery', 'wordpress-project-gallery' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register settings, section, and fields.
	 */
	public function register_settings() {
		register_setting(
			self::OPTION_NAME,
			self::OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => self::get_defaults(),
			)
		);

		add_settings_section(
			'wpg_general_section',
			__( 'General Settings', 'wordpress-project-gallery' ),
			'__return_false',
			self::PAGE_SLUG
		);

		$fields = array(
			'project_slug'      => __( 'Project Slug', 'wordpress-project-gallery' ),
			'default_columns'   => __( 'Default Columns', 'wordpress-project-gallery' ),
			'default_limit'     => __( 'Default Number of Projects', 'wordpress-project-gallery' ),
			'wall_per_page'     => __( 'Photo Wall: Images Per Page', 'wordpress-project-gallery' ),
			'enable_filter'     => __( 'Enable Category Filter', 'wordpress-project-gallery' ),
			'enable_hover_anim' => __( 'Enable Hover Animation', 'wordpress-project-gallery' ),
			'enable_lightbox'   => __( 'Enable Gallery Lightbox', 'wordpress-project-gallery' ),
			'accent_color'      => __( 'Accent Color', 'wordpress-project-gallery' ),
		);

		foreach ( $fields as $id => $label ) {
			add_settings_field(
				$id,
				$label,
				array( $this, 'render_field_' . $id ),
				self::PAGE_SLUG,
				'wpg_general_section'
			);
		}
	}

	/**
	 * Get current settings merged with defaults.
	 *
	 * @return array
	 */
	private function get_settings() {
		return wp_parse_args( get_option( self::OPTION_NAME, array() ), self::get_defaults() );
	}

	/**
	 * Sanitize submitted settings.
	 *
	 * @param array $input Raw submitted values.
	 *
	 * @return array
	 */
	public function sanitize_settings( $input ) {
		$defaults = self::get_defaults();
		$existing = $this->get_settings();
		$output   = array();

		$output['project_slug'] = isset( $input['project_slug'] ) ? sanitize_title( $input['project_slug'] ) : $defaults['project_slug'];
		if ( '' === $output['project_slug'] ) {
			$output['project_slug'] = $defaults['project_slug'];
		}

		$columns                    = isset( $input['default_columns'] ) ? absint( $input['default_columns'] ) : $defaults['default_columns'];
		$output['default_columns'] = max( 1, min( 4, $columns ? $columns : $defaults['default_columns'] ) );

		$limit                    = isset( $input['default_limit'] ) ? intval( $input['default_limit'] ) : $defaults['default_limit'];
		$output['default_limit'] = ( 0 === $limit ) ? -1 : $limit;

		$wall_per_page              = isset( $input['wall_per_page'] ) ? absint( $input['wall_per_page'] ) : $defaults['wall_per_page'];
		$output['wall_per_page']    = $wall_per_page ? $wall_per_page : $defaults['wall_per_page'];

		$output['enable_filter']     = ! empty( $input['enable_filter'] ) ? 1 : 0;
		$output['enable_hover_anim'] = ! empty( $input['enable_hover_anim'] ) ? 1 : 0;
		$output['enable_lightbox']   = ! empty( $input['enable_lightbox'] ) ? 1 : 0;

		$color                    = isset( $input['accent_color'] ) ? sanitize_text_field( $input['accent_color'] ) : $defaults['accent_color'];
		$output['accent_color'] = preg_match( '/^#[0-9a-fA-F]{3,6}$/', $color ) ? $color : $defaults['accent_color'];

		if ( $output['project_slug'] !== $existing['project_slug'] ) {
			update_option( 'wpg_needs_rewrite_flush', '1' );
		}

		return $output;
	}

	/**
	 * Render the settings page wrapper.
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Project Gallery Settings', 'wordpress-project-gallery' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( self::OPTION_NAME );
				do_settings_sections( self::PAGE_SLUG );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Field: project_slug.
	 */
	public function render_field_project_slug() {
		$settings = $this->get_settings();
		printf(
			'<input type="text" name="%1$s[project_slug]" value="%2$s" class="regular-text" /> <p class="description">%3$s</p>',
			esc_attr( self::OPTION_NAME ),
			esc_attr( $settings['project_slug'] ),
			esc_html__( 'Used in single project URLs, e.g. /project/your-project/. Changing this updates permalinks after saving.', 'wordpress-project-gallery' )
		);
	}

	/**
	 * Field: default_columns.
	 */
	public function render_field_default_columns() {
		$settings = $this->get_settings();
		?>
		<select name="<?php echo esc_attr( self::OPTION_NAME ); ?>[default_columns]">
			<?php foreach ( array( 1, 2, 3, 4 ) as $value ) : ?>
				<option value="<?php echo esc_attr( $value ); ?>" <?php selected( (int) $settings['default_columns'], $value ); ?>>
					<?php echo esc_html( $value ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Field: default_limit.
	 */
	public function render_field_default_limit() {
		$settings = $this->get_settings();
		printf(
			'<input type="number" name="%1$s[default_limit]" value="%2$s" class="small-text" /> <p class="description">%3$s</p>',
			esc_attr( self::OPTION_NAME ),
			esc_attr( $settings['default_limit'] ),
			esc_html__( 'Use -1 to show all published projects.', 'wordpress-project-gallery' )
		);
	}

	/**
	 * Field: wall_per_page.
	 */
	public function render_field_wall_per_page() {
		$settings = $this->get_settings();
		printf(
			'<input type="number" min="1" name="%1$s[wall_per_page]" value="%2$s" class="small-text" /> <p class="description">%3$s</p>',
			esc_attr( self::OPTION_NAME ),
			esc_attr( $settings['wall_per_page'] ),
			esc_html__( 'How many images show initially in the layout="wall" shortcode and the Project archive page. A "View More Projects" button reveals the rest.', 'wordpress-project-gallery' )
		);
	}

	/**
	 * Field: enable_filter.
	 */
	public function render_field_enable_filter() {
		$this->render_checkbox( 'enable_filter', __( 'Enable the category filter by default (can still be overridden per shortcode with filter="yes"/"no").', 'wordpress-project-gallery' ) );
	}

	/**
	 * Field: enable_hover_anim.
	 */
	public function render_field_enable_hover_anim() {
		$this->render_checkbox( 'enable_hover_anim', __( 'Scale/overlay effect on project card hover.', 'wordpress-project-gallery' ) );
	}

	/**
	 * Field: enable_lightbox.
	 */
	public function render_field_enable_lightbox() {
		$this->render_checkbox( 'enable_lightbox', __( 'Open project gallery images in the built-in lightbox.', 'wordpress-project-gallery' ) );
	}

	/**
	 * Field: accent_color.
	 */
	public function render_field_accent_color() {
		$settings = $this->get_settings();
		printf(
			'<input type="text" class="wpg-color-picker" name="%1$s[accent_color]" value="%2$s" data-default-color="%3$s" />',
			esc_attr( self::OPTION_NAME ),
			esc_attr( $settings['accent_color'] ),
			esc_attr( self::get_defaults()['accent_color'] )
		);
	}

	/**
	 * Helper to render a checkbox field.
	 *
	 * @param string $key         Settings array key.
	 * @param string $description Field description.
	 */
	private function render_checkbox( $key, $description ) {
		$settings = $this->get_settings();
		printf(
			'<label><input type="checkbox" name="%1$s[%2$s]" value="1" %3$s /> %4$s</label>',
			esc_attr( self::OPTION_NAME ),
			esc_attr( $key ),
			checked( ! empty( $settings[ $key ] ), true, false ),
			esc_html( $description )
		);
	}
}
