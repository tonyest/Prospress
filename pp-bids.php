<?php
/**
 * @package Prospress
 * @author Brent Shepherd
 * @version 0.1
 */

/*
Plugin Name: Prospress Bids
Plugin URI: http://prospress.com
Description: Allow your traders to bid on listings in your Prospress marketplace.
Author: Brent Shepherd
Version: 0.1
Site Wide Only: true
Author URI: http://brentshepherd.com/

Copyright (C) 2010 Leonard's Ego.

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

if ( !defined( 'PP_BIDS_DB_VERSION'))
	define ( 'PP_BIDS_DB_VERSION', '0022' );
if ( !defined( 'PP_BIDS_DIR'))
	define( 'PP_BIDS_DIR', WP_PLUGIN_DIR . '/prospress/pp-bids' );
if ( !defined( 'PP_BIDS_URL'))
	define( 'PP_BIDS_URL', WP_PLUGIN_URL . '/prospress/pp-bids' );

/* Add Bids tables to the wpdb global var */
global $wpdb;

if ( !isset($wpdb->bids) || empty($wpdb->bids))
	$wpdb->bids = $wpdb->prefix . 'bids';
if ( !isset($wpdb->bidsmeta) || empty($wpdb->bidsmeta))
	$wpdb->bidsmeta = $wpdb->prefix . 'bidsmeta';

/* Include required files */
require_once ( PP_BIDS_DIR . '/pp-bids-functions.php' );
require_once ( PP_BIDS_DIR . '/pp-bids-templatetags.php' );

/* Hook for requiring custom bid system. */
//do_action( 'include_bid_systems' );

/**
 * This is where the bid/marketplace system is created. It's a standard class creation: require class file; 
 * create instance of class and store this instance in a global variable to be used elsewhere.
 *
 * However, to make the bid system extensible, filters are applied to the bid system file and bid system name.
 * 
 */

/* Require bid system. */
$bid_system_file = apply_filters( 'bid_system_file', PP_BIDS_DIR . '/PP_Auction_Bid_System.class.php' );
require_once ( $bid_system_file );

/* Determine which type of bid system to use. */
//global $bid_systems_available;
global $bid_system;

$bid_system_name = apply_filters( 'bid_system_name', 'PP_Auction_Bid_System' ); 
$bid_system = new $bid_system_name;

/**
 * 	Checks if the bids database tables are set up and options set, if not,call install function to set them up.
 * 
 * @uses get_site_option to check the current database version  (**WPMU_FUNCTION**)
 * @uses pp_bids_install to create the database tables if they are not up to date
 * @return false if logged in user is not the site admin
 **/
function pp_bids_check_installed() {
	global $wpdb;

	error_log('** pp_bids_check_installed called');

	if ( !current_user_can('edit_plugins') )
		return false;

	if( defined( 'VHOST' ) ) {
		error_log('** pp_bids_check_installed called, VHOST defined');
		// Need to check if db tables exist before creating them
		if ( !get_site_option('pp_bids_db_version') || get_site_option('pp_bids_db_version') < PP_BIDS_DB_VERSION ){
			error_log('** pp_bids_install_site_wide called, VHOST defined');
			pp_bids_install_site_wide();
		}
	} else { //WordPress installation
		error_log('** pp_bids_check_installed called, VHOST NOT defined');
		// Need to check if db tables exist before creating them
		if ( !get_option('pp_bids_db_version') || get_option('pp_bids_db_version') < PP_BIDS_DB_VERSION ) {
			error_log('** pp_bids_install called, VHOST NOT defined');
			pp_bids_install();
		}
	}
}
//register_activation_hook( __FILE__, 'pp_bids_check_installed' ); //no worky
register_activation_hook( __FILE__, 'pp_bids_check_installed' );
//add_action( 'admin_menu', 'pp_bids_check_installed', 1 );

function pp_bids_deactivate() {
	global $wpdb;

	if ( !current_user_can('edit_plugins') || !function_exists( 'delete_site_option') )
		return false;

	error_log('pp_bids_deactivate called');

	delete_site_option( 'pp_bids_db_version' );
}
register_deactivation_hook( __FILE__, 'pp_bids_deactivate' );


/**
 * Set up the bids plugin a Wordpress install. 
 *
 * Creates bid and bidmeta tables and adds bid DB version number to options DB.
 * 
 * @uses dbDelta($sql) to execute the sql query for creating tables
 * @uses update_option(name, value) to set the database version
 **/
function pp_bids_install($blog_id = 0) {
	global $wpdb;

	if ( !empty($wpdb->charset) )
		$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";

	$bids_table_name = ($blog_id == 0) ? $wpdb->prefix . 'bids' : $wpdb->base_prefix . $blog_id . '_bids';
	$bidsmeta_table_name = ($blog_id == 0) ? $wpdb->prefix . 'bidsmeta' : $wpdb->base_prefix . $blog_id . '_bidsmeta';

	$sql[] = "CREATE TABLE {$bids_table_name} (
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

	$sql[] = "CREATE TABLE {$bidsmeta_table_name} (
		  		meta_id bigint(20) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
		  		bid_id bigint(20) unsigned NOT NULL,
		  		meta_key varchar(255) NOT NULL,
		  		meta_value longtext NOT NULL,
			    KEY bid_id (bid_id),
			    KEY meta_key (meta_key)
			   ) {$charset_collate};";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta($sql);

	update_option( 'pp_bids_db_version', PP_BIDS_DB_VERSION );
}
add_action('wpmu_new_blog','pp_bids_install', 10, 1);

