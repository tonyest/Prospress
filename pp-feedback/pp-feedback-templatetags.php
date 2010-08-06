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

	echo '<div "feedback-title">';
	echo __( 'Recent Feedback: ', 'prospress' );
	echo '</div>';
	echo '<blockquote class="feedback-comment">' . $latest['feedback_comment'] . '</blockquote>';
	echo '<div "feedback-provider">';
	echo __( 'From: ', 'prospress' );
	echo get_userdata( $latest['from_user_id'] )->user_nicename;
	echo '</div>';
}


/**
 * Creates an anchor tag linking to the user's feedback table, optionally prints.
 * 
 */
function pp_the_feedback_url( $desc = "View Feedback", $echo = '' ) {

	$feedback_tag = "<a href='" . pp_get_feedback_url() . "' title='$desc'>$desc</a>";

	if( $echo == 'echo' )
		echo $feedback_tag;
	else
		return $feedback_tag;
}


/**
 * Gets the url to the user's feedback table.
 * 
 */
function pp_get_feedback_url() {
	if( current_user_can( 'edit_users' ) )
		$feedback_url = admin_url( 'users.php?page=feedback' );
	else
		$feedback_url = admin_url( 'profile.php?page=feedback' );

	 return $feedback_url;
}
