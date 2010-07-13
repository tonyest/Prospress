<?php
/**
 * The Core Prospress Post Object.
 *
 * Each market type requires its own post type and a few functions that help put it into being. This 
 * class takes care of these functions and makes it possible for each market system to have it's own
 * object to take care of all of this.
 *
 * @package Prospress
 * @version 0.1
 */
class PP_Post {

	private $market_system;	// An array with details of the market system to which this post type belongs.

	public function __construct( $market_system ) {

		$this->market_system = $market_system;

		add_action( 'pp_activation', array( &$this, 'install' ) );

		add_action( 'init', array( &$this, 'register_post_type' ) );

		add_action( 'init', array( &$this, 'register_sidebars' ) );

		add_action( 'template_redirect', array( &$this, 'template_redirects' ) );

		add_action( 'pp_deactivation', array( &$this, 'post_deactivate' ) );

		add_action( 'pp_uninstall', array( &$this, 'uninstall' ) );
		
		add_filter( 'posts_search', array( &$this, 'remove_index' ) );

		if( is_using_custom_taxonomies() )
			// $this->taxonomy = new PP_Taxonomy;
	}


	/**
	 * Sets up Prospress environment with any settings required and/or shared across the 
	 * other components. 
	 *
	 * @uses is_site_admin() returns true if the current user is a site admin, false if not.
	 * @uses add_submenu_page() WP function to add a submenu item.
	 * @uses get_role() WP function to get the administrator role object and add capabilities to it.
	 *
	 * @global wpdb $wpdb WordPress DB access object.
	 * @global PP_Market_System $market_system Prospress market system object for this marketplace.
	 * @global WP_Rewrite $wp_rewrite WordPress Rewrite Component.
	 */
	public function install(){
		global $wpdb, $market_system, $wp_rewrite;

		if( $this->get_index_id() == false ){ // Need an index page for this post type
			$index_page = array();
			$index_page['post_title'] = $this->market_system[ 'display_name' ];
			$index_page['post_name'] = $this->market_system[ 'internal_name' ];
			$index_page['post_status'] = 'publish';
			$index_page['post_content'] = __( 'This is the index for your ' . $this->market_system[ 'display_name' ] . '. Your ' . $this->market_system[ 'display_name' ] . ' will automatically show up here, but you change this text to provide an introduction or instructions.', 'prospress' );
			$index_page['post_type'] = 'page';

			wp_insert_post( $index_page );
		}

		$this->add_sidebars_widgets();
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
	public function template_redirects() {
		global $post;

		if ( is_using_custom_taxonomies() && is_pp_multitax() ) {

			do_action( 'pp_taxonomy_template_redirect' );

			if( file_exists( TEMPLATEPATH . '/taxonomy-' . $this->market_system[ 'internal_name' ] . '.php' ) )
				include( TEMPLATEPATH . '/taxonomy-' . $this->market_system[ 'internal_name' ] . '.php' );
			elseif( file_exists( TEMPLATEPATH . '/pp-taxonomy-' . $this->market_system[ 'internal_name' ] . '.php' ) )
				include( TEMPLATEPATH . '/pp-taxonomy-' . $this->market_system[ 'internal_name' ] . '.php' );
			else
				include( PP_POSTS_DIR . '/pp-taxonomy-' . $this->market_system[ 'internal_name' ] . '.php' );
			exit;

		} elseif( $post->post_name == $this->market_system[ 'internal_name' ] && TEMPLATEPATH . '/page.php' == get_page_template() ){ // No template set for default Prospress index

			do_action( 'pp_index_template_redirect' );

			if( file_exists( TEMPLATEPATH . '/index-' . $this->market_system[ 'internal_name' ] . '.php' ) )	// Copied the default template to the them directory?
				include( TEMPLATEPATH . '/index-' . $this->market_system[ 'internal_name' ] . '.php' );
			elseif( file_exists( TEMPLATEPATH . '/pp-index-' . $this->market_system[ 'internal_name' ] . '.php' ) )	// Theme supports Prospress
				include( TEMPLATEPATH . '/pp-index-' . $this->market_system[ 'internal_name' ] . '.php' );
			else   																				// Default
				include( PP_POSTS_DIR . '/pp-index-' . $this->market_system[ 'internal_name' ] . '.php' );
			exit;

		} elseif ( $post->post_type == $this->market_system[ 'internal_name' ] && is_single() && !isset( $_GET[ 's' ] ) ) {

			do_action( 'pp_single_template_redirect' );

			if( file_exists( TEMPLATEPATH . '/single-' . $this->market_system[ 'singular_name' ] . '.php' ) )
				include( TEMPLATEPATH . '/single-' . $this->market_system[ 'singular_name' ] . '.php' );
			elseif( file_exists( TEMPLATEPATH . '/pp-single-' . $this->market_system[ 'singular_name' ] . '.php' ) )
				include( TEMPLATEPATH . '/pp-single-' . $this->market_system[ 'singular_name' ] . '.php' );
			else
				include( PP_POSTS_DIR . '/pp-single-' . $this->market_system[ 'singular_name' ] . '.php' );
			exit;
		}
	}


	/** 
	 * A custom post type especially for this market system's posts.
	 * 
	 * Admin's may want to allow or disallow users to create, edit and delete marketplace posts. 
	 * To do this without relying on the post capability type, Prospress creates it's own type. 
	 * 
	 * @package Prospress
	 * @since 0.1
	 * 
	 * @global PP_Market_System $market_system Prospress market system object for this marketplace.
	 */
	public function register_post_type() {

		$args = array(
				'label' 	=> $this->market_system[ 'internal_name' ],
				'public' 	=> true,
				'show_ui' 	=> true,
				'rewrite' 	=> array( 'slug' => $this->market_system[ 'internal_name' ], 'with_front' => false ),
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
				'labels'	=> array( 'name'	=> $this->market_system[ 'display_name' ],
								'singular_name'	=> $this->market_system[ 'singular_name' ],
								'add_new_item'	=> sprintf( __( 'Add New %s', 'prospress' ), $this->market_system[ 'singular_name' ] ),
								'edit_item'		=> sprintf( __( 'Edit %s', 'prospress' ), $this->market_system[ 'singular_name' ] ),
								'new_item'		=> sprintf( __( 'New %s', 'prospress' ), $this->market_system[ 'singular_name' ] ),
								'view_item'		=> sprintf( __( 'View %s', 'prospress' ), $this->market_system[ 'singular_name' ] ),
								'search_items'	=> sprintf( __( 'Seach %s', 'prospress' ), $this->market_system[ 'display_name' ] ),
								'not_found'		=> sprintf( __( 'No %s found', 'prospress' ), $this->market_system[ 'display_name' ] ),
								'not_found_in_trash' => sprintf( __( 'No %s found in Trash', 'prospress' ), $this->market_system[ 'display_name' ] ) )
					);

		register_post_type( $this->market_system[ 'internal_name' ], $args );
	}


	/** 
	 * Create default sidebars for Prospress pages if the current theme doesn't support Prospress.
	 *
	 * The index sidebar automatically has the Sort and Filter widgets added to it on activation. 
	 * 
	 * @package Prospress
	 * @subpackage Posts
	 * @since 0.1
	 */
	public function register_sidebars(){

		if ( !file_exists( TEMPLATEPATH . '/index-' . $this->market_system[ 'singular_name' ] . '.php' ) && !file_exists( TEMPLATEPATH . '/pp-index-' . $this->market_system[ 'internal_name' ] . '.php' ) ) {
			register_sidebar( array (
				'name' => $this->market_system[ 'display_name' ] . ' ' . __( 'Index Sidebar', 'prospress' ),
				'id' => $this->market_system[ 'internal_name' ] . '-index-sidebar',
				'description' => sprintf( __( "The sidebar on the %s index.", 'prospress' ), $this->market_system[ 'display_name' ] ),
				'before_widget' => '<li id="%1$s" class="widget-container %2$s">',
				'after_widget' => "</li>",
				'before_title' => '<h3 class="widget-title">',
				'after_title' => '</h3>'
			) );
		}

		if ( !file_exists( TEMPLATEPATH . '/single-' . $this->market_system[ 'singular_name' ] . '.php' ) && !file_exists( TEMPLATEPATH . '/pp-single-' . $this->market_system[ 'singular_name' ] . '.php' ) ) {
			register_sidebar( array (
				'name' => sprintf( __( 'Single %s Sidebar', 'prospress' ), $this->market_system[ 'singular_name' ] ),
				'id' => $this->market_system[ 'internal_name' ] . '-single-sidebar',
				'description' => sprintf( __( "The sidebar on a single %s.", 'prospress' ), $this->market_system[ 'singular_name' ] ),
				'before_widget' => '<li id="%1$s" class="widget-container %2$s">',
				'after_widget' => "</li>",
				'before_title' => '<h3 class="widget-title">',
				'after_title' => '</h3>'
			) );
		}
	}


	/** 
	 * Add the Sort and Filter widgets to the default Prospress sidebar. This function is called on 
	 * Prospress' activation to help get everything working with one-click.
	 * 
	 * @package Prospress
	 * @subpackage Posts
	 * @since 0.1
	 */
	public function add_sidebars_widgets(){

		$sidebars_widgets = get_option( 'sidebars_widgets' );

		if( !isset( $sidebars_widgets[ $this->market_system[ 'internal_name' ] . '-index-sidebar' ] ) )
			$sidebars_widgets[ $this->market_system[ 'internal_name' ] . '-index-sidebar' ] = array();

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
			array_push( $sidebars_widgets[ $this->market_system[ 'internal_name' ] . '-index-sidebar' ], 'pp-sort-0' );
		}

		$filter_widget = '';
		if( empty( $filter_widget ) ){

			$filter_widget[] = array( 'title' => __( 'Price:', 'prospress' ) );

			$filter_widget['_multiwidget'] = 1;
			update_option( 'widget_bid-filter', $filter_widget );
			array_push( $sidebars_widgets[ $this->market_system[ 'internal_name' ] . '-index-sidebar' ], 'bid-filter-0' );
		}

		update_option( 'sidebars_widgets', $sidebars_widgets );
	}


