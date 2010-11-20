<?php
/**
 *
 * https://cms.paypal.com/cms_content/US/en_US/files/developer/IPN_PHP_41.txt
 * @package Prospress
 * @version 1.1
 **/


function pp_paypal_ipn_listener(){
	global $wpdb;
	
	//error_log( 'in paypal ipn listener' );
	//error_log( 'POST = ' . print_r( $_POST, true ) );
	
	if( !isset( $_GET[ 'return_info' ] ) || !$_GET[ 'return_info' ] == 'notify' )
		return;

	exit();

	// read the post from PayPal system and add 'cmd'
	$req = 'cmd=_notify-validate';

	foreach ( $_POST as $key => $value ) {
		$value = urlencode( stripslashes( $value ) );
		$req .= "&$key=$value";
	}

	// post back to PayPal system to validate
	$header .= "POST /cgi-bin/webscr HTTP/1.0\r\n";
	$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
	$header .= "Content-Length: " . strlen($req) . "\r\n\r\n";

	//$user_id = // get user ID of author so can 
	$paypal_sandbox = get_usermeta( $user_id, 'pp_invoice_settings' );
	$paypal_sandbox = $paypal_sandbox[ 'paypal_sandbox' ];

	$fp_url	= 'ssl://www.' . ( $paypal_sandbox == 'true' ) ? "sandbox." : '';
	$fp_url	.= 'paypal.com';
	$fp 	= fsockopen( $fp_url, 443, $errno, $errstr, 30 );

	// assign posted variables to local variables
	$item_name 		= $_POST['item_name'];
	$item_number 	= $_POST['item_number'];
	$payment_status = $_POST['payment_status'];
	$payment_amount = $_POST['mc_gross'];
	$payment_currency = $_POST['mc_currency'];
	$txn_id 		= $_POST['txn_id'];
	$receiver_email = $_POST['receiver_email'];
	$payer_email 	= $_POST['payer_email'];

	if (!$fp) {
	// HTTP ERROR
	} else {
		fputs ($fp, $header . $req);
		while (!feof($fp)) {
			$res = fgets ($fp, 1024);
			if (strcmp ($res, "VERIFIED") == 0) {
			// check the payment_status is Completed
			// check that txn_id has not been previously processed
			// check that receiver_email is your Primary PayPal email
			// check that payment_amount/payment_currency are correct
			// process payment
			}
			else if (strcmp ($res, "INVALID") == 0) {
			// log for manual investigation
			}
		}
		fclose ($fp);
	}
}
add_action( 'init', 'pp_paypal_ipn_listener' );
