<?php
/**
 * Prospress Sort
 *
 * Allows an index of Prospress posts to be sorted by a customisable array of attributes.
 *
 * @package Prospress
 * @author Brent Shepherd
 * @version 0.1
 */

class PP_Sort_Widget extends WP_Widget {
	function PP_Sort_Widget() {
		global $market_systems; 
		/* Widget settings. */
		$widget_ops = array( 'classname' => 'pp-sort', 'description' => sprintf( __('Sort %s in your marketplace.', 'prospress' ), $market_systems['auctions']->name() ) );

		/* Widget control settings. */
		$control_ops = array( 'id_base' => 'pp-sort' );

		/* Create the widget. */
		$this->WP_Widget( 'pp-sort', __('Prospress Sort', 'prospress' ), $widget_ops, $control_ops );
	}

	function widget( $args, $instance ) {
		global $pp_sort_options;

		extract( $args );

		$sorted_by = trim( @$_GET[ 'pp-sort' ] );

		echo $before_widget;

		echo $before_title;
		echo ( $instance['title'] ) ? $instance['title'] : __( 'Sort By:', 'prospress' );
		echo $after_title;

		echo '<form id="pp-sort" method="get" action=""><div>';
		echo '<select id="pp-sort-select" name="pp-sort">';
		foreach ( $pp_sort_options as $key => $label ) {
			if( $instance[ $key ] != 'on' )
				continue;
			echo "<option value='".$key."' ".selected($key, $sorted_by, false).">".$label."</option>";
		}
		echo '</select>';
		echo '<input type="submit" value="' . __("Sort", 'prospress' ) . '" />';
		foreach( $_GET as $name => $value ){ // allow other get parameters to persist
			if( $name == 'pp-sort' ) continue;
			echo '<input type="hidden" name="' . esc_html( $name ) . '" value="' . esc_html( $value ) . '" />';
		}
		echo '</div></form>';

		echo $after_widget;
	}

	function form( $instance ) {
		global $pp_sort_options;

		$title = ( $instance['title'] ) ? $instance['title'] : __( 'Sort By:', 'prospress' );
		?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'prospress' ); ?> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" /></label></p>
		<p><?php _e( 'Sort By:', 'prospress' ) ?></p>
		<?php
		foreach( $pp_sort_options as $key => $label ){
			echo '<p><input class="checkbox" id="' . $this->get_field_id( $key ) . '" name="' . $this->get_field_name( $key ) . '" type="checkbox" ' . checked( $instance[ $key ], "on", false ) . ' /><label for="' . $this->get_field_id( $key ) . '"> ' . $label . '</label></p>';
		}
	}

	function update( $new_instance, $old_instance ) {
		global $pp_sort_options;

		$instance = $old_instance;

		$instance['title'] = strip_tags( $new_instance['title'] );

		foreach( $pp_sort_options as $key => $label )
			$instance[ $key ] = $new_instance[ $key ];

		return $instance;
	}
}
add_action('widgets_init', create_function('', 'return register_widget("PP_Sort_Widget");'));

function pp_set_sort_options(){
	global $pp_sort_options;

	$pp_sort_options = apply_filters( 'pp_sort_options', $pp_sort_options );
}
add_action('init', 'pp_set_sort_options');


class PP_Sort_Query {
	const BID_WINNING = 'winning_bid_value';
	const START_PRICE = 'start_price';
	const POST_END = 'post_end_date_gmt';

	static function init() {
		add_action( 'pre_get_posts', array( __CLASS__, 'add_filters' ) );
	}

	static function add_filters( $obj ) {
		global $market_systems, $wp_query;

		// Don't touch the main query or queries for non-Prospress posts
		if ( $wp_query === $obj || !array_key_exists( $obj->query_vars['post_type'], $market_systems ) )
			return;
		// Fix YARPP incompatibility
		else if( is_array( $obj->query_vars[ 'post_type' ] ) )
			return;
			
		add_filter( 'posts_join_paged', array(__CLASS__, 'posts_join_paged' ) );
		add_filter( 'posts_orderby', array(__CLASS__, 'posts_orderby' ) );
	}
	
	static function posts_orderby( $sql ) {
		remove_filter( current_filter(), array( __CLASS__, __FUNCTION__ ) );

		global $wpdb;

		if ( !$sort = trim( @$_GET[ 'pp-sort' ] ) )
			return $sql;

		list( $orderby, $order ) = explode( '-', $sort );

		if ( 'asc' == $order )
			$order = 'ASC';
		else
			$order = 'DESC';

		if  ( 'price'==$orderby ){
			$price_meta_value = "CAST(meta_value AS DECIMAL(20,2)) ";
			$sql = $price_meta_value . " $order";

		} elseif ( 'end' == $orderby ) {
			$sql = "(
				SELECT meta_value
				FROM $wpdb->postmeta
				WHERE $wpdb->postmeta.post_id = $wpdb->posts.ID
				AND $wpdb->postmeta.meta_key = '" . self::POST_END . "'
			) $order";
		} elseif ( 'post' == $orderby ) {
			$sql = "$wpdb->posts.post_date $order";
		}

		return $sql;
	}

	static function posts_join_paged($sql){
		remove_filter( current_filter(), array( __CLASS__, __FUNCTION__ ) );
		global $wpdb;
		if ( !$sort = trim( @$_GET[ 'pp-sort' ] ) )
			return $sql;

		list( $orderby, $order ) = explode( '-', $sort );

		if ( 'price' == $orderby ) {
			$sql = "JOIN (
						SELECT post_parent AS post_id,meta_key,meta_value
						FROM $wpdb->postmeta
						JOIN $wpdb->posts
							ON $wpdb->posts.ID = $wpdb->postmeta.post_id
						WHERE meta_key = 'winning_bid_value'
						UNION ALL 
						SELECT post_id,meta_key,meta_value
						FROM $wpdb->postmeta
						JOIN $wpdb->posts
							ON $wpdb->posts.ID = $wpdb->postmeta.post_id
						WHERE meta_key ='start_price'
						AND post_id NOT IN (
							SELECT post_parent
							FROM $wpdb->posts
							WHERE post_status = 'winning')
					) AS new_meta
						ON $wpdb->posts.ID = post_id AND (meta_key = 'start_price' OR meta_key = 'winning_bid_value')";
		}
		return $sql;
	}
}

PP_Sort_Query::init();
