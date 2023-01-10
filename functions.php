<?php
/**
 * CommentPress Thoreau Theme Functions.
 *
 * Theme amendments and overrides.
 *
 * This file is loaded before the CommentPress Flat Theme's functions.php file,
 * so changes and updates can be made here. Most theme-related functions are
 * pluggable, so if they are defined here, they will override the ones defined in
 * the CommentPress Flat Theme or common theme functions file.
 *
 * @package CommentPress_Thoreau
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Set our version here.
define( 'COMMENTPRESS_THOREAU_VERSION', '1.0.1' );



/**
 * Augment the CommentPress Flat Theme setup function.
 *
 * @since 1.0
 */
function commentpress_thoreau_setup() {

	/*
	 * Make theme available for translation.
	 *
	 * Translations can be added to the /languages directory of the child theme.
	 */
	load_child_theme_textdomain(
		'commentpress-thoreau',
		get_stylesheet_directory() . '/languages'
	);

	// Get Featured Comments & Liked Comments pages.
	$pages = array_unique( array_merge(
		commentpress_thoreau_get_featured_comments_pages(),
		commentpress_thoreau_get_liked_comments_pages()
	));

	// Add a filter to exclude them from CommentPress page nav.
	if ( count( $pages ) > 0 ) {
		add_filter( 'cp_exclude_pages_from_nav', 'commentpress_thoreau_exclude_pages_from_nav' );
	}

}

// Hook into after_setup_theme.
add_action( 'after_setup_theme', 'commentpress_thoreau_setup' );



/**
 * Exclude the Featured and Liked Comments page(s).
 *
 * @since 1.0
 *
 * @param array $excludes Existing pages to exclude.
 * @return array $excludes Modified pages to exclude.
 */
function commentpress_thoreau_exclude_pages_from_nav( $excludes ) {

	// Get Featured Comments & Liked Comments pages.
	$pages = array_unique( array_merge(
		commentpress_thoreau_get_featured_comments_pages(),
		commentpress_thoreau_get_liked_comments_pages()
	));

	// Merge with exclude array if we have some.
	if ( count( $pages ) > 0 ) {
		$excludes = array_unique( array_merge(
			$excludes,
			$pages
		));
	}

	// Override.
	return $excludes;

}



/**
 * Enqueue child theme styles.
 *
 * Styles can be overridden because the child theme is:
 * 1. enqueueing later than the CommentPress Modern Theme
 * 2. making the file dependent on the CommentPress Modern Theme's stylesheet
 *
 * @since 1.0
 */
function commentpress_thoreau_enqueue_styles() {

	// Dequeue parent theme colour styles.
	wp_dequeue_style( 'cp_colours_css' );

	// Add child theme's CSS file.
	wp_enqueue_style(
		'commentpress_thoreau_css',
		get_stylesheet_directory_uri() . '/assets/css/commentpress-thoreau.css',
		[ 'cp_screen_css' ], // Dependencies.
		COMMENTPRESS_THOREAU_VERSION, // Version.
		'all' // Media.
	);

	// Get Featured Comments & Liked Comments pages.
	$pages = array_unique( array_merge(
		commentpress_thoreau_get_featured_comments_pages(),
		commentpress_thoreau_get_liked_comments_pages()
	));

	// If we have some.
	if ( count( $pages ) > 0 ) {

		// Access post.
		global $post;

		// Bail if there's no post object.
		if ( ! is_object( $post ) ) {
			return;
		}

		// If it's our Featured Comments or Liked Comments page.
		if ( in_array( $post->ID, $pages ) ) {

			// Enqueue accordion-like js.
			wp_enqueue_script(
				'cp_special',
				get_template_directory_uri() . '/assets/js/all-comments.js',
				[ 'cp_form' ], // Dependencies.
				COMMENTPRESS_THOREAU_VERSION,
				false
			);

			// Add our theme javascript.
			wp_enqueue_script(
				'commentpress_thoreau_js',
				get_stylesheet_directory_uri() . '/assets/js/commentpress-thoreau.js',
				[ 'cp_common_js' ],  // Dependencies.
				COMMENTPRESS_THOREAU_VERSION, // Version.
				false
			);

		}

	}

}

// Add action for the above.
add_action( 'wp_enqueue_scripts', 'commentpress_thoreau_enqueue_styles', 998 );



/**
 * Get the Featured Comments page(s).
 *
 * @since 1.0
 *
 * @param bool $is_commentable True if commentable, false otherwise.
 * @return bool $is_commentable True if commentable, false otherwise.
 */
function commentpress_thoreau_check_commentable( $is_commentable ) {

	// Get Featured Comments & Liked Comments pages.
	$pages = array_unique( array_merge(
		commentpress_thoreau_get_featured_comments_pages(),
		commentpress_thoreau_get_liked_comments_pages()
	));

	// If we have some.
	if ( count( $pages ) > 0 ) {

		// Access post.
		global $post;

		// Override if it's our Featured Comments or Liked Comments page.
		if ( ( $post instanceof WP_Post ) && in_array( $post->ID, $pages ) ) {
			return false;
		}

	}

	// Pass through.
	return $is_commentable;

}

// Add a filter for the above.
add_filter( 'cp_is_commentable', 'commentpress_thoreau_check_commentable' );



/**
 * Get the Featured Comments page(s).
 *
 * @since 1.0
 *
 * @return array $pages The IDs of the Featured Comments page(s).
 */
