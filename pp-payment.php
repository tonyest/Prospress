<?php
/**
 * Prospress Payment
 * 
 * Money - the great enabler of trade. This component provides a system for traders in a Prospress market place 
 * to exchange money in return to posted items/services.
 * 
 * @package Prospress
 * @author Brent Shepherd
 * @version 0.1
 */

if ( !defined( 'PP_PAYMENTS_DB_VERSION'))
	define ( 'PP_PAYMENTS_DB_VERSION', '0003' );

if( !defined( 'PP_PAYMENT_DIR' ) )
	define( 'PP_PAYMENT_DIR', PP_PLUGIN_DIR . '/pp-payment' );
if( !defined( 'PP_PAYMENT_URL' ) )
	define( 'PP_PAYMENT_URL', PP_PLUGIN_URL . '/pp-payment' );
if( !defined( 'PP_INVOICE_DIR' ) )
	define( 'PP_INVOICE_DIR', PP_PAYMENT_DIR . '/wp-invoice-m2m' );

global $wpdb;

if ( !isset($wpdb->payments) || empty($wpdb->payments))
	$wpdb->payments = $wpdb->prefix . 'payments';
if ( !isset($wpdb->paymentsmeta) || empty($wpdb->paymentsmeta))
	$wpdb->paymentsmeta = $wpdb->prefix . 'paymentsmeta';
if ( !isset($wpdb->payments_log) || empty($wpdb->payments_log))
	$wpdb->payments_log = $wpdb->prefix . 'payments_log';

/**
 * The engine behind the payment system - TwinCitiesTech's WP Invoice modified for marketplace payments. 
 * All the payment system action happens in there. 
 */
require_once( PP_INVOICE_DIR . '/WP-Invoice.php' );

add_action( 'pp_activation', array( $WP_Invoice, 'install' ) );


/** 
 * Certain administration pages in Prospress provide a hook for other components to add an "action" link. This function 
 * determines and then outputs an appropriate payment/invoice action link, which may be any of send/view invoice or 
 * make payment.
 * 
 * The function receives the existing array of actions from the hook and adds to it an array with the url for 
 * performing a feedback action and label for outputting as the link text. 
 * 
 * @see completed_post_actions hook
 * @see winning_bid_actions hook
 * 
 * @param actions array existing actions for the hook
 * @param post_id int for identifying the post
 * @return array of actions for the hook, including the payment system's action
 */
function pp_add_payment_action( $actions, $post_id ) {
	global $user_ID, $market_system, $blog_id, $wpdb;
 
	$post = get_post( $post_id );

	$is_winning_bidder = $market_system->is_winning_bidder( $user_ID, $post_id );

	if ( $post->post_status != 'completed' || $market_system->get_bid_count( $post_id ) == false || ( !$is_winning_bidder && $user_ID != $post->post_author ) ) 
		return $actions;

	$invoice_id = $wpdb->get_var( "SELECT id FROM $wpdb->payments WHERE post_id = $post_id" );
	$make_payment_url = add_query_arg( array( 'invoice_id' => $invoice_id ), 'admin.php?page=make_payment' );
	$nvoice_url = add_query_arg( array( 'invoice_id' => $invoice_id ), 'admin.php?page=send_invoice' );

	if ( $is_winning_bidder && !$invoice->is_paid ) {
		$actions[ 'make-payment' ] = array( 'label' => __( 'Make Payment', 'prospress' ), 
											'url' => $make_payment_url );
	} else if ( $user_ID == $post->post_author && !$invoice->is_paid ) {
		$actions[ 'send-invoice' ] = array( 'label' => __( 'Send Invoice', 'prospress' ),
											'url' => $nvoice_url );
	} else {
		$actions[ 'view-invoice' ] = array( 'label' => __( 'View Payment Details', 'prospress' ),
											'url' => $nvoice_url );
	}

	return $actions;
}
add_filter( 'completed_post_actions', 'pp_add_payment_action', 10, 2 );
add_filter( 'winning_bid_actions', 'pp_add_payment_action', 10, 2 );
