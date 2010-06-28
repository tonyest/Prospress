<?php
/**
 * An assortment of widgets for outputting feedback items.
 *
 * @package Prospress
 * @author Brent Shepherd
 * @version 0.1
 */

class PP_Feedback_Score_Widget extends WP_Widget {
	function PP_Feedback_Score_Widget() {
		global $market_system; 
		/* Widget settings. */
		$widget_ops = array( 'classname' => 'pp-feedback-score', 'description' => sprintf( __('The feedback score for an author of an %s', 'prospress' ), $market_system->singular_name() ) );

		/* Widget control settings. */
		$control_ops = array( 'id_base' => 'pp-feedback-score' );

		/* Create the widget. */
		$this->WP_Widget( 'pp-feedback-score', __('Prospress Feedback Score', 'prospress' ), $widget_ops, $control_ops );
	}

	function widget( $args, $instance ) {

		if( !is_single() )
			return;

		extract( $args );

		echo $before_widget;

		echo $before_title;
		echo ( $instance['title'] ) ? $instance['title'] : __( 'Feedback Score:', 'prospress' );
		echo $after_title;

		the_users_feedback_items();

		echo $after_widget;
	}

	function form( $instance ) {

		$title = ( $instance['title'] ) ? $instance['title'] : __( 'Feedback Score:', 'prospress' );
		?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'prospress' ); ?> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" /></label></p>
		<?php
	}

	function update( $new_instance, $old_instance ) {

		$instance = $old_instance;

		/* Strip tags (if needed) and update the widget settings. */
		$instance['title'] = strip_tags( $new_instance['title'] );

		return $instance;
	}
}
add_action( 'widgets_init', create_function( '', 'return register_widget("PP_Feedback_Score_Widget");' ) );


class PP_Feedback_Latest_Widget extends WP_Widget {
	function PP_Feedback_Latest_Widget() {
		global $market_system; 
		/* Widget settings. */
		$widget_ops = array( 'classname' => 'pp-feedback-latest', 'description' => sprintf( __('The most recent feedback comment received by the author of an %s', 'prospress' ), $market_system->singular_name() ) );

		/* Widget control settings. */
		$control_ops = array( 'id_base' => 'pp-feedback-latest' );

		/* Create the widget. */
		$this->WP_Widget( 'pp-feedback-latest', __('Prospress Latest Feedback', 'prospress' ), $widget_ops, $control_ops );
	}

	function widget( $args, $instance ) {

		if( !is_single() )
			return;

		extract( $args );

		echo $before_widget;

		echo $before_title;
		echo ( $instance['title'] ) ? $instance['title'] : __( 'Latest Feedback:', 'prospress' );
		echo $after_title;

		the_most_recent_feedback();

		echo $after_widget;
	}

	function form( $instance ) {

		$title = ( $instance['title'] ) ? $instance['title'] : __( 'Latest Feedback:', 'prospress' );
		?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'prospress' ); ?> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" /></label></p>
		<?php
	}

	function update( $new_instance, $old_instance ) {

		$instance = $old_instance;

		/* Strip tags (if needed) and update the widget settings. */
		$instance['title'] = strip_tags( $new_instance['title'] );

		return $instance;
	}
}
add_action( 'widgets_init', create_function( '', 'return register_widget("PP_Feedback_Latest_Widget");' ) );