function commentpress_thoreau_get_featured_comments_pages() {

	// Define as static.
	static $pages;

	// Return if we have it.
	if ( isset( $pages ) ) {
		return $pages;
	}

	// Init list as empty.
	$pages = [];

	// Get Featured Comments special page(s).
	$commentpress_thoreau_pages = get_posts( [
		'post_type' => 'page',
		'orderby' => 'post_date',
		'order' => 'DESC',
		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		'meta_key' => '_wp_page_template',
		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		'meta_value' => 'comments-featured.php',
	] );

	// Add them if we get them.
	if ( ! empty( $commentpress_thoreau_pages ) ) {
		foreach ( $commentpress_thoreau_pages as $commentpress_thoreau_page ) {
			$pages[] = $commentpress_thoreau_page->ID;
		}
	}

	// --<
	return $pages;

}



if ( ! function_exists( 'commentpress_thoreau_get_featured_comments_page_content' ) ) :

	/**
	 * Featured comments page display function.
	 *
	 * @since 1.0
	 *
	 * @return str $_page_content The page content.
	 */
	function commentpress_thoreau_get_featured_comments_page_content() {

		// Declare access to globals.
		global $bp_groupsites;

		// Get core plugin reference.
		$core = commentpress_core();

		// Remove action to insert comments-by-group filter.
		if ( is_object( $bp_groupsites ) ) {
			remove_action( 'commentpress_before_scrollable_comments', [ $bp_groupsites->activity, 'get_group_comments_filter' ] );
		}

		// Set page title.
		$pagetitle = apply_filters(
			'cp_page_featured_comments_title',
			__( 'Featured Comments', 'commentpress-thoreau' )
		);

		// Construct title.
		$_page_content = '<h2 class="post_title">' . $pagetitle . '</h2>' . "\n\n";

		// Get page or post.
		$page_or_post = $core->nav->setting_post_type_get();

		// Set default.
		$blogtitle = apply_filters(
			'cp_page_featured_comments_blog_title',
			__( 'Comments on the Blog', 'commentpress-thoreau' )
		);

		// Set default.
		$booktitle = apply_filters(
			'cp_page_featured_comments_book_title',
			__( 'Comments on the Pages', 'commentpress-thoreau' )
		);

		// Get title.
		$title = ( $page_or_post == 'page' ) ? $booktitle : $blogtitle;

		// Get data.
		$_data = commentpress_thoreau_get_featured_comments_content( $page_or_post );

		// Did we get any?
		if ( $_data != '' ) {

			// Set title.
			$_page_content .= '<h3 class="comments_hl">' . $title . '</h3>' . "\n\n";

			// Set data.
			$_page_content .= $_data . "\n\n";

		}

		// Get data for other page type.
		$other_type = ( $page_or_post == 'page' ) ? 'post' : 'page';

		// Get title.
		$title = ( $page_or_post == 'page' ) ? $blogtitle : $booktitle;

		// Get data.
		$_data = commentpress_thoreau_get_featured_comments_content( $other_type );

		// Did we get any?
		if ( $_data != '' ) {

			// Set title.
			$_page_content .= '<h3 class="comments_hl">' . $title . '</h3>' . "\n\n";

			// Set data.
			$_page_content .= $_data . "\n\n";

		}

		// --<
		return $_page_content;

	}

endif;



if ( ! function_exists( 'commentpress_thoreau_get_featured_comments_content' ) ) :

	/**
	 * Featured comments page display function.
	 *
	 * @since 1.0
	 *
	 * @param str $page_or_post The page or post flag.
	 * @return str $html The page content.
	 */
	function commentpress_thoreau_get_featured_comments_content( $page_or_post = 'page' ) {

		// Declare access to globals.
		global $cp_comment_output;

		// Init output.
		$html = '';

		// Get all approved featured comments.
		$featured_comments = get_comments( [
			'status' => 'approve',
			'orderby' => 'comment_post_ID,comment_date',
			'order' => 'ASC',
			'post_type' => $page_or_post,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'meta_query' => [
				[
					'key'   => 'featured',
					'value' => '1',
				],
			],
		] );

		// Kick out if none.
		if ( count( $featured_comments ) == 0 ) {
			return $html;
		}

		// Build list of posts to which they are attached.
		$posts_with = [];
		$post_comment_counts = [];
		foreach ( $featured_comments as $comment ) {

			// Add to posts with comments array.
			if ( ! in_array( $comment->comment_post_ID, $posts_with ) ) {
				$posts_with[] = $comment->comment_post_ID;
			}

			// Increment counter.
			if ( ! isset( $post_comment_counts[ $comment->comment_post_ID ] ) ) {
				$post_comment_counts[ $comment->comment_post_ID ] = 1;
			} else {
				$post_comment_counts[ $comment->comment_post_ID ]++;
			}

		}

		// Kick out if none.
		if ( count( $posts_with ) == 0 ) {
			return $html;
		}

		// Get those posts.
		$posts = get_posts( [
			'orderby' => 'comment_count',
			'order' => 'DESC',
			'post_type' => $page_or_post,
			'include' => $posts_with,
		] );

		// Kick out if none.
		if ( count( $posts ) == 0 ) {
			return $html;
		}

		// Open ul.
		$html .= '<ul class="all_comments_listing">' . "\n\n";

		foreach ( $posts as $_post ) {

			// Open li.
			$html .= '<li class="page_li"><!-- page li -->' . "\n\n";

			// Define comment count.
			$comment_count_text = sprintf( _n(
				// Singular.
				'<span class="cp_comment_count">%d</span> comment',
				// Plural.
				'<span class="cp_comment_count">%d</span> comments',
				// Number.
				$post_comment_counts[ $_post->ID ],
				// Domain.
				'commentpress-thoreau'
				// Substitution.
			), $post_comment_counts[ $_post->ID ] );

			// Show it.
			$html .= '<h4>' . esc_html( $_post->post_title ) . ' <span>(' . $comment_count_text . ')</span></h4>' . "\n\n";

			// Open comments div.
			$html .= '<div class="item_body">' . "\n\n";

			// Open ul.
			$html .= '<ul class="item_ul">' . "\n\n";

			// Open li.
			$html .= '<li class="item_li"><!-- item li -->' . "\n\n";

			// Show the comments.
			foreach ( $featured_comments as $comment ) {
				if ( $comment->comment_post_ID == $_post->ID ) {
					$html .= commentpress_format_comment( $comment );
				}
			}

			// Close li.
			$html .= '</li><!-- /item li -->' . "\n\n";

			// Close ul.
			$html .= '</ul>' . "\n\n";

			// Close item div.
			$html .= '</div><!-- /item_body -->' . "\n\n";

			// Close li.
			$html .= '</li><!-- /page li -->' . "\n\n\n\n";

		}

		// Close ul.
		$html .= '</ul><!-- /all_comments_listing -->' . "\n\n";

		// --<
		return $html;

	}

