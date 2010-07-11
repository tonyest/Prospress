<?php

/**
 * Returns the permalink for the Prospress index page. 
 * 
 * @param string $echo Optional, default 'echo'. If set to "echo" the function echo's the permalink, else, returns the permalink as a string. 
 * @return returns false if no index page set, true if echod the permalink or a string representing the permalink if 'echo' not set.
 */
function pp_get_index_permalink( $echo = 'echo' ){
	global $market_system, $wpdb;
	
	$pp_index_id = $wpdb->get_var( "SELECT ID FROM $wpdb->posts WHERE post_name = '" . $market_system->name() . "'" );

	if( !$pp_index_id )
		return false;
	elseif( $echo = 'echo' )
		echo get_permalink( $pp_index_id );
	else
		return get_permalink( $pp_index_id );
}

/**
 * Print the details of an individual post, including custom taxonomies.  
 * 
 * @param string $echo Optional, default 'echo'. If set to "echo" the function echo's the permalink, else, returns the permalink as a string. 
 * @return returns false if no index page set, true if echod the permalink or a string representing the permalink if 'echo' not set.
 */
function pp_get_the_term_list(){

	$pp_tax_types = get_option('pp_custom_taxonomies');

	if ( empty( $pp_tax_types ) )
		return;

	foreach( $pp_tax_types as $pp_tax_name => $pp_tax_type ){
		echo '<div class="pp-tax">';
		echo get_the_term_list( $post->ID, $pp_tax_name, $pp_tax_type[ 'labels' ][ 'singular_label' ] . ': ', ', ', '' );		
		echo '</div>';
	}
	
}


/**
 * Get's the end time for a post. Can return it in GMT or user's timezone (specified by UTC offset). 
 * Can also return as either mysql date format or a unix timestamp.
 *
 * @param int $post_id Optional, default 0. The post id for which you want the max bid. 
 * @return returns false if post has no end time, or a string representing the time stamp or sql
 */
function get_post_end_time( $post_id, $type = 'timestamp', $gmt = true ) {
	
	$time = wp_next_scheduled( 'schedule_end_post', array( "ID" => $post_id ) );

	// If a post has not yet ended, use it's actual scheduled end time, if that doesn't exist, 
	// probably becasue the post has ended, get the post end time from the post_meta table.
	if( empty( $time ) )
		$time = strtotime( get_post_meta( $post_id, 'post_end_date_gmt', true ) );

	if( $time == false )
	 	return false;

	if( $gmt == false ){
		$time = date( 'Y-m-d H:i:s', $time );
		$time = get_date_from_gmt( $time );
		if( $type == 'timestamp' ){
			$time = strtotime( $time );
		}
	}elseif( $type == 'mysql' ){
		$time = date( 'H:i Y/m/d', $time );
	}

	return $time;
}

function the_post_end_date(){
	global $post;

	$end_time = wp_next_scheduled( 'schedule_end_post', array( "ID" => $post->ID ) );

	if( empty( $end_time ) )
		$end_time = strtotime( get_post_meta( $post->ID, 'post_end_date_gmt', true ) );

	if( $end_time == false )
	 	return $date;

	echo date( 'F, j Y, G:i e', $end_time );
}

function post_end_time_filter( $date ){
	global $post;

	if( !in_the_loop() || get_post_type( $post ) != 'post' || is_admin() )
		return $content;
		
	$end_time = wp_next_scheduled( 'schedule_end_post', array( "ID" => $post->ID ) );

	if( empty( $end_time ) )
		$end_time = strtotime( get_post_meta( $post->ID, 'post_end_date_gmt', true ) );

	if( $end_time == false )
	 	return $date;
	
	$content .= __(' ending on ', 'prospress' ) . date( 'j F Y, G:i e', $end_time );
	//$date .= ' ending on ' . date( 'r', $end_time );

	return $content;
}

/**
 * Get's the end time for a post.
 *
 * @uses $post
 * @uses $wpdb
 *
 * @param int $post_id Optional, default 0. The post id for which you want the max bid. 
 * @return object Returns the row in the bids 
 */
function get_post_end_countdown( $post_id = '', $units = 3, $separator = ' ' ) {
	global $post;

	return human_interval( wp_next_scheduled( 'schedule_end_post', array( "ID" => $post->ID ) ) - time(), $units, $separator );
}

function the_post_end_countdown( $post_id = '', $units = 3, $separator = ' ' ) {

	echo get_post_end_countdown( $post_id, $units, $separator );
}


/** 
 * Takes a period of time as a unix time stamp and returns a string 
 * describing how long the period of time is, eg. 2 weeks 1 day.
 * 
 * Based on WP Crontrol's Interval function
 **/
function human_interval( $time_period, $units = 3, $separator = ' ' ) {

	if( $time_period <= 0 ) {
	    return __('Now', 'prospress' );
	}

    // array of time period chunks
	$chunks = array(
    	array( 60 * 60 * 24 * 365 , _n_noop( '%s year', '%s years' ) ),
    	array( 60 * 60 * 24 * 30 , _n_noop( '%s month', '%s months' ) ),
    	array( 60 * 60 * 24 * 7, _n_noop( '%s week', '%s weeks' ) ),
    	array( 60 * 60 * 24 , _n_noop( '%s day', '%s days' ) ),
    	array( 60 * 60 , _n_noop( '%s hour', '%s hours' ) ),
    	array( 60 , _n_noop( '%s minute', '%s minutes' ) ),
    	array( 1 , _n_noop( '%s second', '%s seconds' ) ),
	);

	// 1st chunk
	for ($i = 0, $j = count($chunks); $i < $j; $i++) {

		$seconds = $chunks[$i][0];
		$name = $chunks[$i][1];

		// finding the biggest chunk (if the chunk fits, break)
		if ( ( $count = floor( $time_period / $seconds ) ) != 0 ) {
			break;
		}
	}

	// set output var
	$output = sprintf(_n($name[0], $name[1], $count), $count);

	// 2nd chunk
	if ( $i + 1 < $j && $units >= 2 ) {
		$seconds2 = $chunks[$i + 1][0];
		$name2 = $chunks[$i + 1][1];

		if ( ( $count2 = floor( ( $time_period - ( $seconds * $count ) ) / $seconds2) ) != 0 ) {
			// add to output var
			$output .= $separator.sprintf(_n($name2[0], $name2[1], $count2), $count2);
		}
	}

	// 3rd chunk (as long as it's not seconds or minutes)
	if ( $i + 2 < $j - 1 && $units >= 3 ) {
		$seconds3 = $chunks[$i + 2 ][0];
		$name3 = $chunks[$i + 2 ][1];

		if ( ( $count3 = floor( ( $time_period - ( $seconds * $count ) - ( $seconds2 * $count2 ) ) / $seconds3 ) ) != 0 ) {
			// add to output var
			$output .= $separator.sprintf( _n( $name3[0], $name3[1], $count3 ), $count3 );
		}
	}

	return $output;
}

