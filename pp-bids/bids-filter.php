<?php

// Plugin Name: Bids Filter
// Version: 1.0a

class Bid_Filter_Widget extends WP_Widget {

	function Bid_Filter_Widget() {
		$widget_ops = array('description' => __('Filter Prospress Posts by high & low price.') );
		$this->WP_Widget('bid-filter', __('Bid Price Filter'), $widget_ops);
	}

	function widget( $args, $instance ) {
		global $currency_symbol;
		extract($args);

		$title = apply_filters('widget_title', $instance['title'], $instance, $this->id_base);

		echo $before_widget;
		if ( $title )
			echo $before_title . $title . $after_title;

		$min = floatval(@$_GET['p-min']);
		$max = floatval(@$_GET['p-max']);

		if ( !$min )
			$min = '';

		if ( !$max )
			$max = '';

		echo '<form action="" method="get">';
		echo $currency_symbol . ' ';
		echo '<input type="text" id="p-min" name="p-min" size="5" value="' . esc_attr($min) . '"> ';
		echo __('to') . ' ';
		echo '<input type="text" id="p-max" name="p-max" size="5" value="' . esc_attr($max) . '"> ';
		echo '<input type="submit" id="bid-filter" value="' . __('Filter') . '">';
		foreach( $_GET as $name => $value ){
			if( $name == 'p-min' || $name == 'p-max' ) continue;
			echo '<input type="hidden" name="' . esc_html( $name ) . '" value="' . esc_html( $value ) . '">';
		}
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
		add_action( 'pre_get_posts', array( __CLASS__, 'add_filters' )) ;
	}

	static function add_filters($obj) {
		global $bid_system;

		// Don't touch the main query or queries for non-Prospress posts
		if ( $GLOBALS['wp_query'] == $obj || $obj->query_vars['post_type'] != $bid_system->name )
			return;

		add_filter('posts_where', array(__CLASS__, 'posts_where'));
	}

	static function posts_where($where) {
		remove_filter(current_filter(), array(__CLASS__, __FUNCTION__));

		global $wpdb;

		$min = floatval(@$_GET['p-min']);
		$max = floatval(@$_GET['p-max']);

		if ( !$min && !$max )
			return $where;

		$bidsmeta_value = "CAST($wpdb->bidsmeta.meta_value AS decimal)";

		if ( $min && $max )
			$clause = "$bidsmeta_value >= $min AND $bidsmeta_value <= $max";
		elseif ( $min )
			$clause = "$bidsmeta_value >= $min";
		elseif ( $max )
			$clause = "$bidsmeta_value <= $max";

		$where .= " AND ( $wpdb->posts.ID IN ( SELECT post_id FROM $wpdb->bids WHERE bid_id IN ( SELECT bid_id FROM $wpdb->bidsmeta WHERE $wpdb->bidsmeta.meta_key = 'winning_bid_value' AND $clause ) )";

		if( $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->postmeta WHERE meta_key = 'start_price' AND meta_value > 0" ) ){
			$postmeta_value = "CAST($wpdb->postmeta.meta_value AS decimal)";

			if ( $min && $max )
				$clause_sp = "$postmeta_value >= $min AND $postmeta_value <= $max";
			elseif ( $min )
				$clause_sp = "$postmeta_value >= $min";
			elseif ( $max )
				$clause_sp = "$postmeta_value <= $max";

			$where .= " OR $wpdb->posts.ID IN ( SELECT post_id FROM $wpdb->postmeta WHERE $wpdb->postmeta.meta_key = 'start_price' AND $clause_sp AND post_id NOT IN ( SELECT DISTINCT post_id FROM $wpdb->bids ) )";
		}
		
		$where .= ")";

		return $where;
	}
}
Bid_Filter_Query::init();