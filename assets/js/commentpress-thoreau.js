/*
================================================================================
Javascript for "CommentPress Thoreau" Theme
================================================================================
AUTHOR: Christian Wach <needle@haystack.co.uk>
--------------------------------------------------------------------------------
NOTES



--------------------------------------------------------------------------------
*/




// define our vars
var styles;

// init styles
styles = '';

// wrap with js test
if ( document.getElementById ) {

	// open style declaration
	styles += '<style type="text/css" media="screen">';

	// show special pages menu
	styles += '.page-template-comments-featured-php #navigation .paragraph_wrapper.special_pages_wrapper { display: block; } ';
	styles += '.page-template-comments-liked-php #navigation .paragraph_wrapper.special_pages_wrapper { display: block; } ';

	// close style declaration
	styles += '</style>';

}

// write to page now
document.write( styles );



