<?php
/**
 * @package Prospress
 * @subpackage Feedback
 */

/**
 * Prints the feedback for an author. If user id is not specified, the function attempts 
 * 
 * @param $user_id optional the user id of the . 
 */
function the_users_feedback_items( $user_id = '' ){
	global $authordata;

	if( $user_id == '' )
		$user_id = $authordata->ID;

	echo '<ul>';
	echo '<li>' . pp_users_positive_feedback( $user_id, 'received' ) . ' ' . __( 'positive' ) . '</li>';
	echo '<li>' . pp_users_neutral_feedback( $user_id, 'received' ) . ' ' . __( 'neutral' ) .  '</li>';
	echo '<li>' . pp_users_negative_feedback( $user_id, 'received' ) . ' ' . __( 'negative' ) .  '</li>';
	echo '</ul>';
}

/**
 * Prints the latest feedback comment a user received.
 * 
 * @param $user_id optionally specify if a feedback item has been place from a specified user. 
 */
function the_most_recent_feedback( $user_id = '' ){
	global $authordata;

	if( $user_id == '' )
		$user_id = $authordata->ID;

	$latest = pp_get_latest_feedback( $user_id );

	echo "<p>";
	_e( 'Recent Feedback: ', 'prospress' );
	echo '<quote class="sub">' . $latest['feedback_comment'] . '</quote>';
	echo '<br />';
	_e( 'From: ', 'prospress' );
	echo get_userdata( $latest['from_user_id'] )->user_nicename;
	echo '</p>';
	echo "".'<br class="clear" /></div>';
}
