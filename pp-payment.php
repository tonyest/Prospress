<?php
/**
 * @package Prospress
 * @author Brent Shepherd
 * @version 0.1
 */

/*
Plugin Name: Prospress Payment
Plugin URI: http://prospress.com
Description: Money - the great enabler of trade. This plugin extends WordPress to provide a payment system for Prospress posts.
Author: Brent Shepherd
Version: 0.1
Author URI: http://brentshepherd.com/
*/
/**
 * Prints the template containing additional meta fields
 *
 */
function pp_post_payment_options(){
	global $post_ID;
	
	if($post_ID > 0){//If post is not a new post
		//Get Options from db and pass them as var's to view template
		$pp_payment_methods = get_post_meta($post_ID, 'pp_payment_methods', true);
		if(!is_array($pp_payment_methods)) //Make sure payment methods is an array and not a null value or string
			parse_str($pp_payment_methods, $pp_payment_methods);
	} else {
		$pp_payment_methods = array();
	}
	include(PP_POST_PAYMENT_OPTIONS);
}


?>