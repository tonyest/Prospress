<?php
/**
 * Get's the end time for a post.
 *
 * @uses $post
 * @uses $wpdb
 *
 * @param int $post_id Optional, default 0. The post id for which you want the max bid. 
 * @return object Returns the row in the bids 
 */
function get_post_end_date( $date ) {
	global $post;
		
	$date = $date . ' ending in ' . time_until( wp_next_scheduled( 'schedule_end_post', array( "ID" => $post->ID ) ) );

	return $date;
}
add_filter( 'get_the_date', 'get_post_end_date' );

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
function the_post_end_date( $title, $id ) {

	//$title = 'The Title Is: ' . $title;
	
	$title = $title . ' ending in ' . time_until( wp_next_scheduled( 'schedule_end_post', array( "ID" => $id ) ) );
	
	return $title;
}
//add_filter( 'the_title', 'the_post_end_date', 10, 2 );

/**
 * Pretty-prints the time between now and another date.
 *
 * @param time $older_date
 * @param time $newer_date
 * @return string The pretty time_until value
 * @link http://binarybonsai.com/code/timesince.txt
 */
function time_until( $date ) {
    return verbose_interval( $date - time() );
}

/** 
 * Takes a period of time as a unix time stamp and returns a string 
 * describing how long the period of time is, eg. 2 weeks 1 day.
 * 
 * Based on WP_Crontrol's Interval function
 **/
function verbose_interval( $time_period ) {
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

	if( $time_period <= 0 ) {
	    return __('now');
	}

	// step one: the first chunk
	for ($i = 0, $j = count($chunks); $i < $j; $i++) {

		$seconds = $chunks[$i][0];
		$name = $chunks[$i][1];
		error_log("$i ** In verbose_interval, $seconds seconds in " . print_r($name, true));

		// finding the biggest chunk (if the chunk fits, break)
		if ( ( $count = floor( $time_period / $seconds ) ) != 0 ) {

			break;
		}
	}

	// set output var
	$output = sprintf(_n($name[0], $name[1], $count), $count);

	error_log( "$i ** In verbose_interval, output = $output, count = $count, seconds = $seconds  " );

	// step two: the second chunk, if it's not seconds
	if ( $i + 1 <= $j ) {
		$seconds2 = $chunks[$i + 1][0];
		$name2 = $chunks[$i + 1][1];

		if ( ( $count2 = floor( ( $time_period - ( $seconds * $count ) ) / $seconds2) ) != 0 ) {
			// add to output var
			$output .= ' '.sprintf(_n($name2[0], $name2[1], $count2), $count2);
		}
	}

	error_log( "$i ** In verbose_interval, output = $output, count2 = $count2, seconds2 = $seconds2  " );
	// step three: the third chunk
	//if ( $name2 != $chunks[0] &&  $name2 != $chunks[0] ) {
	if ( $i + 2 <= $j ) {
		$seconds3 = $chunks[$i + 2 ][0];
		$name3 = $chunks[$i + 2 ][1];

		if ( ( $count3 = floor( ( $time_period - ( $seconds * $count ) - ( $seconds2 * $count2 ) ) / $seconds3 ) ) != 0 ) {
			// add to output var
			$output .= ' '.sprintf(_n($name3[0], $name3[1], $count3), $count3);
		}
	}

	return $output;
}


?>