=== WordPress Project Gallery ===
Contributors: ianolden
Tags: portfolio, gallery, projects, shortcode, showcase
Requires at least: 5.8
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A lightweight, native WordPress plugin for showcasing portfolio projects via the [project_gallery] shortcode. No page builder, no blocks, no bloat.

== Description ==

WordPress Project Gallery adds a "Project" custom post type and a `[project_gallery]` shortcode so you can build a portfolio/case-study grid on any post or page using nothing but core WordPress functionality.

It does **not** require and does **not** integrate with Elementor, Elementor Pro, Divi, Divi Builder, WPBakery, Bricks, Beaver Builder, Gutenberg blocks, ACF, or WooCommerce. It works on a stock WordPress install.

= Features =

* Custom post type `project` with its own admin menu (Add New, Edit, View, Duplicate).
* `project_category` taxonomy (hierarchical, manageable from wp-admin, with sensible defaults such as WordPress, Web Development, Mobile Apps, Plugins, WooCommerce, Elementor, Divi, UI/UX, Other).
* Project Details meta box: Project URL, Year, Client, Technologies, Featured toggle.
* Project Gallery meta box: multi-image picker (native Media Library) with drag-and-drop ordering.
* `[project_gallery]` shortcode with attributes for category, columns, limit, featured-only, ordering, layout, and an optional category filter.
* Two shortcode layouts: `grid` (default developer-portfolio cards) and `wall` (a full-bleed photo wall of every project image, title/category revealed on hover).
* Category filter that works with AJAX when JavaScript is available, and degrades to plain link-based filtering (via a `?wpg_category=` query var) when it isn't - for both layouts.
* Responsive CSS Grid card layout: 3 columns desktop, 2 tablet, 1 mobile (configurable per shortcode).
* A built-in Project archive page (`/project/` by default) that renders the same photo-wall layout automatically - no shortcode needed if you just want a dedicated portfolio page.
* Single project template with a main image + thumbnail gallery, a lightweight built-in lightbox (keyboard + touch swipe support), a "View Project" button, and Previous/Next project navigation.
* Project Details fields: Project URL, Year, Builder, Value, Client, Technologies, Featured toggle - only the ones you fill in are shown.
* Settings page (Settings -> Project Gallery) for the permalink slug, default columns/limit, filter/animation/lightbox toggles, and an accent color.
* Assets (CSS/JS) are only enqueued on pages that actually use the shortcode or on a single Project page - never site-wide.
* Sanitized input, escaped output, nonce-protected admin/AJAX actions, capability checks throughout.

= Shortcode =

`[project_gallery]`

Automatically displays all published projects in a responsive grid.

= Shortcode Attributes =

* `category` - Limit results to a project_category slug. Example: `category="wordpress"`
* `columns` - Number of grid columns: 1-4. Default: `3`
* `limit` - Number of projects to show. Use `-1` for all. Default: `-1`
* `featured` - `yes` to show only Featured Projects. Default: off
* `orderby` - `date`, `title`, `menu_order`, or `rand`. Default: `date`
* `order` - `ASC` or `DESC`. Default: `DESC`
* `filter` - `yes` to show an All / category filter bar above the grid. Default: off
* `layout` - `grid` (card layout) or `wall` (full-bleed photo wall, one tile per project image). Default: `grid`

Attributes can be combined freely:

`[project_gallery category="wordpress" columns="3" limit="6"]`
`[project_gallery columns="4" featured="yes"]`
`[project_gallery filter="yes" orderby="title" order="ASC"]`
`[project_gallery layout="wall" filter="yes"]`

== Installation ==

1. Upload the `project-gallery` folder to `/wp-content/plugins/`.
2. Activate the plugin through the "Plugins" screen in WordPress.
3. Go to **Projects -> Add New** and create your first project.
4. Add `[project_gallery]` to any post or page.
5. (Optional) Visit **Settings -> Project Gallery** to adjust defaults.

== Managing Projects ==

* **Add a project:** Projects -> Add New. Fill in the title and description (the main content editor), set a Featured Image, and fill in the Project Details meta box (URL, Year, Client, Technologies, Featured toggle).
* **Add gallery images:** In the Project Gallery meta box, click "Add Project Images", select one or more images in the Media Library, and drag them into the order you want them to appear on the frontend.
* **Categorize a project:** Use the Project Categories panel on the edit screen (works like the native Categories panel).
* **Duplicate a project:** From the Projects list, hover a project and click "Duplicate". A draft copy is created with the same content, meta, gallery, featured image, and categories (the Featured toggle is reset).
* **Delete a project:** Use the normal Trash / Delete Permanently controls in the Projects list.

== Frequently Asked Questions ==

= Does this require Elementor, Divi, or Gutenberg blocks? =

No. The only frontend integration is the `[project_gallery]` shortcode, which works in the classic editor, the block editor's Shortcode block, widgets, or directly in a theme template via `do_shortcode( '[project_gallery]' )`.

= Can I filter by category without JavaScript? =

Yes. `[project_gallery filter="yes"]` renders normal links that reload the page with `?wpg_category=your-category`. If JavaScript is available, clicking a category instead triggers an AJAX request and updates the grid in place - JavaScript is an enhancement, not a requirement.

= Can I change the project URL slug (e.g. from /project/ to /portfolio/)? =

Yes, in Settings -> Project Gallery. Rewrite rules are flushed once automatically after you save a changed slug - the plugin never flushes rewrite rules on every page load.

= Does the plugin load CSS/JS on every page? =

No. Public assets only load on a singular Project page or a post/page whose content contains `[project_gallery]`.

= Will deleting the plugin delete my projects? =

No. Uninstalling only removes the plugin's own settings. Your Project posts, meta, images, and categories are left untouched.

== Changelog ==

= 1.1.0 =
* Added a Project archive page (photo wall) and a matching `layout="wall"` shortcode option.
* Added Builder and Value fields to Project Details.
* Redesigned the single project page (two-column layout, Back button, meta table).

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.1.0 =
Adds an archive page and photo-wall shortcode layout; rewrite rules flush automatically once after updating.

= 1.0.0 =
Initial release.
