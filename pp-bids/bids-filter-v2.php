<?php

// Plugin Name: Bids Filter
// Version: 0.2a


class PP_Sort_Filter_Widget extends WP_Widget {

	function PP_Sort_Filter_Widget() {
		$widget_ops = array('description' => __('Sort and filter posts.') );
		$this->WP_Widget('bid-filter', __('Sort and Filter'), $widget_ops);
	}

	function widget( $args, $instance ) {
		global $currency_symbol;
		extract($args);

		$title = apply_filters('widget_title', $instance['title'], $instance, $this->id_base);

		echo $before_widget;
		if ( $title )
			echo $before_title . $title . $after_title;

		echo '<form action="" method="get">';

		// Filter
		extract(get_bid_filter_args());

		if ( !$min )
			$min = '';

		if ( !$max )
			$max = '';

		echo '<div id="bid-filter">';
		echo $currency_symbol;
		echo '<input type="text" id="bid-min" name="bid-min" size="7" value="' . esc_attr($min) . '"> ';
		echo __( 'to' ) . ' ';
		echo '<input type="text" id="bid-max" name="bid-max" size="7" value="' . esc_attr($max) . '"> ';
		echo '<p><input type="submit" id="bid-filter-submit" name="bid-filter-submit" value="' . __( 'Filter' ) . '"></p>';
		echo '</div>';

		// Sort
		$sort = get_bid_sort_arg();

		$sorting_options = array(
			'price-asc' => __('Price: low to high'),
			'price-desc'=> __('Price: high to low'),
			'end-asc'	=> __('Time: Ending soon'),
			'end-desc'	=> __('Time: Newly posted'),
		);

		echo '<div id="bid-sort">';
		echo '<select id="bid-sort" name="bid-sort">';
		echo '<option value="0">' . __('-- sort by --') . '</option>';
		foreach ( $sorting_options as $value => $title )
			echo '<option value="' . esc_attr($value) . '"' . selected($value, $sort). '>' . $title . '</option>';
		echo '</select>';
		echo '<p><input type="submit" id="bid-filter-submit" name="bid-filter-submit" value="' . __('Sort') . '"></p>';
		echo '</div>';
		echo '</form>';

		echo $after_widget;
	}

	function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, array( 'title' => '') );
		$title = $instance['title'];
	?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" /></label></p>
	<?php
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$new_instance = wp_parse_args((array) $new_instance, array( 'title' => ''));
		$instance['title'] = strip_tags($new_instance['title']);
		return $instance;
	}
}

function pp_sort_filter_widget_init() {
	register_widget('PP_Sort_Filter_Widget');
}
add_action('widgets_init', 'pp_sort_filter_widget_init');

class PP_Sort_Filter_Query {
	const BID_WINNING = 'winning_bid_value';
	const POST_END = 'post_end_date_gmt';

	static function init() {
		add_action( 'pre_get_posts', array( __CLASS__, 'add_filters' ) );
	}

	static function add_filters( $obj ) {
		// Operate only on the main query
		if ( $GLOBALS['wp_query'] != $obj )
			return;

		if ( is_singular() )
			return;

		add_filter('posts_where', array(__CLASS__, 'posts_where'));
		add_filter('posts_orderby', array(__CLASS__, 'posts_orderby'));
	}

	static function posts_where( $where ) {
		remove_filter(current_filter(), array(__CLASS__, __FUNCTION__));

		global $wpdb;

		extract(get_bid_filter_args());

		$meta_value = "CAST($wpdb->bidsmeta.meta_value AS decimal)";

		if ( $min && $max )
			$clause = "$meta_value >= $min AND $meta_value <= $max";
		elseif ( $min )
			$clause = "$meta_value >= $min";
		elseif ( $max )
			$clause = "$meta_value <= $max";
		else
			return $where;

		return $where . " AND $wpdb->posts.ID IN (
			SELECT post_id
			FROM $wpdb->bids
			WHERE bid_id
			IN (
				SELECT bid_id
				FROM $wpdb->bidsmeta
				WHERE $wpdb->bidsmeta.meta_key = '" . self::BID_WINNING . "'
				AND $clause
			)
		)";
	}

	static function posts_orderby( $sql ) {
		remove_filter(current_filter(), array(__CLASS__, __FUNCTION__));

		global $wpdb;

		if ( !$sort = get_bid_sort_arg() )
			return $sql;

		list($orderby, $order) = explode('-', $sort);

		if ( 'asc' == $order )
			$order = 'ASC';
		else
			$order = 'DESC';

		$meta_value = "CAST($wpdb->bidsmeta.meta_value AS decimal)";

		if ( 'price' == $orderby ) {
			$sql = "(
				SELECT $meta_value
				FROM $wpdb->bidsmeta
				JOIN $wpdb->bids
					ON $wpdb->bids.bid_id = $wpdb->bidsmeta.bid_id
				WHERE $wpdb->bids.post_id = $wpdb->posts.ID
				AND $wpdb->bidsmeta.meta_key = '" . self::BID_WINNING . "'
			) $order";
		}

		if ( 'end' == $orderby ) {
			$sql = "(
				SELECT meta_value
				FROM $wpdb->postmeta
				WHERE $wpdb->postmeta.post_id = $wpdb->posts.ID
				AND $wpdb->postmeta.meta_key = '" . self::POST_END . "'
			) $order";
		}

		return $sql;
	}
}
PP_Sort_Filter_Query::init();


function get_bid_filter_args() {
	return array(
		'min' => intval(@$_GET['bid-min']),
		'max' => intval(@$_GET['bid-max']),
	);
}

function get_bid_sort_arg() {
	return trim(@$_GET['bid-sort']);
}

