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

	public $name;
	private $labels;

	public function __construct( $name, $args ) {

		$this->name = $name;
		$this->labels = $args[ 'labels' ];

		add_action( 'pp_activation', array( &$this, 'activate' ) );

		add_action( 'init', array( &$this, 'register_post_type' ) );

		add_action( 'pp_deactivation', array( &$this, 'deactivate' ) );

		add_action( 'pp_uninstall', array( &$this, 'uninstall' ) );
		
		add_filter( 'posts_search', array( &$this, 'remove_index' ) );

		add_filter( 'manage_' . $this->name . '_posts_columns', array( &$this, 'post_columns' ) );

		add_action( 'manage_posts_custom_column', array( &$this, 'post_custom_columns' ), 10, 2 );

		// If current theme doesn't support this market type, use default templates & add default widgets
		if( !current_theme_supports( $this->name ) ) {

			add_action( 'template_redirect', array( &$this, 'template_redirects' ) );
			add_action( 'init', array( &$this, 'register_sidebars' ) );
		}
	}


	/**
	 * Sets up Prospress environment with any settings required and/or shared across the 
	 * other components. 
	 *
	 * @uses is_site_admin() returns true if the current user is a site admin, false if not.
	 * @uses add_submenu_page() WP function to add a submenu item.
	 * @uses get_role() WP function to get the administrator role object and add capabilities to it.
	 * @uses add_post_meta
	 *
	 * @global wpdb $wpdb WordPress DB access object.
	 */
	public function activate(){
		global $wpdb;

		$meta_index_id = $this->get_index_id(); //get ID from wp_postmeta
		$index_page = get_post( $meta_index_id, ARRAY_A ); // get $post array from wp_posts

		if ( !empty( $index_page ) && isset( $meta_index_id ) ) { // page exists with prospress _index meta
			//make sure page is published as it get's trashed on plugin deactivation
			$index_page[ 'post_status' ] = 'publish';
			wp_update_post( $index_page );
		} else {
			// search for index page in prospress 1.0 method
			$index_id = $wpdb->get_var( "SELECT ID FROM $wpdb->posts WHERE post_name = '". $this->name. "'" );

			if ( isset( $meta_index_id ) && !isset($index_id) ) // meta exists without page
				delete_post_meta( $meta_index_id, '_pp_'. $this->name. '_index' ); // clean up old meta

			if ( !isset($index_page) && !empty($index_id) ) { // page exists without prospress _index meta
				add_post_meta( $index_id, '_pp_'. $this->name. '_index', 'is_index' , true ); // add meta to existing page
				$index_page = get_post( $index_id, ARRAY_A ); // get $post array from wp_posts
				$index_page[ 'post_status' ] = 'publish';
				wp_update_post( $index_page );	

			} else { // page does not exist

				//create new page & meta
				$index_page = array();
				$index_page['post_title']	=	$this->labels[ 'name' ];
				$index_page['post_name']	=	$this->name;
				$index_page['post_type']	=	'page';
				$index_page['post_status']	=	'publish';
				$index_page['post_content']	=	__( 'This is the index for your '. $this->labels[ 'name' ]. '. Your '. $this->labels[ 'name' ]. ' will automatically show up here, but you change this text to provide an introduction or instructions.', 'prospress' );

				$index_id = wp_insert_post( $index_page );
				add_post_meta( $index_id, '_pp_'. $this->name. '_index', 'is_index', true );	
			}
		}
			//add sample prospress sidebar widgets
			$this->add_index_sidebars_widgets();
			$this->add_single_sidebars_widgets();

		$this->register_post_type();
		// Update rewrites to account for this post type
		flush_rewrite_rules();
	}

	/** 
	 * Prospress posts are not your vanilla WordPress post, they have special meta that needs to
	 * be presented displayed. They also need to be sorted and filtered to make them easier to
	 * browse and compare. 
	 *
	 * This function firstly checks the theme's directory for suitable templates, which must be 
	 * named single-auctions.php, for displaying a single auction, index-auctions.php, for displaying
	 * the list of all auctions, and taxonomy-auctions.php, for displaying a list of items that
	 * are of a certain taxonomy. 
	 * 
	 * If no templates are found in the theme's directory, this function redirects to default
	 * templates that ship with Prospress.
	 * 
	 * @package Prospress
	 * @subpackage Posts
	 * @since 0.1
	 */
	public function template_redirects() {
		
		if ( is_pp_multitax() ) {

			$this->pp_get_query_template("taxonomy");

		} elseif( $this->is_index() ) { // No template set for default Prospress index

			$this->pp_get_query_template("index");
			
		} elseif ( $this->is_single() && is_single() && !isset( $_GET[ 's' ] ) ) {

			$this->pp_get_query_template("single");
		}
	}
	
	/** 
	 * Checks standard parent and child-theme locations for template as well as prospress plugin directory
	 * Can search optional template names along with Wordpress & Prospress defaults
	 * 
	 * @package Prospress
	 * @subpackage Posts
	 * @since 1.1.1
	 * @uses get_query_template
	 */
	public function pp_get_query_template( $template, $options = array() ) {

		global $market_systems;
		$market = $market_systems[ $this->name ];

		array_splice( $options, count($options), 0, array(
			$template."-auctions.php",
			"pp-".$template."-auctions.php",
			"template-".$template.".php" ) );
			
		$path = get_query_template( "", $options )? 
				get_query_template( "", $options ): 
				PP_POSTS_DIR . "/pp-" . $template . "-" . $this->name . ".php";

		do_action( 'pp_index_template_redirect' );

		wp_enqueue_style( 'prospress',  PP_CORE_URL . '/prospress.css' );

		include_once($path); exit; //exit to avoid wordpress loading natural single template page.
	}

	/** 
	 * A custom post type especially for this market system's posts.
	 * 
	 * Admin's may want to allow or disallow users to create, edit and delete marketplace posts. 
	 * To do this without relying on the post capability type, Prospress creates it's own type. 
	 * 
	 * @package Prospress
	 * @since 0.1
	 */
	public function register_post_type() {

		$args = array(
				'label' 	=> $this->labels[ 'name' ],
				'public' 	=> true,
				'show_ui' 	=> true,
				'rewrite' 	=> array( 'slug' => $this->name, 'with_front' => false ),
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
				'labels'	=> array( 'name'	=> $this->labels[ 'name' ],
								'singular_name'	=> $this->labels[ 'singular_name' ],
								'add_new_item'	=> sprintf( __( 'Add New %s', 'prospress' ), $this->labels[ 'singular_name' ] ),
								'edit_item'		=> sprintf( __( 'Edit %s', 'prospress' ), $this->labels[ 'singular_name' ] ),
								'new_item'		=> sprintf( __( 'New %s', 'prospress' ), $this->labels[ 'singular_name' ] ),
								'view_item'		=> sprintf( __( 'View %s', 'prospress' ), $this->labels[ 'singular_name' ] ),
								'search_items'	=> sprintf( __( 'Seach %s', 'prospress' ), $this->labels[ 'name' ] ),
								'not_found'		=> sprintf( __( 'No %s found', 'prospress' ), $this->labels[ 'name' ] ),
								'not_found_in_trash' => sprintf( __( 'No %s found in Trash', 'prospress' ), $this->labels[ 'name' ] ) )
					);

		$args = apply_filters( 'prospress_post_type_args', $args );
		$args = apply_filters( $this->name . '-post_type_args', $args );

		register_post_type( $this->name, $args );
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

			register_sidebar( array (
			'name' => sprintf( __( '%s Index Sidebar', 'prospress' ), $this->labels[ 'name' ] ),
			'id' => $this->name . '-index-sidebar',
			'description' => sprintf( __( "The sidebar for the index of your %s.", 'prospress' ), $this->labels[ 'name' ] ),
			'before_widget' => '<li id="%1$s" class="widget-container %2$s">',
			'after_widget' => "</li>",
			'before_title' => '<h3 class="widget-title">',
			'after_title' => '</h3>'
		) );

		register_sidebar( array (
			'name' => sprintf( __( 'Single %s Sidebar', 'prospress' ), $this->labels[ 'singular_name' ] ),
			'id' => $this->name . '-single-sidebar',
			'description' => sprintf( __( "The sidebar for a single %s.", 'prospress' ), $this->labels[ 'singular_name' ] ),
			'before_widget' => '<li id="%1$s" class="widget-container %2$s">',
			'after_widget' => "</li>",
			'before_title' => '<h3 class="widget-title">',
			'after_title' => '</h3>'
		) );
	}


	/**
	 * A boolean function to centralise the logic for whether the current page is an admin page for this post type.
	 *
	 * This is required when enqueuing scripts, styles and performing other Prospress post admin page 
	 * specific functions.
	 * 
	 * @package Prospress
	 * @subpackage Posts
	 * @since 0.1
	 */
	public function is_post_admin_page(){
		global $post;

		if( !is_admin() )
			return false;
		elseif( isset( $post->post_type ) && $post->post_type == $this->name ) // edit page
			return true;
		elseif( ( isset( $_GET[ 'post_type' ] ) && $_GET[ 'post_type' ] == $this->name ) || ( isset( $_GET[ 'post' ] ) && $_GET[ 'post' ] == $this->name ) )  // admin list page
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
			$search .= " AND ID != " . $this->get_index_id() . " ";

		return $search;
	}


	/**
	 * Template tag - is the current page/post the index for this market system's posts.
	 */
	public function is_index() {
		global $post;

		$pp_index_page = get_post_meta( $post->ID, '_pp_'. $this->name. '_index', true );

		return ( $pp_index_page == "is_index" ) ? true : false;
	}


	/**
	 * Template tag - is the current post a single post of this market system's type.
	 */
	public function is_single() {
		global $post;

		if( isset( $post->post_type ) && $post->post_type == $this->name )
			return true;
		else
			return false;
	}

	/*
	 *	Retrieves stored index page ID, returns null by default if option does not yet exist.
	 */
	public function get_index_id() {
		global $wpdb;

		$meta_key = '_pp_'. $this->name. '_index';
		$meta_value = "is_index";
		$index_id = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value = %s", $meta_key, $meta_value ));

		return $index_id;
	}

	public function get_index_permalink() {

		$index_id = $this->get_index_id();

		if( $index_id == false )
			return false; 
		else
			return get_permalink( $index_id );
	}

	/**
	 * Creates an anchor tag linking to the user's payments, optionally prints.
	 * 
	 */
	function the_add_new_url( $desc = "Add New", $echo = '' ) {

		$add_new_tag = "<a href='" . $this->get_add_new_url() . "' title='$desc'>$desc " . $this->labels[ 'singular_name' ] . "</a>";

		if( $echo == 'echo' )
			echo $add_new_tag;
		else
			return $add_new_tag;
	}


	/**
	 * Gets the url to the user's feedback table.
	 * 
	 */
	function get_add_new_url() {
		 return admin_url( '/post-new.php?post_type=' . $this->name );
	}


	/** 
	 * Prospress posts end and a post's end date/time is important enough to be shown on the posts 
	 * admin table. Completed posts also require follow up actions, so these actions are shown on 
	 * the posts admin table, but only for completed posts. 
	 *
	 * This function adds the end date and completed posts actions columns to the column headings array
	 * for Prospress posts admin tables. 
	 * 
	 * @package Prospress
	 * @subpackage Posts
	 * @since 0.1
	 */
	public function post_columns( $column_headings ) {

		if( !is_pp_post_admin_page() )
			return $column_headings;

		if( strpos( $_SERVER['REQUEST_URI'], 'completed' ) !== false ) {
			$column_headings[ 'end_date' ] = __( 'End Time', 'prospress' );
			$column_headings[ 'post_actions' ] = __( 'Action', 'prospress' );
			unset( $column_headings[ 'date' ] );
		} else {
			$column_headings[ 'date' ] = __( 'Date Published', 'prospress' );
			$column_headings[ 'end_date' ] = __( 'End Time', 'prospress' );
		}

		return $column_headings;
	}


	/** 
	 * The admin tables for Prospress posts have custom columns for Prospress specific information. 
	 * This function fills those columns with their appropriate information.
	 * 
	 * @package Prospress
	 * @subpackage Posts
	 * @since 0.1
	 */
	public function post_custom_columns( $column_name, $post_id ) {
		global $wpdb;

		if( $column_name == 'end_date' ) {
			$end_time_gmt = get_post_end_time( $post_id );

			if ( $end_time_gmt == false || empty( $end_time_gmt ) ) {
				$m_time = $human_time = __( 'Not set.', 'prospress' );
				$time_diff = 0;
			} else {
				$human_time = pp_human_interval( $end_time_gmt - time(), 3 );
				$human_time .= '<br/>' . get_post_end_time( $post_id, 'mysql', 'user' );
			}
			echo '<abbr>' . apply_filters( 'post_end_date_column', $human_time, $post_id, $column_name) . '</abbr>';
		} elseif( $column_name == 'post_actions' ) {
			$actions = apply_filters( 'completed_post_actions', array(), $post_id );
			if( is_array( $actions ) && !empty( $actions ) ){?>
				<div class="prospress-actions">
					<ul class="actions-list">
						<li class="base"><?php _e( 'Take action:', 'prospress' ) ?></li>
					<?php foreach( $actions as $action => $attributes )
						echo "<li class='action'><a href='" . add_query_arg ( array( 'action' => $action, 'post' => $post_id ) , $attributes['url'] ) . "'>" . $attributes['label'] . "</a></li>";
					 ?>
					</ul>
				</div>
			<?php
			} else {
				echo '<p>' . __( 'No action can be taken.', 'prospress' ) . '</p>';
			}
		}
	}


	/** 
	 * Add the Sort and Filter widgets to the default Prospress Index sidebar. This function is called on 
	 * Prospress' activation to help get everything working with one-click.
	 * 
	 * @package Prospress
	 * @subpackage Posts
	 * @since 0.1
	 **/
	public function add_index_sidebars_widgets(){

		$sidebars_widgets = wp_get_sidebars_widgets();

		if( !isset( $sidebars_widgets[ $this->name . '-index-sidebar' ] ) )
			$sidebars_widgets[ $this->name . '-index-sidebar' ] = array();

		$sort_widget = get_option( 'widget_pp-sort' );
		if( empty( $sort_widget ) ){ //sort widget not added to any sidebars yet

			$sort_widget['_multiwidget'] = 1;

			$sort_widget[] = array(
								'title' => __( 'Sort by:', 'prospress' ),
								'post-desc' => 'on',
								'post-asc' => 'on',
								'end-asc' => 'on',
								'end-desc' => 'on',
								'price-asc' => 'on',
								'price-desc' => 'on'
								);

			$widget_id = end( array_keys( $sort_widget ) );

			update_option( 'widget_pp-sort', $sort_widget );
			array_push( $sidebars_widgets[ $this->name . '-index-sidebar' ], 'pp-sort-' . $widget_id );
		}

		$filter_widget = get_option( 'widget_bid-filter' );
		if( empty( $filter_widget ) ){ //filter_widget widget not added to any sidebars yet

			$filter_widget['_multiwidget'] = 1;

			$filter_widget[] = array( 'title' => __( 'Price:', 'prospress' ) );

			$filter_id = end( array_keys( $filter_widget ) );

			update_option( 'widget_bid-filter', $filter_widget );
			array_push( $sidebars_widgets[ $this->name . '-index-sidebar' ], 'bid-filter-' . $filter_id );
		}

		wp_set_sidebars_widgets( $sidebars_widgets );
	}

	/** 
	 * Add the Taxonomy Widgets to single-auctions.This function is called on 
	 * Prospress' activation to help get everything working with one-click.
	 * 
	 * @package Prospress
	 * @subpackage Posts
	 * @since 1.1
	 **/
	public function add_single_sidebars_widgets(){

		$sidebars_widgets = wp_get_sidebars_widgets();

		if( !isset( $sidebars_widgets[ $this->name . '-single-sidebar' ] ) )
			$sidebars_widgets[ $this->name . '-single-sidebar' ] = array();

		$widget = get_option( 'widget_pp_countdown' );
		if( empty( $widget ) ){ //countdown widget not added to any sidebars yet

			$widget['_multiwidget'] = 1;

			$widget[] = array( 'title' => __( 'Ending:', 'prospress' ) );

			$widget_id = end( array_keys( $widget ) );

			update_option( 'widget_pp_countdown', $widget );
			array_push( $sidebars_widgets[ $this->name . '-single-sidebar' ], 'pp_countdown-' . $widget_id );
		}
		
		$widget = get_option( 'widget_pp_single_tax' );
		if( empty( $widget ) ){ //taxonomy lists widget not added to any sidebars yet

			$widget['_multiwidget'] = 1;

			$widget[] = array( 'title' => __( 'Details:', 'prospress' ) );

			$widget_id = end( array_keys( $widget ) );

			update_option( 'widget_pp_single_tax', $widget );
			array_push( $sidebars_widgets[ $this->name . '-single-sidebar' ], 'pp_single_tax-' . $widget_id );
		}

		$widget = get_option( 'widget_pp-feedback-latest' );
		if( empty( $widget ) ){ //latest feedback widget not added to any sidebars yet

			$widget['_multiwidget'] = 1;

			$widget[] = array( 'title' => __( 'Latest Feedback:', 'prospress' ) );

			$widget_id = end( array_keys( $widget ) );

			update_option( 'widget_pp-feedback-latest', $widget );
			array_push( $sidebars_widgets[ $this->name . '-single-sidebar' ], 'pp-feedback-latest-' . $widget_id );
		}
		wp_set_sidebars_widgets( $sidebars_widgets );
	}

	/** 
	 * Clean up anything added on activation, including the index page. 
	 * 
	 * @package Prospress
	 * @subpackage Posts
	 * @since 0.1
	 */
	public function deactivate(){

		if ( !current_user_can( 'edit_plugins' ) || !function_exists( 'delete_site_option' ) )
			return false;
		
		wp_delete_post( $this->get_index_id() );

		flush_rewrite_rules();
	}


	/** 
	 * When Prospress is uninstalled completely, remove the index page created on activation.
	 * 
	 * @package Prospress
	 * @subpackage Posts
	 * @since 0.1
	 */
	public function uninstall(){
		global $wpdb;

		if ( !current_user_can( 'edit_plugins' ) || !function_exists( 'delete_site_option' ) )
			return false;

		wp_delete_post( $this->get_index_id() );
	}
}
