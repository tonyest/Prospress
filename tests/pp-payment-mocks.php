<?php
/*
Plugin Name: Prospress Payment Mocks
Plugin URI: http://prospress.org
Description: Creates mock posts with winning bids. These are used on an admin page to create links to "Send Invoice" and "Make Payment" and there is a function to generate an Invoice that needs to be filled in.
Author: Brent Shepherd
Version: 0.1
Author URI: http://brentshepherd.com/
*/

if ( !defined( 'PP_BIDS_DB_VERSION') )
	define ( 'PP_BIDS_DB_VERSION', '0022' );

global $wpdb;

if ( !isset($wpdb->bids) || empty($wpdb->bids))
	$wpdb->bids = $wpdb->prefix . 'bids';
if ( !isset($wpdb->bidsmeta) || empty($wpdb->bidsmeta))
	$wpdb->bidsmeta = $wpdb->prefix . 'bidsmeta';

function pp_payment_install( ) {
	global $wpdb;

	if ( !empty($wpdb->charset) )
		$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";

	if( $wpdb->get_var("SHOW TABLES LIKE '". $wpdb->bids ."'") != $wpdb->bids ) {
		$sql[] = "CREATE TABLE {$wpdb->bids} (
			  		bid_id bigint(20) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
			  		post_id bigint(20) unsigned NOT NULL,
			  		bidder_id bigint(20) unsigned NOT NULL,
			  		bid_value float(16,6) NOT NULL,
					bid_status varchar(20) NOT NULL DEFAULT 'pending',
			  		bid_date datetime NOT NULL,
			  		bid_date_gmt datetime NOT NULL,
				    KEY post_id (post_id),
				    KEY bidder_id (bidder_id),
				    KEY bid_date_gmt (bid_date_gmt)
				   ) {$charset_collate};";
	
		$sql[] = "CREATE TABLE {$wpdb->bidsmeta} (
			  		meta_id bigint(20) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
			  		bid_id bigint(20) unsigned NOT NULL,
			  		meta_key varchar(255) NOT NULL,
			  		meta_value longtext NOT NULL,
				    KEY bid_id (bid_id),
				    KEY meta_key (meta_key)
				   ) {$charset_collate};";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta($sql);
	}

	update_site_option( 'pp_bids_db_version_mock', PP_BIDS_DB_VERSION );
	
	pp_insert_mocks();
}
register_activation_hook( __FILE__, 'pp_payment_install' );

