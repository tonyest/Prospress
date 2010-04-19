<?php

add_action('parse_query','set_pp_sort_flag');
function set_pp_sort_flag( $query ) {
	global $pp_sort;

	if( !isset( $query->query_vars['pp_sort'] ) ){
		//error_log('*** NOT SET query->query_vars[pp_sort] ***');
		//error_log('in set_pp_sort $query->query_vars = ' . print_r($query->query_vars, true));
		return $query;
	}

	$pp_sort = explode( '-', $query->query_vars['pp_sort'] );
	$pp_sort['orderby']	= $pp_sort[ 0 ];
	$pp_sort['order']	= $pp_sort[ 1 ];
	unset( $pp_sort[ 0 ] );
	unset( $pp_sort[ 1 ] );
	error_log('****** in set_pp_sort $pp_sort = ' . print_r($pp_sort, true));
}

add_filter('posts_join','pp_join_filter');
function pp_join_filter( $arg ) {
	global $wpdb, $wp_query, $pp_sort;

	if( !isset( $pp_sort['orderby'] ) )
		return $arg;

	if( $pp_sort['orderby'] == 'end_date' )
		$arg .= "JOIN $wpdb->postmeta ON ($wpdb->posts".".ID = $wpdb->postmeta".".post_id) ";
	else
		$arg = apply_filters('pp_posts_join', $arg);

	error_log('in pp_join_filter, query_vars, arg = ' . $arg);

	return $arg;
}

add_filter('posts_orderby','pp_orderby_filter');
function pp_orderby_filter( $arg ) {
	global $wpdb, $wp_query, $pp_sort;

	error_log('* in start of pp_orderby_filter, arg = ' . $arg);

	if( !isset( $pp_sort['orderby'] ) )
		return $arg;

	if( $pp_sort['orderby'] == 'end_date' )
		$arg = $wpdb->postmeta . ".meta_key " . $pp_sort['order'];//$arg = str_replace("$wpdb->posts.post_date",$wpdb->postmeta . ".meta_key ",$arg);
	else
		$arg = apply_filters('pp_posts_orderby', $arg);

	error_log('** in pp_orderby_filter, arg = ' . $arg);

	return $arg;
}

add_filter('posts_where','pp_where_filter');
function pp_where_filter( $arg ) {
	global $wpdb, $wp_query, $pp_sort;

	if( !isset( $pp_sort['orderby'] ) )
		return $arg;

	if( $pp_sort['orderby'] == 'end_date' )
		$arg = $arg . " AND " . $wpdb->postmeta . ".meta_key = 'post_end_date_gmt' ";
	else
		$arg = apply_filters('pp_posts_where', $arg);

	error_log('in pp_where_filter, arg = ' . $arg);

	return $arg;
}

add_action('query_vars','pp_insert_rewrite_query_vars');
function pp_insert_rewrite_query_vars( $vars ) {
	$vars[] = 'pp_sort';

	//error_log('in pp_insert_rewrite_query_vars, vars = ' . print_r($vars,true));
	return $vars;
}



/**************************************************************************************
 *************************************** WIDGET ***************************************
 **************************************************************************************/
class PP_Sort_Widget extends WP_Widget {
	function PP_Sort_Widget() {
		/* Widget settings. */
		$widget_ops = array( 'classname' => 'pp_sort', 'description' => __( 'Sort posts in your Prospress Marketplace.' ) );

		/* Widget control settings. */
		$control_ops = array( 'id_base' => 'pp_sort' );

		/* Create the widget. */
		$this->WP_Widget( 'pp_sort', 'Prospress Sort', $widget_ops, $control_ops );
	}

	function widget($args, $instance) {
		extract( $args );
		
		//Don't want to print on single posts or pages
		if( is_single() || is_page() ){
			error_log('in widget, is single true');
			return;
		}

		$end_date = isset( $instance['end_date'] ) ? $instance['end_date'] : false;
		$price = isset( $instance['price'] ) ? $instance['price'] : false;

		echo $before_widget;

		echo $before_title;
		echo ( $instance['title'] ) ? $instance['title'] : __('Sort By:');
		echo $after_title;

		$saved_options = array( 'end_date' => "End Time", 'price' => "Price" );

		echo '<form id="pp_sort" method="get" action="">';
		echo '<select name="pp_sort" >';
		foreach ( $saved_options as $key => $label ) {
			echo "<option value='".$key."-ASC'>".$label." ". __('Ascending')."</option>";
			echo "<option value='".$key."-DESC'>".$label." ". __('Descending')."</option>";
		}
		echo '</select>';
		echo '<input type="submit" value="' . __("Sort") . '">';
		echo '</form>';
		
		echo $after_widget;

		echo $after_widget;
	}

	function form($instance) {

		/* Set up some default widget settings. */
		$defaults = array( 'title' => 'Sort By:', 'end_date' => true, 'price' => true );
		$instance = wp_parse_args( (array) $instance, $defaults ); 
		error_log('in form, $instance = ' . print_r($instance, true));?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php __('Title') ?></label>
			<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" />
		</p>
		<p>
			<input class="checkbox" type="checkbox" <?php checked( $instance['end_date'], 'on' ); ?> id="<?php echo $this->get_field_id( 'end_date' ); ?>" name="<?php echo $this->get_field_name( 'end_date' ); ?>" />
			<label for="<?php echo $this->get_field_id( 'end_date' ); ?>"><?php _e('Sort by end date') ?></label>
		</p>
		<p>
			<input class="checkbox" type="checkbox" <?php checked( $instance['price'], 'on' ); ?> id="<?php echo $this->get_field_id( 'price' ); ?>" name="<?php echo $this->get_field_name( 'price' ); ?>" />
			<label for="<?php echo $this->get_field_id( 'price' ); ?>"><?php _e('Sort by price') ?></label>
		</p>
		<?php
	}

	function update($new_instance, $old_instance) {
		$instance = $old_instance;

		/* Strip tags (if needed) and update the widget settings. */
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['end_date'] = $new_instance['end_date'];
		$instance['price'] = $new_instance['price'];

		return $instance;
	}
}
add_action('widgets_init', create_function('', 'return register_widget("PP_Sort_Widget");'));


add_action('wp_head', 'pp_print_query');
function pp_print_query(){
	global $wp_query;
	error_log('in pp_print_query, $wp_query request = ' . print_r($wp_query->request, true));
	//error_log('in pp_print_query, $wp_query request = ' . print_r($wp_query, true));
}

/*
add_filter('posts_fields','pp_select_filter');
function pp_select_filter($arg) {
	global $wpdb;

	//$arg .= str_replace("$wpdb->posts.post_date","$wpdb->bids.bid_value",$arg);
	//$arg .= ' ' . $wpdb->bids . ".*";
	error_log('in pp_select_filter, arg = ' . $arg);
	return $arg;
}
*/

?>