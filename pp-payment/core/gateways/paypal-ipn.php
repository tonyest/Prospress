<?php
/**
 *
 * 
 * @package Prospress
 * @version 1.1
 **/

/**
 * I sit and lookout at PayPal notifications. If the page request is from PayPal
 * return the parameters sent with the request to confirm the transaction as required by
 * PayPal's IPN.  
 *
 * Code based on PayPal example here: https://cms.paypal.com/cms_content/US/en_US/files/developer/IPN_PHP_41.txt
 **/
function pp_paypal_ipn_listener(){
	global $wpdb;

	error_log( 'in paypal ipn listener' );
	//error_log( 'POST = ' . print_r( $_POST, true ) );
	//error_log( 'GET = ' . print_r( $_GET, true ) );

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

	// Determine if sandbox mode should be used
	$user_id = get_post( $_POST[ 'item_number' ] )->post_author;
	$pp_invoice_settings = get_usermeta( $user_id, 'pp_invoice_settings' );
	$paypal_sandbox = $pp_invoice_settings[ 'paypal_sandbox' ];

	$fp_url	= 'ssl://www.';
	$fp_url	.= ( $paypal_sandbox == 'true' ) ? "sandbox." : '';
	$fp_url	.= 'paypal.com';
	error_log( 'IN PPIPN = fp_url = ' . print_r( $fp_url, true ) );
	$fp 	= fsockopen( $fp_url, 443, $errno, $errstr, 30 );
	error_log( 'After fsockopen, $fp = ' . print_r( $fp, true ) );
	error_log( 'After fsockopen, $errno = ' . print_r( $errno, true ) );
	error_log( 'After fsockopen, $errstr = ' . print_r( $errstr, true ) );

	if ( !$fp ) {
		// HTTP ERROR
		error_log('There has been a HTTP error with PayPal IPN: $req = ' . print_r( $req, true ) );
		error_log('There has been a HTTP error with PayPal IPN: $_POST = ' . print_r( $_POST, true ) );
	} else {
		error_log( 'IN PPIPN = header' . print_r( $header, true ) );
		error_log( 'IN PPIPN = req' . print_r( $req, true ) );
		fputs( $fp, $header . $req );
		error_log( 'fputs complete in paypal IPN listener' );
		while( !feof( $fp ) ) {
			error_log( 'in while of paypal IPN listener, $fp = ' . print_r( $fp, true ) );
			$res = fgets( $fp, 1024 );
			error_log( 'in while of paypal IPN listener, $res = ' . print_r( $res, true ) );
			if ( strcmp( $res, "VERIFIED" ) == 0 ) {
				error_log( 'VERIFIED  response in paypal ipn listener' );
				do_action( 'paypal_ipn_verified', $_POST );
				add_post_meta( $_POST['item_number'], 'paypal_ipn_valid', $_POST );
			} else if ( strcmp ( $res, "INVALID" ) == 0 ) {
				error_log( 'INVALID  response in paypal ipn listener' );
				// log for manual investigation
				add_post_meta( $_POST['item_number'], 'paypal_ipn_invalid', $_POST );
				do_action( 'paypal_ipn_invalid', $_POST );
			}
		}
		fclose( $fp );
	}
}