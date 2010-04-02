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
Site Wide Only: true

Copyright (C) 2009 Prospress Pty. Ltd.

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