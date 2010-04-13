<?php
add_filter('pp_posts_join','pp_join_bids');
function pp_join_bids($arg) {
	global $wpdb, $wp_query, $pp_sort;

	if( $pp_sort['orderby'] == 'price' )
		$arg .= "JOIN $wpdb->bids ON ($wpdb->posts".".ID = $wpdb->bids".".post_id) ";

	error_log('in pp_join_bids, pp_sort = ' . $pp_sort['orderby']);
	error_log('in pp_join_bids, arg = ' . $arg);
	return $arg;
}

add_filter('pp_posts_orderby','pp_orderby_bids');
function pp_orderby_bids($arg) {
	global $wpdb, $wp_query, $pp_sort;

	if( $pp_sort['orderby'] == 'price' )
		$arg = str_replace("$wpdb->posts.post_date",$wpdb->bids . ".bid_value ",$arg);
//		$arg = $wpdb->bids . ".bid_value " . $pp_sort[ 'order' ];

	return $arg;
}

add_filter('pp_posts_where','pp_where_bids');
function pp_where_bids($arg) {
	global $wpdb, $wp_query, $pp_sort;

	if( $pp_sort['orderby'] == 'price' )
		$arg .= " AND " . $wpdb->bids . ".bid_status = 'winning' ";

	return $arg;
}

?>