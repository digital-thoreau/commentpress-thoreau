<?php
/**
 * Table of Contents Dropdown Template.
 *
 * @package CommentPress_Thoreau
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Get core plugin reference.
$core = commentpress_core();

?>
<!-- toc_sidebar.php (CommentPress Thoreau theme) -->

<div id="navigation">
	<div id="toc_sidebar" class="sidebar_container">

		<?php

		/**
		 * Fires before the Contents Tab.
		 *
		 * @since 3.4
		 */
		do_action( 'cp_content_tab_before' );

		?>

		<div class="sidebar_header">
			<h2><?php esc_html_e( 'Contents', 'commentpress-thoreau' ); ?></h2>
		</div>

		<div class="sidebar_minimiser">
			<div class="sidebar_contents_wrapper">

				<?php

				/**
				 * Fires before the Search accordion.
				 *
				 * @since 3.4
				 */
				do_action( 'cp_content_tab_before_search' );

				?>

				<h3 class="activity_heading search_heading">
					<?php

					/**
					 * Filters the Search accordion title.
					 *
					 * @since 3.4
					 */
					echo apply_filters( 'cp_content_tab_search_title', __( 'Search', 'commentpress-thoreau' ) );

					?>
				</h3>

				<div class="paragraph_wrapper search_wrapper">
					<div id="document_search">
						<?php get_search_form(); ?>
					</div><!-- /document_search -->
				</div>

				<?php if ( apply_filters( 'cp_content_tab_special_pages_visible', true ) ) : ?>
					<h3 class="activity_heading special_pages_heading">
						<?php

						/**
						 * Filters the Special Pages accordion title.
						 *
						 * @since 3.4
						 */
						echo apply_filters( 'cp_content_tab_special_pages_title', __( 'Special Pages', 'commentpress-thoreau' ) );

						?>
					</h3>

					<div class="paragraph_wrapper special_pages_wrapper">
						<?php

						/**
						 * Try to locate template using WordPress method.
						 *
						 * @since 3.4
						 *
						 * @param str The existing path returned by WordPress.
						 * @return str The modified path.
						 */
						$cp_navigation = apply_filters( 'cp_template_navigation', locate_template( 'assets/templates/navigation.php' ) );

						// Load it if we find it.
						if ( $cp_navigation != '' ) {
							load_template( $cp_navigation );
						}

						?>
					</div>
				<?php endif; ?>

				<h3 class="activity_heading toc_heading">
					<?php

					/**
					 * Filters the Table of Contents accordion title.
					 *
					 * @since 3.4
					 */
					echo apply_filters( 'cp_content_tab_toc_title', __( 'Table of Contents', 'commentpress-thoreau' ) );

					?>
				</h3>

				<div class="paragraph_wrapper start_open">
					<?php if ( ! empty( $core ) ) : ?>
						<ul id="toc_list">
							<?php

							// Exclude Featured Comments & Liked Comments pages.
							$excluded = array_unique( array_merge(
								commentpress_thoreau_get_featured_comments_pages(),
								commentpress_thoreau_get_liked_comments_pages()
							));

							// Show the TOC.
							echo $core->display->get_toc_list( $excluded );

							?>
						</ul>
					<?php endif; ?>
				</div>

				<?php

				/**
				 * Fires after the Contents Tab.
				 *
				 * @since 3.4
				 */
				do_action( 'cp_content_tab_after' );

				?>

			</div><!-- /sidebar_contents_wrapper -->
		</div><!-- /sidebar_minimiser -->

	</div><!-- /toc_sidebar -->
</div><!-- /navigation -->
