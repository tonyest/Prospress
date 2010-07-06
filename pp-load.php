<?php
/*
Plugin Name: Prospress
Plugin URI: http://prospress.org
Description: Publishing and trade - two prosperous human endeavours. WordPress advances the first, Prospress the second. This plugin slips your very own auction site along side your WordPress blog.
Author: Brent Shepherd
Version: 0.1
Author URI: http://prospress.org/
*/

if ( !defined( 'PP_VERSION' ) )
	define( 'PP_VERSION', '0.1' );

/***
 * Ye be a brave soul who enters these waters. Feel free to delve into the dark depths of this code; but be warned: 
 * this code is released as a real beta, not that Google style we-use-beta-for-awesome-finished-versions type of beta.
 * The code is messy, undocumentated and will change over the coming months. For example, feedback items may become a
 * custom post type instead of having their own table. 
 * 
 * I hacked out too many features without enough polish. That will change over time, but don't expect poetry here
 * until approximately December.
 */

/*
 * Prospress uses a component architecture. I copied the idea from BuddyPress as it seemed like a great way to help with testing 
 * individual components and also to help me get my little brain around the enormity of creating an online marketplace. There is 
 * a post system, feedback system and market/bid system. Each has it's own central file, which is called here to create Prospress.
 */

if( !defined( 'PP_PLUGIN_DIR' ) )
	define( 'PP_PLUGIN_DIR', WP_PLUGIN_DIR . '/prospress' );
if( !defined( 'PP_PLUGIN_URL' ) )
	define( 'PP_PLUGIN_URL', WP_PLUGIN_URL . '/prospress' );

load_plugin_textdomain( 'prospress', PP_PLUGIN_DIR . '/languages', dirname( plugin_basename(__FILE__) ) . '/languages' );


require_once( PP_PLUGIN_DIR . '/pp-core.php' );

require_once( PP_PLUGIN_DIR . '/pp-bids.php' );

require_once( PP_PLUGIN_DIR . '/pp-posts.php' );

require_once( PP_PLUGIN_DIR . '/pp-feedback.php' );

require_once( PP_PLUGIN_DIR . '/pp-payment.php' );


function pp_activate(){
	do_action( 'pp_activation' );
}
register_activation_hook( __FILE__, 'pp_activate' );

function pp_deactivate(){
	do_action( 'pp_deactivation' );
}
register_deactivation_hook( __FILE__, 'pp_deactivate' );

function pp_uninstall(){
	do_action( 'pp_uninstall' );
}
register_uninstall_hook( __FILE__, 'pp_uninstall' );

