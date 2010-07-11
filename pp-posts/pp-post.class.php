<?php
/**
 * The Core Prospress Post Object.
 * 
 * This class forms the basis for all market systems. It provides a framework for creating a new market
 * systems and is extended to implement the core market systems, eg. Auction, that ship with Prospress.
 * 
 * The class takes care of the control logic and other generic functions and defines a few abstract 
 * functions for implementing your market specific code, but you can also overide many of its
 * other functions to create novel market types.
 * 
 * Extend this class to create a new bid system and implement PP_Market_System::bid_form_fields(), 
 * PP_Market_System::bid_form_submit(), PP_Market_System::bid_form_validate(), PP_Market_System::view_details(),
 * PP_Market_System::view_list() and PP_Market_System::post_fields().
 *
 * @package Prospress
 * @version 0.1
 */

/** @TODO Refactor this class to create a bid object. The class currently fulfills too many roles, need a separate bid object class. */
class PP_Post {

	public $name;					// Public name of the market system e.g. "Auctions".

	public function __construct( $name ) {

		$this->name = (string)$name;

		add_filter( '', array( &$this, '' ), 10, 2 );

	}

}



/** 
 * Prospress posts are not your vanilla WordPress post, they have special meta which needs to
 * be presented in a special way. They also need to be sorted and filtered to make them easier to
 * browse and compare. That's why this function redirects individual Prospress posts to a default
 * template for single posts - pp-single.php - and the auto-generated Prospress index page to a 
 * special index template - pp-index.php. 
 * 
 * However, before doing so, it provides a hook for overriding the templates and also checks if the 
 * current theme has Prospress compatible templates.
 * 
 * @package Prospress
 * @subpackage Posts
 * @since 0.1
 */
function pp_template_redirects() {
	global $post, $market_system, $pp_use_custom_taxonomies;

	if ( $pp_use_custom_taxonomies && is_pp_multitax() ) {

		do_action( 'pp_taxonomy_template_redirect' );

		if( file_exists( TEMPLATEPATH . '/pp-taxonomy-' . $market_system->name() . '.php' ) )
			include( TEMPLATEPATH . '/pp-taxonomy-' . $market_system->name() . '.php' );
		elseif( file_exists( TEMPLATEPATH . '/taxonomy-' . $market_system->name() . '.php' ) )
			include( TEMPLATEPATH . '/taxonomy-' . $market_system->name() . '.php' );
		else
			include( PP_POSTS_DIR . '/pp-taxonomy-' . $market_system->name() . '.php' );
		exit;

	} elseif( $post->post_name == $market_system->name() && TEMPLATEPATH . '/page.php' == get_page_template() ){ // No template set for default Prospress index

		do_action( 'pp_index_template_redirect' );

		if( file_exists( TEMPLATEPATH . '/pp-index-' . $market_system->name() . '.php' ) )	// Theme supports Prospress
			include( TEMPLATEPATH . '/pp-index-' . $market_system->name() . '.php' );
		elseif( file_exists( TEMPLATEPATH . '/index-' . $market_system->name() . '.php' ) )	// Copied the default template to the them directory?
			include( TEMPLATEPATH . '/index-' . $market_system->name() . '.php' );
		else   																				// Default
			include( PP_POSTS_DIR . '/pp-index-' . $market_system->name() . '.php' );
		exit;

	} elseif ( $post->post_type == $market_system->name() && is_single() && !isset( $_GET[ 's' ] ) ) {

		do_action( 'pp_single_template_redirect' );

		if( file_exists( TEMPLATEPATH . '/single-' . $market_system->singular_name() . '.php' ) )
			include( TEMPLATEPATH . '/single-' . $market_system->singular_name() . '.php' );
		elseif( file_exists( TEMPLATEPATH . '/pp-single-' . $market_system->singular_name() . '.php' ) )
			include( TEMPLATEPATH . '/pp-single-' . $market_system->singular_name() . '.php' );
		else
			include( PP_POSTS_DIR . '/pp-single-' . $market_system->singular_name() . '.php' );
		exit;
	}
}
add_action( 'template_redirect', 'pp_template_redirects' );


