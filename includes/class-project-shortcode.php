<?php
/**
 * Registers the [project_gallery] shortcode.
 *
 * @package WordPress_Project_Gallery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPG_Shortcode
 */
class WPG_Shortcode {

	/**
	 * Incrementing counter so multiple shortcode instances on one page
	 * get unique DOM ids for AJAX targeting.
	 *
	 * @var int
	 */
	private static $instance_count = 0;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_shortcode( 'project_gallery', array( $this, 'render' ) );
	}

	/**
	 * Sanitize and normalize shortcode attributes against plugin defaults.
	 *
	 * @param array $atts Raw shortcode attributes.
	 *
	 * @return array
	 */
	public static function parse_atts( $atts ) {
		$settings = get_option( 'wpg_settings', array() );

		$default_columns = isset( $settings['default_columns'] ) ? absint( $settings['default_columns'] ) : 3;
		$default_columns = $default_columns ? $default_columns : 3;

		$default_limit = isset( $settings['default_limit'] ) ? intval( $settings['default_limit'] ) : -1;

		$allowed_orderby = array( 'date', 'title', 'menu_order', 'rand' );
		$allowed_order    = array( 'ASC', 'DESC' );

		$atts = shortcode_atts(
			array(
				'category' => '',
				'columns'  => $default_columns,
				'limit'    => $default_limit,
				'featured' => '',
				'orderby'  => 'date',
				'order'    => 'DESC',
				'filter'       => '',
				'layout'       => 'grid',
				'filter_style' => 'pills',
				'per_page'     => 0,
			),
			$atts,
			'project_gallery'
		);

		$columns = absint( $atts['columns'] );
		$columns = $columns ? $columns : 3;
		$columns = max( 1, min( 4, $columns ) );

		$limit = intval( $atts['limit'] );
		if ( 0 === $limit ) {
			$limit = -1;
		}

		$orderby = in_array( $atts['orderby'], $allowed_orderby, true ) ? $atts['orderby'] : 'date';
		$order   = in_array( strtoupper( $atts['order'] ), $allowed_order, true ) ? strtoupper( $atts['order'] ) : 'DESC';
		$layout       = 'wall' === strtolower( (string) $atts['layout'] ) ? 'wall' : 'grid';
		$filter_style = 'dropdown' === strtolower( (string) $atts['filter_style'] ) ? 'dropdown' : 'pills';
		$per_page     = absint( $atts['per_page'] );

		return array(
			'category'     => sanitize_title( $atts['category'] ),
			'columns'      => $columns,
			'limit'        => $limit,
			'featured'     => self::is_truthy( $atts['featured'] ),
			'orderby'      => $orderby,
			'order'        => $order,
			'filter'       => self::is_truthy( $atts['filter'] ),
			'layout'       => $layout,
			'filter_style' => $filter_style,
			'per_page'     => $per_page,
		);
	}

	/**
	 * Resolve the effective photo-wall "images per page" value: a
	 * positive shortcode `per_page` attribute wins, otherwise the
	 * Settings -> Project Gallery default.
	 *
	 * @param int $per_page_att The shortcode's `per_page` attribute (0 = unset).
	 *
	 * @return int
	 */
	public static function get_wall_per_page( $per_page_att = 0 ) {
		if ( $per_page_att > 0 ) {
			return $per_page_att;
		}

		$settings = wp_parse_args( get_option( 'wpg_settings', array() ), WPG_Settings::get_defaults() );

		return absint( $settings['wall_per_page'] );
	}

	/**
	 * Interpret common truthy string values used in shortcode attributes.
	 *
	 * @param string $value Raw attribute value.
	 *
	 * @return bool
	 */
	private static function is_truthy( $value ) {
		return in_array( strtolower( (string) $value ), array( 'yes', 'true', '1' ), true );
	}

	/**
	 * Render the [project_gallery] shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 *
	 * @return string
	 */
	public function render( $atts ) {
		$atts = self::parse_atts( (array) $atts );

		self::$instance_count++;
		$instance_id = 'wpg-gallery-' . self::$instance_count;

		// Progressive-enhancement fallback: a plain link-based category
		// filter works even with JavaScript disabled, via ?wpg_category=.
		$active_category = $atts['category'];
		if ( $atts['filter'] && isset( $_GET['wpg_category'] ) ) {
			$requested = sanitize_title( wp_unslash( $_GET['wpg_category'] ) );
			if ( '' === $requested || term_exists( $requested, WPG_Taxonomy::TAXONOMY ) ) {
				$active_category = $requested;
			}
		}

		$query_atts             = $atts;
		$query_atts['category'] = $active_category;

		$query = WPG_Gallery::get_projects_query( $query_atts );

		$wall_per_page = self::get_wall_per_page( $atts['per_page'] );

		// Progressive-enhancement fallback for "View More Projects" without JS.
		$wall_show = 0;
		if ( 'wall' === $atts['layout'] && isset( $_GET['wpg_show'] ) ) {
			$wall_show = absint( wp_unslash( $_GET['wpg_show'] ) );
		}

		$settings          = wp_parse_args( get_option( 'wpg_settings', array() ), WPG_Settings::get_defaults() );
		$anim_enabled_class = ! empty( $settings['enable_hover_anim'] ) ? ' wpg-anim-enabled' : '';

		$output = '';

		ob_start();
		?>
		<div
			id="<?php echo esc_attr( $instance_id ); ?>"
			class="wpg-gallery-wrapper<?php echo esc_attr( $anim_enabled_class ); ?> wpg-layout-<?php echo esc_attr( $atts['layout'] ); ?>"
			data-columns="<?php echo esc_attr( $atts['columns'] ); ?>"
			data-limit="<?php echo esc_attr( $atts['limit'] ); ?>"
			data-featured="<?php echo esc_attr( $atts['featured'] ? '1' : '' ); ?>"
			data-orderby="<?php echo esc_attr( $atts['orderby'] ); ?>"
			data-order="<?php echo esc_attr( $atts['order'] ); ?>"
			data-filter="<?php echo esc_attr( $atts['filter'] ? '1' : '' ); ?>"
			data-layout="<?php echo esc_attr( $atts['layout'] ); ?>"
			data-category="<?php echo esc_attr( $active_category ); ?>"
			data-per-page="<?php echo esc_attr( $wall_per_page ); ?>"
			data-nonce="<?php echo esc_attr( wp_create_nonce( 'wpg_filter_projects' ) ); ?>"
		>
			<?php if ( $atts['filter'] ) : ?>
				<?php if ( 'dropdown' === $atts['filter_style'] ) : ?>
					<?php WPG_Gallery::render_filter_dropdown( $active_category ); ?>
				<?php else : ?>
					<?php WPG_Gallery::render_filter_nav( $active_category ); ?>
				<?php endif; ?>
			<?php endif; ?>

			<div class="wpg-gallery-results">
				<?php
				if ( 'wall' === $atts['layout'] ) {
					echo WPG_Gallery::render_wall( $query, $wall_per_page, $wall_show ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- render_wall() escapes all dynamic values internally.
				} else {
					echo WPG_Gallery::render_grid( $query, $atts['columns'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- render_grid() escapes all dynamic values internally.
				}
				?>
			</div>
		</div>
		<?php
		$output = ob_get_clean();

		return $output;
	}
}
