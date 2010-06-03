<?php
/**
 * Prospress Payment
 * 
 * Money - the great enabler of trade. This component provides a system for traders in a Prospress market place 
 * to exchange money.
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

//Payment tables
global $wpdb;
if ( !isset($wpdb->payments) || empty($wpdb->payments))
	$wpdb->payments = $wpdb->prefix . 'payments';
if ( !isset($wpdb->paymentsmeta) || empty($wpdb->paymentsmeta))
	$wpdb->paymentsmeta = $wpdb->prefix . 'paymentsmeta';
if ( !isset($wpdb->payments_log) || empty($wpdb->payments_log))
	$wpdb->payments_log = $wpdb->prefix . 'payments_log';

// The engine behind the payment system - TwinCitiesTech's WP Invoice modified for marketplace payments
require_once( PP_INVOICE_DIR . '/WP-Invoice.php' );

//register_activation_hook(__FILE__, array( $WP_Invoice, 'install' ) );
add_action( 'pp_activation', array( $WP_Invoice, 'install' ) );


/**
 * Adds the "Make Payment" & "Send Invoice" actions to ended posts. 
 * 
 **/
function pp_add_payment_action( $actions, $post_id ) {
	global $user_ID, $market_system, $blog_id, $wpdb;
 
	$post = get_post( $post_id );

	$is_winning_bidder = $market_system->is_winning_bidder( $user_ID, $post_id );

	if ( $post->post_status != 'completed' || $market_system->get_bid_count( $post_id ) == false || ( !$is_winning_bidder && $user_ID != $post->post_author ) ) 
		return $actions;

	$invoice_id = $wpdb->get_var( "SELECT id FROM $wpdb->payments WHERE post_id = $post_id" );
	$invoice_info = new WP_Invoice_GetInfo($invoice_id);
	error_log( '*** WP_Invoice_GetInfo = ' . print_r( $invoice_info, true ) );
	error_log( '************************************************************' );
	error_log( '************************************************************' );
	error_log( '************************************************************' );
	error_log( '************************************************************' );
	error_log( '************************************************************' );
	$invoice_class = new wp_invoice_get($invoice_id);
	error_log( '*** wp_invoice_get = ' . print_r( $invoice_class, true ) );

	$errors = $invoice_class->error;
	$invoice = $invoice_class->data;

	$make_payment_url = 'admin.php?page=make_payment';
	$send_invoice_url = 'admin.php?page=send_invoice';

	if ( $is_winning_bidder && !$invoice->is_paid ) { // Make payment on post if payment isn't already made
		$actions[ 'make-payment' ] = array( 'label' => __( 'Make Payment', 'prospress' ), 
											'url' => add_query_arg( array( 'invoice_id' => $invoice_id ), $make_payment_url ) );
	} else if ( $user_ID == $post->post_author && !$invoice->is_paid ) { // Send Invoice if invoice hasn't been sent & payment hasn't been made
		$actions[ 'send-invoice' ] = array('label' => __( 'Send Invoice', 'prospress' ),
											'url' => add_query_arg( array( 'invoice_id' => $invoice_id ), $send_invoice_url ) );
	} else {
		$actions[ 'view-invoice' ] = array('label' => __( 'View Payment Details', 'prospress' ),
											'url' => add_query_arg( array( 'invoice_id' => $invoice_id ), $send_invoice_url ) );
	}

	return $actions;
}
add_filter( 'completed_post_actions', 'pp_add_payment_action', 10, 2 );
add_filter( 'winning_bid_actions', 'pp_add_payment_action', 10, 2 );