endif;



/**
 * Get the Liked Comments page(s).
 *
 * @since 1.0
 *
 * @return array $pages The IDs of the Liked Comments page(s).
 */
function commentpress_thoreau_get_liked_comments_pages() {

	// Define as static.
	static $pages;

	// Return if we have it.
	if ( isset( $pages ) ) {
		return $pages;
	}

	// Init list as empty.
	$pages = [];

	// Get Liked Comments special page.
	$commentpress_thoreau_pages = get_posts( [
		'post_type' => 'page',
		'orderby' => 'post_date',
		'order' => 'DESC',
		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		'meta_key' => '_wp_page_template',
		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		'meta_value' => 'comments-liked.php',
	] );

	// Add them if we get them.
	if ( ! empty( $commentpress_thoreau_pages ) ) {
		foreach ( $commentpress_thoreau_pages as $commentpress_thoreau_page ) {
			$pages[] = $commentpress_thoreau_page->ID;
		}
	}

	// --<
	return $pages;

}



/**
 * Show our Liked Comments link.
 *
 * @since 1.0
 */
function commentpress_thoreau_get_liked_comments_link() {

	// Get Liked Comments special page.
	$commentpress_thoreau_pages = commentpress_thoreau_get_liked_comments_pages();

	// Did we get them?
	if ( ! empty( $commentpress_thoreau_pages ) ) {

		// Access current post.
		global $post;

		// Loop.
		foreach ( $commentpress_thoreau_pages as $post_id ) {

			// Init active page.
			$active_page = '';

			// If this is the active page.
			if ( ( $post instanceof WP_Post ) && $post_id == $post->ID ) {
				$active_page = ' class="active_page"';
			}

			echo '<li' . $active_page . '>' .
				'<a href="' . get_permalink( $post_id ) . '" title="' . esc_attr( wp_strip_all_tags( get_the_title( $post_id ) ) ) . '">' .
					get_the_title( $post_id ) .
				'</a>' .
			'</li>';

		}

	}

}

// Add action for the above.
add_action( 'cp_before_blog_page', 'commentpress_thoreau_get_liked_comments_link' );



if ( ! function_exists( 'commentpress_thoreau_get_liked_comments_page_content' ) ) :

	/**
	 * Liked comments page display function.
	 *
	 * @since 1.0
	 *
	 * @return str $_page_content The page content.
	 */
	function commentpress_thoreau_get_liked_comments_page_content() {

		// Access to globals.
		global $bp_groupsites;

		// Get core plugin reference.
		$core = commentpress_core();

		// Remove action to insert comments-by-group filter.
		if ( is_object( $bp_groupsites ) ) {
			remove_action( 'commentpress_before_scrollable_comments', [ $bp_groupsites->activity, 'get_group_comments_filter' ] );
		}

		// Add filter for comment meta, showing liked data.
		add_filter( 'commentpress_format_comment_all_meta', 'commentpress_thoreau_get_liked_comments_meta', 10, 5 );

		// Set default.
		$pagetitle = apply_filters(
			'cp_page_liked_comments_title',
			__( 'Liked Comments', 'commentpress-thoreau' )
		);

		// Set title.
		$_page_content = '<h2 class="post_title">' . $pagetitle . '</h2>' . "\n\n";

		// Get page or post.
		$page_or_post = $core->nav->setting_post_type_get();

		// Set default.
		$blogtitle = apply_filters(
			'cp_page_all_comments_blog_title',
			__( 'Comments on the Blog', 'commentpress-thoreau' )
		);

		// Set default.
		$booktitle = apply_filters(
			'cp_page_all_comments_book_title',
			__( 'Comments on the Pages', 'commentpress-thoreau' )
		);

		// Get title.
		$title = ( $page_or_post == 'page' ) ? $booktitle : $blogtitle;

		// Get data.
		$_data = commentpress_thoreau_get_liked_comments_content( $page_or_post );

		// Did we get any?
		if ( $_data != '' ) {

			// Set title.
			$_page_content .= '<h3 class="comments_hl">' . $title . '</h3>' . "\n\n";

			// Set data.
			$_page_content .= $_data . "\n\n";

		}

		// Get data for other page type.
		$other_type = ( $page_or_post == 'page' ) ? 'post' : 'page';

		// Get title.
		$title = ( $page_or_post == 'page' ) ? $blogtitle : $booktitle;

		// Get data.
		$_data = commentpress_thoreau_get_liked_comments_content( $other_type );

		// Did we get any?
		if ( $_data != '' ) {

			// Set title.
			$_page_content .= '<h3 class="comments_hl">' . $title . '</h3>' . "\n\n";

			// Set data.
			$_page_content .= $_data . "\n\n";

		}

		// --<
		return $_page_content;

	}

