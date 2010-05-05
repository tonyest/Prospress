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

	if( is_single() )
		$content .= $bid_system->bid_form();
		//echo $bid_system->bid_form();
	return $content;
}






/** MOVED TO BID CLASS
 */
function _get_max_bid( $post_id = 0 ) {
	global $post, $wpdb;
	
	if ( empty($post_id) || $post_id == 0 )
		$post_id = $post->ID;
	
	$max_bid = $wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->bids WHERE post_id = %d AND bid_value = (SELECT MAX(bid_value) FROM $wpdb->bids WHERE post_id = %d)", $post_id, $post_id));

	//if ( empty($max_bid->bid_value))
	//	$max_bid->bid_value = 0;

	//error_log('$max_bid = '. print_r($max_bid, true));
	
	return $max_bid;
}

/** MOVED TO BID CLASS
 */

function _the_max_bid_value( $post_id = 0) {
	$max_bid = get_max_bid( $post_id );
	if ($max_bid->bid_value)
		echo $max_bid->bid_value;
	else 
		_e('no bids');
}

/** MOVED TO BID CLASS
 */
function _the_winning_bidder( $post_id = 0 ) {
	global $wpdb, $user_ID, $display_name;
	
	get_currentuserinfo(); //to set global $display_name
	
	$max_bid = get_max_bid( $post_id );
	
	if ($max_bid->bidder_id){
		if( $max_bid->bidder_id == $user_ID)
			_e('You.');
		else 
			echo get_userdata($max_bid->bidder_id)->display_name;
	} else {
		_e('No bidders.');
	}
}

/** MOVED TO BID CLASS
 */
function _get_winning_bid_value( $post_id = 0 ) {
	global $post, $wpdb;
	
	if ( empty($post_id) || $post_id == 0 )
		$post_id = $post->ID;

	$winning_bid = get_post_meta($post_id, 'winning_bid_value', true);
	error_log("got winning_bid = $winning_bid");

	if ($winning_bid)
		return $winning_bid;
	else 
		return 0;
}

/** MOVED TO BID CLASS
 */
function _the_winning_bid_value( $post_id = 0 ) {
	
	$winning_bid = get_winning_bid_value($post_id);
	error_log("printing the winning_bid = $winning_bid");

	if ($winning_bid)
		echo $winning_bid;
	else
		_e('No Bids.');
}

/** MOVED TO BID CLASS
 */
function _get_users_max_bid( $user_id = 0, $post_id = 0 ) {
	global $user_ID, $post, $wpdb;
	
	if ( empty($user_id) || $user_id == 0 )
		$user_id = $user_ID;

	if ( empty($post_id) || $post_id == 0 )
		$post_id = $post->ID;
	
	$users_max_bid = $wpdb->get_row($wpdb->prepare("SELECT * FROM %s WHERE post_id = %d AND bidder_id = %d AND bid_value = (SELECT MAX(bid_value) FROM %s WHERE post_id = %d AND bidder_id = %d)", $wpdb->bids, $post_id, $user_id, $wpdb->bids, $post_id, $user_id));
	error_log("users_max_bid = " . print_r($users_max_bid, true));

	//if ( empty($users_max_bid->bid_value))
	//	$users_max_bid->bid_value = 0;

	return $users_max_bid;
}

/** MOVED TO BID CLASS
 */
function _the_users_max_bid_value( $user_id = 0, $post_id = 0) {
	$users_max_bid = get_users_max_bid( $user_id, $post_id );
	if ($users_max_bid->bid_value)
		echo $users_max_bid->bid_value;
	else
		_e('No Bids.');
}


/** MOVED TO BID CLASS
 */
function _the_number_bids($post_id = 0, $print = false){
	global $post, $wpdb;

	if ( empty($post_id) || $post_id == 0 )
		$post_id = $post->ID;

	//$bid_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(ID) FROM $wpdb->bids WHERE post_id= %d", $post_id));
	//error_log("bid_count = $bid_count");
}

/** MOVED TO BID CLASS
 */
function _bid_hidden_fields() {
	global $post, $current_blog, $user_ID;

	echo "<input type='hidden' name='bid_post_ID' value='$post->ID' id='bid_post_ID' />\n";
	do_action('make_bid_hidden_fields');
}

/** MOVED TO BID CLASS
 */
function _bid_extra_fields() {

	do_action('bid_extra_fields');
}

/** MOVED TO BID CLASS
 */
function _pp_bid_form_action() {
	echo pp_get_bid_form_action();
}

/** MOVED TO BID CLASS
 */
function _pp_get_bid_form_action() {
	return apply_filters( 'pp_bid_form_action', PP_BIDS_URL . '/bid-post.php' );
}

/** MOVED TO BID CLASS
 */
function _print_bid_messages(){
	if(isset($_GET['bid_msg'])){
		$message_id = $_GET['bid_msg'];
		switch($message_id) {
			case 1:
				$message = __("1. You are the highest bidder.");
				break;
			case 2:
				$message = __("2. You have been outbid.");
				break;
			case 3:
				$message = __("3. You must bid higher than the current winning bid.");
				break;
			case 4:
				$message = __("4. Your max bid as been increased.");
				break;
			case 5:
				$message = __("5. You can not decrease your max bid.");
				break;
			case 6:
				$message = __("6. You have entered a bid equal to your current max bid.");
				break;
			case 7:
				$message = __("7. Invalid bid. Please enter a valid number. e.g. 11.23 or 58");
				break;
		}
		$message = apply_filters('print_bid_message', $message);
		echo '<div class="bid-updated fade" id="messages">';
		echo "<p>$message</p>";
		echo '</div>';
	}
}


?>