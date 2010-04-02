<?php

/**
 * Extend the Prospress Bid Widget class to create custom widgets for the bid form (bidbar).
 * 
 * This class must be extended for each bid widget and Bid_Widget::widget(), Bid_Widget::update(),
 * Bid_Widget::form() and Bid_Widget::process_bid() need to be over-ridden.
 *
 * This class subclasses the WP_Widget class. All member functions that must be over ridden
 * and functions that need to be called have been reimplemented for ease of understanding. 
 * 
 * In addition to the WP_Widget member functions, this class adds a method for processing bids.
 * This method must be over-ridden with the logic for processing the data your bid widget submits.
 */
/** 
 * @TODO add bid display function. Requiring column heading (eg. widget or bid data name) and value.
 */
class PP_Bid_Widget extends WP_Widget {

	// Member functions that you must over-ride.

	/** 
	 * Echo the widget's content in the bid form.
	 *
	 * Subclasses must over-ride this function to generate their bid form fields.
	 *
	 * @param array $args Display arguments including before_title, after_title, before_widget, and after_widget.
	 * @param array $instance The settings for the particular instance of the widget
	 */
	function widget($args, $instance) {
		die('function PP_Bid_Widget::widget() must be over-ridden in your bid widget sub-class.');
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
	 * Displays the settings form in the Bid Widget admin screen.
	 *
	 * @param array $instance Current settings
	 */
	function form($instance) {
		echo '<p class="no-options-widget">' . __('There are no options for this widget.') . '</p>';
		return 'noform';
	}

	/**
	 * Logic to process the widget's data on submission.
	 * 
	 * Data should be stored as an associative array in the bidsmeta table with a metakey of "bid_field".
	 *
	 * @param array $instance Current settings
	 */
	function process_bid( $instance ) {
		die('function PP_Bid_Widget::process_bid() must be over-ridden in your bid widget sub-class.');
	}

	// Functions you'll need to call.

	/**
	 * PHP4 constructor
	 */
	function PP_Bid_Widget( $id_base = false, $name, $widget_options = array(), $control_options = array() ) {
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
		$this->id_base = empty($id_base) ? preg_replace( '/(wp_)?bidget_/', '', strtolower(get_class($this)) ) : 'bidget_' . strtolower($id_base);
		$this->name = $name;
		$this->option_name = 'bidget_' . $this->id_base;
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
		return 'widget-' . $this->id_base . '[' . $this->number . '][' . $field_name . ']';
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
		return 'widget-' . $this->id_base . '-' . $this->number . '-' . $field_name;
	}
}



/**
 * 	Adds bid widgets admin page to admin menu
 * 
 * @uses add_options_page to add administration pages for bid settings
 * @uses add_submenu_page to add "Bids" and "Bid History" submenus to "Bids"
 * @return false if logged in user is not the site admin
 **/
function pp_add_bid_widgets_admin_pages() {

    // Add a bid fields submenu to the settings top-level menu
	if (function_exists('add_options_page')){
		$bid_widgets_page = add_options_page('Bid Widgets', 'Bid Widgets', 58, 'bid-widgets', 'pp_bid_widgets_admin');
	} else {
		$bid_widgets_page = add_submenu_page('options-general.php', 'Bid Widgets', 'Bid Widgets', 58, 'bid-widgets', 'pp_bid_widgets_admin');
	}
}
add_action('admin_menu', 'pp_add_bid_widgets_admin_pages');

// Enqueue scripts & CSS for bid fields admin page
function pp_bid_widgets_admin_head(){
	if(strpos($_SERVER['REQUEST_URI'], 'bid-widgets') !== false ){
		wp_enqueue_style('widgets');
		wp_enqueue_script('admin-widgets');
	} elseif ( strpos( $_SERVER['REQUEST_URI'], 'widgets.php' ) !== false ) {
		wp_enqueue_script( 'hide-bidgets', PP_BIDS_URL . '/hide-bidgets.js', array('jquery') );
	}
	//wp_admin_css( 'widgets' );
}
//add_action('init', 'pp_bid_widgets_admin_head');
//add_action('admin_head', 'pp_bid_widgets_admin_head'); // Too late
add_action('admin_print_styles', 'pp_bid_widgets_admin_head'); // admin_head hook happens too late


function pp_bid_widgets_admin() {
	//global $wp_registered_sidebars;
	global $pp_registered_bidbars, $sidebars_widgets;

	require_once(ABSPATH . 'wp-admin/includes/widgets.php'); //widgets API

	do_action( 'bidbar_admin_setup' );

	//retrieve_bidgets();

	// These are the widgets grouped by sidebar
	$sidebars_widgets = wp_get_sidebars_widgets();
	//$sidebars_widgets = pp_get_bidbars_widgets();
	if ( empty( $sidebars_widgets ) )
		$sidebars_widgets = wp_get_widget_defaults();

	error_log('$sidebars_widgets = ' . print_r($sidebars_widgets, true));

	$title = __( 'Bid Widgets' );

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

	include_once(PP_BIDS_DIR . '/bid-widgets-admin-view.php');

	do_action( 'bidbar_admin_page' );
}


/* Template tags & API functions */

// ************************************************************************************
// ************************************************************************************

/* Set Global Variables */

/** @ignore */
//global $pp_registered_bidbars, $pp_registered_bidgets, $pp_registered_bidget_controls, $pp_registered_bidget_updates;

/**
 * Sets Global variables for bid widgets and bid side bars.
 * 
 * This must be done in a function and run on widgets_init as it makes use of wp widget globals
 * that are not set until after this time. 
 *
 * @global array $pp_registered_bidbars
 */
function pp_bidgets_set_globals(){
	global $pp_registered_bidbars; //Stores the bid bars, since themes can have more than one.
	global $wp_registered_sidebars;
	
	foreach ( $wp_registered_sidebars as $sidebar ) {
		if ( substr_count(strtolower($sidebar['id']), 'bidbar' ) >= 1 )
			$pp_registered_bidbars[$sidebar['id']] = $sidebar;
	}
	//error_log('From widgets_init $pp_registered_bidbars = ' . print_r($pp_registered_bidbars, true));
}
add_action('widgets_init', 'pp_bidgets_set_globals');

/**
 * Stores the registered widgets.
 *
 * @global array $pp_registered_bidgets
 * @since 2.2.0
 */
$pp_registered_bidgets = array();

/**
 * Stores the registered widget control (options).
 *
 * @global array $pp_registered_bidget_controls
 * @since 2.2.0
 */
$pp_registered_bidget_controls = array();
$pp_registered_bidget_updates = array();

/**
 * Private
 */
$_pp_sidebars_bidgets = array();


// ************************************************************************************
// ************************************************************************************

/* Template tags & API functions */


/**
 * Builds the definition for a single bid bar and returns the ID.
 *
 * For bid bar identification, this function ensures the sidebar id is prepended with 'bidbar'.
 * It then calls 'register_sidebar' to actually register the sidebar.
 *
 * @since 2.2.0
 * @uses $pp_registered_bidbars Stores the new sidebar in this array by sidebar ID.
 * @uses parse_str() Converts a string to an array to be used in the rest of the function.
 * @usedby register_sidebars()
 *
 * @param string|array $args Builds Sidebar based off of 'name' and 'id' values
 * @return string The sidebar id that was added.
 */
function register_bidbar($args = array()) {
	global $pp_registered_bidbars;

	if ( is_string($args) )
		parse_str($args, $args);
	
	if(!array_key_exists('id',$args)){
		$i = count($pp_registered_bidbars) + 1;
		$args['id'] = "bidbar-$i";
		//error_log('array_key_exists... not!');
	} else if( substr_count(strtolower($args['id']), 'bidbar' ) < 1 ) {//If id does not contain 'bidbar', prepend it
		$args['id'] = 'bidbar-' . $args['id'];
		//error_log('substr_count... < 1!');
	}

	return register_sidebar($args);
}

/**
 * Removes a bid bar from the list.
 *
 * @since 2.2.0
 *
 * @uses $wp_registered_sidebars Removes the bid bar in this array by bid bar ID.
 *
 * @param string $name The ID of the bid bar when it was added.
 */
function unregister_bidbar( $name ) {
	global $wp_registered_sidebars;

	if ( isset( $wp_registered_sidebars[$name] ) )
		unset( $wp_registered_sidebars[$name] );
}

/**
 * Display dynamic bid bar.
 *
 * @since 2.2.0
 *
 * @param int|string $index Optional, default is 1. Name or ID of dynamic bid bar.
 * @return bool True, if widget bid bar was found and called. False if not found or not called.
 */
function dynamic_bidbar($index = 1) {
	return dynamic_bidbar($index);
}


function pp_list_bid_widgets(){
	global $wp_registered_widgets, $sidebars_widgets, $wp_registered_widget_controls;

	$sort = $wp_registered_widgets;
	usort( $sort, create_function( '$a, $b', 'return strnatcasecmp( $a["name"], $b["name"] );' ) );
	//error_log("sort = " . print_r($sort, true));
	$done = array();

	foreach ( $sort as $widget ) {
		if ( in_array( $widget['callback'], $done, true ) ) // We already showed this multi-widget
			continue;

		if( 1 != preg_match('/^bidget/', $widget['callback'][0]->id_base)) { //Ignore not bid widgets
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