/**
 * Set up the bids plugin for WPMU. Creates tables and add site options to meta DB.
 * 
 * @uses dbDelta($sql) to execute the sql query for creating tables
 * @uses add_site_option(name, value) to set the database version (**WPMU_FUNCTION**)
 **/
function pp_bids_install_site_wide() {
	global $wpdb, $charset_collate;

	if ( !empty($wpdb->charset) )
		$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";

	//$markets = get_blog_list(0, 'all'); 	//only works for safe and public markets
	//$markets = get_blog_count(); 			//only gives number of markets, need to get ID afterwards
	//error_log("there are $markets markets");
	$markets = $wpdb->get_results( $wpdb->prepare("SELECT blog_id FROM $wpdb->blogs WHERE site_id = %d AND archived = '0' AND spam = '0' AND deleted = '0' ORDER BY registered ASC", $wpdb->siteid), ARRAY_A );

	foreach($markets as $market){
		if( $market['blog_id'] == 1 )
			$tablename = $wpdb->base_prefix . 'bids';
		else
			$tablename = $wpdb->base_prefix . $market['blog_id'] . '_bids';
		$sql[] = "CREATE TABLE {$tablename} (
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

		$tablename .= 'meta';
		$sql[] = "CREATE TABLE {$tablename} (
		  		meta_id bigint(20) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
		  		bid_id bigint(20) unsigned NOT NULL,
		  		meta_key varchar(255) NOT NULL,
		  		meta_value longtext NOT NULL,
			    KEY bid_id (bid_id),
			    KEY meta_key (meta_key)
			   ) {$charset_collate};";
	}

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta($sql);

	update_site_option( 'pp_bids_db_version', PP_BIDS_DB_VERSION );
}


/** 
 * Installs bids plugin when a new market is created in WPMU.
 *
 * @uses dbDelta($sql) to execute the sql query for creating tables
 * @uses update_option(name, value) to set the database version
 */
function pp_bids_install_new_market($blog_id){
	global $wpdb, $charset_collate;
	
	error_log("****************** in pp_bids_install_new_market ******************");
	
	if ( !empty($wpdb->charset) )
		$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
	
	$sql[] = "CREATE TABLE {$wpdb->base_prefix}{$blog_id}_bids (
	  		bid_id bigint(20) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
	  		post_id bigint(20) unsigned NOT NULL,
	  		bidder_id bigint(20) unsigned NOT NULL,
	  		bid_value float(16,2) NOT NULL,
			bid_status varchar(20) NOT NULL DEFAULT 'pending',
	  		bid_date datetime NOT NULL,
	  		bid_date_gmt datetime NOT NULL,
		    KEY post_id (post_id),
		    KEY bidder_id (bidder_id),
		    KEY bid_date_gmt (bid_date_gmt)
		   ) {$charset_collate};";

	$sql[] = "CREATE TABLE {$wpdb->base_prefix}{$blog_id}_bidsmeta (
	  		meta_id bigint(20) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
	  		bid_id bigint(20) unsigned NOT NULL,
	  		meta_key varchar(255) NOT NULL,
	  		meta_value longtext NOT NULL,
		    KEY bid_id (bid_id),
		    KEY meta_key (meta_key)
		   ) {$charset_collate};";

	error_log('pp_bids_install_new_market $sql[] = ' . print_r($sql, true));

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta($sql);
}
//add_action('wpmu_new_blog','pp_bids_install_new_market', 10, 1);

/**
 * 	Adds bid pages to admin menu
 * 
 * @uses add_object_page to add "Bids" top level menu
 * @uses add_menu_page if add object page is not available to add "Bids" menu
 * @uses add_submenu_page to add "Bids" and "Bid History" submenus to "Bids"
 * @uses add_options_page to add administration pages for bid settings
 * @return false if logged in user is not the site admin
 **/
function pp_bids_add_admin_pages() {

	//add_options_page( $page_title, $menu_title, $access_level, $file, $function = '' )
	//add_submenu_page( $parent, $page_title, $menu_title, $access_level, $file, $function = '' );

	$base_page = "bids";

	$bids_title = apply_filters( 'bids_admin_title', __('Bids') );

	if ( function_exists( 'add_object_page' ) ) {
		add_object_page( $bids_title, $bids_title, 1, $base_page, '', WP_PLUGIN_URL . '/prospress/images/menu.png' );
	} elseif ( function_exists( 'add_menu_page' ) ) {
		add_menu_page( $bids_title, $bids_title, 1, $base_page, '', WP_PLUGIN_URL . '/prospress/images/menu.png' );
	}

	$winning_bids_title = apply_filters( '', __('Winning Bids') );
	$bid_history_title = apply_filters( '', __('Bid History') );

    // Add submenu items to the bids top-level menu
	if (function_exists('add_submenu_page')){
	    add_submenu_page($base_page, $winning_bids_title, $winning_bids_title, 1, $base_page, 'pp_bids_winning');
	    add_submenu_page($base_page, $bid_history_title, $bid_history_title, 1, 'bid-history', 'pp_bids_history_admin');
	}

	/*
	if ( function_exists( 'add_settings_section' ) ){
		add_settings_section( 'bid_system', 'Bid System', 'bid_system_settings_section', 'general' );
	} else {
		$bid_settings_page = add_submenu_page( 'options-general.php', 'Bid System', 'Bid System', 58, 'bid_system', 'bid_system_settings_section' );
	}
	*/
}
add_action( 'admin_menu', 'pp_bids_add_admin_pages' );

