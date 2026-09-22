<?php
/**
 * Registers the "Project" custom post type.
 *
 * @package WordPress_Project_Gallery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPG_Post_Type
 */
class WPG_Post_Type {

	/**
	 * Post type slug.
	 *
	 * @var string
	 */
	const POST_TYPE = 'project';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_filter( 'use_block_editor_for_post_type', array( $this, 'disable_block_editor' ), 10, 2 );

		// Admin list table customization.
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( $this, 'admin_columns' ) );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( $this, 'admin_column_content' ), 10, 2 );
		add_filter( 'manage_edit-' . self::POST_TYPE . '_sortable_columns', array( $this, 'sortable_columns' ) );
		add_action( 'pre_get_posts', array( $this, 'sort_columns' ) );
	}

	/**
	 * Get the configured permalink slug for the post type.
	 *
	 * @return string
	 */
	public static function get_slug() {
		$settings = get_option( 'wpg_settings', array() );
		$slug     = isset( $settings['project_slug'] ) ? $settings['project_slug'] : 'project';
		$slug     = sanitize_title( $slug );

		return $slug ? $slug : 'project';
	}

	/**
	 * Register the Project post type.
	 */
	public function register_post_type() {
		$labels = array(
			'name'                  => _x( 'Projects', 'Post type general name', 'wordpress-project-gallery' ),
			'singular_name'         => _x( 'Project', 'Post type singular name', 'wordpress-project-gallery' ),
			'menu_name'             => _x( 'Projects', 'Admin Menu text', 'wordpress-project-gallery' ),
			'name_admin_bar'        => _x( 'Project', 'Add New on Toolbar', 'wordpress-project-gallery' ),
			'add_new'               => __( 'Add New', 'wordpress-project-gallery' ),
			'add_new_item'          => __( 'Add New Project', 'wordpress-project-gallery' ),
			'new_item'              => __( 'New Project', 'wordpress-project-gallery' ),
			'edit_item'             => __( 'Edit Project', 'wordpress-project-gallery' ),
			'view_item'             => __( 'View Project', 'wordpress-project-gallery' ),
			'view_items'            => __( 'View Projects', 'wordpress-project-gallery' ),
			'all_items'             => __( 'All Projects', 'wordpress-project-gallery' ),
			'search_items'          => __( 'Search Projects', 'wordpress-project-gallery' ),
			'not_found'             => __( 'No projects found.', 'wordpress-project-gallery' ),
			'not_found_in_trash'    => __( 'No projects found in Trash.', 'wordpress-project-gallery' ),
			'featured_image'        => __( 'Featured Image', 'wordpress-project-gallery' ),
			'set_featured_image'    => __( 'Set featured image', 'wordpress-project-gallery' ),
			'remove_featured_image' => __( 'Remove featured image', 'wordpress-project-gallery' ),
			'use_featured_image'    => __( 'Use as featured image', 'wordpress-project-gallery' ),
			'archives'              => __( 'Project archives', 'wordpress-project-gallery' ),
			'insert_into_item'      => __( 'Insert into project', 'wordpress-project-gallery' ),
			'uploaded_to_this_item' => __( 'Uploaded to this project', 'wordpress-project-gallery' ),
			'filter_items_list'     => __( 'Filter projects list', 'wordpress-project-gallery' ),
			'items_list_navigation' => __( 'Projects list navigation', 'wordpress-project-gallery' ),
			'items_list'            => __( 'Projects list', 'wordpress-project-gallery' ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_in_admin_bar'  => true,
			'show_in_nav_menus'  => true,
			'show_in_rest'       => false,
			'query_var'          => true,
			'capability_type'    => 'post',
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => 20,
			'menu_icon'          => 'dashicons-portfolio',
			'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions' ),
			'taxonomies'         => array( WPG_Taxonomy::TAXONOMY ),
			'rewrite'            => array(
				'slug'       => self::get_slug(),
				'with_front' => false,
			),
		);

		register_post_type( self::POST_TYPE, $args );
	}

	/**
	 * Disable the block editor for the Project post type so the classic
	 * meta box UI (media uploader, sortable gallery) works reliably.
	 *
	 * @param bool   $use_block_editor Whether to use the block editor.
	 * @param string $post_type        Post type slug.
	 *
	 * @return bool
	 */
	public function disable_block_editor( $use_block_editor, $post_type ) {
		if ( self::POST_TYPE === $post_type ) {
			return false;
		}

		return $use_block_editor;
	}

	/**
	 * Define custom admin list table columns.
	 *
	 * @param array $columns Existing columns.
	 *
	 * @return array
	 */
	public function admin_columns( $columns ) {
		$new_columns = array();

		foreach ( $columns as $key => $label ) {
			$new_columns[ $key ] = $label;

			if ( 'title' === $key ) {
				$new_columns['wpg_thumbnail'] = __( 'Image', 'wordpress-project-gallery' );
			}
		}

		$new_columns['wpg_category'] = __( 'Category', 'wordpress-project-gallery' );
		$new_columns['wpg_year']     = __( 'Year', 'wordpress-project-gallery' );
		$new_columns['wpg_featured'] = __( 'Featured', 'wordpress-project-gallery' );

		return $new_columns;
	}

	/**
	 * Output custom admin list table column content.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Post ID.
	 */
	public function admin_column_content( $column, $post_id ) {
		switch ( $column ) {
			case 'wpg_thumbnail':
				if ( has_post_thumbnail( $post_id ) ) {
					echo get_the_post_thumbnail( $post_id, array( 60, 60 ), array( 'style' => 'width:60px;height:60px;object-fit:cover;border-radius:4px;' ) );
				} else {
					echo '&#8212;';
				}
				break;

			case 'wpg_category':
				$terms = get_the_terms( $post_id, WPG_Taxonomy::TAXONOMY );

				if ( $terms && ! is_wp_error( $terms ) ) {
					$names = wp_list_pluck( $terms, 'name' );
					echo esc_html( implode( ', ', $names ) );
				} else {
					echo '&#8212;';
				}
				break;

			case 'wpg_year':
				$year = get_post_meta( $post_id, '_wpg_project_year', true );
				echo $year ? esc_html( $year ) : '&#8212;';
				break;

			case 'wpg_featured':
				$featured = get_post_meta( $post_id, '_wpg_featured', true );
				echo $featured ? '<span class="dashicons dashicons-star-filled" style="color:#e2a300;" aria-label="' . esc_attr__( 'Featured', 'wordpress-project-gallery' ) . '"></span>' : '&#8212;';
				break;
		}
	}

	/**
	 * Register sortable admin columns.
	 *
	 * @param array $columns Sortable columns.
	 *
	 * @return array
	 */
	public function sortable_columns( $columns ) {
		$columns['wpg_year']     = 'wpg_year';
		$columns['wpg_featured'] = 'wpg_featured';

		return $columns;
	}

	/**
	 * Handle sorting for custom columns.
	 *
	 * @param WP_Query $query The current query.
	 */
	public function sort_columns( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		$orderby = $query->get( 'orderby' );

		if ( 'wpg_year' === $orderby ) {
			$query->set( 'meta_key', '_wpg_project_year' );
			$query->set( 'orderby', 'meta_value_num' );
		} elseif ( 'wpg_featured' === $orderby ) {
			$query->set( 'meta_key', '_wpg_featured' );
			$query->set( 'orderby', 'meta_value' );
		}
	}
}
