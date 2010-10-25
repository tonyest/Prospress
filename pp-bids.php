<?php
/**
 * Prospress Bids
 *
 * Allow your traders to bid on listings in your Prospress marketplace.
 *
 * @package Prospress
 * @author Brent Shepherd
 * @version 0.1
 */

if ( !defined( 'PP_BIDS_DB_VERSION' ) )
	define ( 'PP_BIDS_DB_VERSION', '0023' );
if ( !defined( 'PP_BIDS_DIR' ) )
	define( 'PP_BIDS_DIR', PP_PLUGIN_DIR . '/pp-bids' );
if ( !defined( 'PP_BIDS_URL' ) )
	define( 'PP_BIDS_URL', PP_PLUGIN_URL . '/pp-bids' );


require_once( PP_BIDS_DIR . '/pp-bids-templatetags.php' );

include_once( PP_BIDS_DIR . '/bids-filter.php' );

require_once( PP_BIDS_DIR . '/pp-auction-system.class.php' );

/**
 * @global PP_Auction_Bid_System $market_system Stores the market system object, defaults to PP_Auction_Bid_System.
 */
global $market_system, $market_systems;

$market_system = new PP_Auction_Bid_System(); // for legacy
$market_systems[ $market_system->name() ] = $market_system;

$market_systems = apply_filters( 'add_market_system', $market_systems );


/**
 * To save updating/installing the bids tables when they already exist and are up-to-date, check 
 * the current bids database version both exists and is not of a prior version.
 * 
 * @uses pp_bids_install to create the database tables if they are not up to date
 **/
function pp_bids_maybe_install() {
	global $wpdb;

	if ( !current_user_can( 'edit_plugins' ) )
		return false;

	if ( !get_option( 'pp_bids_db_version' ) || get_option( 'pp_bids_db_version' ) < PP_BIDS_DB_VERSION )
		pp_bids_install();
}
add_action( 'pp_activation', 'pp_bids_maybe_install' );


/**
 * Set ups the bid system by creating tables, adding options and setting sensible defaults.
 * 
 * @uses dbDelta($sql) to execute the sql query for creating tables
 **/
function pp_bids_install($blog_id = 0) {
	global $wpdb;

	if ( !empty($wpdb->charset) )
		$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";

	update_option( 'pp_bids_db_version', PP_BIDS_DB_VERSION );
}


/**
 * Clean up if the plugin is deleted by removing bids related options, posts and database tables. 
 * 
 **/
function pp_bids_uninstall() {
	global $wpdb;

	if ( !current_user_can( 'edit_plugins' ) || !function_exists( 'delete_site_option' ) )
		return false;

	delete_site_option( 'pp_bids_db_version' );

	$wpdb->query( "DROP TABLE IF EXISTS $wpdb->bids" );
	$wpdb->query( "DROP TABLE IF EXISTS $wpdb->bidsmeta" );

}
add_action( 'pp_uninstall', 'pp_bids_uninstall' );

/**
 * Function to test if a given user is classified as a winning bidder for a given post. 
 * 
 * As some market systems may have multiple winners, it is important to use this function 
 * instead of testing a user id directly against a user id provided with get_winning_bid.
 * 
 * Optionally takes $user_id and $post_id, if not specified, using the ID of the currently
 * logged in user and post in the loop.
 */
function is_winning_bidder( $user_id = '', $post_id = '' ){
	global $user_ID, $post, $market_systems;

	if ( empty( $post_id ) )
		$post_id = $post->ID;

	if ( $user_id == '' )
		$user_id = $user_ID;
	
	$market = $market_systems[ get_post_type( $post_id ) ];

	return ( $user_id == $market->get_winning_bid( $post_id )->post_author ) ? true : false;
	
}


/**
 * Get's all the details of the winning bid on a post, optionally specified with $post_id.
 *
 * If no post id is specified, the global $post var is used. 
 */
function get_winning_bid( $post_id = '' ) {
	global $post, $market_systems;

	if ( empty( $post_id ) )
		$post_id = $post->ID;

	$market = $market_systems[ get_post_type( $post_id ) ];

	return $market->get_winning_bid( $post_id );
}

/**
 * Provides the user id of the winning bidder on a post.  
 */
function get_winning_bidder( $post_id = '' ) {
	return get_winning_bid( $post_id )->post_author;
}

/**
 * Gets the number of bids for a post, optionally specified with $post_id.
 */
function get_bid_count( $post_id = '' ) {
	global $post, $market_systems;

	if ( empty( $post_id ) )
		$post_id = $post->ID;

	$market = $market_systems[ get_post_type( $post_id ) ];

	return $market->get_bid_count( $post_id );
}

/**
 * Get's the role of a given user for a post.
 *
 * A wrapper function for the PP_Market_System get_users_role function
 *
 * @param $post int|array either the id of a post or a post object
 */
function pp_get_users_role( $post, $user_id = NULL ) {
	global $market_systems;

	if ( is_numeric( $post ) )
		$post = get_post( $post );

	return $market_systems[ get_post_type( $post ) ]->get_users_role( $post->ID, $user_id );
}
