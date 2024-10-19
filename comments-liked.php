<?php
/**
 * Template Name: Liked Comments
 *
 * @package CommentPress_Thoreau
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/*
 * Get page content.
 *
 * I prefer to do this before the page is sent to the browser: the markup is
 * generated before anything is displayed.
 */
$_page_content = commentpress_thoreau_get_liked_comments_page_content();

get_header();

?>
<!-- comments-featured.php -->
<div id="wrapper">
	<div id="main_wrapper" class="clearfix">
		<div id="page_wrapper">

			<div id="content">
				<div class="post">

					<div id="comments_in_page_wrapper">
						<?php echo $_page_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>

				</div><!-- /post -->
			</div><!-- /content -->

		</div><!-- /page_wrapper -->
	</div><!-- /main_wrapper -->
</div><!-- /wrapper -->

<?php get_sidebar(); ?>

<?php get_footer(); ?>
