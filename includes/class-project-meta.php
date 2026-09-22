<?php
/**
 * Registers and saves the Project Details and Project Gallery meta boxes.
 *
 * @package WordPress_Project_Gallery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPG_Meta
 */
class WPG_Meta {

	/**
	 * Nonce action/name for the meta box save.
	 *
	 * @var string
	 */
	const NONCE_ACTION = 'wpg_save_project_meta';
	const NONCE_NAME   = 'wpg_project_meta_nonce';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'register_meta_boxes' ) );
		add_action( 'save_post_' . WPG_Post_Type::POST_TYPE, array( $this, 'save_meta' ), 10, 2 );
	}

	/**
	 * Register meta boxes on the Project edit screen.
	 */
	public function register_meta_boxes() {
		add_meta_box(
			'wpg_project_details',
			__( 'Project Details', 'wordpress-project-gallery' ),
			array( $this, 'render_details_meta_box' ),
			WPG_Post_Type::POST_TYPE,
			'side',
			'default'
		);

		add_meta_box(
			'wpg_project_gallery',
			__( 'Project Gallery', 'wordpress-project-gallery' ),
			array( $this, 'render_gallery_meta_box' ),
			WPG_Post_Type::POST_TYPE,
			'normal',
			'default'
		);
	}

	/**
	 * Render the Project Details meta box.
	 *
	 * @param WP_Post $post Current post object.
	 */
	public function render_details_meta_box( $post ) {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );

		$url          = get_post_meta( $post->ID, '_wpg_project_url', true );
		$year         = get_post_meta( $post->ID, '_wpg_project_year', true );
		$builder      = get_post_meta( $post->ID, '_wpg_builder', true );
		$value        = get_post_meta( $post->ID, '_wpg_value', true );
		$client       = get_post_meta( $post->ID, '_wpg_client', true );
		$technologies = get_post_meta( $post->ID, '_wpg_technologies', true );
		$featured     = get_post_meta( $post->ID, '_wpg_featured', true );
		?>
		<p>
			<label for="wpg_builder"><strong><?php esc_html_e( 'Builder', 'wordpress-project-gallery' ); ?></strong></label>
			<input type="text" id="wpg_builder" name="wpg_builder" class="widefat" value="<?php echo esc_attr( $builder ); ?>" />
		</p>
		<p>
			<label for="wpg_project_year"><strong><?php esc_html_e( 'Project Year', 'wordpress-project-gallery' ); ?></strong></label>
			<input type="number" id="wpg_project_year" name="wpg_project_year" class="widefat" min="1990" max="2100" step="1" value="<?php echo esc_attr( $year ); ?>" />
		</p>
		<p>
			<label for="wpg_value"><strong><?php esc_html_e( 'Value', 'wordpress-project-gallery' ); ?></strong></label>
			<input type="text" id="wpg_value" name="wpg_value" class="widefat" placeholder="<?php esc_attr_e( 'e.g. $440k', 'wordpress-project-gallery' ); ?>" value="<?php echo esc_attr( $value ); ?>" />
		</p>
		<p>
			<label for="wpg_client"><strong><?php esc_html_e( 'Client', 'wordpress-project-gallery' ); ?></strong></label>
			<input type="text" id="wpg_client" name="wpg_client" class="widefat" value="<?php echo esc_attr( $client ); ?>" />
		</p>
		<p>
			<label for="wpg_technologies"><strong><?php esc_html_e( 'Technologies', 'wordpress-project-gallery' ); ?></strong></label>
			<input type="text" id="wpg_technologies" name="wpg_technologies" class="widefat" placeholder="<?php esc_attr_e( 'e.g. PHP, React, WooCommerce', 'wordpress-project-gallery' ); ?>" value="<?php echo esc_attr( $technologies ); ?>" />
			<span class="description"><?php esc_html_e( 'Comma-separated list.', 'wordpress-project-gallery' ); ?></span>
		</p>
		<p>
			<label for="wpg_project_url"><strong><?php esc_html_e( 'Project URL', 'wordpress-project-gallery' ); ?></strong></label>
			<input type="url" id="wpg_project_url" name="wpg_project_url" class="widefat" placeholder="https://" value="<?php echo esc_attr( $url ); ?>" />
		</p>
		<p>
			<label for="wpg_featured">
				<input type="checkbox" id="wpg_featured" name="wpg_featured" value="1" <?php checked( $featured, '1' ); ?> />
				<strong><?php esc_html_e( 'Featured Project', 'wordpress-project-gallery' ); ?></strong>
			</label>
		</p>
		<?php
	}

	/**
	 * Render the Project Gallery meta box.
	 *
	 * @param WP_Post $post Current post object.
	 */
	public function render_gallery_meta_box( $post ) {
		$ids_raw = get_post_meta( $post->ID, '_wpg_gallery_images', true );
		$ids     = $ids_raw ? array_filter( array_map( 'absint', explode( ',', $ids_raw ) ) ) : array();
		?>
		<div id="wpg-gallery-field">
			<ul id="wpg-gallery-list" class="wpg-gallery-list">
				<?php foreach ( $ids as $attachment_id ) : ?>
					<?php
					$thumb = wp_get_attachment_image_src( $attachment_id, 'thumbnail' );
					if ( ! $thumb ) {
						continue;
					}
					?>
					<li class="wpg-gallery-item" data-id="<?php echo esc_attr( $attachment_id ); ?>">
						<img src="<?php echo esc_url( $thumb[0] ); ?>" alt="" />
						<button type="button" class="wpg-remove-image" aria-label="<?php esc_attr_e( 'Remove image', 'wordpress-project-gallery' ); ?>">&times;</button>
					</li>
				<?php endforeach; ?>
			</ul>
			<input type="hidden" id="wpg_gallery_images" name="wpg_gallery_images" value="<?php echo esc_attr( implode( ',', $ids ) ); ?>" />
			<p>
				<button type="button" class="button button-secondary" id="wpg-add-images"><?php esc_html_e( 'Add Project Images', 'wordpress-project-gallery' ); ?></button>
			</p>
			<p class="description"><?php esc_html_e( 'Drag images to reorder. This order is used for the frontend gallery.', 'wordpress-project-gallery' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Save Project meta fields.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public function save_meta( $post_id, $post ) {
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) || ! wp_verify_nonce( wp_unslash( $_POST[ self::NONCE_NAME ] ), self::NONCE_ACTION ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Project URL.
		if ( isset( $_POST['wpg_project_url'] ) ) {
			update_post_meta( $post_id, '_wpg_project_url', esc_url_raw( wp_unslash( $_POST['wpg_project_url'] ) ) );
		}

		// Project Year.
		if ( isset( $_POST['wpg_project_year'] ) ) {
			$year = sanitize_text_field( wp_unslash( $_POST['wpg_project_year'] ) );
			$year = preg_match( '/^\d{4}$/', $year ) ? $year : '';
			update_post_meta( $post_id, '_wpg_project_year', $year );
		}

		// Builder.
		if ( isset( $_POST['wpg_builder'] ) ) {
			update_post_meta( $post_id, '_wpg_builder', sanitize_text_field( wp_unslash( $_POST['wpg_builder'] ) ) );
		}

		// Value.
		if ( isset( $_POST['wpg_value'] ) ) {
			update_post_meta( $post_id, '_wpg_value', sanitize_text_field( wp_unslash( $_POST['wpg_value'] ) ) );
		}

		// Client.
		if ( isset( $_POST['wpg_client'] ) ) {
			update_post_meta( $post_id, '_wpg_client', sanitize_text_field( wp_unslash( $_POST['wpg_client'] ) ) );
		}

		// Technologies.
		if ( isset( $_POST['wpg_technologies'] ) ) {
			$technologies = sanitize_text_field( wp_unslash( $_POST['wpg_technologies'] ) );
			$parts        = array_filter( array_map( 'trim', explode( ',', $technologies ) ) );
			update_post_meta( $post_id, '_wpg_technologies', implode( ', ', array_map( 'sanitize_text_field', $parts ) ) );
		}

		// Featured toggle.
		$featured = isset( $_POST['wpg_featured'] ) ? '1' : '';
		update_post_meta( $post_id, '_wpg_featured', $featured );

		// Gallery images.
		if ( isset( $_POST['wpg_gallery_images'] ) ) {
			$raw_ids = sanitize_text_field( wp_unslash( $_POST['wpg_gallery_images'] ) );
			$ids     = array_filter( array_map( 'absint', explode( ',', $raw_ids ) ) );
			update_post_meta( $post_id, '_wpg_gallery_images', implode( ',', $ids ) );
		}
	}
}
