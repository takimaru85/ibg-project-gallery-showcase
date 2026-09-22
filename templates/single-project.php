<?php
/**
 * Default single Project template.
 *
 * A theme can override this by adding its own single-project.php.
 *
 * @package WordPress_Project_Gallery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();

	$post_id     = get_the_ID();
	$gallery_ids = WPG_Gallery::get_gallery_ids( $post_id );
	$settings    = wp_parse_args( get_option( 'wpg_settings', array() ), WPG_Settings::get_defaults() );
	$prev_post   = get_previous_post();
	$next_post   = get_next_post();
	$back_url    = WPG_Template::get_back_url();
	?>

	<article id="project-<?php echo esc_attr( $post_id ); ?>" <?php post_class( 'wpg-single-project' ); ?>>
		<div class="wpg-single-layout">

			<div class="wpg-single-gallery-col">
				<?php if ( ! empty( $gallery_ids ) || has_post_thumbnail() ) : ?>
					<div
						class="wpg-single-gallery"
						data-lightbox="<?php echo esc_attr( ! empty( $settings['enable_lightbox'] ) ? '1' : '0' ); ?>"
					>
						<div class="wpg-gallery-main">
							<?php
							if ( ! empty( $gallery_ids ) ) {
								$first_full = wp_get_attachment_image_url( $gallery_ids[0], 'large' );
								$first_full = $first_full ? $first_full : wp_get_attachment_image_url( $gallery_ids[0], 'full' );
							} elseif ( has_post_thumbnail() ) {
								$first_full = get_the_post_thumbnail_url( $post_id, 'large' );
							} else {
								$first_full = '';
							}
							?>
							<button type="button" class="wpg-gallery-main-trigger" id="wpg-main-image-trigger" aria-label="<?php esc_attr_e( 'View larger image', 'wordpress-project-gallery' ); ?>">
								<img id="wpg-main-image" src="<?php echo esc_url( $first_full ); ?>" alt="<?php the_title_attribute(); ?>" />
							</button>

							<?php if ( count( $gallery_ids ) > 1 ) : ?>
								<button type="button" class="wpg-gallery-nav wpg-gallery-prev" aria-label="<?php esc_attr_e( 'Previous image', 'wordpress-project-gallery' ); ?>">
									<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
										<path d="M15 5l-7 7 7 7" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" />
									</svg>
								</button>
								<button type="button" class="wpg-gallery-nav wpg-gallery-next" aria-label="<?php esc_attr_e( 'Next image', 'wordpress-project-gallery' ); ?>">
									<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
										<path d="M9 5l7 7-7 7" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" />
									</svg>
								</button>
							<?php endif; ?>
						</div>

						<?php if ( count( $gallery_ids ) > 1 ) : ?>
							<ul class="wpg-gallery-thumbs" role="list">
								<?php foreach ( $gallery_ids as $index => $attachment_id ) : ?>
									<?php
									$thumb_url = wp_get_attachment_image_url( $attachment_id, 'medium' );
									$full_url  = wp_get_attachment_image_url( $attachment_id, 'large' );
									$full_url  = $full_url ? $full_url : wp_get_attachment_image_url( $attachment_id, 'full' );
									$alt_text  = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
									if ( ! $thumb_url ) {
										continue;
									}
									?>
									<li>
										<button
											type="button"
											class="wpg-thumb<?php echo 0 === $index ? ' is-active' : ''; ?>"
											data-full="<?php echo esc_url( $full_url ); ?>"
											data-index="<?php echo esc_attr( $index ); ?>"
											aria-label="<?php echo esc_attr( sprintf( /* translators: %d: image number. */ __( 'Show image %d', 'wordpress-project-gallery' ), $index + 1 ) ); ?>"
											<?php echo 0 === $index ? ' aria-current="true"' : ''; ?>
										>
											<img src="<?php echo esc_url( $thumb_url ); ?>" alt="<?php echo esc_attr( $alt_text ); ?>" loading="lazy" />
										</button>
									</li>
								<?php endforeach; ?>
							</ul>

							<div class="wpg-gallery-full-urls" id="wpg-gallery-full-urls" hidden>
								<?php foreach ( $gallery_ids as $attachment_id ) : ?>
									<?php
									$full_url = wp_get_attachment_image_url( $attachment_id, 'large' );
									$full_url = $full_url ? $full_url : wp_get_attachment_image_url( $attachment_id, 'full' );
									?>
									<span data-full="<?php echo esc_url( $full_url ); ?>"></span>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>

			<div class="wpg-single-info-col">
				<a class="wpg-back-button" href="<?php echo esc_url( $back_url ); ?>">
					<svg class="wpg-back-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
						<path d="M15 5l-7 7 7 7" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" />
					</svg>
					<?php esc_html_e( 'Back', 'wordpress-project-gallery' ); ?>
				</a>

				<h1 class="wpg-single-title"><?php the_title(); ?></h1>

				<?php if ( get_the_content() ) : ?>
					<div class="wpg-single-description">
						<?php the_content(); ?>
					</div>
				<?php endif; ?>

				<?php WPG_Template::render_meta_table( $post_id ); ?>

				<?php if ( $prev_post || $next_post ) : ?>
					<nav class="wpg-project-nav" aria-label="<?php esc_attr_e( 'Project navigation', 'wordpress-project-gallery' ); ?>">
						<?php if ( $prev_post ) : ?>
							<a class="wpg-project-nav-prev" href="<?php echo esc_url( add_query_arg( 'wpg_from', rawurlencode( $back_url ), get_permalink( $prev_post ) ) ); ?>">
								&larr; <?php echo esc_html( get_the_title( $prev_post ) ); ?>
							</a>
						<?php else : ?>
							<span></span>
						<?php endif; ?>

						<?php if ( $next_post ) : ?>
							<a class="wpg-project-nav-next" href="<?php echo esc_url( add_query_arg( 'wpg_from', rawurlencode( $back_url ), get_permalink( $next_post ) ) ); ?>">
								<?php echo esc_html( get_the_title( $next_post ) ); ?> &rarr;
							</a>
						<?php endif; ?>
					</nav>
				<?php endif; ?>
			</div>

		</div>
	</article>

	<?php
endwhile;

get_footer();
