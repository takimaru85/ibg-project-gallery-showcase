<?php
/**
 * Registers the "project_category" taxonomy.
 *
 * @package WordPress_Project_Gallery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPG_Taxonomy
 */
class WPG_Taxonomy {

	/**
	 * Taxonomy slug.
	 *
	 * @var string
	 */
	const TAXONOMY = 'project_category';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_taxonomy' ) );
	}

	/**
	 * Register the project_category taxonomy.
	 */
	public function register_taxonomy() {
		$labels = array(
			'name'              => _x( 'Project Categories', 'taxonomy general name', 'wordpress-project-gallery' ),
			'singular_name'     => _x( 'Project Category', 'taxonomy singular name', 'wordpress-project-gallery' ),
			'search_items'      => __( 'Search Categories', 'wordpress-project-gallery' ),
			'all_items'         => __( 'All Categories', 'wordpress-project-gallery' ),
			'parent_item'       => __( 'Parent Category', 'wordpress-project-gallery' ),
			'parent_item_colon' => __( 'Parent Category:', 'wordpress-project-gallery' ),
			'edit_item'         => __( 'Edit Category', 'wordpress-project-gallery' ),
			'update_item'       => __( 'Update Category', 'wordpress-project-gallery' ),
			'add_new_item'      => __( 'Add New Category', 'wordpress-project-gallery' ),
			'new_item_name'     => __( 'New Category Name', 'wordpress-project-gallery' ),
			'menu_name'         => __( 'Categories', 'wordpress-project-gallery' ),
		);

		$args = array(
			'labels'            => $labels,
			'hierarchical'      => true,
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => true,
			'show_in_rest'      => true,
			'query_var'         => true,
			'rewrite'           => array(
				'slug'       => 'project-category',
				'with_front' => false,
			),
		);

		register_taxonomy( self::TAXONOMY, array( WPG_Post_Type::POST_TYPE ), $args );
	}

	/**
	 * Create the default set of project categories if none exist yet.
	 *
	 * Runs only on plugin activation, guarded by an option flag so it
	 * never re-creates terms a user has intentionally deleted.
	 */
	public function maybe_create_default_terms() {
		if ( get_option( 'wpg_default_terms_created' ) ) {
			return;
		}

		$defaults = array(
			__( 'WordPress', 'wordpress-project-gallery' ),
			__( 'Web Development', 'wordpress-project-gallery' ),
			__( 'Mobile Apps', 'wordpress-project-gallery' ),
			__( 'Plugins', 'wordpress-project-gallery' ),
			__( 'WooCommerce', 'wordpress-project-gallery' ),
			__( 'Elementor', 'wordpress-project-gallery' ),
			__( 'Divi', 'wordpress-project-gallery' ),
			__( 'UI/UX', 'wordpress-project-gallery' ),
			__( 'Other', 'wordpress-project-gallery' ),
		);

		foreach ( $defaults as $term ) {
			if ( ! term_exists( $term, self::TAXONOMY ) ) {
				wp_insert_term( $term, self::TAXONOMY );
			}
		}

		update_option( 'wpg_default_terms_created', '1' );
	}
}
