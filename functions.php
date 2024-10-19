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
define( 'COMMENTPRESS_THOREAU_VERSION', '1.0.3a' );

/**
 * Instantiate search modifier object.
 *
 * @since 1.0
 *
 * @return object The SUNY_Seach_Modifier instance.
 */
function commentpress_thoreau_search_modifier() {
	include_once get_stylesheet_directory() . '/includes/class-search-modifier.php';
	return SUNY_Seach_Modifier::instance();
}

// Init Seach Modifier.
commentpress_thoreau_search_modifier();

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
	$pages = array_unique(
		array_merge(
			commentpress_thoreau_get_featured_comments_pages(),
			commentpress_thoreau_get_liked_comments_pages()
		)
	);

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
	$pages = array_unique(
		array_merge(
			commentpress_thoreau_get_featured_comments_pages(),
			commentpress_thoreau_get_liked_comments_pages()
		)
	);

	// Merge with exclude array if we have some.
	if ( count( $pages ) > 0 ) {
		$excludes = array_unique(
			array_merge(
				$excludes,
				$pages
			)
		);
	}

	// Override.
	return $excludes;

}

/**
 * Enqueue child theme styles.
 *
 * Styles can be overridden because the child theme is:
 *
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
	$pages = array_unique(
		array_merge(
			commentpress_thoreau_get_featured_comments_pages(),
			commentpress_thoreau_get_liked_comments_pages()
		)
	);

	// If we have some.
	if ( count( $pages ) > 0 ) {

		// Access post.
		global $post;

		// Bail if there's no post object.
		if ( ! ( $post instanceof WP_Post ) ) {
			return;
		}

		// If it's our Featured Comments or Liked Comments page.
		if ( in_array( (int) $post->ID, $pages, true ) ) {

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
	$pages = array_unique(
		array_merge(
			commentpress_thoreau_get_featured_comments_pages(),
			commentpress_thoreau_get_liked_comments_pages()
		)
	);

	// If we have some.
	if ( count( $pages ) > 0 ) {

		// Access post.
		global $post;

		// Override if it's our Featured Comments or Liked Comments page.
		if ( ( $post instanceof WP_Post ) && in_array( (int) $post->ID, $pages, true ) ) {
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

	// Build query args.
	$args = [
		'post_type'  => 'page',
		'orderby'    => 'post_date',
		'order'      => 'DESC',
		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		'meta_key'   => '_wp_page_template',
		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		'meta_value' => 'comments-featured.php',
	];

	// Get Featured Comments special page(s).
	$commentpress_thoreau_pages = get_posts( $args );

	// Add them if we get them.
	if ( ! empty( $commentpress_thoreau_pages ) ) {
		foreach ( $commentpress_thoreau_pages as $commentpress_thoreau_page ) {
			$pages[] = (int) $commentpress_thoreau_page->ID;
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

		/**
		 * Filters the page title.
		 *
		 * @since 1.0
		 *
		 * @param string
		 */
		$pagetitle = apply_filters( 'cp_page_featured_comments_title', __( 'Featured Comments', 'commentpress-thoreau' ) );

		// Construct title.
		$_page_content = '<h2 class="post_title">' . $pagetitle . '</h2>' . "\n\n";

		// Get page or post.
		$page_or_post = $core->nav->setting_post_type_get();

		/**
		 * Filters the blog title.
		 *
		 * @since 1.0
		 *
		 * @param string
		 */
		$blogtitle = apply_filters( 'cp_page_featured_comments_blog_title', __( 'Comments on the Blog', 'commentpress-thoreau' ) );

		/**
		 * Filters the "Comments on the Pages" title.
		 *
		 * @since 1.0
		 *
		 * @param string
		 */
		$booktitle = apply_filters( 'cp_page_featured_comments_book_title', __( 'Comments on the Pages', 'commentpress-thoreau' ) );

		// Get title.
		$title = ( 'page' === $page_or_post ) ? $booktitle : $blogtitle;

		// Get data.
		$_data = commentpress_thoreau_get_featured_comments_content( $page_or_post );

		// Did we get any?
		if ( ! empty( $_data ) ) {

			// Set title.
			$_page_content .= '<h3 class="comments_hl">' . $title . '</h3>' . "\n\n";

			// Set data.
			$_page_content .= $_data . "\n\n";

		}

		// Get data for other page type.
		$other_type = ( 'page' === $page_or_post ) ? 'post' : 'page';

		// Get title.
		$title = ( 'page' === $page_or_post ) ? $blogtitle : $booktitle;

		// Get data.
		$_data = commentpress_thoreau_get_featured_comments_content( $other_type );

		// Did we get any?
		if ( ! empty( $_data ) ) {

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

		// Build args.
		$args = [
			'status'     => 'approve',
			'orderby'    => 'comment_post_ID,comment_date',
			'order'      => 'ASC',
			'post_type'  => $page_or_post,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'meta_query' => [
				[
					'key'   => 'featured',
					'value' => '1',
				],
			],
		];

		// Get all approved featured comments.
		$featured_comments = get_comments( $args );

		// Kick out if none.
		if ( 0 === count( $featured_comments ) ) {
			return $html;
		}

		// Build list of posts to which they are attached.
		$posts_with          = [];
		$post_comment_counts = [];
		foreach ( $featured_comments as $comment ) {

			// Add to posts with comments array.
			if ( ! in_array( $comment->comment_post_ID, $posts_with, true ) ) {
				$posts_with[] = (int) $comment->comment_post_ID;
			}

			// Increment counter.
			if ( ! isset( $post_comment_counts[ $comment->comment_post_ID ] ) ) {
				$post_comment_counts[ $comment->comment_post_ID ] = 1;
			} else {
				$post_comment_counts[ $comment->comment_post_ID ]++;
			}

		}

		// Kick out if none.
		if ( 0 === count( $posts_with ) ) {
			return $html;
		}

		// Build query args.
		$args = [
			'orderby'   => 'comment_count',
			'order'     => 'DESC',
			'post_type' => $page_or_post,
			'include'   => $posts_with,
		];

		// Get those posts.
		$posts = get_posts( $args );

		// Kick out if none.
		if ( 0 === count( $posts ) ) {
			return $html;
		}

		// Open ul.
		$html .= '<ul class="all_comments_listing">' . "\n\n";

		foreach ( $posts as $_post ) {

			// Open li.
			$html .= '<li class="page_li"><!-- page li -->' . "\n\n";

			// Define comment count.
			$comment_count_text = sprintf(
				/* translators: The placeholder is the number of comments. */
				_n(
					'<span class="cp_comment_count">%d</span> comment', // Singular.
					'<span class="cp_comment_count">%d</span> comments', // Plural.
					$post_comment_counts[ $_post->ID ], // Number.
					'commentpress-thoreau' // Domain.
				),
				// Substitution.
				$post_comment_counts[ $_post->ID ]
			);

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
				if ( (int) $comment->comment_post_ID === (int) $_post->ID ) {
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

	// Build query args.
	$args = [
		'post_type'  => 'page',
		'orderby'    => 'post_date',
		'order'      => 'DESC',
		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		'meta_key'   => '_wp_page_template',
		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		'meta_value' => 'comments-liked.php',
	];

	// Get Liked Comments special page.
	$commentpress_thoreau_pages = get_posts( $args );

	// Add them if we get them.
	if ( ! empty( $commentpress_thoreau_pages ) ) {
		foreach ( $commentpress_thoreau_pages as $commentpress_thoreau_page ) {
			$pages[] = (int) $commentpress_thoreau_page->ID;
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
			if ( ( $post instanceof WP_Post ) && (int) $post_id === (int) $post->ID ) {
				$active_page = ' class="active_page"';
			}

			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '<li' . $active_page . '>' .
				'<a href="' . esc_url( get_permalink( $post_id ) ) . '" title="' . esc_attr( wp_strip_all_tags( get_the_title( $post_id ) ) ) . '">' .
					esc_html( get_the_title( $post_id ) ) .
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

		/**
		 * Filters the default "Liked Comments" section title.
		 *
		 * @since 1.0
		 *
		 * @param string
		 */
		$pagetitle = apply_filters( 'cp_page_liked_comments_title', __( 'Liked Comments', 'commentpress-thoreau' ) );

		// Set title.
		$_page_content = '<h2 class="post_title">' . $pagetitle . '</h2>' . "\n\n";

		// Get page or post.
		$page_or_post = $core->nav->setting_post_type_get();

		/**
		 * Filters the default "Comments on the Blog" section title.
		 *
		 * @since 1.0
		 *
		 * @param string
		 */
		$blogtitle = apply_filters( 'cp_page_all_comments_blog_title', __( 'Comments on the Blog', 'commentpress-thoreau' ) );

		/**
		 * Filters the default "Comments on the Pages" section title.
		 *
		 * @since 1.0
		 *
		 * @param string
		 */
		$booktitle = apply_filters( 'cp_page_all_comments_book_title', __( 'Comments on the Pages', 'commentpress-thoreau' ) );

		// Get title.
		$title = ( 'page' === $page_or_post ) ? $booktitle : $blogtitle;

		// Get data.
		$_data = commentpress_thoreau_get_liked_comments_content( $page_or_post );

		// Did we get any?
		if ( ! empty( $_data ) ) {

			// Set title.
			$_page_content .= '<h3 class="comments_hl">' . $title . '</h3>' . "\n\n";

			// Set data.
			$_page_content .= $_data . "\n\n";

		}

		// Get data for other page type.
		$other_type = ( 'page' === $page_or_post ) ? 'post' : 'page';

		// Get title.
		$title = ( 'page' === $page_or_post ) ? $blogtitle : $booktitle;

		// Get data.
		$_data = commentpress_thoreau_get_liked_comments_content( $other_type );

		// Did we get any?
		if ( ! empty( $_data ) ) {

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

		// Build query args.
		$args = [
			'status'     => 'approve',
			'orderby'    => 'upvotes',
			'order'      => 'DESC',
			'post_type'  => $page_or_post,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'meta_query' => [
				[
					'key'     => 'upvotes',
					'value'   => '1',
					'compare' => '>=',
				],
			],
		];

		// Get all approved liked comments.
		$liked_comments = get_comments( $args );

		// Kick out if none.
		if ( 0 === count( $liked_comments ) ) {
			return $html;
		}

		// Build list of posts to which they are attached.
		$posts_with          = [];
		$post_comment_counts = [];
		foreach ( $liked_comments as $comment ) {

			// Add likes to comment object.
			$comment->likes = get_comment_meta( $comment->comment_ID, 'upvotes', true );

			// Add to posts with comments array.
			if ( ! in_array( $comment->comment_post_ID, $posts_with, true ) ) {
				$posts_with[] = (int) $comment->comment_post_ID;
			}

			// Increment counter.
			if ( ! isset( $post_comment_counts[ $comment->comment_post_ID ] ) ) {
				$post_comment_counts[ $comment->comment_post_ID ] = 1;
			} else {
				$post_comment_counts[ $comment->comment_post_ID ]++;
			}

		}

		// Kick out if none.
		if ( 0 === count( $posts_with ) ) {
			return $html;
		}

		/*
		 * The meta query above doesn't seem to sort correctly, so we need to sort
		 * by likes manually...
		 */

		// Sort.
		$sorted_comments = commentpress_thoreau_sort_liked_comments( $liked_comments );

		// Build query args.
		$args = [
			'orderby'   => 'comment_count',
			'order'     => 'DESC',
			'post_type' => $page_or_post,
			'include'   => $posts_with,
		];

		// Get those posts.
		$posts = get_posts( $args );

		// Kick out if none.
		if ( 0 === count( $posts ) ) {
			return $html;
		}

		// Open ul.
		$html .= '<ul class="all_comments_listing">' . "\n\n";

		foreach ( $posts as $_post ) {

			// Open li.
			$html .= '<li class="page_li"><!-- page li -->' . "\n\n";

			// Define comment count.
			$comment_count_text = sprintf(
				/* translators: The placeholder is the number of comments. */
				_n(
					'<span class="cp_comment_count">%d</span> comment', // Singular.
					'<span class="cp_comment_count">%d</span> comments', // Plural.
					$post_comment_counts[ $_post->ID ], // Number.
					'commentpress-thoreau' // Domain.
				),
				// Substitution.
				$post_comment_counts[ $_post->ID ]
			);

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
				if ( (int) $comment->comment_post_ID === (int) $_post->ID ) {
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
	 * @param str    $comment_meta The Comment meta string.
	 * @param object $comment The Comment data object.
	 * @param str    $comment_anchor The Comment link markup.
	 * @param str    $comment_author The Comment Author markup.
	 * @param str    $comment_date The Comment date markup.
	 * @return str $comment_meta The comment meta.
	 */
	function commentpress_thoreau_get_liked_comments_meta( $comment_meta, $comment, $comment_anchor, $comment_author, $comment_date ) {

		// Define comment likes text.
		$comment_likes_text = sprintf(
			/* translators: The placeholder is the number of comment likes. */
			_n(
				'<span class="cp_comment_likes">%d</span> comment', // Singular.
				'<span class="cp_comment_likes">%d</span> comments', // Plural.
				$comment->likes, // Number.
				'commentpress-thoreau' // Domain.
			),
			// Substitution.
			$comment->likes
		);

		// Construct likes.
		$comment_likes = '<span class="comment_likes" style="float: right">' . $comment_likes_text . '</span>';

		// Construct comment header content.
		$comment_meta_content = sprintf(
			/* translators: 1: The Comment link markup, 2: The Comment Author markup, 3: The Comment date markup. */
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

			// Build query args.
			$args = [
				'post_id'    => $post->ID,
				'status'     => 'approve',
				'orderby'    => [ 'upvotes' ],
				'order'      => 'DESC',
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'meta_query' => [
					[
						'key'     => 'upvotes',
						'value'   => '1',
						'compare' => '>=',
					],
				],
			];

			// Get all approved liked comments.
			$liked_comments = get_comments( $args );

			// Kick out if none.
			if ( 0 === count( $liked_comments ) ) {
				return $html;
			}

			// Add filter for comment meta, showing liked data.
			// phpcs:ignore Squiz.Commenting.InlineComment.InvalidEndChar
			// add_filter( 'commentpress_format_comment_all_meta', 'commentpress_thoreau_get_liked_comments_meta', 10, 5 );

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

			?><h3 class="activity_heading"><?php esc_html_e( 'Most liked comments on this page', 'commentpress-thoreau' ); ?></h3>

			<div class="paragraph_wrapper page_comments_output">
				<?php echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
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