endif;



if ( ! function_exists( 'commentpress_thoreau_get_liked_comments_content' ) ) :

	/**
	 * Liked comments page display function.
	 *
	 * @since 1.0
	 *
	 * @param str $page_or_post The page or post flag.
	 * @return str $html The comments markup.
	 */
	function commentpress_thoreau_get_liked_comments_content( $page_or_post = 'page' ) {

		// Declare access to globals.
		global $cp_comment_output;

		// Init output.
		$html = '';

		// Get all approved liked comments.
		$liked_comments = get_comments( [
			'status' => 'approve',
			'orderby' => 'upvotes',
			'order' => 'DESC',
			'post_type' => $page_or_post,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'meta_query' => [
				[
					'key'   => 'upvotes',
					'value' => '1',
					'compare' => '>=',
				],
			],
		] );

		// Kick out if none.
		if ( count( $liked_comments ) == 0 ) {
			return $html;
		}

		// Build list of posts to which they are attached.
		$posts_with = [];
		$post_comment_counts = [];
		foreach ( $liked_comments as $comment ) {

			// Add likes to comment object.
			$comment->likes = get_comment_meta( $comment->comment_ID, 'upvotes', true );

			// Add to posts with comments array.
			if ( ! in_array( $comment->comment_post_ID, $posts_with ) ) {
				$posts_with[] = $comment->comment_post_ID;
			}

			// Increment counter.
			if ( ! isset( $post_comment_counts[ $comment->comment_post_ID ] ) ) {
				$post_comment_counts[ $comment->comment_post_ID ] = 1;
			} else {
				$post_comment_counts[ $comment->comment_post_ID ]++;
			}

		}

		// Kick out if none.
		if ( count( $posts_with ) == 0 ) {
			return $html;
		}

		/*
		 * The meta query above doesn't seem to sort correctly, so we need to sort
		 * by likes manually...
		 */

		// Sort.
		$sorted_comments = commentpress_thoreau_sort_liked_comments( $liked_comments );

		// Get those posts.
		$posts = get_posts( [
			'orderby' => 'comment_count',
			'order' => 'DESC',
			'post_type' => $page_or_post,
			'include' => $posts_with,
		] );

		// Kick out if none.
		if ( count( $posts ) == 0 ) {
			return $html;
		}

		// Open ul.
		$html .= '<ul class="all_comments_listing">' . "\n\n";

		foreach ( $posts as $_post ) {

			// Open li.
			$html .= '<li class="page_li"><!-- page li -->' . "\n\n";

			// Define comment count.
			$comment_count_text = sprintf( _n(
				// Singular.
				'<span class="cp_comment_count">%d</span> comment',
				// Plural.
				'<span class="cp_comment_count">%d</span> comments',
				// Number.
				$post_comment_counts[ $_post->ID ],
				// Domain.
				'commentpress-thoreau'
				// Substitution.
			), $post_comment_counts[ $_post->ID ] );

			// Show it.
			$html .= '<h4>' . esc_html( $_post->post_title ) . ' <span>(' . $comment_count_text . ')</span></h4>' . "\n\n";

			// Open comments div.
			$html .= '<div class="item_body">' . "\n\n";

			// Open ul.
			$html .= '<ul class="item_ul">' . "\n\n";

			// Open li.
			$html .= '<li class="item_li"><!-- item li -->' . "\n\n";

			// Show the comments.
			foreach ( $sorted_comments as $comment ) {
				if ( $comment->comment_post_ID == $_post->ID ) {
					$html .= commentpress_format_comment( $comment );
				}
			}

			// Close li.
			$html .= '</li><!-- /item li -->' . "\n\n";

			// Close ul.
			$html .= '</ul>' . "\n\n";

			// Close item div.
			$html .= '</div><!-- /item_body -->' . "\n\n";

			// Close li.
			$html .= '</li><!-- /page li -->' . "\n\n\n\n";

		}

		// Close ul.
		$html .= '</ul><!-- /all_comments_listing -->' . "\n\n";

		// --<
		return $html;

	}

endif;



if ( ! function_exists( 'commentpress_thoreau_get_liked_comments_meta' ) ) :

	/**
	 * Liked comments meta display function.
	 *
	 * @since 1.0
	 *
	 * @param str $comment_meta The Comment meta string.
	 * @param object $comment The Comment data object.
	 * @param str $comment_anchor The Comment link markup.
	 * @param str $comment_author The Comment Author markup.
	 * @param str $comment_date The Comment date markup.
	 * @return str $comment_meta The comment meta.
	 */
	function commentpress_thoreau_get_liked_comments_meta( $comment_meta, $comment, $comment_anchor, $comment_author, $comment_date ) {

		// Define comment likes text.
		$comment_likes_text = sprintf( _n(
			// Singular.
			'<span class="cp_comment_likes">%d</span> like',
			// Plural.
			'<span class="cp_comment_likes">%d</span> likes',
			// Number.
			$comment->likes,
			// Domain.
			'commentpress-thoreau'
			// Substitution.
		), $comment->likes );

		// Construct likes.
		$comment_likes = '<span class="comment_likes" style="float: right">' . $comment_likes_text . '</span>';

		// Construct comment header content.
		$comment_meta_content = sprintf(
			__( '%1$s by %2$s on %3$s', 'commentpress-thoreau' ),
			$comment_anchor,
			$comment_author,
			$comment_date
		);

		// Comment header.
		$comment_meta = '<div class="comment_meta">' . $comment_meta_content . $comment_likes . '</div>' . "\n";

		// --<
		return $comment_meta;

	}

endif;



