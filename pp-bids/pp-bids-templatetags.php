<?php
/**
 * Loads the template for making a bid as specified in $file.
 *
 * The $file path is passed through a filter hook called, 'make_bid_template'
 * which includes the TEMPLATEPATH and $file combined. Tries the $filtered path
 * first and if it fails it will require the default bids template.
 *
 * @since 0.1
 * @global array $comment List of comment objects for the current post
 * @uses $id
 * @uses $post
 *
 * @param string $file Optional, default '/bid-form.php'. The file containing the template for making a bid.
 * @return null Returns null if no bids appear
 */
function the_bid_form( $content ) {
	global $bid_system;

	if( is_single() && !is_admin() )
		$content = $bid_system->bid_form() . $content;
		//$content .= $bid_system->bid_form();
		//echo $bid_system->bid_form();
	return $content;
}