function pp_insert_mocks() {
	global $wpdb, $market_system;

	// Make sure there are at least two users
	$user_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $wpdb->users;"));
	if( $user_count < 2 )
		$wpdb->insert( $wpdb->users, array( 'user_login' => 'amockeduser',
											'user_pass' => md5('password'),
											'user_nicename' => 'MockUser',
											'user_email' => 'mock@example.com', 
											'user_url' => 'http://www.example.com', 
											'user_status' => 0, 
											'display_name' => 'Mock User',
											'user_registered' => date ("Y-m-d H:i:s") ) );

	update_site_option( 'pp_mock_user', $wpdb->insert_id );

	$users = $wpdb->get_results( "SELECT ID FROM $wpdb->users", ARRAY_N );
	//$user_one = $users[0][0];
	//$user_two = $users[1][0];
	$user_one = 1;
	$user_two = 3;
	
	$mock_post_ids = array();

	//Generate mock posts and fill with bids
	for( $i = 1; $i < 6; $i++){
		$post_content = "This is an auto generated mock post number $i";
		$post_title = "Mock post number $i";

		$wpdb->insert( $wpdb->posts, array( 'post_author' => $user_one, 'post_date' => date ("Y-m-d H:i:s"), 'post_date_gmt' => date ("Y-m-d H:i:s"), 'post_content' => $post_content, 'post_title' => $post_title, 'post_type' => $market_system->name() ), array( '%d', '%s', '%s', '%s', '%s', '%s' ) );

		$mock_post_ids[] = $post_id = $wpdb->insert_id;

		// create mock end time $i number of minutes from now
		$end_time = time() + ( 60 * $i );
		// schedule a wp cron job for the post to end in $i minutes form now and call the hook schedule_end_post when ending
		wp_schedule_single_event( $end_time, 'schedule_end_post', array( 'ID' => $post_id ) );
		add_post_meta( $post_id, 'post_end_date', $end_time );
		add_post_meta( $post_id, 'post_end_date_gmt', $end_time );

		// Insert Winning Bids Meta for this post
		for( $b = $i; $b < $i + 5; $b++ ){
			if( $b == $i + 4 ){ // last bid on a post, make it the winning bid
				$bid_stat = "winning";
				$bid_value = $b + 10;
				$wpdb->insert( $wpdb->bids, array( 'post_id' => $post_id, 'bidder_id' => $user_two, 'bid_value' => $bid_value, 'bid_status' => $bid_stat, 'bid_date' => date ("Y-m-d H:i:s"), 'bid_date_gmt' => date ("Y-m-d H:i:s") ), array( '%d', '%d', '%f', '%s', '%s', '%s' ) );
				$bid_id = $wpdb->insert_id;
				$winning_bid_value = $bid_value - $i * 2; // Calculate a winning bid value below the actual bid vale of the winning bid to simulate real world situation where winning bid value is actually only larger than the next highest bid, rather than the value of the highest bid
				$wpdb->insert( $wpdb->bidsmeta, array( 'bid_id' => $bid_id, 'meta_key' => 'winning_bid_value', 'meta_value' => $winning_bid_value ) );
			} else {
				$bid_stat = "outbid";
				$wpdb->insert( $wpdb->bids, array( 'post_id' => $post_id, 'bidder_id' => $user_two, 'bid_value' => $b, 'bid_status' => $bid_stat, 'bid_date' => date ("Y-m-d H:i:s"), 'bid_date_gmt' => date ("Y-m-d H:i:s") ), array( '%d', '%d', '%f', '%s', '%s', '%s' ) );
			}
		}
	$user_mid = $user_two;
	$user_two = $user_one;
	$user_one = $user_mid;
	}
	update_site_option( 'pp_mock_posts', $mock_post_ids );	
}

function pp_add_payment_mock_admin_pages(){

	$base_page = "Payment Mocks";

	if ( function_exists( 'add_object_page' ) )
		add_object_page( $base_page, $base_page, 1, $base_page, 'pp_payment_mock_page');
}
add_action( 'admin_menu', 'pp_add_payment_mock_admin_pages' );

function pp_payment_mock_page(){
	?>
	<div class="wrap feedback-history">
		<h2>Payment Mocks</h2>
		<?php
		$mock_post_ids = get_site_option( 'pp_mock_posts' );
		foreach( $mock_post_ids as $post_id ) {
			$winning_bid = get_winning_bid( $post_id );
			$payer_id = $winning_bid->bidder_id;
			$payer_name = get_userdata( $payer_id )->display_name;
			$payee_id = get_post( $post_id )->post_author;
			$payee_name = get_userdata( $payee_id )->display_name;
			$made_up_invoice = rand( 1000, 9999 );
			// **********************
			// add an actual "send_invoice" link here
			$send_invoice_url = admin_url( 'admin.php?page=send_invoice' );
			$send_invoice_url = add_query_arg( array( 'inv' => $made_up_invoice ), $send_invoice_url );
			// **********************
			// add an actual "make_payment" link here
			$make_payment_url = admin_url( 'admin.php?page=make_payment' );
			$make_payment_url = add_query_arg( array( 'inv' => $made_up_invoice ), $make_payment_url );
			// **********************
			// add an actual "view_invoice" link here
			$view_invoice_url = admin_url( 'admin.php?page=view_invoice' );
			$view_invoice_url = add_query_arg( array( 'inv' => $made_up_invoice ), $view_invoice_url );
			echo "<h3>For post $post_id</h3>";
			echo "<p>Invoice should be generated with the 'schedule_end_post' hook.</p>";
			echo "<p>Then $payee_name, as payee, can: <a href='$send_invoice_url' >Send Invoice</a></p>";
			echo "<p>And $payer_name, as payer, can: <a href='$make_payment_url' >Make Payment</a></p>";
			echo "<p>Finally, both $payee_name and $payer_name should be able to: <a href='$view_invoice_url' >View Invoice</a></p>";
		}?>
	</div>
	<?php
}