/** 
 * A custom post type especially for Prospress posts. 
 * 
 * Admin's may want to allow or disallow users to create, edit and delete marketplace posts. 
 * To do this without relying on the post capability type, Prospress creates it's own type. 
 * 
 * @package Prospress
 * @since 0.1
 * 
 * @global PP_Market_System $market_system Prospress market system object for this marketplace.
 */
function pp_post_type() {
	global $market_system;

	$args = array(
			'label' 	=> $market_system->display_name(),
			'public' 	=> true,
			'show_ui' 	=> true,
			'rewrite' 	=> array( 'slug' => $market_system->name(), 'with_front' => false ),
			'capability_type' => 'prospress_post', //generic to cover multiple Prospress marketplace types
			'show_in_nav_menus' => false,
			'exclude_from_search' => true,
			'menu_icon' => PP_PLUGIN_URL . '/images/auctions16.png',
			'supports' 	=> array(
							'title',
							'editor',
							'thumbnail',
							'post-thumbnails',
							'comments',
							'revisions' ),
			'labels'	=> array( 'name'	=> $market_system->display_name(),
							'singular_name'	=> $market_system->singular_name(),
							'add_new_item'	=> sprintf( __( 'Add New %s', 'prospress' ), $market_system->singular_name() ),
							'edit_item'		=> sprintf( __( 'Edit %s', 'prospress' ), $market_system->singular_name() ),
							'new_item'		=> sprintf( __( 'New %s', 'prospress' ), $market_system->singular_name() ),
							'view_item'		=> sprintf( __( 'View %s', 'prospress' ), $market_system->singular_name() ),
							'search_items'	=> sprintf( __( 'Seach %s', 'prospress' ), $market_system->display_name() ),
							'not_found'		=> sprintf( __( 'No %s found', 'prospress' ), $market_system->display_name() ),
							'not_found_in_trash' => sprintf( __( 'No %s found in Trash', 'prospress' ), $market_system->display_name() ) )
				);

	register_post_type( $market_system->name(), $args );
}
add_action( 'init', 'pp_post_type' );


/** 
 * Create default sidebars for Prospress pages if the current theme doesn't support Prospress.
 *
 * The index sidebar automatically has the Sort and Filter widgets added to it on activation. 
 * 
 * @package Prospress
 * @subpackage Posts
 * @since 0.1
 */
function pp_register_sidebars(){
	global $market_system;
	
	if ( !file_exists( TEMPLATEPATH . '/pp-index-' . $market_system->name() . '.php' ) ) {
		register_sidebar( array (
			'name' => $market_system->display_name() . ' ' . __( 'Index Sidebar', 'prospress' ),
			'id' => $market_system->name() . '-index-sidebar',
			'description' => sprintf( __( "The sidebar on the %s index.", 'prospress' ), $market_system->display_name() ),
			'before_widget' => '<li id="%1$s" class="widget-container %2$s">',
			'after_widget' => "</li>",
			'before_title' => '<h3 class="widget-title">',
			'after_title' => '</h3>'
		) );
	}

	if ( !file_exists( TEMPLATEPATH . '/single-' . $market_system->singular_name() . '.php' ) && !file_exists( TEMPLATEPATH . '/pp-single-' . $market_system->singular_name() . '.php' ) ) {
		register_sidebar( array (
			'name' => sprintf( __( 'Single %s Sidebar', 'prospress' ), $market_system->singular_name() ),
			'id' => $market_system->name() . '-single-sidebar',
			'description' => sprintf( __( "The sidebar on a single %s.", 'prospress' ), $market_system->singular_name() ),
			'before_widget' => '<li id="%1$s" class="widget-container %2$s">',
			'after_widget' => "</li>",
			'before_title' => '<h3 class="widget-title">',
			'after_title' => '</h3>'
		) );
	}
}
add_action( 'init', 'pp_register_sidebars' );


/** 
 * Add the Sort and Filter widgets to the default Prospress sidebar. This function is called on 
 * Prospress' activation to help get everything working with one-click.
 * 
 * @package Prospress
 * @subpackage Posts
 * @since 0.1
 */
