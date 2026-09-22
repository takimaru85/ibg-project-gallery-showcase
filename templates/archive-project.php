<?php
/**
 * Default Project archive template: a full-bleed photo wall of every
 * published project's images, with the project title/category revealed
 * on hover or keyboard focus.
 *
 * A theme can override this by adding its own archive-project.php.
 *
 * @package WordPress_Project_Gallery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$query = WPG_Gallery::get_projects_query(
	array(
		'category' => '',
		'limit'    => -1,
		'featured' => false,
		'orderby'  => 'date',
		'order'    => 'DESC',
	)
);

$settings = wp_parse_args( get_option( 'wpg_settings', array() ), WPG_Settings::get_defaults() );
$per_page = absint( $settings['wall_per_page'] );

$show = 0;
if ( isset( $_GET['wpg_show'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only pagination link, no state change.
	$show = absint( wp_unslash( $_GET['wpg_show'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only pagination link, no state change.
}
?>

<div
	class="wpg-archive-project wpg-gallery-wrapper"
	data-layout="wall"
	data-category=""
	data-orderby="date"
	data-order="DESC"
	data-featured=""
	data-per-page="<?php echo esc_attr( $per_page ); ?>"
	data-nonce="<?php echo esc_attr( wp_create_nonce( 'wpg_filter_projects' ) ); ?>"
>
	<header class="wpg-archive-header">
		<h1 class="wpg-archive-title"><?php post_type_archive_title(); ?></h1>
	</header>

	<div class="wpg-gallery-results">
		<?php echo WPG_Gallery::render_wall( $query, $per_page, $show ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- render_wall() escapes all dynamic values internally. ?>
	</div>
</div>

<?php
get_footer();
