<?php

/**
 * Feedback widget back class.
 * 
 * This class must be extended to create a feedback widget and Feedback_Widget::widget(), 
 * Feedback_Widget::update(), Feedback_Widget::form() and Feedback_Widget::form_submit() 
 * need to be over-ridden.
 *
 * This class extends the WP_Widget class. All member functions that must be over ridden
 * and functions that need to be called by the WP_Widget class have been reimplemented 
 * to aid understanding.
 * 
 * In addition to the WP_Widget member functions, this class adds a method to process feedback forms 
 * upon submission. This method must be over-ridden with the logic for processing the data your feedback 
 * widget submits.
 */

/** @TODO add feedback display function. Requiring column heading (eg. widget or feedback data name) and value. */

class PP_Feedback_Widget extends WP_Widget {

	// Member functions that you must over-ride.

	/** 
	 * Prints the widget's form fields as part of the feedback form.
	 *
	 * Subclasses must over-ride this function to generate their feedback form fields.
	 *
	 * @param array $args Display arguments including before_title, after_title, before_widget, and after_widget.
	 * @param array $instance The settings for the particular instance of the widget
	 */
	function widget($args, $instance) {
		die('function PP_Feedback_Widget::widget() must be over-ridden in your feedback widget sub-class.');
	}

	/** 
	 * Update a particular instance of the widget.
	 *
	 * This function should check that $new_instance is set correctly.
	 * The newly calculated value of $instance should be returned.
	 * If "false" is returned, the instance won't be saved/updated.
	 *
	 * @param array $new_instance New settings for this instance as input by the user via form()
	 * @param array $old_instance Old settings for this instance
	 * @return array Settings to save or bool false to cancel saving
	 */
	function update($new_instance, $old_instance) {
		return $new_instance;
	}