function pp_add_sidebars_widgets(){
	global $market_system;

	$sidebars_widgets = get_option( 'sidebars_widgets' );

	if( !isset( $sidebars_widgets[ $market_system->name() . '-index-sidebar' ] ) )
		$sidebars_widgets[ $market_system->name() . '-index-sidebar' ] = array();

	$sort_widget = get_option( 'widget_pp-sort' );
	if( empty( $sort_widget ) ){

		$sort_widget[] = array(
							'title' => __( 'Sort by:', 'prospress' ),
							'post-desc' => 'on',
							'post-asc' => 'on',
							'end-asc' => 'on',
							'end-desc' => 'on',
							'price-asc' => 'on',
							'price-desc' => 'on'
							);

		$sort_widget['_multiwidget'] = 1;
		update_option( 'widget_pp-sort',$sort_widget );
		array_push( $sidebars_widgets[ $market_system->name() . '-index-sidebar' ], 'pp-sort-0' );
	}

	$filter_widget = '';
	if( empty( $filter_widget ) ){

		$filter_widget[] = array( 'title' => __( 'Price:', 'prospress' ) );

		$filter_widget['_multiwidget'] = 1;
		update_option( 'widget_bid-filter', $filter_widget );
		array_push( $sidebars_widgets[ $market_system->name() . '-index-sidebar' ], 'bid-filter-0' );
	}

	update_option( 'sidebars_widgets', $sidebars_widgets );
}
//add_action( 'pp_activation', 'pp_posts_install' );




/** 
 * A boolean function to centralise the check for whether the current page is a Prospress posts admin page. 
 *
 * This is required when enqueuing scripts, styles and performing other Prospress post admin page 
 * specific functions so it makes sense to centralise it. 
 * 
 * @package Prospress
 * @subpackage Posts
 * @since 0.1
 */
function is_pp_post_admin_page(){
	global $market_system, $post;

	if( $_GET[ 'post_type' ] == $market_system->name() || $_GET[ 'post' ] == $market_system->name() || $post->post_type == $market_system->name() )
		return true;
	else
		return false;
}




/** 
 * Removes the Prospress index page from the search results as it's meant to be an empty place-holder.
 * 
 * @package Prospress
 * @subpackage Posts
 * @since 0.1
 */
function pp_remove_index( $search ){
	global $wpdb, $market_system;

	if ( isset( $_GET['s'] ) ) // remove index post from search results
		$search .= "AND ID != " . $market_system->get_index_url() . " ";

	return $search;
}
add_filter( 'posts_search', 'pp_remove_index' );



/** 
 * Clean up anything added on activation that does not need to persist incase of reactivation. 
 * 
 * @package Prospress
 * @subpackage Posts
 * @since 0.1
 */
function pp_post_deactivate(){
	global $market_system;

	//delete_option( 'widget_bid-filter' );
	//delete_option( 'widget_pp-sort' );

	//$sidebars_widgets = get_option( 'sidebars_widgets' );

	//if( isset( $sidebars_widgets[ $market_system->name() . '-index-sidebar' ] ) ){
	//	unset( $sidebars_widgets[ $market_system->name() . '-index-sidebar' ] );
	//	update_option( 'sidebars_widgets', $sidebars_widgets );
	//}
	//if( isset( $sidebars_widgets[ $market_system->name() . '-single-sidebar' ] ) ){
	//	unset( $sidebars_widgets[ $market_system->name() . '-single-sidebar' ] );
	//	update_option( 'sidebars_widgets', $sidebars_widgets );
	//}

}
add_action( 'pp_deactivation', 'pp_post_deactivate' );


/** 
 * When Prospress is uninstalled completely, remove that nasty index page created on activation. 
 * 
 * @package Prospress
 * @subpackage Posts
 * @since 0.1
 */
function pp_post_uninstall(){
	global $wpdb, $market_system;

	if ( !current_user_can( 'edit_plugins' ) || !function_exists( 'delete_site_option' ) )
		return false;

	wp_delete_post( $market_system->get_index_id() );

	$pp_post_ids = $wpdb->get_col($wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type = '" . $market_system->name() . "'" ) );

	if ( $pp_post_ids )
		foreach ( $pp_post_ids as $pp_post_id )
			wp_delete_post( $pp_post_id );
}
add_action( 'pp_uninstall', 'pp_post_uninstall' );