/**
 * Sort liked comments.
 *
 * @since 1.0
 *
 * @param array $unsorted The array of unsorted comments.
 * @return array $sorted_comments The array of sorted comments.
 */
function commentpress_thoreau_sort_liked_comments( $unsorted ) {

	// Init sorted list.
	$sorted_ids = [];

	// Add comments keyed by comment ID.
	foreach ( $unsorted as $key => $comment ) {
		$sorted_ids[ $key ] = $comment->likes;
	}

	// Sort, retaining keys.
	arsort( $sorted_ids );

	// Init sorted list.
	$sorted_comments = [];

	// Add comments keyed by comment ID.
	foreach ( $sorted_ids as $key => $likes ) {
		$sorted_comments[] = $unsorted[ $key ];
	}

	// --<
	return $sorted_comments;

}



if ( ! function_exists( 'commentpress_thoreau_get_activity_sidebar' ) ) :

	/**
	 * Liked comments activity sidebar display function.
	 *
	 * @since 1.0
	 */
	function commentpress_thoreau_get_activity_sidebar() {

		// Init output.
		$html = '';

		// For logged in users when on a commentable page.
		if ( commentpress_is_commentable() ) {

			// Access post.
			global $post;

			// Get all approved liked comments.
			$liked_comments = get_comments( [
				'post_id' => $post->ID,
				'status' => 'approve',
				'orderby' => [ 'upvotes' ],
				'order' => 'DESC',
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'meta_query' => [
					[
						'key'   => 'upvotes',
						'value' => '1',
						'compare' => '>=',
					],
				],
			] );

			// Kick out if none.
			if ( count( $liked_comments ) == 0 ) {
				return $html;
			}

			// Add filter for comment meta, showing liked data.
			// phpcs:ignore Squiz.Commenting.InlineComment.InvalidEndChar
			//add_filter( 'commentpress_format_comment_all_meta', 'commentpress_thoreau_get_liked_comments_meta', 10, 5 );

			// Add likes to comment objects.
			foreach ( $liked_comments as $comment ) {
				$comment->likes = get_comment_meta( $comment->comment_ID, 'upvotes', true );
			}

			/*
			 * The meta query above doesn't seem to sort correctly, so we need to
			 * sort by likes manually...
			 */

			// Sort.
			$sorted_comments = commentpress_thoreau_sort_liked_comments( $liked_comments );

			// Open ul.
			$html .= '<ol class="comment_activity">' . "\n\n";

			// Show the comments.
			foreach ( $sorted_comments as $comment ) {
				$html .= commentpress_get_comment_activity_item( $comment );
			}

			// Close ul.
			$html .= '</ol><!-- /comment_activity -->' . "\n\n";

			?><h3 class="activity_heading"><?php echo __( 'Most liked comments on this page', 'commentpress-thoreau' ); ?></h3>

			<div class="paragraph_wrapper page_comments_output">
				<?php echo $html; ?>
			</div>

			<?php

		}

	}

endif;

// Add section to activity sidebar in CommentPress.
add_action( 'commentpress_bp_activity_sidebar_after_page_comments', 'commentpress_thoreau_get_activity_sidebar', 11 );



/**
 * Override "Title Page".
 *
 * @since 1.0
 *
 * @return string The new title page.
 */
function commentpress_thoreau_title_page() {

	// Override.
	return __( 'How to read this Text', 'commentpress-thoreau' );

}

// Add filter for the above.
add_filter( 'cp_nav_title_page_title', 'commentpress_thoreau_title_page', 1000 );




/**
 * SUNY Seach Modifier Class.
 *
 * @since 1.0
 *
 * This works in much the same way as a plugin, encapsulating search modification
 * for Walden to enable the desired functionality.
 */
class SUNY_Seach_Modifier {

	/**
	 * Registered stopwords.
	 *
	 * @since 1.0
	 * @access public
	 * @var array $stopwords The registered stopwords.
	 */
	public $stopwords = [];

	/**
	 * Returns a single instance of this object when called.
	 *
	 * @since 1.0
	 *
	 * @return object $instance SUNY_Seach_Modifier instance.
	 */
	public static function instance() {

		// Store the instance locally to avoid private static replication.
		static $instance = null;

		// Do we have it?
		if ( null === $instance ) {

			// Instantiate.
			$instance = new SUNY_Seach_Modifier();

			// Initialise.
			$instance->register_hooks();

		}

		// Always return instance.
		return $instance;

	}

	/**
	 * Register the hooks that our plugin needs.
	 *
	 * @since 1.0
	 */
	private function register_hooks() {

		// ---------------------------------------------------------------------
		// Add filters for text highlighting...
		// ---------------------------------------------------------------------

		// Filter page permalinks to carry search query over to page itself.
		add_filter( 'page_link', [ $this, 'permalink_filter' ], 10, 3 );

		// Filter the_title.
		add_filter( 'the_title', [ $this, 'title_highlight' ] );

		// Search content, truncated to simulate the_excerpt.
		add_filter( 'the_content', [ $this, 'content_highlight' ] );

		// Actual page content (with a *very* high priority).
		add_filter( 'the_content', [ $this, 'the_content' ], 10000, 2 );

		// ---------------------------------------------------------------------
		// Add filters for query modification...
		// ---------------------------------------------------------------------

		// Add filter for search query modification.
		add_filter( 'pre_get_posts', [ $this, 'restrict_query' ] );

		// Add filter to retrieve stopwords.
		add_filter( 'wp_search_stopwords', [ $this, 'intercept_stopwords' ], 10, 1 );

		// Add filter to amend search filter.
		add_filter( 'posts_search', [ $this, 'where_filter' ], 10, 2 );

		// Add filter to amend the search orderby.
		add_filter( 'posts_search_orderby', [ $this, 'orderby_filter' ], 10, 2 );

		// Add results filter.
		add_filter( 'posts_results', [ $this, 'results_filter' ], 10, 2 );

	}

