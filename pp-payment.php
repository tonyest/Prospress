<?php
/**
 * Prospress Payment
 * 
 * Money - the great enabler of trade. This component provides a system for traders in a Prospress marketplace 
 * to exchange money in return for posted items/services.
 * 
 * @package Prospress
 * @author Brent Shepherd
 * @version 0.1
 */

if( !defined( 'PP_PAYMENT_DIR' ) )
	define( 'PP_PAYMENT_DIR', PP_PLUGIN_DIR . '/pp-payment' );
if( !defined( 'PP_PAYMENT_URL' ) )
	define( 'PP_PAYMENT_URL', PP_PLUGIN_URL . '/pp-payment' );

global $wpdb;

if ( !isset($wpdb->payments) || empty($wpdb->payments))
	$wpdb->payments = $wpdb->base_prefix . 'payments';
if ( !isset($wpdb->paymentsmeta) || empty($wpdb->paymentsmeta))
	$wpdb->paymentsmeta = $wpdb->base_prefix . 'paymentsmeta';
if ( !isset($wpdb->payments_log) || empty($wpdb->payments_log))
	$wpdb->payments_log = $wpdb->base_prefix . 'payments_log';

/**
 * The engine behind the payment system - TwinCitiesTech's WP Invoice modified for marketplace payments. 
 * All the payment system action happens in there. 
 */
require_once( PP_PAYMENT_DIR . '/pp-invoice.php' );

include_once( PP_PAYMENT_DIR . '/pp-payment-templatetags.php' );

/**
 * Setup payment database tables. 
 **/
function pp_payment_install() {
	global $wpdb;

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

	if ( !empty( $wpdb->charset ) )
		$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";

	if( $wpdb->get_var("SHOW TABLES LIKE '". $wpdb->payments ."'") != $wpdb->payments ) {
		$sql_main = "CREATE TABLE $wpdb->payments (
				id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				post_id bigint(20) NOT NULL,
				payer_id bigint(20) NOT NULL,
				payee_id bigint(20) NOT NULL,
				amount float(16,6) default '0',
				status varchar(20) NOT NULL,
				type varchar(255) NOT NULL,
				blog_id int(11) NOT NULL,
		    	KEY post_id (post_id),
		    	KEY payer_id (payer_id),
	    		KEY payee_id (payee_id)
				) {$charset_collate};";
		dbDelta( $sql_main);
	}

	if( $wpdb->get_var("SHOW TABLES LIKE '". $wpdb->paymentsmeta ."'") != $wpdb->paymentsmeta ) {
		$sql_meta= "CREATE TABLE $wpdb->paymentsmeta (
			meta_id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
			invoice_id bigint(20) NOT NULL default '0',
			meta_key varchar(255) default NULL,
			meta_value longtext,
    		KEY invoice_id ( invoice_id)
			) {$charset_collate};";
		dbDelta( $sql_meta);
	}

	if( $wpdb->get_var("SHOW TABLES LIKE '". $wpdb->payments_log ."'") != $wpdb->payments_log ) {
		$sql_log = "CREATE TABLE $wpdb->payments_log (
			id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
			invoice_id int(11) NOT NULL default '0',
			action_type varchar(255) NOT NULL,
			value longtext NOT NULL,
			time_stamp timestamp NOT NULL,
    		KEY invoice_id ( invoice_id)
			) {$charset_collate};";
		dbDelta( $sql_log);
	}

	// Localization Labels
	if( false == get_option( 'pp_invoice_custom_label_tax' ) )
		add_option( 'pp_invoice_custom_label_tax', "Tax" );
	if( false == get_option( 'pp_invoice_force_https' ) )
		add_option( 'pp_invoice_force_https','false' );

	pp_invoice_add_email_template_content();
}
add_action( 'pp_activation', 'pp_payment_install' );


/** 
 * Certain administration pages in Prospress provide a hook for other components to add an "action" link. This function 
 * determines and then outputs an appropriate payment/invoice action link, which may be any of send/view invoice or 
 * make payment.
 * 
 * The function receives the existing array of actions from the hook and adds to it an array with the url for 
 * performing a feedback action and label for outputting as the link text. 
 * 
 * @see bid_table_actions hook
 * @see completed_post_actions hook
 * 
 * @param actions array existing actions for the hook
 * @param post_id int for identifying the post
 * @return array of actions for the hook, including the payment system's action
 */
function pp_add_payment_action( $actions, $post_id ) {
	global $user_ID, $blog_id, $wpdb;
	
	$post = get_post( $post_id );

	$is_winning_bidder = is_winning_bidder( $user_ID, $post_id );

	if ( $post->post_status != 'completed' || get_bid_count( $post_id ) == false || ( !$is_winning_bidder && $user_ID != $post->post_author ) ) 
		return $actions;

	$invoice_id = $wpdb->get_var( "SELECT id FROM $wpdb->payments WHERE post_id = $post_id" );
	$make_payment_url = add_query_arg( array( 'invoice_id' => $invoice_id ), 'admin.php?page=make_payment' );
	$invoice_url = add_query_arg( array( 'invoice_id' => $invoice_id ), 'admin.php?page=send_invoice' );
	$invoice_is_paid = pp_invoice_is_paid( $invoice_id );

	if ( $is_winning_bidder && !$invoice_is_paid ) {
		$actions[ 'make-payment' ] = array( 'label' => __( 'Pay Now', 'prospress' ), 
											'url' => $make_payment_url );
	} else if ( $user_ID == $post->post_author && !$invoice_is_paid ) {
		$actions[ 'send-invoice' ] = array( 'label' => __( 'Send Invoice', 'prospress' ),
											'url' => $invoice_url );
	} else {
		$actions[ 'view-invoice' ] = array( 'label' => __( 'View Invoice', 'prospress' ),
											'url' => $invoice_url );
	}

	return $actions;
}
add_filter( 'completed_post_actions', 'pp_add_payment_action', 10, 2 );
add_filter( 'bid_table_actions', 'pp_add_payment_action', 10, 2 );


/**
 * Generate an invoice for a post. Hooked to post completion.
 **/
function pp_generate_invoice( $post_id ) {
	global $wpdb, $market_systems;

	$market = $market_systems[ get_post_type( $post_id ) ];
	
	if( $market->get_bid_count( $post_id ) == 0 )
		return;

	$winning_bid = $market->get_winning_bid( $post_id );

	$payer_id 	= $winning_bid->post_author;
	$payee_id	= get_post( $post_id )->post_author;
	$amount		= $winning_bid->winning_bid_value;
	$status		= 'pending';

	$args = compact( 'post_id', 'payer_id', 'payee_id', 'amount', 'status', 'type' );
	do_action( 'generate_invoice', $args );
	
	return pp_invoice_create( $args );
}
add_action( 'post_completed', 'pp_generate_invoice' );

