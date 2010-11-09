<?php
/**
 * Print the bid form
 *
 * @since 0.1
 */
function the_bid_form( $post_id = '' ) {
	global $post, $market_systems;

	if ( empty( $post_id ) )
		$post_id = $post->ID;

	$market = $market_systems[ get_post_type( $post_id ) ];

	$market->the_bid_form();
}

/**
 * Print the winning bid value for a post. If no post id is specified, the global post object
 * is used allowing this function to be used within a loop.
 **/
function the_winning_bid_value( $post_id = '' ) {
	global $post, $market_systems;

	if ( empty( $post_id ) )
		$post_id = $post->ID;

	$market = $market_systems[ get_post_type( $post_id ) ];

	$market->the_winning_bid_value();
}

