<?php
/**
 * CommentPress Thoreau Seach Modifier Class.
 *
 * Encapsulates search modification for Walden to enable the desired functionality.
 *
 * @package CommentPress_Thoreau
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * SUNY Seach Modifier Class.
 *
 * This works in much the same way as a plugin, encapsulating search modification
 * for Walden to enable the desired functionality.
 *
 * @since 1.0
 */
class SUNY_Seach_Modifier {

	/**
	 * Registered stopwords.
	 *
	 * @since 1.0
	 * @access public
	 * @var array
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

		// Maybe instantiate and initialise.
		if ( null === $instance ) {
			$instance = new SUNY_Seach_Modifier();
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
		// Add filters for text highlighting.
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
		// Add filters for query modification.
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
	 * @param int    $post_id The ID of the page.
	 * @param bool   $sample Whether it is a sample permalink.
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
			$link = add_query_arg(
				[
					's' => urlencode_deep( $wp_query->query_vars['s'] ),
				],
				$link
			);

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
			array_walk(
				$keys,
				function( &$item ) {
					$item = preg_quote( trim( $item ), '/' );
				}
			);

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
			$reserved = []; // $this->get_stopwords();

			// Init clean words.
			$clean = [];

			// Build clean array.
			foreach ( $keys as $key => $word ) {
				if ( ! in_array( $word, $reserved, true ) ) {
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
			array_walk(
				$clean,
				function( &$item ) {
					$item = preg_quote( trim( $item ), '/' );
				}
			);

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

			// Did we find our phrase?
			if ( false === $found_phrase ) {

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
				if ( ! in_array( $word, $reserved, true ) ) {
					$clean[] = $word;
				}
			}

			// Bail if there's nothing left.
			if ( empty( $clean ) ) {
				return $text;
			}

			// Escape each entry.
			array_walk(
				$clean,
				function( &$item ) {
					$item = preg_quote( trim( $item ), '/' );
				}
			);

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
			$pages = array_unique(
				array_merge(
					commentpress_thoreau_get_featured_comments_pages(),
					commentpress_thoreau_get_liked_comments_pages()
				)
			);

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
				$raw       = $wpdb->esc_like( $query->query_vars['s'] );
				$like      = '%' . $raw . '%';

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
				// $search_orderby .= 'ELSE 6 END)';

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
	 * @param array  $posts The existing array of posts.
	 * @param object $query The query object.
	 * @return array $posts The modified array of posts.
	 */
	public function results_filter( $posts, $query ) {

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
			if ( ! in_array( $word, $this->stopwords, true ) ) {
				$this->stopwords[] = $word;
			}
		}

		// --<
		return $this->stopwords;

	}

}