	/**
	 * Displays the settings form in the Feedback Widget admin screen.
	 *
	 * @param array $instance Current settings
	 */
	function form($instance) {
		echo '<p class="no-options-widget">' . __('There are no options for this widget.') . '</p>';
		return 'noform';
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

	// Functions you'll need to call.

	/**
	 * PHP4 constructor
	 */
	function PP_Feedback_Widget( $id_base = false, $name, $widget_options = array(), $control_options = array() ) {
		$this->__construct( $id_base, $name, $widget_options, $control_options );
	}

	/**
	 * PHP5 constructor
	 *
	 * @param string $id_base Optional Base ID for the widget, lower case,
	 * if left empty a portion of the widget's class name will be used. Has to be unique.
	 * @param string $name Name for the widget displayed on the configuration page.
	 * @param array $widget_options Optional Passed to wp_register_sidebar_widget()
	 *	 - description: shown on the configuration page
	 *	 - classname
	 * @param array $control_options Optional Passed to wp_register_widget_control()
	 *	 - width: required if more than 250px
	 *	 - height: currently not used but may be needed in the future
	 */
	function __construct( $id_base = false, $name, $widget_options = array(), $control_options = array() ) {
		$this->id_base = empty($id_base) ? preg_replace( '/(wp_)?fidget_/', '', strtolower(get_class($this)) ) : 'fidget_' . strtolower($id_base);
		$this->name = $name;
		$this->option_name = $this->id_base; //'fidget_' . $this->id_base;
		$this->widget_options = wp_parse_args( $widget_options, array('classname' => $this->option_name) );
		$this->control_options = wp_parse_args( $control_options, array('id_base' => $this->id_base) );
	}

	/**
	 * Constructs name attributes for use in form() fields
	 *
	 * This function should be used in form() methods to create name attributes for fields to be saved by update()
	 *
	 * @param string $field_name Field name
	 * @return string Name attribute for $field_name
	 */
	function get_field_name($field_name) {
		return 'fidget-' . $this->id_base . '[' . $this->number . '][' . $field_name . ']';
	}

	/**
	 * Constructs id attributes for use in form() fields
	 *
	 * This function should be used in form() methods to create id attributes for fields to be saved by update()
	 *
	 * @param string $field_name Field name
	 * @return string ID attribute for $field_name
	 */
	function get_field_id($field_name) {
		return 'fidget-' . $this->id_base . '-' . $this->number . '-' . $field_name;
	}
}


/* Template tags & API functions */

//**************************************************************************************************//
// Functions to add feedback pages to admin interface
//**************************************************************************************************//

function pp_add_feedback_widgets_admin_pages() {

	// Add feedback widgets submenu to the settings top-level menu
	if (function_exists('add_options_page')){
		$feedback_widgets_page = add_options_page('Feedback Widgets', 'Feedback Widgets', 58, 'feedback-widgets', 'pp_feedback_widgets_admin');
	} else {
		$feedback_widgets_page = add_submenu_page('options-general.php', 'Feedback Widgets', 'Feedback Widgets', 58, 'feedback-widgets', 'pp_feedback_widgets_admin');
	}
    
}
add_action('admin_menu', 'pp_add_feedback_widgets_admin_pages');

/**
 * Prints the administration interface for configuring feedback form widgets.
 * 
 **/
function pp_feedback_widgets_admin() {
	//global $wp_registered_sidebars;
	global $pp_registered_feedback_bars, $sidebars_widgets;

	require_once(ABSPATH . 'wp-admin/includes/widgets.php'); //widgets API

	do_action( 'feedback_bar_admin_setup' );

	//retrieve_fidgets();

	// These are the widgets grouped by sidebar
	$sidebars_widgets = wp_get_sidebars_widgets();
	//$sidebars_widgets = pp_get_feedback_bars_widgets();
	if ( empty( $sidebars_widgets ) )
		$sidebars_widgets = wp_get_widget_defaults();

	error_log('$sidebars_widgets = ' . print_r($sidebars_widgets, true));

	$title = __( 'Feedback Widgets' );

	$widgets_access = get_user_setting( 'widgets_access' );

	if ( isset($_GET['widgets-access']) ) {
		$widgets_access = 'on' == $_GET['widgets-access'] ? 'on' : 'off';
		set_user_setting( 'widgets_access', $widgets_access );
	}

	if ( 'on' == $widgets_access )
		add_filter( 'admin_body_class', create_function('', '{return " widgets_access ";}') );

	$messages = array(
		__('Changes saved.')
	);

	$errors = array(
		__('Error while saving.'),
		__('Error in displaying the widget settings form.')
	);

	include_once(PP_FEEDBACK_DIR . '/feedback-widgets-admin-view.php');

	do_action( 'feedback_bar_admin_page' );
}


// Enqueue scripts & CSS for feedback widgets admin page
function pp_feedback_widgets_admin_head(){
	if ( strpos( $_SERVER['REQUEST_URI'], 'feedback-widgets' ) !== false ){
		wp_enqueue_style( 'widgets' );
		wp_enqueue_script( 'admin-widgets' );
	} elseif ( strpos( $_SERVER['REQUEST_URI'], 'widgets.php' ) !== false ) { // hide feedback widgets on other widget pages
		/** @TODO would be nice to do this server side, but it's not possible at present without modifying core code in wp_list_widgets function. */
		wp_enqueue_script( 'hide-fidgets', PP_FEEDBACK_URL . '/hide-fidgets.js', array('jquery') );
	}
	//wp_admin_css( 'widgets' );
}
add_action('admin_print_styles', 'pp_feedback_widgets_admin_head'); // admin_head hook happens too late

// Create core feedback bar
if ( function_exists( 'register_feedback_bar') ) {
	register_feedback_bar( array(
		'name' => __( 'Feedback Bar' ),
		'id' => 'feedback_bar',
		'before_widget' => '',
		'after_widget' => '',
		'before_title' => '',
		'after_title' => '',
	));
}



// ************************************************************************************
// ************************************************************************************

/* Set Global Variables */

/** @ignore */
global $pp_registered_feedback_bars, $pp_registered_fidgets, $pp_registered_fidget_controls, $pp_registered_fidget_updates;

$pp_registered_feedback_bars = array();

/**
 * Sets Global variables for feedback widgets and feedback side bars.
 * 
 * This must be done in a function and run on widgets_init as it makes use of wp widget globals
 * that are not set until after this time. 
 *
 * @global array $pp_registered_feedback_bars
 */
function pp_fidgets_set_globals(){
	global $pp_registered_feedback_bars; //Stores the feedback bars, since themes can have more than one.
	global $wp_registered_sidebars;
	
	foreach ( $wp_registered_sidebars as $sidebar ) {
		if ( substr_count( strtolower( $sidebar['id'] ), 'feedback_bar' ) >= 1 )
			$pp_registered_feedback_bars[ $sidebar[ 'id' ] ] = $sidebar;
	}

	if ( empty( $pp_registered_feedback_bars ) )
		$pp_registered_feedback_bars = array();
}
add_action('widgets_init', 'pp_fidgets_set_globals');

/**
 * Stores the registered widgets.
 *
 * @global array $pp_registered_fidgets
 * @since 2.2.0
 */
$pp_registered_fidgets = array();

/**
 * Stores the registered widget control (options).
 *
 * @global array $pp_registered_fidget_controls
 * @since 2.2.0
 */
$pp_registered_fidget_controls = array();
$pp_registered_fidget_updates = array();

/**
 * Private
 */
$_pp_sidebars_fidgets = array();



/**
 * Builds the definition for a single feedback bar and returns the ID.
 *
 * For feedback bar identification, this function ensures the sidebar id is prepended with 'feedback_bar'.
 * It then calls 'register_sidebar' to actually register the sidebar.
 *
 * @since 2.2.0
 * @uses $pp_registered_feedback_bars Stores the new sidebar in this array by sidebar ID.
 * @uses parse_str() Converts a string to an array to be used in the rest of the function.
 * @usedby register_sidebars()
 *
 * @param string|array $args Builds Sidebar based off of 'name' and 'id' values
 * @return string The sidebar id that was added.
 */
function register_feedback_bar($args = array()) {
	global $pp_registered_feedback_bars;

	if ( is_string($args) )
		parse_str($args, $args);
	
	if(!array_key_exists('id',$args)){
		$i = count($pp_registered_feedback_bars) + 1;
		$args['id'] = "feedback_bar-$i";
		//error_log('array_key_exists... not!');
	} else if( substr_count(strtolower($args['id']), 'feedback_bar' ) < 1 ) {//If id does not contain 'feedback_bar', prepend it
		$args['id'] = 'feedback_bar-' . $args['id'];
		//error_log('substr_count... < 1!');
	}

	return register_sidebar($args);
}

/**
 * Removes a feedback bar from the list.
 *
 * @since 2.2.0
 *
 * @uses $wp_registered_sidebars Removes the feedback bar in this array by feedback bar ID.
 *
 * @param string $name The ID of the feedback bar when it was added.
 */
function unregister_feedback_bar( $name ) {
	global $wp_registered_sidebars;

	if ( isset( $wp_registered_sidebars[$name] ) )
		unset( $wp_registered_sidebars[$name] );
}

/**
 * Display dynamic feedback bar.
 *
 * @since 2.2.0
 *
 * @param int|string $index Optional, default is 1. Name or ID of dynamic feedback bar.
 * @return bool True, if widget feedback bar was found and called. False if not found or not called.
 */
function dynamic_feedback_bar($index = 1) {
	return dynamic_feedback_bar($index);
}


function pp_list_feedback_widgets(){
	global $wp_registered_widgets, $sidebars_widgets, $wp_registered_widget_controls;

	$sort = $wp_registered_widgets;
	usort( $sort, create_function( '$a, $b', 'return strnatcasecmp( $a["name"], $b["name"] );' ) );
	//error_log("sort = " . print_r($sort, true));
	$done = array();

	foreach ( $sort as $widget ) {
		if ( in_array( $widget['callback'], $done, true ) ) // We already showed this multi-widget
			continue;

		if( 1 != preg_match('/^fidget/', $widget['callback'][0]->id_base)) { //Ignore not feedback widgets
			continue;
		}

		$sidebar = is_active_widget( $widget['callback'], $widget['id'], false, false );
		$done[] = $widget['callback'];

		if ( ! isset( $widget['params'][0] ) )
			$widget['params'][0] = array();

		$args = array( 'widget_id' => $widget['id'], 'widget_name' => $widget['name'], '_display' => 'template' );

		if ( isset($wp_registered_widget_controls[$widget['id']]['id_base']) && isset($widget['params'][0]['number']) ) {
			$id_base = $wp_registered_widget_controls[$widget['id']]['id_base'];
			$args['_temp_id'] = "$id_base-__i__";
			$args['_multi_num'] = next_widget_id_number($id_base);
			$args['_add'] = 'multi';
		} else {
			$args['_add'] = 'single';
			if ( $sidebar )
				$args['_hide'] = '1';
		}

		$args = wp_list_widget_controls_dynamic_sidebar( array( 0 => $args, 1 => $widget['params'][0] ) );
		call_user_func_array( 'wp_widget_control', $args );
	}
}

?>