	/**
	 * Filter permalinks and add search query to them.
	 *
	 * @since 1.0
	 *
	 * @param string $link The page's permalink.
	 * @param int $post_id The ID of the page.
	 * @param bool $sample Whether it is a sample permalink.
	 * @return string $link The modified permalink for the page.
	 */
	public function permalink_filter( $link, $post_id, $sample ) {

		// Is this a search result?
		if ( is_search() && in_the_loop() ) {

			// Access the query.
			global $wp_query;

			// Sanity check search terms.
			if ( ! isset( $wp_query->query_vars['s'] ) ) {
				return $link;
			}

			// Add search query to permalink.
			$link = add_query_arg( [
				's' => urlencode_deep( $wp_query->query_vars['s'] ),
			], $link );

		}

		// --<
		return $link;

	}

	/**
	 * Highlight searched-for words in the_content.
	 *
	 * @since 1.0
	 *
	 * @param string $text The existing text content.
	 * @return string $text The modified text content.
	 */
	public function title_highlight( $text ) {

		// Is this a search result?
		if ( is_search() && in_the_loop() ) {

			// We need this for the search terms.
			global $wp_query;

			// Get search terms without stopwords.
			$keys = $wp_query->query_vars['search_terms'];

			// Get raw search.
			$raw_search = explode( ' ', $wp_query->query_vars['s'] );

			// If this is greater than one word.
			if ( count( $raw_search ) > 1 ) {

				// Prepend full phrase so it has highest priority in regex.
				array_unshift( $keys, $wp_query->query_vars['s'] );

			}

			// Escape each entry.
			array_walk( $keys, function( &$item ) {
				$item = preg_quote( trim( $item ), '/' );
			} );

			// Convert for use in regex.
			$regex = '/(' . implode( '|', $keys ) . ')/iu';

			// Define substitution.
			$substitution = '<span class="search_highlight">\0</span>';

			// Perform replacement.
			$text = preg_replace(
				$regex,
				$substitution,
				$text
			);

		}

		// --<
		return $text;

	}

	/**
	 * Highlight searched-for words in the_content.
	 *
	 * @since 1.0
	 *
	 * @param string $text The existing text content.
	 * @return string $text The modified text content.
	 */
	public function content_highlight( $text ) {

		// Is this a search result?
		if ( is_search() && in_the_loop() ) {

			// Process as if it were the_excerpt.
			$processed_text = strip_shortcodes( $text );
			$processed_text = wp_strip_all_tags( $processed_text );
			$processed_text = str_replace( ']]>', ']]&gt;', $processed_text );

			// We need this for the search terms.
			global $wp_query;

			// Sanity checks.
			if ( ! isset( $wp_query->query_vars['search_terms'] ) ) {
				return $text;
			}
			if ( ! isset( $wp_query->query_vars['s'] ) ) {
				return $text;
			}

			// Get search terms without stopwords.
			$keys = $wp_query->query_vars['search_terms'];

			// Get raw search.
			$raw_search = explode( ' ', $wp_query->query_vars['s'] );

			// If this is greater than one word.
			if ( count( $raw_search ) > 1 ) {

				// Prepend full phrase so it has highest priority in regex.
				array_unshift( $keys, $wp_query->query_vars['s'] );

			}

			// Get stopwords - disabled for now.
			// phpcs:ignore Squiz.Commenting.InlineComment.InvalidEndChar
			$reserved = []; //$this->get_stopwords();

			// Init clean words.
			$clean = [];

			// Build clean array.
			foreach ( $keys as $key => $word ) {
				if ( ! in_array( $word, $reserved ) ) {
					$clean[] = $word;
				}
			}

			// Get subsequent text for full phrase search.
			$subsequent_text = stristr( $processed_text, $wp_query->query_vars['s'] );

			// Do we have subsequent text?
			if ( false !== $subsequent_text ) {

				// Set flag.
				$found_phrase = true;

				// Get the position where it starts.
				$offset = stripos( $processed_text, $wp_query->query_vars['s'] );

				// Get the preceding text.
				$preceding_text = substr( $processed_text, 0, $offset );

				// Join text.
				$joined = $this->get_joined_text( $preceding_text, $subsequent_text );

			} else {

				// Set flag.
				$found_phrase = false;

				// Wait until after regex.
				$joined = $processed_text;

			}

			// Escape each entry.
			array_walk( $clean, function( &$item ) {
				$item = preg_quote( trim( $item ), '/' );
			} );

			// Convert for use in regex.
			$regex = '/(' . implode( '|', $clean ) . ')/iu';

			// Define substitution.
			$substitution = '<span class="search_highlight">\0</span>';

			// {erform replacement.
			$processed_text = preg_replace(
				$regex,
				$substitution,
				$joined
			);

			/*
			// Log something.
			print_r( array(
				//'subsequent_text' => $subsequent_text,
				//'offset' => $offset,
				//'preceding_text' => $preceding_text,
				//'previous' => $previous,
				'joined' => $joined,
				'regex' => $regex,
				'processed_text' => $processed_text,
			) ); //die();
			*/

			// Did we find our phrase?
			if ( $found_phrase === false ) {

				// No, limit excerpt to first instance of highlight.

				// Get subsequent text from first instance of a substitution.
				$subsequent_text = stristr( $processed_text, '<span class="search_highlight">' );

				// Do we have subsequent text?
				if ( false !== $subsequent_text ) {

					// Get the position where it starts.
					$offset = stripos( $processed_text, '<span class="search_highlight">' );

					// Get the preceding text (won't have any HTML).
					$preceding_text = substr( $processed_text, 0, $offset );

					// Join text.
					$joined = $this->get_joined_text( $preceding_text, $subsequent_text );

					// Perform replacement again.
					$processed_text = preg_replace(
						$regex,
						$substitution,
						$joined
					);

				} else {

					// We've searched for something that may be an HTML element
					// or CSS class - let's just trim to the first 55 words.
					$processed_text = wp_trim_words( $processed_text, 55, '' );

				}

			}

			// Wrap in ellipsis.
			$text = '&hellip;' . $processed_text . '&hellip;';

		}

		// --<
		return $text;

	}

