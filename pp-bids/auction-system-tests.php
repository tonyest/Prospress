<?php
/**
 * A suite of tests for the auction market system class.
 *
 * @package Prospress
 * @since 0.1
 */
require_once ( PP_BIDS_DIR . '/pp-auction-system.class.php' );

//Wrapper to make protected internal functions public
class PP_Auction_Bid_System_Test extends PP_Auction_Bid_System {

	public function bid_form_submit( $post_id = NULL, $bid_value = NULL, $bidder_id = NULL ){
		return parent::bid_form_submit( $post_id, $bid_value, $bidder_id );
	}

	public function update_bid( $bid ){
		return parent::update_bid( $bid );
	}

	public function validate_bid( $post_id, $bid_value, $bidder_id ){
		return parent::validate_bid( $post_id, $bid_value, $bidder_id );
	}
}


function auction_test(){
	global $wpdb;

	$market_system_test = new PP_Auction_Bid_System_Test();

	$author_id		= 331;
	$bidder_id		= 332;
	$bid_value		= 11.23;
	$bid_status		= '';
	$bid_date 		= current_time( 'mysql' );
	$bid_date_gmt 	= current_time( 'mysql', 1 );
	$post_content 	= "This is an auto generated mock post number";
	$post_title 	= "Mock post number";

	$wpdb->insert( $wpdb->posts, array( 'post_author' => $author_id, 'post_date' => $bid_date, 'post_date_gmt' => $bid_date_gmt, 'post_content' => $post_content, 'post_title' => $post_title ), array( '%d', '%s', '%s', '%s', '%s' ) );
	$post_id = $wpdb->insert_id;

	if ( false ) { // test bid_form_submit
		// Create first bid
		error_log( "******************* Create first bid *********************" );
		$bid = $market_system_test->bid_form_submit( $post_id, $bid_value, $bidder_id );
		error_log( "bid status = " . $bid[ 'message_id' ] . " (should be 0: Congratulations, you are the winning bidder.)" );
		$winning_bid = $market_system_test->get_winning_bid_value( $post_id );
		//error_log( "winning_bid = ". $winning_bid->get_value() . " (should be 11.23 * " . BID_INCREMENT . ")" );
		error_log( "winning_bid = ". $winning_bid . " (should be 11.23 * " . BID_INCREMENT . ")" );
		error_log( "winning_bidder = " . $market_system_test->get_winning_bid( $post_id )->bidder_id );

		// Have bidder one try to decrease max bid
		error_log( "****************************************" );
		$bid_value	= 2;
		$bid = $market_system_test->bid_form_submit( $post_id, $bid_value, $bidder_id );
		error_log( "bid status = " . $bid[ 'message_id' ] . " (should be 5: You can not decrease your maximum bid.)" );
		$winning_bid = $market_system_test->get_winning_bid_value( $post_id );
		error_log( "winning_bid = ". $winning_bid . " (should be 11.23 * " . BID_INCREMENT . ")" );
		error_log( "winning_bidder = " . $market_system_test->get_winning_bid( $post_id )->bidder_id );

		// Have bidder one increase max bid
		error_log( "****************************************" );
		$bid_value	= 20;
		$bid = $market_system_test->bid_form_submit( $post_id, $bid_value, $bidder_id );
		error_log( "bid status = " . $bid[ 'message_id' ] . " (should be 4: Your maximum bid has been increased.)" );
		$winning_bid = $market_system_test->get_winning_bid_value( $post_id );
		error_log( "winning_bid = ". $winning_bid . " (should be 11.23 * " . BID_INCREMENT . ")" );
		error_log( "winning_bidder = " . $market_system_test->get_winning_bid( $post_id )->bidder_id );

		// Change to new bidder
		$bidder_id	= 111;

		// Have bidder two bid below winning bid value
		error_log( "****************************************" );
		$bid_value	= 0.1;
		$bid = $market_system_test->bid_form_submit( $post_id, $bid_value, $bidder_id );
		error_log( "bid status = " . $bid[ 'message_id' ] . " (should be 3: You must bid more than the winning bid.)" );
		$winning_bid = $market_system_test->get_winning_bid_value( $post_id );
		error_log( "winning_bid = ". $winning_bid . " (should be 11.23 * " . BID_INCREMENT . ")" );
		error_log( "winning_bidder = " . $market_system_test->get_winning_bid( $post_id )->bidder_id );

		// Have bidder two bid above winning bid value, but below previous bidder's max bid
		error_log( "****************************************" );
		$bid_value	= 10;
		$bid = $market_system_test->bid_form_submit( $post_id, $bid_value, $bidder_id );
		error_log( "bid status = " . $bid[ 'message_id' ] . " (should be 2: You have been outbid.)" );
		$winning_bid = $market_system_test->get_winning_bid_value( $post_id );
		error_log( "winning_bid = ". $winning_bid . " (should be $bid_value * " . BID_INCREMENT . ")" );
		error_log( "winning_bidder = " . $market_system_test->get_winning_bid( $post_id )->bidder_id );

		// Have bidder two bid exactly the same as bidder one's max bid
		error_log( "****************************************" );
		$bid_value	= 20;
		$bid = $market_system_test->bid_form_submit( $post_id, $bid_value, $bidder_id );
		error_log( "bid status = " . $bid[ 'message_id' ] . " (should be 2: You have been outbid.)" );
		$winning_bid = $market_system_test->get_winning_bid_value( $post_id );
		error_log( "winning_bid = ". $winning_bid . " (should be $bid_value)" );
		error_log( "winning_bidder = " . $market_system_test->get_winning_bid( $post_id )->bidder_id );

		// Have bidder two bid above bidder one's winning bid value but below winning bid * (1 + bid increment)
		error_log( "****************************************" );
		$bid_value	= 20.9;
		$bid = $market_system_test->bid_form_submit( $post_id, $bid_value, $bidder_id );
		error_log( "bid status = " . $bid[ 'message_id' ] . " (should be 1: Congratulations, you are the winning bidder.)" );
		$winning_bid = $market_system_test->get_winning_bid_value( $post_id );
		error_log( "winning_bid = ". $winning_bid . " (should be $bid_value)" );
		error_log( "winning_bidder = " . $market_system_test->get_winning_bid( $post_id )->bidder_id );

		// Have bidder two bid above own winning bid value and above winning bid * (1 + bid increment)
		error_log( "****************************************" );
		$bid_value	= 25;
		$bid = $market_system_test->bid_form_submit( $post_id, $bid_value, $bidder_id );
		error_log( "bid status = " . $bid[ 'message_id' ] . " (should be 4: Your maximum bid has been increased.)" );
		$winning_bid = $market_system_test->get_winning_bid_value( $post_id );
		error_log( "winning_bid = ". $winning_bid . " (should be $bid_value * " . BID_INCREMENT . ")" );
		error_log( "winning_bidder = " . $market_system_test->get_winning_bid( $post_id )->bidder_id );

		// Have bidder two bid equal to his current max bid
		error_log( "****************************************" );
		$bid = $market_system_test->bid_form_submit( $post_id, $bid_value, $bidder_id );
		error_log( "bid status = " . $bid[ 'message_id' ] . " (should be 6: You have entered a bid equal to your current maximum bid.)" );
		$winning_bid = $market_system_test->get_winning_bid_value( $post_id );
		error_log( "winning_bid = ". $winning_bid . " (should be $bid_value * " . BID_INCREMENT . ")" );
		error_log( "winning_bidder = " . $market_system_test->get_winning_bid( $post_id )->bidder_id );
	}

	if ( true ) { // test validate bid
		// Control entry in bids table and post meta table, on mythical post id with mythical bidder
		$bid = array( "post_id" => $post_id, "bidder_id" => $bidder_id, "bid_value" => $bid_value, 'bid_status' => 'winning', 'bid_date' => $bid_date, 'bid_date_gmt' => $bid_date_gmt );
		$winning_bid = $market_system_test->update_bid( $bid );

		error_log( "****************************************" );
		error_log( "winning_bid = $winning_bid (should be 11.23 * " . BID_INCREMENT . ")" );
		$current_winning_bid = $market_system_test->get_winning_bid_value( $post_id );
		error_log( "current_winning_bid = " . print_r($current_winning_bid, true) . " (should be 11.23 * " . BID_INCREMENT . ")" );
		error_log( "winning_bidder = " . $market_system_test->get_winning_bid( $post_id )->bidder_id );

		error_log( "****************************************" );
		$bid_msg_stat = $market_system_test->validate_bid( $post_id, $bid_value, $bidder_id );
		$message_id = $bid_msg_stat[ 'message_id' ];
		error_log( "bid_message_id = $message_id (should be 6: You have entered a bid equal to your current maximum bid.)" );
		$winning_bid = $market_system_test->get_winning_bid_value( $post_id );
		error_log( "winning_bid = $winning_bid (should be 11.23 * " . BID_INCREMENT . ")" );
		error_log( "winning_bidder = " . $market_system_test->get_winning_bid( $post_id )->bidder_id );

		error_log( "****************************************" );
		$bid_value	= 2;
		$bid_msg_stat = $market_system_test->validate_bid( $post_id, $bid_value, $bidder_id );
		$message_id = $bid_msg_stat[ 'message_id' ];
		error_log( "bid_message_id = $message_id (should be 5: You can not decrease your maximum bid.)" );
		$winning_bid = $market_system_test->get_winning_bid_value( $post_id );
		error_log( "winning_bid = $winning_bid (should be 11.23 * " . BID_INCREMENT . ")" );
		error_log( "winning_bidder = " . $market_system_test->get_winning_bid( $post_id )->bidder_id );

		error_log( "****************************************" );
		$bid_value	= 20;
		$bid_msg_stat = $market_system_test->validate_bid( $post_id, $bid_value, $bidder_id );
		$message_id = $bid_msg_stat[ 'message_id' ];
		error_log( "bid_message_id = $message_id (should be 4: Your maximum bid has been increased.)" );
		$winning_bid = $market_system_test->get_winning_bid_value( $post_id );
		error_log( "winning_bid = $winning_bid (should be 11.23 * " . BID_INCREMENT . ")" );
		error_log( "winning_bidder = " . $market_system_test->get_winning_bid( $post_id )->bidder_id );

		//$wpdb->insert( $wpdb->bids, array( 'post_id' => $post_id, 'bidder_id' => $bidder_id, 'bid_value' => $bid_value, 'bid_date' => $bid_date, 'bid_date_gmt' => $bid_date_gmt ) );

		$bidder_id = $bid[ 'bidder_id' ] = 111;

		error_log( "**************** NEW BIDDER ************************" );
		$bid_value = $bid[ 'bid_value' ] = 0.1;
		$bid_msg_stat = $market_system_test->validate_bid( $post_id, $bid_value, $bidder_id );
		$message_id = $bid_msg_stat[ 'message_id' ];
		error_log( "bid_message_id = $message_id (should be 3: You must bid more than the winning bid.)" );
		$winning_bid = $market_system_test->get_winning_bid_value( $post_id );
		error_log( "winning_bid = $winning_bid (should be 11.23 * " . BID_INCREMENT . ")" );
		error_log( "winning_bidder = " . $market_system_test->get_winning_bid( $post_id )->bidder_id );

		error_log( "****************************************" );
		$bid_value = $bid[ 'bid_value' ] = 10;
		$bid_msg_stat = $market_system_test->validate_bid( $post_id, $bid_value, $bidder_id );
		$message_id = $bid_msg_stat[ 'message_id' ];
		error_log( "bid_message_id = $message_id (should be 2: You have been outbid.)" );
		$winning_bid = $market_system_test->get_winning_bid_value( $post_id );
		error_log( "winning_bid = $winning_bid (should be 11.23 * " . BID_INCREMENT . ")" );
		error_log( "winning_bidder = " . $market_system_test->get_winning_bid( $post_id )->bidder_id );

		error_log( "****************************************" );
		$bid_value = $bid[ 'bid_value' ] = 20.9;
		$bid_msg_stat = $market_system_test->validate_bid( $post_id, $bid_value, $bidder_id );
		$message_id = $bid_msg_stat[ 'message_id' ];
		error_log( "bid_message_id = $message_id (should be 1: Congratulations, you are the winning bidder.)" );
		$winning_bid = $market_system_test->update_bid( $bid );
		error_log( "winning_bid = $winning_bid (should be 11.23 + 11.23 * " . BID_INCREMENT . ")" );
		error_log( "winning_bidder = " . $market_system_test->get_winning_bid( $post_id )->bidder_id );

		error_log( "****************************************" );
		$bid_value	= 25;
		$bid_msg_stat = $market_system_test->validate_bid( $post_id, $bid_value, $bidder_id );
		$message_id = $bid_msg_stat[ 'message_id' ];
		error_log( "bid_message_id = $message_id (should be 4: Your maximum bid has been increased.)" );
		$winning_bid = $market_system_test->update_bid( $bid );
		error_log( "winning_bid = $winning_bid (should be 11.23 + 11.23 * " . BID_INCREMENT . ")" );
		error_log( "winning_bidder = " . $market_system_test->get_winning_bid( $post_id )->bidder_id );
	}

	// clean up mock post & bid data
	wp_delete_post( $post_id, true );
	$wpdb->query( "DELETE FROM $wpdb->bids WHERE post_id = $post_id" );
	delete_post_meta( $post_id, 'winning_bid_value' );
	delete_post_meta( $post_id, 'winning_bidder_id' );
}
add_action( 'wp_footer', 'auction_test' );
