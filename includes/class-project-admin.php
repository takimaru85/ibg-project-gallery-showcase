<?php
/**
 * Misc admin functionality: the "Duplicate" row action for Projects.
 *
 * @package WordPress_Project_Gallery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPG_Admin
 */
class WPG_Admin {

	const DUPLICATE_ACTION = 'wpg_duplicate_project';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'post_row_actions', array( $this, 'add_duplicate_row_action' ), 10, 2 );
		add_action( 'admin_action_' . self::DUPLICATE_ACTION, array( $this, 'duplicate_project' ) );
	}

	/**
	 * Add a "Duplicate" link to the Project row actions.
	 *
	 * @param array   $actions Existing row actions.
	 * @param WP_Post $post    Current post.
	 *
	 * @return array
	 */
	public function add_duplicate_row_action( $actions, $post ) {
		if ( WPG_Post_Type::POST_TYPE !== $post->post_type ) {
			return $actions;
		}

		if ( ! current_user_can( 'edit_post', $post->ID ) ) {
			return $actions;
		}

		$url = wp_nonce_url(
			add_query_arg(
				array(
					'action' => self::DUPLICATE_ACTION,
					'post'   => $post->ID,
				),
				admin_url( 'admin.php' )
			),
			self::DUPLICATE_ACTION . '_' . $post->ID
		);

		$actions['wpg_duplicate'] = sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( $url ),
			esc_html__( 'Duplicate', 'wordpress-project-gallery' )
		);

		return $actions;
	}

	/**
	 * Handle the duplicate admin action.
	 */
	public function duplicate_project() {
		$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;

		if ( ! $post_id ) {
			wp_die( esc_html__( 'No project specified for duplication.', 'wordpress-project-gallery' ) );
		}

		check_admin_referer( self::DUPLICATE_ACTION . '_' . $post_id );

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_die( esc_html__( 'You do not have permission to duplicate this project.', 'wordpress-project-gallery' ) );
		}

		$original = get_post( $post_id );

		if ( ! $original || WPG_Post_Type::POST_TYPE !== $original->post_type ) {
			wp_die( esc_html__( 'Project not found.', 'wordpress-project-gallery' ) );
		}

		$new_post_id = wp_insert_post(
			array(
				'post_title'     => sprintf(
					/* translators: %s: original project title. */
					__( '%s (Copy)', 'wordpress-project-gallery' ),
					$original->post_title
				),
				'post_content'   => $original->post_content,
				'post_excerpt'   => $original->post_excerpt,
				'post_status'    => 'draft',
				'post_type'      => WPG_Post_Type::POST_TYPE,
				'post_author'    => get_current_user_id(),
				'comment_status' => $original->comment_status,
				'ping_status'    => $original->ping_status,
			),
			true
		);

		if ( is_wp_error( $new_post_id ) ) {
			wp_die( esc_html( $new_post_id->get_error_message() ) );
		}

		// Copy custom meta.
		$meta_keys = array( '_wpg_project_url', '_wpg_project_year', '_wpg_builder', '_wpg_value', '_wpg_client', '_wpg_technologies', '_wpg_featured', '_wpg_gallery_images' );

		foreach ( $meta_keys as $key ) {
			$value = get_post_meta( $post_id, $key, true );
			if ( '' !== $value ) {
				update_post_meta( $new_post_id, $key, $value );
			}
		}

		// Featured project toggle should not carry over automatically.
		update_post_meta( $new_post_id, '_wpg_featured', '' );

		// Copy featured image.
		$thumbnail_id = get_post_thumbnail_id( $post_id );
		if ( $thumbnail_id ) {
			set_post_thumbnail( $new_post_id, $thumbnail_id );
		}

		// Copy taxonomy terms.
		$terms = wp_get_object_terms( $post_id, WPG_Taxonomy::TAXONOMY, array( 'fields' => 'ids' ) );
		if ( ! is_wp_error( $terms ) ) {
			wp_set_object_terms( $new_post_id, $terms, WPG_Taxonomy::TAXONOMY );
		}

		wp_safe_redirect( admin_url( 'post.php?action=edit&post=' . $new_post_id ) );
		exit;
	}
}