	/**
	 * Highlight searched-for words in the_content.
	 *
	 * @since 1.0
	 *
	 * @param string $text The existing text content.
	 * @return string $text The modified text content.
	 */
	public function the_content( $text ) {

		// Access query.
		global $wp_query;

		// Is this a page visited with a search query?
		if (
			is_page() &&
			isset( $wp_query->query_vars['s'] ) &&
			! empty( $wp_query->query_vars['s'] )
		) {

			/*
			// Log something.
			print_r( array(
				'wp_query' => $wp_query,
				'query_vars' => $wp_query->query_vars,
			) ); die();
			*/

			// Get search terms without stopwords.
			$keys = $wp_query->query_vars['search_terms'];

			// Get raw search.
			$raw_search = explode( ' ', $wp_query->query_vars['s'] );

			// If this is greater than one word.
			if ( count( $raw_search ) > 1 ) {

				// Prepend full phrase so it has highest priority in regex.
				array_unshift( $keys, $wp_query->query_vars['s'] );

			}

			// Get stopwords.
			$reserved = $this->get_stopwords();

			// Init clean words.
			$clean = [];

			// Build clean array.
			foreach ( $keys as $key => $word ) {
				if ( ! in_array( $word, $reserved ) ) {
					$clean[] = $word;
				}
			}

			// Bail if there's nothing left.
			if ( empty( $clean ) ) {
				return $text;
			}

			// Escape each entry.
			array_walk( $clean, function( &$item ) {
				$item = preg_quote( trim( $item ), '/' );
			} );

			// Convert for use in regex.
			$regex = '/(' . implode( '|', $clean ) . ')/iu';

			// Define substitution.
			$substitution = '<span class="search_highlight">\0</span>';

			// Perform replacement.
			$text = preg_replace(
				$regex,
				$substitution,
				$text
			);

		}

		// --<
		return $text;

	}

	/**
	 * Get joined text.
	 *
	 * @since 1.0
	 *
	 * @param string $preceding_text The preceding text.
	 * @param string $subsequent_text The subsequent text.
	 */
	private function get_joined_text( $preceding_text, $subsequent_text ) {

		// Init separator.
		$sep = '';

		// Get final character.
		$final_char = substr( $preceding_text, -1 );

		// Is there a trailing space - allows us to determine if the
		// search term is in the middle of a word or not.
		if ( ctype_space( $final_char ) ) {
			$sep = ' ';
		}

		// Grab (up to) the previous 10 words.
		$previous = strrev( wp_trim_words( strrev( $preceding_text ), 10, '' ) );

		// Prepend to found string and limit to defined excerpt length.
		$joined = wp_trim_words( $previous . $sep . wp_strip_all_tags( $subsequent_text ), 55, '' );

		// --<
		return $joined;

	}

	/**
	 * Restrict search query to pages only.
	 *
	 * @since 1.0
	 *
	 * @param object $query The query object, passed by reference.
	 */
	public function restrict_query( &$query ) {

		// Restrict to search outside admin.
		if ( ! is_admin() && $query->is_search ) {

			// Restrict to pages.
			$query->set( 'post_type', 'page' );

			// Get Featured Comments & Liked Comments pages.
			$pages = array_unique( array_merge(
				commentpress_thoreau_get_featured_comments_pages(),
				commentpress_thoreau_get_liked_comments_pages()
			));

			// Exclude them.
			$query->set( 'post__not_in', $pages );

		}

	}

	/**
	 * Filter search query WHERE clause, if necessary.
	 *
	 * @since 1.0
	 *
	 * @param string $search Existing search SQL for WHERE clause.
	 * @param object $query The query object, NOT passed by reference.
	 * @return string $search Modified search SQL for WHERE clause.
	 */
	public function where_filter( $search, $query ) {

		/*
		// Restrict to search outside admin.
		if ( ! is_admin() && $query->is_search ) {
			print_r( $query ); die();
			print_r( $search ); die();
		}
		*/

		// --<
		return $search;

	}