// displays the page content for the custom Bids Toplevel sub menu
function pp_bids_winning() {
	global $bid_system;

	$bid_system->winning_history();
}

//Function to print the feedback history for a user
function pp_bids_history_admin() {
	global $bid_system;

	$bid_system->admin_history();
}

//Add bid history column headings to the built in print_column_headers function
function pp_bid_history_columns_admin(){
 	return array(
		'cb' => '<input type="checkbox" />',
		'bid_id' => __('Bid ID'),
		'post_id' => __('Post'),
		'bid_value' => __('Amount'),
		'bid_date' => __('Date'),
	);
}
add_filter('manage_bid_history_columns','pp_bid_history_columns_admin');


// Add jQuery and other required scripts to post pages.
function pp_bids_add_scripts() {
	//wp_enqueue_script('jquery');
	//wp_enqueue_script('jquery-ui-core');
	wp_enqueue_style( 'bids', PP_BIDS_URL . '/admin.css' );
}
add_action( 'admin_menu', 'pp_bids_add_scripts' );


// Add jQuery and other required scripts to post pages.
function pp_update_bid_meta() {
	global $wpdb;
	
	error_log( 'pp_update_bid_meta called' );
}

// Administration functions for choosing default bid system
function add_bid_system_admin(){
}
// Adds bid system admin menu option
//add_action( 'admin_menu', 'add_bid_system_admin' );

// A grossly inefficient function that cycles through all registered classes to determine if they are
// a bid system (subclass of the bid system base class).
// Iterating over all classes means a custom bid class need only make sure it is registered when this
// function is called. A much more efficient alternative is to have custom bid classes populate their
// details the bid_systems_available global. This adds a little extra complexity for the plugin developer. 
// So has not been used.
// This function is used to populate the bid_systems_available global variable. 
function get_bid_systems() {
	global $bid_systems_available;
	
	$all_classes = get_declared_classes();
	foreach ( $all_classes as $a_class ){
		if ( is_subclass_of( $a_class, 'PP_Bid_System' ) ) {
			$bid_sys = new $a_class;
			$bid_sys_available[ $a_class ] = array( 'name' => $bid_sys->name, 
														'description' => $bid_sys->description, 
														'file' => $bid_sys->file );
		}
	}
	//$bid_sys_available[ 'reverse_auction' ] = array( 'name' => 'reverse auction', 'description' => 'Reverse Auctions are great for making the sellers do the work.', 'file' => __FILE__ );
	return $bid_sys_available;
}


// This is called when switch to blog and restore blog functions are called. It makes the correct bid table names available for the given blog.
function set_bid_table() {
	global $wpdb;

	$wpdb->bids = $wpdb->prefix . 'bids';
	$wpdb->bidsmeta = $wpdb->prefix . 'bidsmeta';
}
add_action('switch_blog', 'set_bid_table' );



// Displays the fields for handling currency default options
/*
function bid_system_settings_section() {
	global $bid_systems_available;

	$chosen_bid_sys = get_option( 'bid_system' );
	?>
	<p><?php _e( 'Please choose a marketplace type.' ); ?></p>
	<table class='form-table'>
		<?php foreach ( $bid_systems_available as $bid_sys_class => $bid_sys ) { ?>
			<tr>
				<td>
					<input type='radio' value='<?php echo $bid_sys_class; ?>' name='bid_system' id='<?php echo $bid_sys_class; ?>' 
						<?php echo ( $chosen_bid_sys == $bid_sys_class ) ? "checked='checked'" : '' ; ?>
						<?php error_log( '$chosen_bid_sys = ' . $chosen_bid_sys ); ?> 
						<?php error_log( '$bid_sys_class = ' . $bid_sys_class ); ?> 
					/> 
					<label for='<?php echo $bid_sys_class; ?>'>
						<?php echo ucwords( $bid_sys[ 'name' ] ); ?><br/>
					</label>
					<?php echo ucfirst( $bid_sys['description'] ); ?>
				</td>
			</tr>
		<?php } ?>
	</table>
<?php
}
*/

/*
function bid_system_admin_option( $whitelist_options ) {
	$whitelist_options['general'][] = 'bid_system';
	return $whitelist_options;
}
// Adds bid system admin menu option to options whitelist for saving on options page submission
add_filter( 'whitelist_options', 'bid_system_admin_option' );
*/

?>