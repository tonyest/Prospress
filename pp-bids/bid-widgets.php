<?php

//Add default bid widgets here.

require_once ( PP_BIDS_DIR . '/bid-widget.class.php' );

/**
 * Prospress Currency Bid Widget
 * 
 * 
 * 
 */
class Pp_Currency_Bidget extends PP_Bid_Widget {
	function Pp_Currency_Bidget() {
		$widget_ops = array( 'classname' => 'currency', 'description' => 'Accept currency as part of a bid. Bids can include sequential and/or private currency values. ' );
		$this->PP_Bid_Widget( 'currency', 'Currency', $widget_ops );
	}

	function widget( $args, $instance ) {
		extract( $args, EXTR_SKIP );
		echo $before_widget;
		echo $before_title;
		echo 'Currency'; // Can set this with a widget option, or omit altogether
		echo $after_title;

		//
		// Widget display logic goes here
		//
		
		echo "The Currency bid widget has been added.";

		echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		// update logic goes here
		$updated_instance = $new_instance;
		return $updated_instance;
	}

	function form( $instance ) {
	  //Defaults
	    $instance = wp_parse_args( (array) $instance, array('title'=>'', 'sequential'=>'', 'private'=>'') );

	    $title = htmlspecialchars($instance['title']);
	    $sequential = htmlspecialchars($instance['sequential']);
	    $private = htmlspecialchars($instance['private']);

	    # Output the options
	    echo '<p style="text-align:right;"><label for="' . $this->get_field_name('title') . '">' . __('Title:') . ' <input id="' . $this->get_field_id('title') . '" name="' . $this->get_field_name('title') . '" type="text" value="' . $title . '" /></label></p>';
	    # Sequential
	    echo '<p style="text-align:right;"><label for="' . $this->get_field_name('sequential') . '">' . __('Sequential:') . ' <input id="' . $this->get_field_id('sequential') . '" name="' . $this->get_field_name('sequential') . '" type="checkbox" ' . ($sequential == "on" ? 'checked="checked"' : '') . ' /></label></p>';
	    # Private
	    echo '<p style="text-align:right;"><label for="' . $this->get_field_name('private') . '">' . __('Private:') . ' <input id="' . $this->get_field_id('private') . '" name="' . $this->get_field_name('private') . '" type="checkbox" ' . ($private == "on" ? 'checked="checked"' : '') . ' /></label></p>';
	}
}
add_action( 'widgets_init', create_function( '', "register_widget('Pp_Currency_Bidget');" ) );

?>