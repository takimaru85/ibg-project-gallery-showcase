<?php
/**
 * Shared helpers for reading project data and rendering markup that is
 * reused by the shortcode, the AJAX filter handler, and the single
 * project template.
 *
 * @package WordPress_Project_Gallery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPG_Gallery
 */
class WPG_Gallery {

	/**
	 * Get the ordered gallery attachment IDs for a project.
	 *
	 * @param int $post_id Project post ID.
	 *
	 * @return int[]
	 */
	public static function get_gallery_ids( $post_id ) {
		$raw = get_post_meta( $post_id, '_wpg_gallery_images', true );

		if ( ! $raw ) {
			return array();
		}

		return array_values( array_filter( array_map( 'absint', explode( ',', $raw ) ) ) );
	}

	/**
	 * Build a WP_Query for the given shortcode/AJAX attributes.
	 *
	 * @param array $atts Sanitized attributes: category, columns, limit, featured, orderby, order, paged.
	 *
	 * @return WP_Query
	 */
	public static function get_projects_query( $atts ) {
		$args = array(
			'post_type'      => WPG_Post_Type::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => $atts['limit'],
			'orderby'        => $atts['orderby'],
			'order'          => $atts['order'],
			'paged'          => isset( $atts['paged'] ) ? max( 1, absint( $atts['paged'] ) ) : 1,
			'no_found_rows'  => true,
			'ignore_sticky_posts' => true,
		);

		if ( ! empty( $atts['category'] ) ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => WPG_Taxonomy::TAXONOMY,
					'field'    => 'slug',
					'terms'    => $atts['category'],
				),
			);
		}

		if ( ! empty( $atts['featured'] ) ) {
			$args['meta_query'] = array(
				array(
					'key'   => '_wpg_featured',
					'value' => '1',
				),
			);
		}

		return new WP_Query( $args );
	}

	/**
	 * Render a grid of project cards for the given WP_Query.
	 *
	 * @param WP_Query $query   Query with project posts.
	 * @param int      $columns Number of grid columns (1-4).
	 *
	 * @return string HTML output.
	 */
	public static function render_grid( $query, $columns ) {
		ob_start();

		if ( $query->have_posts() ) {
			?>
			<div class="wpg-grid wpg-columns-<?php echo esc_attr( $columns ); ?>">
				<?php
				while ( $query->have_posts() ) :
					$query->the_post();
					self::render_card( get_the_ID() );
				endwhile;
				?>
			</div>
			<?php
			wp_reset_postdata();
		} else {
			?>
			<p class="wpg-no-projects"><?php esc_html_e( 'No projects found.', 'wordpress-project-gallery' ); ?></p>
			<?php
		}

		return ob_get_clean();
	}

	/**
	 * Render a single project card.
	 *
	 * @param int $post_id Project post ID.
	 */
	public static function render_card( $post_id ) {
		$title    = get_the_title( $post_id );
		$link     = add_query_arg( 'wpg_from', rawurlencode( WPG_Template::get_current_url() ), get_permalink( $post_id ) );
		$excerpt  = get_the_excerpt( $post_id );
		$year     = get_post_meta( $post_id, '_wpg_project_year', true );
		$terms    = get_the_terms( $post_id, WPG_Taxonomy::TAXONOMY );
		$category = ( $terms && ! is_wp_error( $terms ) ) ? $terms[0]->name : '';
		?>
		<article class="wpg-card">
			<a class="wpg-card-link" href="<?php echo esc_url( $link ); ?>" aria-label="<?php echo esc_attr( $title ); ?>">
				<div class="wpg-card-image">
					<?php if ( has_post_thumbnail( $post_id ) ) : ?>
						<?php echo get_the_post_thumbnail( $post_id, 'medium_large', array( 'alt' => esc_attr( $title ), 'loading' => 'lazy' ) ); ?>
					<?php else : ?>
						<div class="wpg-card-image-placeholder" aria-hidden="true"></div>
					<?php endif; ?>
					<div class="wpg-card-overlay">
						<span class="wpg-card-view"><?php esc_html_e( 'View Project', 'wordpress-project-gallery' ); ?></span>
					</div>
				</div>
				<div class="wpg-card-body">
					<h3 class="wpg-card-title"><?php echo esc_html( $title ); ?></h3>
					<?php if ( $category ) : ?>
						<span class="wpg-card-category"><?php echo esc_html( $category ); ?></span>
					<?php endif; ?>
					<?php if ( $excerpt ) : ?>
						<p class="wpg-card-excerpt"><?php echo esc_html( wp_trim_words( $excerpt, 18 ) ); ?></p>
					<?php endif; ?>
					<?php if ( $year ) : ?>
						<span class="wpg-card-year"><?php echo esc_html( $year ); ?></span>
					<?php endif; ?>
				</div>
			</a>
		</article>
		<?php
	}

	/**
	 * Flatten every project in the given query into one ordered list of
	 * photo-wall tiles: one entry per image (gallery images, or the
	 * featured image as a fallback).
	 *
	 * @param WP_Query $query Query with project posts.
	 *
	 * @return array[] Each item: title, category, image, link.
	 */
	public static function get_wall_tiles( $query ) {
		$tiles = array();

		if ( ! $query->have_posts() ) {
			return $tiles;
		}

		foreach ( $query->posts as $post ) {
			$post_id   = $post->ID;
			$title     = get_the_title( $post_id );
			$terms     = get_the_terms( $post_id, WPG_Taxonomy::TAXONOMY );
			$category  = ( $terms && ! is_wp_error( $terms ) ) ? $terms[0]->name : '';
			$link      = add_query_arg( 'wpg_from', rawurlencode( WPG_Template::get_current_url() ), get_permalink( $post_id ) );
			$image_ids = self::get_gallery_ids( $post_id );

			if ( empty( $image_ids ) && has_post_thumbnail( $post_id ) ) {
				$image_ids = array( get_post_thumbnail_id( $post_id ) );
			}

			foreach ( $image_ids as $attachment_id ) {
				$image_url = wp_get_attachment_image_url( $attachment_id, 'large' );

				if ( ! $image_url ) {
					continue;
				}

				$tiles[] = array(
					'title'    => $title,
					'category' => $category,
					'image'    => $image_url,
					'link'     => $link,
				);
			}
		}

		return $tiles;
	}

	/**
	 * Render one photo-wall tile.
	 *
	 * @param array $tile Tile data from get_wall_tiles().
	 */
	public static function render_wall_tile( $tile ) {
		?>
		<a class="wpg-archive-tile" href="<?php echo esc_url( $tile['link'] ); ?>">
			<img src="<?php echo esc_url( $tile['image'] ); ?>" alt="<?php echo esc_attr( $tile['title'] ); ?>" loading="lazy" />
			<span class="wpg-archive-tile-overlay">
				<span class="wpg-archive-tile-caption">
					<span class="wpg-archive-tile-title"><?php echo esc_html( $tile['title'] ); ?></span>
					<?php if ( $tile['category'] ) : ?>
						<span class="wpg-archive-tile-category"><?php echo esc_html( $tile['category'] ); ?></span>
					<?php endif; ?>
				</span>
			</span>
		</a>
		<?php
	}

	/**
	 * Render a full-bleed photo wall for the given query, optionally
	 * paginated with a "View More Projects" button.
	 *
	 * @param WP_Query $query    Query with project posts.
	 * @param int      $per_page How many tiles to reveal per "page". 0 = show all, no button.
	 * @param int      $show     How many tiles are already revealed (cumulative). 0 = use $per_page.
	 *
	 * @return string HTML output.
	 */
	public static function render_wall( $query, $per_page = 0, $show = 0 ) {
		$tiles = self::get_wall_tiles( $query );

		if ( empty( $tiles ) ) {
			return '<p class="wpg-no-projects">' . esc_html__( 'No projects found.', 'wordpress-project-gallery' ) . '</p>';
		}

		$total = count( $tiles );
		$show  = $show > 0 ? $show : ( $per_page > 0 ? $per_page : $total );
		$show  = min( $show, $total );
		$visible = array_slice( $tiles, 0, $show );

		ob_start();
		?>
		<div class="wpg-archive-grid">
			<?php foreach ( $visible as $tile ) : ?>
				<?php self::render_wall_tile( $tile ); ?>
			<?php endforeach; ?>
		</div>
		<?php if ( $total > $show ) : ?>
			<?php $next_show = min( $total, $show + ( $per_page > 0 ? $per_page : $total ) ); ?>
			<div class="wpg-wall-load-more">
				<a
					href="<?php echo esc_url( add_query_arg( 'wpg_show', $next_show ) ); ?>"
					class="wpg-load-more-btn"
					data-show="<?php echo esc_attr( $next_show ); ?>"
				>
					<?php esc_html_e( 'View More Projects', 'wordpress-project-gallery' ); ?>
				</a>
			</div>
		<?php endif; ?>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render the category filter navigation.
	 *
	 * @param string $active_category Currently active category slug, or empty for "All".
	 */
	public static function render_filter_nav( $active_category = '' ) {
		$terms = get_terms(
			array(
				'taxonomy'   => WPG_Taxonomy::TAXONOMY,
				'hide_empty' => true,
			)
		);

		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return;
		}
		?>
		<nav class="wpg-filter-nav" aria-label="<?php esc_attr_e( 'Filter projects by category', 'wordpress-project-gallery' ); ?>">
			<ul class="wpg-filter-list">
				<li>
					<a href="<?php echo esc_url( add_query_arg( 'wpg_category', '', '' ) ); ?>"
						class="wpg-filter-link<?php echo '' === $active_category ? ' is-active' : ''; ?>"
						data-category=""
						<?php echo '' === $active_category ? ' aria-current="true"' : ''; ?>>
						<?php esc_html_e( 'All', 'wordpress-project-gallery' ); ?>
					</a>
				</li>
				<?php foreach ( $terms as $term ) : ?>
					<li>
						<a href="<?php echo esc_url( add_query_arg( 'wpg_category', $term->slug, '' ) ); ?>"
							class="wpg-filter-link<?php echo $active_category === $term->slug ? ' is-active' : ''; ?>"
							data-category="<?php echo esc_attr( $term->slug ); ?>"
							<?php echo $active_category === $term->slug ? ' aria-current="true"' : ''; ?>>
							<?php echo esc_html( $term->name ); ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</nav>
		<?php
	}

	/**
	 * Render the category filter as a "Filter by:" dropdown. Works without
	 * JavaScript (a real form GET submission reloads the page with
	 * ?wpg_category=); the public script intercepts the submit for AJAX.
	 *
	 * @param string $active_category Currently active category slug, or empty for "All".
	 */
	public static function render_filter_dropdown( $active_category = '' ) {
		$terms = get_terms(
			array(
				'taxonomy'   => WPG_Taxonomy::TAXONOMY,
				'hide_empty' => true,
			)
		);

		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return;
		}

		$select_id = wp_unique_id( 'wpg-filter-select-' );
		?>
		<form class="wpg-filter-form" method="get" action="">
			<label for="<?php echo esc_attr( $select_id ); ?>"><?php esc_html_e( 'Filter by:', 'wordpress-project-gallery' ); ?></label>
			<span class="wpg-filter-select-wrap">
				<select name="wpg_category" id="<?php echo esc_attr( $select_id ); ?>" class="wpg-filter-select">
					<option value="" <?php selected( '', $active_category ); ?>><?php esc_html_e( 'All', 'wordpress-project-gallery' ); ?></option>
					<?php foreach ( $terms as $term ) : ?>
						<option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $term->slug, $active_category ); ?>><?php echo esc_html( $term->name ); ?></option>
					<?php endforeach; ?>
				</select>
			</span>
			<button type="submit" class="wpg-filter-submit"><?php esc_html_e( 'Filter', 'wordpress-project-gallery' ); ?></button>
		</form>
		<?php
	}
}
