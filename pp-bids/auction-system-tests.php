<?php
/**
 * A suite of tests for the auction market system class.
 *
 * @package Prospress
 * @since 0.1
 */
require_once ( PP_BIDS_DIR . '/pp-auction-system.class.php' );

function auction_test(){

	$market_system_test = new PP_Auction_Bid_System();

	$post_id		= 333333;
	$bidder_id		= 333;
	$bid_value		= 11.23;
	$bid_status		= '';
	$bid_date 		= current_time( 'mysql' );
	$bid_date_gmt 	= current_time( 'mysql', 1 );

	if ( true ) { // test bid_form_submit
		// Create first bid
		error_log( "******************* Create first bid *********************" );
		$bid_status = $market_system_test->bid_form_submit( $post_id, $bid_value, $bidder_id );
		error_log( "bid_status = $bid_status (should be 1)" );
		$winning_bid = $market_system_test->get_winning_bid_value( $post_id );
		//error_log( "winning_bid = ". $winning_bid->get_value() . " (should be 11.23 * " . BID_INCREMENT . ")" );
		error_log( "winning_bid = ". $winning_bid . " (should be 11.23 * " . BID_INCREMENT . ")" );
		error_log( "winning_bidder = " . $market_system_test->get_winning_bid( $post_id )->bidder_id );

		// Have bidder one try to decrease max bid
		error_log( "****************************************" );
		$bid_value	= 2;
		$bid_status = $market_system_test->bid_form_submit( $post_id, $bid_value, $bidder_id );
		error_log( "bid_status = $bid_status (should be 5)" );
		$winning_bid = $market_system_test->get_winning_bid_value( $post_id );
		error_log( "winning_bid = ". $winning_bid . " (should be 11.23 * " . BID_INCREMENT . ")" );
		error_log( "winning_bidder = " . $market_system_test->get_winning_bid( $post_id )->bidder_id );

		// Have bidder one increase max bid
		error_log( "****************************************" );
		$bid_value	= 20;
		$bid_status = $market_system_test->bid_form_submit( $post_id, $bid_value, $bidder_id );
		error_log( "bid_status = $bid_status (should be 4)" );
		$winning_bid = $market_system_test->get_winning_bid_value( $post_id );
		error_log( "winning_bid = ". $winning_bid . " (should be 11.23 * " . BID_INCREMENT . ")" );
		error_log( "winning_bidder = " . $market_system_test->get_winning_bid( $post_id )->bidder_id );

		// Change to new bidder
		$bidder_id	= 111;

		// Have bidder two bid below winning bid value
		error_log( "****************************************" );
		$bid_value	= 0.1;
		$bid_status = $market_system_test->bid_form_submit( $post_id, $bid_value, $bidder_id );
		error_log( "bid_status = $bid_status (should be 3)" );
		$winning_bid = $market_system_test->get_winning_bid_value( $post_id );
		error_log( "winning_bid = ". $winning_bid . " (should be 11.23 * " . BID_INCREMENT . ")" );
		error_log( "winning_bidder = " . $market_system_test->get_winning_bid( $post_id )->bidder_id );

		// Have bidder two bid above winning bid value, but below previous bidder's max bid
		error_log( "****************************************" );
		$bid_value	= 10;
		$bid_status = $market_system_test->bid_form_submit( $post_id, $bid_value, $bidder_id );
		error_log( "bid_status = $bid_status (should be 2)" );
		$winning_bid = $market_system_test->get_winning_bid_value( $post_id );
		error_log( "winning_bid = ". $winning_bid . " (should be $bid_value * " . BID_INCREMENT . ")" );
		error_log( "winning_bidder = " . $market_system_test->get_winning_bid( $post_id )->bidder_id );

		// Have bidder two bid exactly the same as bidder one's max bid
		error_log( "****************************************" );
		$bid_value	= 20;
		$bid_status = $market_system_test->bid_form_submit( $post_id, $bid_value, $bidder_id );
		error_log( "bid_status = $bid_status (should be 2)" );
		$winning_bid = $market_system_test->get_winning_bid_value( $post_id );
		error_log( "winning_bid = ". $winning_bid . " (should be $bid_value)" );
		error_log( "winning_bidder = " . $market_system_test->get_winning_bid( $post_id )->bidder_id );

		// Have bidder two bid above bidder one's winning bid value but below winning bid * (1 + bid increment)
		error_log( "****************************************" );
		$bid_value	= 20.9;
		$bid_status = $market_system_test->bid_form_submit( $post_id, $bid_value, $bidder_id );
		error_log( "bid_status = $bid_status (should be 1)" );
		$winning_bid = $market_system_test->get_winning_bid_value( $post_id );
		error_log( "winning_bid = ". $winning_bid . " (should be $bid_value)" );
		error_log( "winning_bidder = " . $market_system_test->get_winning_bid( $post_id )->bidder_id );

		// Have bidder two bid above own winning bid value and above winning bid * (1 + bid increment)
		error_log( "****************************************" );
		$bid_value	= 25;
		$bid_status = $market_system_test->bid_form_submit( $post_id, $bid_value, $bidder_id );
		error_log( "bid_status = $bid_status (should be 4)" );
		$winning_bid = $market_system_test->get_winning_bid_value( $post_id );
		error_log( "winning_bid = ". $winning_bid . " (should be $bid_value * " . BID_INCREMENT . ")" );
		error_log( "winning_bidder = " . $market_system_test->get_winning_bid( $post_id )->bidder_id );

		// Have bidder two bid equal to his current max bid
		error_log( "****************************************" );
		$bid_status = $market_system_test->bid_form_submit( $post_id, $bid_value, $bidder_id );
		error_log( "bid_status = $bid_status (should be 6)" );
		$winning_bid = $market_system_test->get_winning_bid_value( $post_id );
		error_log( "winning_bid = ". $winning_bid . " (should be $bid_value * " . BID_INCREMENT . ")" );
		error_log( "winning_bidder = " . $market_system_test->get_winning_bid( $post_id )->bidder_id );
	}

	if ( false ) { // test bid_form_validate
		// Control entry in bids table and post meta table, on mythical post id with mythical bidder
		$winning_bid = $market_system_test->update_winning_bid( 1, $post_id, $bid_value, $bidder_id );
		error_log( "winning_bid = $winning_bid (should be 11.23 * " . BID_INCREMENT . ")" );
		$current_winning_bid = $market_system_test->get_winning_bid_value( $post_id );
		error_log( "current_winning_bid = " . print_r($current_winning_bid, true) . " (should be 11.23 * " . BID_INCREMENT . ")" );
		error_log( "winning_bidder = " . $market_system_test->get_winning_bid( $post_id )->bidder_id );

		$wpdb->insert( $wpdb->bids, array( 'post_id' => $post_id, 'bidder_id' => $bidder_id, 'bid_value' => $bid_value, 'bid_date' => $bid_date, 'bid_date_gmt' => $bid_date_gmt ) );

		//$market_system_test->bid_form_validate( $post_id, $bid_value, $bidder_id );

		$bid_status = $market_system_test->bid_form_validate( $post_id, $bid_value, $bidder_id );
		error_log( "bid_status = $bid_status (should be 6)" );
		$winning_bid = $market_system_test->update_winning_bid( $bid_status, $post_id, $bid_value, $bidder_id );
		error_log( "winning_bid = $winning_bid (should be 11.23 * " . BID_INCREMENT . ")" );
		error_log( "winning_bidder = " . $market_system_test->get_winning_bid( $post_id )->bidder_id );

		$bid_value	= 2;
		$bid_status = $market_system_test->bid_form_validate( $post_id, $bid_value, $bidder_id );
		error_log( "bid_status = $bid_status (should be 5)" );
		$winning_bid = $market_system_test->update_winning_bid( $bid_status, $post_id, $bid_value, $bidder_id );
		error_log( "winning_bid = $winning_bid (should be 11.23 * " . BID_INCREMENT . ")" );
		error_log( "winning_bidder = " . $market_system_test->get_winning_bid( $post_id )->bidder_id );

		$bid_value	= 20;
		$bid_status = $market_system_test->bid_form_validate( $post_id, $bid_value, $bidder_id );
		error_log( "bid_status = $bid_status (should be 4)" );
		$winning_bid = $market_system_test->update_winning_bid( $bid_status, $post_id, $bid_value, $bidder_id );
		error_log( "winning_bid = $winning_bid (should be 11.23 * " . BID_INCREMENT . ")" );
		error_log( "winning_bidder = " . $market_system_test->get_winning_bid( $post_id )->bidder_id );

		$wpdb->insert( $wpdb->bids, array( 'post_id' => $post_id, 'bidder_id' => $bidder_id, 'bid_value' => $bid_value, 'bid_date' => $bid_date, 'bid_date_gmt' => $bid_date_gmt ) );

		$bidder_id	= 111;

		$bid_value	= 0.1;
		$bid_status = $market_system_test->bid_form_validate( $post_id, $bid_value, $bidder_id );
		error_log( "bid_status = $bid_status (should be 3)" );
		$winning_bid = $market_system_test->update_winning_bid( $bid_status, $post_id, $bid_value, $bidder_id );
		error_log( "winning_bid = $winning_bid (should be 11.23 * " . BID_INCREMENT . ")" );
		error_log( "winning_bidder = " . $market_system_test->get_winning_bid( $post_id )->bidder_id );

		$bid_value	= 10;
		$bid_status = $market_system_test->bid_form_validate( $post_id, $bid_value, $bidder_id );
		error_log( "bid_status = $bid_status (should be 2)" );
		$winning_bid = $market_system_test->update_winning_bid( $bid_status, $post_id, $bid_value, $bidder_id );
		error_log( "winning_bid = $winning_bid (should be $bid_value * " . BID_INCREMENT . ")" );
		error_log( "winning_bidder = " . $market_system_test->get_winning_bid( $post_id )->bidder_id );

		$bid_value	= 20;
		$bid_status = $market_system_test->bid_form_validate( $post_id, $bid_value, $bidder_id );
		error_log( "bid_status = $bid_status (should be 2)" );
		$winning_bid = $market_system_test->update_winning_bid( $bid_status, $post_id, $bid_value, $bidder_id );
		error_log( "winning_bidder = " . $market_system_test->get_winning_bid( $post_id )->bidder_id );

		$bid_value	= 20.9;
		$bid_status = $market_system_test->bid_form_validate( $post_id, $bid_value, $bidder_id );
		error_log( "bid_status = $bid_status (should be 1)" );
		$winning_bid = $market_system_test->update_winning_bid( $bid_status, $post_id, $bid_value, $bidder_id );
		error_log( "winning_bid = $winning_bid (should be $bid_value * " . BID_INCREMENT . ")" );
		error_log( "winning_bidder = " . $market_system_test->get_winning_bid( $post_id )->bidder_id );

		$bid_value	= 25;
		$bid_status = $market_system_test->bid_form_validate( $post_id, $bid_value, $bidder_id );
		error_log( "bid_status = $bid_status (should be 4)" );
		$winning_bid = $market_system_test->update_winning_bid( $bid_status, $post_id, $bid_value, $bidder_id );
		error_log( "winning_bid = $winning_bid (should be $bid_value * " . BID_INCREMENT . ")" );
		error_log( "winning_bidder = " . $market_system_test->get_winning_bid( $post_id )->bidder_id );
	}

	{ // clean up mythical post
		$wpdb->query( "DELETE FROM $wpdb->bids WHERE post_id = $post_id" );
		delete_post_meta( $post_id, 'winning_bid_value' );
		delete_post_meta( $post_id, 'winning_bidder_id' );
	}
}
add_action( 'wp_footer', 'auction_test' );