	/**
	 * A boolean function to centralise the logic for whether the current page is an admin page for this post type.
	 *
	 * This is required when enqueuing scripts, styles and performing other Prospress post admin page 
	 * specific functions so it makes sense to centralise it. 
	 * 
	 * @package Prospress
	 * @subpackage Posts
	 * @since 0.1
	 */
	public function is_post_admin_page(){
		global $post;

		if( $_GET[ 'post_type' ] == $this->market_system[ 'internal_name' ] || $_GET[ 'post' ] == $this->market_system[ 'internal_name' ] || $post->post_type == $this->market_system[ 'internal_name' ] )
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
	public function remove_index( $search ){
		global $wpdb;

		if ( isset( $_GET['s'] ) ) // remove index post from search results
			$search .= "AND ID != " . $this->get_index_id() . " ";

		return $search;
	}


	/** 
	 * Clean up anything added on activation that does not need to persist incase of reactivation. 
	 * 
	 * @package Prospress
	 * @subpackage Posts
	 * @since 0.1
	 */
	public function post_deactivate(){
		global $market_system;

		//delete_option( 'widget_bid-filter' );
		//delete_option( 'widget_pp-sort' );

		//$sidebars_widgets = get_option( 'sidebars_widgets' );

		//if( isset( $sidebars_widgets[ $this->market_system[ 'internal_name' ] . '-index-sidebar' ] ) ){
		//	unset( $sidebars_widgets[ $this->market_system[ 'internal_name' ] . '-index-sidebar' ] );
		//	update_option( 'sidebars_widgets', $sidebars_widgets );
		//}
		//if( isset( $sidebars_widgets[ $this->market_system[ 'internal_name' ] . '-single-sidebar' ] ) ){
		//	unset( $sidebars_widgets[ $this->market_system[ 'internal_name' ] . '-single-sidebar' ] );
		//	update_option( 'sidebars_widgets', $sidebars_widgets );
		//}

	}


	public function get_index_id() {
		global $wpdb;

		$index_id = $wpdb->get_var( "SELECT ID FROM $wpdb->posts WHERE post_name = '" . $this->market_system[ 'internal_name' ] . "'" );

		if( $index_id == NULL)
			return false; 
		else 
			return $index_id;
	}

	public function get_index_url() {

		$index_id = $this->get_index_id();

		if( $index_id == false )
			return false; 
		else
			return get_permalink( $index_id );
	}

	/** 
	 * When Prospress is uninstalled completely, remove that nasty index page created on activation. 
	 * 
	 * @package Prospress
	 * @subpackage Posts
	 * @since 0.1
	 */
	public function uninstall(){
		global $wpdb, $market_system;

		if ( !current_user_can( 'edit_plugins' ) || !function_exists( 'delete_site_option' ) )
			return false;

		wp_delete_post( $market_system->get_index_id() );

		$pp_post_ids = $wpdb->get_col($wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type = '" . $this->market_system[ 'internal_name' ] . "'" ) );

		if ( $pp_post_ids )
			foreach ( $pp_post_ids as $pp_post_id )
				wp_delete_post( $pp_post_id );
	}

}