	/**
	 * Filter search query ORDER BY clause, if necessary.
	 *
	 * @since 1.0
	 *
	 * @param string $search_orderby Existing search SQL for ORDER BY clause.
	 * @param object $query The query object, NOT passed by reference.
	 * @return string $search_orderby Modified search SQL for ORDER BY clause.
	 */
	public function orderby_filter( $search_orderby, $query ) {

		// Restrict to search outside admin.
		if ( ! is_admin() && $query->is_search ) {

			global $wpdb;

			if ( $query->query_vars['search_terms_count'] > 1 ) {

				$num_terms = count( $query->query_vars['search_orderby_title'] );
				$raw = $wpdb->esc_like( $query->query_vars['s'] );
				$like = '%' . $raw . '%';

				// Open case.
				$search_orderby = '(CASE ';

				// Sentence match in 'post_title'.
				$search_orderby .= $wpdb->prepare( "WHEN $wpdb->posts.post_title LIKE %s THEN 1 ", $like );

				// Sanity limit, sort as sentence when more than 10 terms.
				if ( $num_terms < 10 ) {

					// All words in title.
					$search_orderby .= 'WHEN ' . implode( ' AND ', $query->query_vars['search_orderby_title'] ) . ' THEN 2 ';

					// Any word in title, not needed when $num_terms == 1.
					if ( $num_terms > 1 ) {
						$search_orderby .= 'WHEN ' . implode( ' OR ', $query->query_vars['search_orderby_title'] ) . ' THEN 3 ';
					}

				}

				// Full sentence match in 'post_content'.
				$search_orderby .= $wpdb->prepare( "WHEN $wpdb->posts.post_content LIKE %s THEN 4 ", $like );

				/*
				 * I'm not really happy with the following breakdown of the sentence,
				 * given that it's so rudimentary. I can enhance this at a later stage
				 * if it's a desired feature.
				 */

				/*
				// Get words as terms.
				$terms = explode( ' ', $raw );
				$count = count( $terms );

				// Let's break the raw earch down if we have more than a few terms.
				if ( $count > 3 ) {

					// Init breakdown
					$breakdown = array();

					for( $i = 0; $i < $count; $i++ ) {
						array_pop( $terms );
						if ( count( $terms ) > 3 ) {
							$breakdown[] = implode( ' ', $terms );
						}
					}

				}
				*/

				// Close case without breakdown.
				$search_orderby .= 'ELSE 5 END)';

				// Close case with breakdown.
				// phpcs:ignore Squiz.Commenting.InlineComment.InvalidEndChar
				//$search_orderby .= 'ELSE 6 END)';

			} else {

				// Single word or sentence search.
				$search_orderby = reset( $query->query_vars['search_orderby_title'] ) . ' DESC';

			}

			// Add menu order ASC here, because relevance is omitted when set via pre_get_posts.
			$search_orderby .= ', ' . $wpdb->posts . '.menu_order ASC';

			/*
			// For example:
			(CASE
				WHEN wp_kf98nz_8_posts.post_title LIKE '%turn it thus in your mind%'
					THEN 1
				WHEN wp_kf98nz_8_posts.post_title LIKE '%turn%'
					AND wp_kf98nz_8_posts.post_title LIKE '%thus%'
					AND wp_kf98nz_8_posts.post_title LIKE '%your%'
					AND wp_kf98nz_8_posts.post_title LIKE '%mind%'
					THEN 2
				WHEN wp_kf98nz_8_posts.post_title LIKE '%turn%'
					OR wp_kf98nz_8_posts.post_title LIKE '%thus%'
					OR wp_kf98nz_8_posts.post_title LIKE '%your%'
					OR wp_kf98nz_8_posts.post_title LIKE '%mind%'
					THEN 3
				WHEN wp_kf98nz_8_posts.post_content LIKE '%turn it thus in your mind%'
					THEN 4
				ELSE 5
			END)
			*/

		}

		// --<
		return $search_orderby;

	}

	/**
	 * Intercept final search results. Allows us to inspect the final SQL.
	 *
	 * @since 1.0
	 *
	 * @param array $posts The existing array of posts.
	 * @param object $query The query object.
	 * @return array $posts The modified array of posts.
	 */
	public function results_filter( $posts, $query ) {

		/*
		// Log something.
		print_r( array(
			'posts' => $posts,
			'query' => $query,
		) ); die();
		*/

		// --<
		return $posts;

	}

	/**
	 * Get the reserved words, so that we don't break our CSS in our regex.
	 *
	 * @since 1.0
	 *
	 * @param array $stopwords Existing stopwords.
	 * @return array $stopwords Modified stopwords.
	 */
	public function intercept_stopwords( $stopwords ) {

		// Grab and store.
		$this->stopwords = $stopwords;

		// --<
		return $stopwords;

	}

	/**
	 * Builds a complete list of stopwords for exclusion from highlight regex.
	 *
	 * @since 1.0
	 *
	 * @return array $stopwords The complete list of stopwords.
	 */
	private function get_stopwords() {

		// Define our reserved words.
		$reserved = [
			'class',
			'lass',
			'ass',
			'as', // For HTML attributes.
			'span',
			'spa',
			'pan',
			'an', // For the <span> element.
			'cite', // For the <cite> element.
			'do',
			'not',
			'break', // For the .do-not-break class.
			'blockquote',
			'block',
			'lock',
			'bloc',
			'quote',
			'in',
			'para', // For the .blockquote-in-para class.
			'bq',
			'indent',
			'none',
			'non', // For the .blockquote-in-para.bq-indent-none class.
			'line', // For the  .bq-line-indent class.
			'leaders',
			'lead',
			'ad', // For the .leaders class.
			'table',
			'able',
			'tab',
			'item', // For the .table-item class.
			'indented',
			'dented',
			'dent',
			'ted', // For the .table-item-indented class.
			'tweaked',
			'tweak',
			'weak', // For the .tweaked-item class.
			'clearfix',
			'clear',
			'fix',
			'ear', // For the .tweaked-item class.
			'beanfield',
			'field',
			'bean', // For the .beanfield-2 class.
		];

		// Add to stopwords, if not present.
		foreach ( $reserved as $key => $word ) {
			if ( ! in_array( $word, $this->stopwords ) ) {
				$this->stopwords[] = $word;
			}
		}

		// --<
		return $this->stopwords;

	}

}

/**
 * Instantiate search modifier object.
 *
 * @since 1.0
 *
 * @return object The SUNY_Seach_Modifier instance.
 */
function commentpress_thoreau_search_modifier() {
	return SUNY_Seach_Modifier::instance();
}

// Init Seach Modifier.
commentpress_thoreau_search_modifier();
