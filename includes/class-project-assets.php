<?php
/**
 * Conditionally enqueues public and admin assets.
 *
 * Public assets are only loaded on a singular Project page or on a
 * post/page whose content contains the [project_gallery] shortcode -
 * never globally on every page.
 *
 * @package WordPress_Project_Gallery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPG_Assets
 */
class WPG_Assets {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_public_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Determine whether the current request needs the public assets.
	 *
	 * @return bool
	 */
	private function should_enqueue() {
		global $post;

		$should = false;

		if ( is_singular( WPG_Post_Type::POST_TYPE ) || is_post_type_archive( WPG_Post_Type::POST_TYPE ) ) {
			$should = true;
		} elseif ( $post instanceof WP_Post && has_shortcode( $post->post_content, 'project_gallery' ) ) {
			$should = true;
		}

		/**
		 * Filter whether the Project Gallery public assets should load on
		 * this request. Useful if the shortcode is rendered from a widget
		 * or a template area that isn't part of the main post content.
		 *
		 * @param bool $should Whether to enqueue.
		 */
		return (bool) apply_filters( 'wpg_should_enqueue_assets', $should );
	}

	/**
	 * Enqueue frontend CSS/JS only when needed.
	 */
	public function enqueue_public_assets() {
		if ( ! $this->should_enqueue() ) {
			return;
		}

		$settings = get_option( 'wpg_settings', array() );

		wp_enqueue_style(
			'wpg-public',
			WPG_PLUGIN_URL . 'public/css/project-gallery.css',
			array(),
			WPG_VERSION
		);

		$accent = isset( $settings['accent_color'] ) ? $settings['accent_color'] : '';
		if ( $accent && preg_match( '/^#[0-9a-fA-F]{3,6}$/', $accent ) ) {
			wp_add_inline_style(
				'wpg-public',
				sprintf( ':root{--wpg-accent-color:%s;}', esc_html( $accent ) )
			);
		}

		wp_enqueue_script(
			'wpg-public',
			WPG_PLUGIN_URL . 'public/js/project-gallery.js',
			array(),
			WPG_VERSION,
			true
		);

		wp_localize_script(
			'wpg-public',
			'wpgGallery',
			array(
				'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
				'enableLightbox'  => ! isset( $settings['enable_lightbox'] ) || ! empty( $settings['enable_lightbox'] ),
				'i18n'            => array(
					'close'    => __( 'Close', 'wordpress-project-gallery' ),
					'previous' => __( 'Previous image', 'wordpress-project-gallery' ),
					'next'     => __( 'Next image', 'wordpress-project-gallery' ),
					'loading'  => __( 'Loading…', 'wordpress-project-gallery' ),
					'error'    => __( 'Unable to load projects. Please try again.', 'wordpress-project-gallery' ),
				),
			)
		);
	}

	/**
	 * Enqueue admin CSS/JS only on the Project edit screen and settings page.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_assets( $hook ) {
		$screen = get_current_screen();

		if ( ! $screen ) {
			return;
		}

		$is_project_edit_screen = ( 'project' === $screen->post_type ) && in_array( $screen->base, array( 'post', 'post-new' ), true );
		$is_settings_screen     = ( 'settings_page_wpg-settings' === $screen->id );

		if ( $is_project_edit_screen ) {
			wp_enqueue_media();

			wp_enqueue_style(
				'wpg-admin',
				WPG_PLUGIN_URL . 'admin/css/admin.css',
				array(),
				WPG_VERSION
			);

			wp_enqueue_script(
				'wpg-admin',
				WPG_PLUGIN_URL . 'admin/js/admin.js',
				array( 'jquery', 'jquery-ui-sortable' ),
				WPG_VERSION,
				true
			);

			wp_localize_script(
				'wpg-admin',
				'wpgAdmin',
				array(
					'i18n' => array(
						'modalTitle'  => __( 'Select Project Images', 'wordpress-project-gallery' ),
						'modalButton' => __( 'Add to gallery', 'wordpress-project-gallery' ),
						'remove'      => __( 'Remove image', 'wordpress-project-gallery' ),
					),
				)
			);
		}

		if ( $is_settings_screen ) {
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_script(
				'wpg-admin-settings',
				WPG_PLUGIN_URL . 'admin/js/admin.js',
				array( 'jquery', 'wp-color-picker' ),
				WPG_VERSION,
				true
			);
		}
	}
}
