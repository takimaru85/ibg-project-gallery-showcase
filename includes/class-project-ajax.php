<?php
/**
 * Handles the AJAX category filter request for [project_gallery].
 *
 * This is progressive enhancement only: the shortcode already works via
 * plain links (?wpg_category=) when JavaScript is unavailable.
 *
 * @package WordPress_Project_Gallery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPG_Ajax
 */
class WPG_Ajax {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_ajax_wpg_filter_projects', array( $this, 'filter_projects' ) );
		add_action( 'wp_ajax_nopriv_wpg_filter_projects', array( $this, 'filter_projects' ) );
	}

	/**
	 * Handle the AJAX request and return rendered grid HTML.
	 */
	public function filter_projects() {
		check_ajax_referer( 'wpg_filter_projects', 'nonce' );

		$allowed_orderby = array( 'date', 'title', 'menu_order', 'rand' );
		$allowed_order    = array( 'ASC', 'DESC' );

		$category = isset( $_POST['category'] ) ? sanitize_title( wp_unslash( $_POST['category'] ) ) : '';

		if ( '' !== $category && ! term_exists( $category, WPG_Taxonomy::TAXONOMY ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid category.', 'wordpress-project-gallery' ) ), 400 );
		}

		$columns = isset( $_POST['columns'] ) ? absint( $_POST['columns'] ) : 3;
		$columns = max( 1, min( 4, $columns ? $columns : 3 ) );

		$limit = isset( $_POST['limit'] ) ? intval( $_POST['limit'] ) : -1;
		if ( 0 === $limit ) {
			$limit = -1;
		}

		$featured = ! empty( $_POST['featured'] );

		$orderby = isset( $_POST['orderby'] ) ? sanitize_text_field( wp_unslash( $_POST['orderby'] ) ) : 'date';
		$orderby = in_array( $orderby, $allowed_orderby, true ) ? $orderby : 'date';

		$order = isset( $_POST['order'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_POST['order'] ) ) ) : 'DESC';
		$order = in_array( $order, $allowed_order, true ) ? $order : 'DESC';

		$layout = isset( $_POST['layout'] ) ? sanitize_text_field( wp_unslash( $_POST['layout'] ) ) : 'grid';
		$layout = 'wall' === $layout ? 'wall' : 'grid';

		$per_page = isset( $_POST['per_page'] ) ? absint( $_POST['per_page'] ) : 0;
		$show     = isset( $_POST['show'] ) ? absint( $_POST['show'] ) : 0;

		$atts = array(
			'category' => $category,
			'columns'  => $columns,
			'limit'    => $limit,
			'featured' => $featured,
			'orderby'  => $orderby,
			'order'    => $order,
		);

		$query = WPG_Gallery::get_projects_query( $atts );
		$html  = ( 'wall' === $layout ) ? WPG_Gallery::render_wall( $query, $per_page, $show ) : WPG_Gallery::render_grid( $query, $columns );

		wp_send_json_success(
			array(
				'html'     => $html,
				'category' => $category,
			)
		);
	}
}
