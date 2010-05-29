<?php
/**
 * @package Prospress
 * @subpackage Feedback
 */

/**
 * Prints the feedback for an author. If user id is not specified, the function attempts 
 * 
 * @param $post_id the post to check feedback records for 
 * @param $user_id optionally specify if a feedback item has been place from a specified user. 
 */
function the_authors_feedback_list( $user_id = '' ){
	global $authordata;

	error_log( '$authordata = ' . print_r( $authordata, true ) );
	if( $user_id == '' )
		$user_id = $authordata->ID;

	echo '<ul>';
	echo '<li>' . pp_users_positive_feedback( $user_id, 'received' ) . ' positive</li>';
	echo '<li>' . pp_users_neutral_feedback( $user_id, 'received' ) . ' neutral</li>';
	echo '<li>' . pp_users_negative_feedback( $user_id, 'received' ) . ' negative</li>';
	echo '</ul>';
}
