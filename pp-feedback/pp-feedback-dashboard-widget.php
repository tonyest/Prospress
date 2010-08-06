<?php
/**
 * Outputs the a user's Feedback into a Dashboard Widget
 *
 * @package Prospress
 * @author Brent Shepherd
 * @version 0.1
 */
function pp_feedback_dashboard_widget() {
	global $user_ID;

	echo '<p class="sub">' . __('Overview', 'prospress' ) . '</p>';
	echo '<div class="table">' . '<table>';
	echo '<tr class="first">';

	// Feedback
	$total_feedback = pp_users_feedback_count( $user_ID );
	$num = number_format_i18n( $total_feedback );
	$text = __( 'Feedback', 'prospress' );
	if ( $total_feedback > 0 ) {
		$feedback_url = add_query_arg( array( 'page' => 'feedback' ), 'users.php' );
		$num = "<a href='$feedback_url'>$num</a>";
		$text = "<a href='$feedback_url'>$text</a>";
	}
	echo "<td class='first b b-posts'>$num</td>";
	echo "<td class='t posts'>$text</td>";

	// Feedback break down
	// Feedback Received
	$received = pp_users_feedback_count( $user_ID, 'received' );
	$num = '<span class="total-count">' . number_format_i18n( $received ) . '</span>';
	$text = __( 'Received', 'prospress' );
	if ( $received > 0 ) {
		$received_url = add_query_arg( array( 'filter' => 'received' ), 'users.php?page=feedback' );
		$num = "<a href='$received_url'>$num</a>";
		$text = "<a href='$received_url'>$text</a>";
	}
	echo "<td class='b b-comments'>$num</td>";
	echo "<td class='last t comments'>$text</td>";

	// Feedback Given
	$given = pp_users_feedback_count( $user_ID, 'given' );
	$num = '<span class="total-count">' . number_format_i18n( $given ) . '</span>';
	$text = __( 'Given', 'prospress' );
	if ( $given > 0 ) {
		$given_url = add_query_arg( array( 'filter' => 'given' ), 'users.php?page=feedback' );
		$num = "<a href='$given_url'>$num</a>";
		$text = "<a href='$given_url'>$text</a>";
	}
	echo "<td class='b b-comments'>$num</td>";
	echo "<td class='last t comments'>$text</td>";

	if ( $total_feedback > 0 ) {
		echo '</tr><tr>';
		// Feedback Received Breakdown
		// Positive Feedback Received
		$positive = pp_users_positive_feedback( $user_ID, 'received' );
		$num = number_format_i18n( $positive );
		$text = __( 'Positive Feedback Received', 'prospress' );
		if ( $positive > 0 ) {
			$given_url = add_query_arg( array( 'filter' => 'received' ), 'users.php?page=feedback' );
			$num = "<a href='$feedback_url'>$num</a>";
			$text = "<a href='$feedback_url'>$text</a>";
		}
		echo "<td class='first b b-posts'>$num</td>";
		echo "<td class='t posts'>$text</td>";

		// Neutral Feedback Received
		$neutral = pp_users_neutral_feedback( $user_ID, 'received' );
		$num = '<span class="total-count">' . number_format_i18n( $neutral ) . '</span>';
		$text = __( 'Neutral Feedback Received', 'prospress' );
		if ( $neutral > 0 ) {
			$given_url = add_query_arg( array( 'filter' => 'received' ), 'users.php?page=feedback' );
			$num = "<a href='$received_url'>$num</a>";
			$text = "<a href='$received_url'>$text</a>";
		}
		echo "<td class='b b-comments'>$num</td>";
		echo "<td class='last t comments'>$text</td>";

		// Negative Feedback Received
		$negative = pp_users_negative_feedback( $user_ID, 'received' );
		$num = '<span class="total-count">' . number_format_i18n( $negative ) . '</span>';
		$text = __( 'Negative Feedback Received', 'prospress' );
		if ( $negative > 0 ) {
			$given_url = add_query_arg( array( 'filter' => 'received' ), 'users.php?page=feedback' );
			$num = "<a href='$given_url'>$num</a>";
			$text = "<a href='$given_url'>$text</a>";
		}
		echo "<td class='b b-comments'>$num</td>";
		echo "<td class='last t comments'>$text</td>";

		echo '</tr><tr>';
		// Feedback Given Breakdown
		// Positive Feedback Given
		$positive = pp_users_positive_feedback( $user_ID, 'given' );
		$num = number_format_i18n( $positive );
		$text = __( 'Positive Feedback Given', 'prospress' );
		if ( $positive > 0 ) {
			$received_url = add_query_arg( array( 'filter' => 'given' ), 'users.php?page=feedback' );
			$num = "<a href='$feedback_url'>$num</a>";
			$text = "<a href='$feedback_url'>$text</a>";
		}
		echo "<td class='first b b-posts'>$num</td>";
		echo "<td class='t posts'>$text</td>";

		// Neutral Feedback Given
		$neutral = pp_users_neutral_feedback( $user_ID, 'given' );
		$num = '<span class="total-count">' . number_format_i18n( $neutral ) . '</span>';
		$text = __( 'Neutral Feedback Given', 'prospress' );
		if ( $neutral > 0 ) {
			$received_url = add_query_arg( array( 'filter' => 'given' ), 'users.php?page=feedback' );
			$num = "<a href='$received_url'>$num</a>";
			$text = "<a href='$received_url'>$text</a>";
		}
		echo "<td class='b b-comments'>$num</td>";
		echo "<td class='last t comments'>$text</td>";

		// Negative Feedback Given
		$negative = pp_users_negative_feedback( $user_ID, 'given' );
		$num = '<span class="total-count">' . number_format_i18n( $negative ) . '</span>';
		$text = __( 'Negative Feedback Given', 'prospress' );
		if ( $negative > 0 ) {
			$given_url = add_query_arg( array( 'filter' => 'given' ), 'users.php?page=feedback' );
			$num = "<a href='$given_url'>$num</a>";
			$text = "<a href='$given_url'>$text</a>";
		}
		echo "<td class='b b-comments'>$num</td>";
		echo "<td class='last t comments'>$text</td>";
	}

	echo '</tr>';
	echo '</table>';
	echo '</div>';

	if ( $received > 0 ) {
		the_most_recent_feedback( $user_ID );
	}
	do_action( 'feedback_box_end' );
}

/**
 * Creates the widget function to use in the dashboard set-up action hook if current user has required privileges
 * 
 * @uses wp_add_dashboard_widget
 **/
function pp_feedback_add_dashboard_widgets() {
	global $wp_meta_boxes;

	if ( current_user_can('read') ){
		$wp_meta_boxes['dashboard']['side']['core']['dashboard_feedback'] = array(
											'id' => 'dashboard_feedback',
											'title' => 'Feedback',
											'callback' => 'pp_feedback_dashboard_widget',
											'args' => ''
		                                );
	}
}
//Save it for later...
//add_action('wp_dashboard_setup', 'pp_feedback_add_dashboard_widgets' );
