<?php

//Add default feedback widgets here.

require_once ( PP_FEEDBACK_DIR . '/feedback-widget.class.php' );

/**
 * Include a text field on the feedback form. 
 * 
 * Prospress Text Field Feedback Widget
 * 
 */
class Pp_Text_Field_Fidget extends PP_Feedback_Widget {
	function Pp_Text_Field_Fidget() {
		$widget_ops = array( 'classname' => 'text', 'description' => __('Include a text box on the feedback form.') );
		$this->PP_Feedback_Widget( 'text', __('Text Box'), $widget_ops );
	}

	function widget( $args, $instance ) {
		extract( $args, EXTR_SKIP );
		echo $before_widget;
		echo $before_title;
		echo __('Text Box');
		echo $after_title;

		//
		// Widget display logic goes here
		//
		
		echo "<p>The Text Field feedback widget has been added.</p>";

		echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {

	    $instance = $old_instance;
	    $instance['title'] = strip_tags( stripslashes( $new_instance['title'] ) );
	    $instance['maxlength'] = strip_tags( stripslashes( $new_instance['maxlength'] ) );

  		return $instance;
	}

	function form( $instance ) {
	  //Defaults
	    $instance = wp_parse_args( (array) $instance, array( 'title'=>'', 'maxlength'=>'' ) );

	    $title = htmlspecialchars($instance['title']);
	    $maxlength = htmlspecialchars($instance['maxlength']);

	    // Output the options
	    echo '<p><label for="' . $this->get_field_name('title') . '">' . __('Title:') . '</label> <input id="' . $this->get_field_id('title') . '" name="' . $this->get_field_name('title') . '" type="text" value="' . $title . '" /></p>';
	    echo '<p><label for="' . $this->get_field_name('maxlength') . '">' . __('Maximum Characters:') . '</label> <input id="' . $this->get_field_id('maxlength') . '" name="' . $this->get_field_name('maxlength') . '" type="text" value="' . ( !empty( $maxlength ) ? $maxlength : "256" ) . '" /></p>';
	}

	/**
	 * Logic to process the widget's data on submission of the feedback form.
	 * 
	 * Data should be stored as an associative array in the feedbackmeta table with a metakey of "feedback_field".
	 *
	 * @param array $instance Current settings
	 */
	function submit($instance) {
		die( 'function PP_Feedback_Widget::submit() must be over-ridden in feedback widget sub-class.' );
	}
}
add_action( 'widgets_init', create_function( '', "register_widget('Pp_Text_Field_Fidget');" ) );

/**
 * Prospress Five Stars Bid Widget
 * 
 */
class Pp_Five_Stars_Fidget extends PP_Feedback_Widget {
 /**
  * Declares the HelloWorldWidget class.
  *
  */
	function Pp_Five_Stars_Fidget() {
		$widget_ops = array( 'classname' => 'Pp_Five_Stars_Fidget', 'description' => 'Solicit a rating on the ubiquitous 5 star scale.' );
		$this->PP_Feedback_Widget( 'five-stars', 'Five Stars', $widget_ops );
	}

  /**
    * Displays the Widget
    *
    */
	function widget( $args, $instance ) {
		extract( $args, EXTR_SKIP );
		echo $before_widget;
		echo $before_title;
		echo 'Five Stars'; // Can set this with a widget option, or omit altogether
		echo $after_title;

		//
		// Widget display logic goes here
		//
		
		echo "<p>The Five Stars feedback widget has been added.</p>";

		echo $after_widget;
	}

  /**
    * Saves the widgets settings.
    *
    */
	function update( $new_instance, $old_instance ) {

	    $instance = $old_instance;
	    $instance['title'] = strip_tags( stripslashes( $new_instance['title'] ) );

  		return $instance;
	}

  /**
    * Creates the edit form for the widget.
    *
    */
	function form( $instance ) {
	  //Defaults
	    $instance = wp_parse_args( (array) $instance, array('title'=>'', 'sequential'=>'', 'private'=>'') );

	    $title = htmlspecialchars($instance['title']);
	    $sequential = htmlspecialchars($instance['sequential']);
	    $private = htmlspecialchars($instance['private']);

	    # Output the options
	    echo '<p style="text-align:right;"><label for="' . $this->get_field_name('title') . '">' . __('Title:') . ' <input id="' . $this->get_field_id('title') . '" name="' . $this->get_field_name('title') . '" type="text" value="' . $title . '" /></label></p>';
	}

	/**
	 * Process the widget's data on submission.
	 * 
	 */
	function submit($instance) {
		die( 'function PP_Feedback_Widget::submit() must be over-ridden in feedback widget sub-class.' );
	}
}
add_action( 'widgets_init', create_function( '', "register_widget('Pp_Five_Stars_Fidget');" ) );

?>