<?php
/**
 * Search Template.
 *
 * @since 1.0
 *
 * @package CommentPress_Thoreau
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Get core plugin reference.
$core = commentpress_core();

// Init.
$_special_pages = [];

// If we have the plugin enabled.
if ( ! empty( $core ) ) {

	// Get Special Pages.
	$special_pages = $core->db->setting_get( 'cp_special_pages' );
	if ( is_array( $special_pages ) && ! empty( $special_pages ) ) {
		foreach ( $special_pages as $special_page ) {
			$_special_pages[] = (int) $special_page;
		}
	}

}

get_header();

?>
<!-- search.php -->
<div id="wrapper">
	<div id="main_wrapper" class="clearfix">
		<div id="page_wrapper">

			<?php commentpress_page_navigation_template(); ?>

			<div id="content" class="clearfix">

				<?php // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				<?php if ( isset( $_GET['s'] ) && ! empty( $_GET['s'] ) && have_posts() ) : ?>

					<div class="post">

						<h3 class="post_title"><?php esc_html_e( 'Search Results for', 'commentpress-thoreau' ); ?> &#8216;<?php the_search_query(); ?>&#8217;</h3>

						<?php while ( have_posts() ) : ?>

							<?php the_post(); ?>

							<?php if ( ! in_array( (int) get_the_ID(), $_special_pages, true ) ) : ?>

								<div class="search_result">

									<h3 id="post-<?php the_ID(); ?>"><a href="<?php the_permalink(); ?>" rel="bookmark" title="<?php esc_attr_e( 'Permanent Link to', 'commentpress-thoreau' ); ?> <?php the_title_attribute(); ?>"><?php the_title(); ?></a></h3>

									<div class="search_meta"<?php commentpress_post_meta_visibility( get_the_ID() ); ?>>
										<?php commentpress_echo_post_meta(); ?>
									</div>

									<?php the_content(); ?>

									<p class="search_meta"><?php the_tags( __( 'Tags: ', 'commentpress-thoreau' ), ', ', '<br />' ); ?> <?php esc_html_e( 'Posted in', 'commentpress-thoreau' ); ?> <?php the_category( ', ' ); ?> | <?php edit_post_link( __( 'Edit', 'commentpress-thoreau' ), '', ' | ' ); ?>  <?php comments_popup_link( __( 'No Comments &#187;', 'commentpress-thoreau' ), __( '1 Comment &#187;', 'commentpress-thoreau' ), __( '% Comments &#187;', 'commentpress-thoreau' ) ); ?></p>

								</div><!-- /search_result -->

							<?php endif; ?>

						<?php endwhile; ?>

					</div><!-- /post -->

				<?php else : ?>

					<div class="post">
						<h2><?php esc_html_e( 'Nothing found for', 'commentpress-thoreau' ); ?> &#8216;<?php the_search_query(); ?>&#8217;</h2>
						<p><?php esc_html_e( 'Try a different search?', 'commentpress-thoreau' ); ?></p>
						<?php get_search_form(); ?>
					</div><!-- /post -->

				<?php endif; ?>

			</div><!-- /content -->

			<div class="page_nav_lower">
				<?php commentpress_page_navigation_template(); ?>
			</div><!-- /page_nav_lower -->

		</div><!-- /page_wrapper -->
	</div><!-- /main_wrapper -->
</div><!-- /wrapper -->

<?php get_sidebar(); ?>

<?php get_footer(); ?>
