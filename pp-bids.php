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

if ( !defined( 'PP_BIDS_DB_VERSION' ))
	define ( 'PP_BIDS_DB_VERSION', '0022' );
if ( !defined( 'PP_BIDS_DIR' ))
	define( 'PP_BIDS_DIR', PP_PLUGIN_DIR . '/pp-bids' );
if ( !defined( 'PP_BIDS_URL' ))
	define( 'PP_BIDS_URL', PP_PLUGIN_URL . '/pp-bids' );


global $wpdb;

if ( !isset($wpdb->bids) || empty($wpdb->bids))
	$wpdb->bids = $wpdb->prefix . 'bids';
if ( !isset($wpdb->bidsmeta) || empty($wpdb->bidsmeta))
	$wpdb->bidsmeta = $wpdb->prefix . 'bidsmeta';


require_once( PP_BIDS_DIR . '/pp-bids-templatetags.php' );

include_once( PP_BIDS_DIR . '/bids-filter.php' );

require_once( PP_BIDS_DIR . '/pp-auction-system.class.php' );

include_once( PP_BIDS_DIR . '/auction-system-tests.php' );

/**
 * @global PP_Auction_Bid_System $market_system Stores the market system object, defaults to PP_Auction_Bid_System.
 */
global $market_system;

$market_system = new PP_Auction_Bid_System();


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

	pp_set_bid_tables();

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
		dbDelta( $sql );

	update_option( 'pp_bids_db_version', PP_BIDS_DB_VERSION );
}


/**
 * For multi-site installations, a bids table exists for each site. This function sets the bids 
 * and bidsmeta table names in the wpdb global so these are correct on multi-site installations. 
 **/
function pp_set_bid_tables() {
	global $wpdb;

	$wpdb->bids = $wpdb->prefix . 'bids';
	$wpdb->bidsmeta = $wpdb->prefix . 'bidsmeta';
}
add_action( 'switch_blog', 'pp_set_bid_tables' );


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

