<?php
/*
Plugin Name: Prospress
Plugin URI: http://prospress.org
Description: Publishing and trade - two prosperous human endeavours. WordPress advances the first, Prospress the second. This plugin transforms WordPress into your very own auction site.
Author: Brent Shepherd
Version: 0.2
Author URI: http://prospress.org/
*/

if ( !defined( 'PP_VERSION'))
	define( 'PP_VERSION', '0.2' );

/***
 * Ye be a brave soul who enters these waters. Feel free to delve into the dark depths of this code; but be warned: 
 * this code is released as real beta, not that Google style we-use-beta-for-awesome-finished-versions type of beta.
 * The code is messy, undocumentated and will change over the coming months.
 * 
 * Ultimately, there are too many features and not enough polish. That will change over time, but not expect no poetry here
 * until approximately September.
 * 
 */

/*
 * Prospress uses a component architecture. I copied the idea from BuddyPress as it seemed like a great way to help with testing 
 * individual components and also to help me get my little brain around the enormity of creating an online marketplace. There is 
 * a post system, feedback system and market/bid system. Each has it's own central file, which is called here to create Prospress.
 */

require_once( WP_PLUGIN_DIR . '/prospress/pp-core.php' );

require_once( WP_PLUGIN_DIR . '/prospress/pp-posts.php' );

require_once( WP_PLUGIN_DIR . '/prospress/pp-bids.php' );

require_once( WP_PLUGIN_DIR . '/prospress/pp-feedback.php' );


function pp_activate(){
	do_action( 'pp_activation' );
}
register_activation_hook( __FILE__, 'pp_deactivate' );

function pp_deactivate(){
	do_action( 'pp_deactivation' );
}
register_deactivation_hook( __FILE__, 'pp_deactivate' );

function pp_uninstall(){
	do_action( 'pp_uninstall' );
}
register_uninstall_hook( __FILE__, 'pp_uninstall' );
