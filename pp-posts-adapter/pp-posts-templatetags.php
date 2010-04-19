<?php
/**
 * Get's the end time for a post. Can return it in GMT or user's timezone (specified by UTC offset). 
 * Can also return as either mysql date format or a unix timestamp.
 *
 * @param int $post_id Optional, default 0. The post id for which you want the max bid. 
 * @return returns false if post has no end time, or a string representing the time stamp or sql
 */
function get_post_end_time( $post_id, $type = 'timestamp', $gmt = true ) {

	$time = wp_next_scheduled( 'schedule_end_post', array( "ID" => $post_id ) );

	// If a post has not yet ended, use it's actual scheduled end time, if that doesn't exist, probably becasue the post has ended
	// get the post end time from the post_meta table
	if( empty( $time ) )
		$time = strtotime( get_post_meta( $post_id, 'post_end_date_gmt', true ) );

	//error_log('--** in get_post_end_time start, time = ' . $time);

	if( $time == false )
	 	return false;

	if( $gmt == false ){
		$time = date( 'Y-m-d H:i:s', $time );
	//	error_log('--** in get_post_end_time, gmt == false, after date function, time = ' . $time);
		$time = get_date_from_gmt( $time );
	//	error_log('--** in get_post_end_time, gmt time = ' . $time);
		if( $type == 'timestamp' ){
			$time = strtotime( $time );
	//		error_log('--** in get_post_end_time, gmt == false, type == timestamp, time = ' . $time);
		}
	}elseif( $type == 'mysql' ){
		$time = date( 'H:i Y/m/d', $time );
	//	error_log('--** in get_post_end_time, gmt == true, type == mysql, time = ' . $time);
	}

	return $time;
}
add_filter( 'get_the_date', 'get_post_end_time' );

/**
 * Get's the end time for a post.
 *
 * @uses $post
 * @uses $wpdb
 *
 * @param int $post_id Optional, default 0. The post id for which you want the max bid. 
 * @return object Returns the row in the bids 
 */
function get_post_end_countdown( $date ) {
	global $post;

	$date = $date . ' ending ' . human_interval( wp_next_scheduled( 'schedule_end_post', array( "ID" => $post->ID ) ) - time() );

	return $date;
}
add_filter( 'get_the_date', 'get_post_end_countdown' );

/**
 * Print's the end time for a post.
 *
 */
function the_post_end_time( $post_id = 0) {
}

/**
 * Print's the date and time a post is scheduled to end.
 *
 */
function the_post_end_date( $date, $id ) {

}
//add_filter( 'the_title', 'the_post_end_date', 10, 2 );


/** 
 * Takes a period of time as a unix time stamp and returns a string 
 * describing how long the period of time is, eg. 2 weeks 1 day.
 * 
 * Based on WP_Crontrol's Interval function
 **/
function human_interval( $time_period, $units = 3 ) {
    // array of time period chunks
	$chunks = array(
    	array(60 * 60 * 24 * 365 , _n_noop('%s year', '%s years')),
    	array(60 * 60 * 24 * 30 , _n_noop('%s month', '%s months')),
    	array(60 * 60 * 24 * 7, _n_noop('%s week', '%s weeks')),
    	array(60 * 60 * 24 , _n_noop('%s day', '%s days')),
    	array(60 * 60 , _n_noop('%s hour', '%s hours')),
    	array(60 , _n_noop('%s minute', '%s minutes')),
    	array( 1 , _n_noop('%s second', '%s seconds')),
	);
	error_log("$i ** In human_interval, chunks " . print_r( $chunks, true));

	if( $time_period <= 0 ) {
	    return __('now');
	}

	// step one: the first chunk
	for ($i = 0, $j = count($chunks); $i < $j; $i++) {

		$seconds = $chunks[$i][0];
		$name = $chunks[$i][1];
		error_log("$i ** In human_interval, $seconds seconds in " . print_r($name, true));

		// finding the biggest chunk (if the chunk fits, break)
		if ( ( $count = floor( $time_period / $seconds ) ) != 0 ) {

			break;
		}
	}

	// set output var
	$output = sprintf(_n($name[0], $name[1], $count), $count);

	error_log( "$i ** In human_interval, output = $output, count = $count, seconds = $seconds  " );

	// step two: the second chunk, if it's not seconds
	if ( $i + 1 < $j && $units >= 2 ) {
		$seconds2 = $chunks[$i + 1][0];
		$name2 = $chunks[$i + 1][1];

		if ( ( $count2 = floor( ( $time_period - ( $seconds * $count ) ) / $seconds2) ) != 0 ) {
			// add to output var
			$output .= ' '.sprintf(_n($name2[0], $name2[1], $count2), $count2);
		}
	error_log( "$i ** In human_interval, output = $output, count2 = $count2, seconds2 = $seconds2  " );
	}

	// step three: the third chunk
	if ( $i + 2 < $j - 1 && $units >= 3 ) {
		$seconds3 = $chunks[$i + 2 ][0];
		$name3 = $chunks[$i + 2 ][1];

		error_log( "$i ** In human_interval, name3 = " . print_r( $name3, true ) );
		if ( ( $count3 = floor( ( $time_period - ( $seconds * $count ) - ( $seconds2 * $count2 ) ) / $seconds3 ) ) != 0 ) {
			// add to output var
			$output .= ' '.sprintf( _n( $name3[0], $name3[1], $count3 ), $count3 );
		}
	error_log( "$i ** In human_interval, output = $output, count3 = $count3, seconds3 = $seconds3  " );
	}

	return $output;
}


?>