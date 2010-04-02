<?php
/**
 * Handles Bid Submission to Prospress and prevents duplicate bid posting.
 *
 * @package WordPress
 */

//if ( 'POST' != $_SERVER['REQUEST_METHOD'] ) {
//	header('Allow: POST');
//	header('HTTP/1.1 405 Method Not Allowed');
//	header('Content-Type: text/plain');
//	exit;
//}

/** Set up the WP Environment. */
// @TODO fix this hack (figure out a nicer way to load WP environment)
require_once( '../../../../wp-load.php' ); // HAAACK!

if ( !is_user_logged_in() ){ //if bidder is not logged in
	do_action('bidder_not_logged_in');

	$redirect = is_ssl() ? "https://" : "http://";
	$redirect .= $_SERVER['HTTP_HOST'] . esc_url( $_SERVER['PHP_SELF'] );
	$redirect = add_query_arg( urlencode_deep( $_GET ), $redirect);
	$redirect = add_query_arg('bid_redirect', wp_get_referer(), $redirect);
	$redirect = wp_login_url( $redirect );
	$redirect = apply_filters( 'bid_login_redirect', $redirect );

	wp_safe_redirect( $redirect );
	exit;
}

global $bid_system;

$bid_status = $bid_system->form_submission();

// Redirect user back to post
if ( !empty( $_GET[ 'bid_redirect' ] ) )
	$location = $_GET[ 'bid_redirect' ];
elseif ( wp_get_referer() ) 
	$location = wp_get_referer();
else
	$location = get_permalink( $_GET[ 'post_ID' ] );

if ( isset( $bid_status ) )
	$location = add_query_arg( 'status', $bid_status, $location );

error_log("location = $location");
$location = apply_filters( 'bid_made_redirect', $location, $bid );

wp_safe_redirect( $location );