// *******************************************************************************************
// This is where the Invoice should be generated. 
// This function is automatically called when a post ends. In this mockup, that is 1-5 minutes after the 
// plugin is activated. The post_id of the post that has ended is passed to the function by the hook and
// then used to determine the other essential information: payer, payee & amount. 
function call_generate_invoice( $post_id ) { //receive post ID from hook
	global $market_system;

	$winning_bid = get_winning_bid( $post_id );
	$payer_id = $winning_bid->bidder_id;
	$payer_name = get_userdata( $payer_id )->display_name;
	$payee_id = get_post( $post_id )->post_author;
	$payee_name = get_userdata( $payee_id )->display_name;
	$amount = $winning_bid->bid_value;
	$status = 'pending';
	$type = $market_system->name();

	require_once( PP_PAYMENT_DIR . "/core/functions.php" );

	$args = compact( 'post_id', 'payer_id', 'payee_id', 'amount', 'status', 'type' );

	pp_invoice_create( $args );
}
add_action( 'schedule_end_post', 'call_generate_invoice');

function get_winning_bid( $post_id = '' ) {
	global $post, $wpdb;

	if ( empty( $post_id ) )
		$post_id = $post->ID;

	$winning_bid = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->bids WHERE post_id = %d AND bid_status = %s", $post_id, 'winning' ) );
	
	return $winning_bid;
}

function pp_payment_deactivate() {
	global $wpdb;

	if ( !current_user_can('edit_plugins') || !function_exists( 'delete_site_option') )
		return false;

	delete_site_option( 'pp_bids_db_version_mock' );
	$mock_post_ids = get_site_option( 'pp_mock_posts' );

	foreach( $mock_post_ids as $post_to_delete ){
		$wpdb->query( "DELETE FROM $wpdb->posts WHERE ID = $post_to_delete" );
		$end_time = wp_next_scheduled( 'schedule_end_post', array( 'ID' => $post_id ) );
		wp_unschedule_event( $end_time, 'schedule_end_post', array( 'ID' => $post_id ) );
		delete_post_meta( $post_id, 'post_end_date', $end_time );
		delete_post_meta( $post_id, 'post_end_date_gmt', $end_time );

		$sql = "DELETE FROM $wpdb->bids WHERE  post_id = " . $post_to_delete . ";";
		error_log('Running sql = ' . print_r($sql, true));
		$wpdb->query( $sql );
		$invoice_no = $wpdb->get_var( "SELECT id FROM $wpdb->payments WHERE post_id = " . $post_to_delete . ";" );
		if( $invoice_no ){
			$sql = "DELETE FROM $wpdb->payments WHERE post_id = " . $post_to_delete . ";";
			error_log('Running sql = ' . print_r($sql, true));
			$wpdb->query( $sql );
			$sql = "DELETE FROM $wpdb->paymentsmeta WHERE invoice_id = " . $invoice_no . ";";
			error_log('Running sql = ' . print_r($sql, true));
			$wpdb->query( $sql );
			$sql = "DELETE FROM $wpdb->payments_log WHERE invoice_id = " . $invoice_no . ";";
			error_log('Running sql = ' . print_r($sql, true));
			$wpdb->query( $sql );
		}
	}

	delete_site_option( 'pp_mock_posts' );
	delete_site_option( 'pp_mock_user' );
}
register_deactivation_hook( __FILE__, 'pp_payment_deactivate' );
