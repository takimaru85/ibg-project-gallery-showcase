<?php
/**
 * Loads the single Project template (unless the active theme provides its
 * own single-project.php) and exposes small template helpers.
 *
 * @package WordPress_Project_Gallery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPG_Template
 */
class WPG_Template {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'single_template', array( $this, 'load_single_template' ) );
		add_filter( 'archive_template', array( $this, 'load_archive_template' ) );
	}

	/**
	 * Use the plugin's single-project.php template unless the active
	 * theme already provides one.
	 *
	 * @param string $template Template path resolved by WordPress core.
	 *
	 * @return string
	 */
	public function load_single_template( $template ) {
		if ( ! is_singular( WPG_Post_Type::POST_TYPE ) ) {
			return $template;
		}

		$theme_override = locate_template( array( 'single-' . WPG_Post_Type::POST_TYPE . '.php' ) );

		if ( $theme_override ) {
			return $theme_override;
		}

		return WPG_PLUGIN_DIR . 'templates/single-project.php';
	}

	/**
	 * Use the plugin's archive-project.php template unless the active
	 * theme already provides one.
	 *
	 * @param string $template Template path resolved by WordPress core.
	 *
	 * @return string
	 */
	public function load_archive_template( $template ) {
		if ( ! is_post_type_archive( WPG_Post_Type::POST_TYPE ) ) {
			return $template;
		}

		$theme_override = locate_template( array( 'archive-' . WPG_Post_Type::POST_TYPE . '.php' ) );

		if ( $theme_override ) {
			return $theme_override;
		}

		return WPG_PLUGIN_DIR . 'templates/archive-project.php';
	}

	/**
	 * Render the meta table (builder, year, value, category, client,
	 * technologies, project URL) as label/value rows. Only fields that
	 * have been filled in are shown.
	 *
	 * @param int $post_id Project post ID.
	 */
	public static function render_meta_table( $post_id ) {
		$builder      = get_post_meta( $post_id, '_wpg_builder', true );
		$year         = get_post_meta( $post_id, '_wpg_project_year', true );
		$value        = get_post_meta( $post_id, '_wpg_value', true );
		$client       = get_post_meta( $post_id, '_wpg_client', true );
		$technologies = get_post_meta( $post_id, '_wpg_technologies', true );
		$project_url  = get_post_meta( $post_id, '_wpg_project_url', true );
		$terms        = get_the_terms( $post_id, WPG_Taxonomy::TAXONOMY );

		$rows = array();

		if ( $builder ) {
			$rows[] = array(
				'label' => __( 'Builder', 'wordpress-project-gallery' ),
				'value' => esc_html( $builder ),
			);
		}

		if ( $year ) {
			$rows[] = array(
				'label' => __( 'Year', 'wordpress-project-gallery' ),
				'value' => esc_html( $year ),
			);
		}

		if ( $value ) {
			$rows[] = array(
				'label' => __( 'Value', 'wordpress-project-gallery' ),
				'value' => esc_html( $value ),
			);
		}

		if ( $terms && ! is_wp_error( $terms ) ) {
			$rows[] = array(
				'label' => __( 'Category', 'wordpress-project-gallery' ),
				'value' => esc_html( implode( ', ', wp_list_pluck( $terms, 'name' ) ) ),
			);
		}

		if ( $client ) {
			$rows[] = array(
				'label' => __( 'Client', 'wordpress-project-gallery' ),
				'value' => esc_html( $client ),
			);
		}

		if ( $technologies ) {
			$rows[] = array(
				'label' => __( 'Technologies', 'wordpress-project-gallery' ),
				'value' => esc_html( $technologies ),
			);
		}

		if ( $project_url ) {
			$rows[] = array(
				'label' => __( 'Website', 'wordpress-project-gallery' ),
				'value' => sprintf(
					'<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s<span class="screen-reader-text">%3$s</span></a>',
					esc_url( $project_url ),
					esc_html__( 'View Project', 'wordpress-project-gallery' ),
					esc_html__( '(opens in a new tab)', 'wordpress-project-gallery' )
				),
			);
		}

		if ( empty( $rows ) ) {
			return;
		}
		?>
		<dl class="wpg-meta-table">
			<?php foreach ( $rows as $row ) : ?>
				<div class="wpg-meta-row">
					<dt><?php echo esc_html( $row['label'] ); ?></dt>
					<dd><?php echo $row['value']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- values are escaped above or built from esc_url()/esc_html() parts. ?></dd>
				</div>
			<?php endforeach; ?>
		</dl>
		<?php
	}

	/**
	 * Build the current request URL (scheme + host + URI), used to send
	 * visitors back to the exact gallery page/filter state they came from.
	 *
	 * @return string
	 */
	public static function get_current_url() {
		$host = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
		$uri  = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/';

		if ( ! $host ) {
			return home_url( '/' );
		}

		$scheme = is_ssl() ? 'https://' : 'http://';

		return esc_url_raw( $scheme . $host . $uri );
	}

	/**
	 * Resolve a safe "Back" URL from the `wpg_from` request var, falling
	 * back to the site home. Only same-host URLs are accepted.
	 *
	 * @return string
	 */
	public static function get_back_url() {
		if ( ! empty( $_GET['wpg_from'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only navigation link, no state change.
			$candidate = esc_url_raw( wp_unslash( $_GET['wpg_from'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only navigation link, no state change.

			if ( $candidate && wp_parse_url( $candidate, PHP_URL_HOST ) === wp_parse_url( home_url(), PHP_URL_HOST ) ) {
				return $candidate;
			}
		}

		return home_url( '/' );
	}
}
