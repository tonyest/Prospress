<?php

// Plugin Name: Bids Filter
// Version: 1.0a

class Bid_Filter_Widget extends WP_Widget {

	function Bid_Filter_Widget() {
		$widget_ops = array('description' => __('A search form for your blog', 'your-textdomain') );
		$this->WP_Widget('bid-filter', __('Bids Filter', 'your-textdomain'), $widget_ops);
	}

	function widget( $args, $instance ) {
		extract($args);

		if( is_single() || is_page() ){
			error_log('in widget, is single true');
			return;
		}

		$title = apply_filters('widget_title', $instance['title'], $instance, $this->id_base);

		echo $before_widget;
		if ( $title )
			echo $before_title . $title . $after_title;

		extract(get_bid_filter_args());

		if ( !$min )
			$min = '';

		if ( !$max )
			$max = '';

		echo '<form action="' . get_bloginfo('url') . '" method="get">';
		echo __('$', 'your-textdomain') . ' ';
		echo '<input type="text" id="bid-min" name="bid-min" size="5" value="' . esc_attr($min) . '"> ';
		echo __('to', 'your-textdomain') . ' ';
		echo '<input type="text" id="bid-max" name="bid-max" size="5" value="' . esc_attr($max) . '"> ';
		echo '<input type="submit" id="bid-filter-submit" name="bid-filter-submit" value="' . __('Filter', 'your-textdomain') . '">';
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

function bid_filter_widget_init() {
	register_widget('Bid_Filter_Widget');
}
add_action('widgets_init', 'bid_filter_widget_init');


class Bid_Filter_Query {

	static function init() {
		add_action('pre_get_posts', array(__CLASS__, 'add_filters'));
	}

	static function add_filters($obj) {
		// Operate only on the main query
		if ( $GLOBALS['wp_query'] != $obj )
			return;

		if ( is_singular() )
			return;

		add_filter('posts_where', array(__CLASS__, 'posts_where'));
	}

	static function posts_where($where) {
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
				WHERE $wpdb->bidsmeta.meta_key = 'winning_bid_value'
				AND $clause
			)
		)";
	}
}
Bid_Filter_Query::init();


function get_bid_filter_args() {
	return array(
		'min' => intval(@$_GET['bid-min']),
		'max' => intval(@$_GET['bid-max']),
	);
}

