<?php
/**
 * The Core Market System: this is where it get's exciting... and a little messy. 
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

abstract class PP_Market_System {

	protected $name;				// Internal name of the market system, probably plural e.g. "auctions".
	public $label;					// Label to display the market system publicly, e.g. "Auction".
	public $labels;					// Array of labels used to represent market system elements publicly, includes name & singular_name
	public $post;					// Hold the custom PP_Post object for this market system.
	public $adds_post_fields;		// Flag indicating whether the market system adds new post fields. If anything but null, the post_fields_meta_box and post_fields_save functions are hooked
	public $post_table_columns;		// Array of arrays, each array is used to create a column in the post tables. By default it adds two columns, 
									// one for number of bids on the post and the other for the current winning bid on the post 
									// e.g. 'current_bid' => array( 'title' => 'Winning Bid', 'function' => 'get_winning_bid' ), 'bid_count' => array( 'title => 'Number of Bids', 'function' => 'get_bid_count' )
	public $bid_form_heading;		// Text to output as the heading in the bid form
	public $bid_table_headings;		// Array of name/value pairs to be used as column headings when printing table of bids. 
									// e.g. 'bid_id' => 'Bid ID', 'post_id' => 'Post', 'bid_value' => 'Amount', 'bid_date' => 'Date'
	public $taxonomy;				// A PP_Taxonomy object for this post type
	public $bid_object_name;

	protected $bid_status;
	protected $message;

	private $capability;			// the capability for making bids and viewing bid menus etc.

	public function __construct( $name, $args = array() ) {

		$this->name = sanitize_user( $name, true );

		$defaults = array(
						'bid_form_heading' => __( 'Place Bid', 'prospress' ),
						'description' => '',
						'label' => ucfirst( $this->name ),
						'labels' => array(
							'name' => ucfirst( $this->name ),
							'singular_name' => ucfirst( substr( $this->name, 0, -1 ) ), // Remove 's' - certainly not a catch all default!
							'bid_button' => __( 'Bid Now!', 'prospress' ) ),
						'adds_post_fields' => null,
						'post_table_columns' => array (
											'current_bid' => array( 'title' => __( 'Price', 'prospress' ), 'function' => 'the_winning_bid_value' ),
											'winning_bidder' => array( 'title' => __( 'Winning Bidder', 'prospress' ), 'function' => 'the_winning_bidder' ) ),
						'bid_table_headings' => array( 
											'post_id' => __( 'Bid On', 'prospress' ),
											'author' => __( 'Bidder', 'prospress' ),
											'bid_status' => __( 'Status', 'prospress' ),
											'bid_value' => __( 'Bid Amount','prospress' ),
											'winning_bid_value' => __( 'Current Price', 'prospress' ),
											'date' => __( 'Bid Date', 'prospress' ), 
											'post_end' => __( 'Post End Date', 'prospress' )
											),
						'capability' => PP_BASE_CAP,
						'bid_object_name' => $this->name . '-bids'
						);

		$args = wp_parse_args( $args, $defaults );

		$this->label 				= $args[ 'label' ];
		$this->labels 				= $args[ 'labels' ];
		$this->post_table_columns 	= $args[ 'post_table_columns' ];
		$this->bid_form_heading    = $args[ 'bid_form_heading' ];
		$this->bid_table_headings 	= $args[ 'bid_table_headings' ];
		$this->adds_post_fields 	= $args[ 'adds_post_fields' ];
		$this->capability 			= $args[ 'capability' ];
		$this->bid_object_name 		= $args[ 'bid_object_name' ];

		$this->post	= new PP_Post( $this->name, array( 'labels' => $this->labels ) );

		if( class_exists( 'PP_Taxonomy' ) )
			$this->taxonomy = new PP_Taxonomy( $this->name, array( 'labels' => $this->labels ) );

		if( $this->adds_post_fields != null ){
			add_action( 'admin_menu', array( &$this, 'post_fields_meta_box' ) );
			add_action( 'save_post', array( &$this, 'post_fields_save' ), 10, 2 );
		}

		if( !empty( $this->post_table_columns ) ){
			add_filter( 'manage_posts_columns', array( &$this, 'add_post_column_headings' ) );
			add_action( 'manage_posts_custom_column', array( &$this, 'add_post_column_content' ), 10, 2 );
		}

		add_filter( 'bid_table_actions', array( &$this, 'add_bid_table_actions' ), 10, 3 );

		// Create the bid post type and stati
		add_action( 'init', array( &$this, 'register_bid_post_type' ) );

		// Determine if any of this class's functions should be called
		add_action( 'init', array( &$this, 'controller' ) );

		// Pages for bid history
		add_action( 'admin_menu', array( &$this, 'admin_pages' ) );

		// Columns for printing bid history table
		add_filter( 'manage_' . $this->bid_object_name . '_posts_columns', array( &$this, 'add_bid_column_headings' ) );
		// For < WP 3.1
		add_action( 'manage_pages_custom_column', array( &$this, 'add_bid_column_content' ), 10, 2 );
		// For >= WP 3.1
		add_action( 'manage_posts_custom_column', array( &$this, 'add_bid_column_content' ), 10, 2 );

		add_action( 'load-edit.php', array( &$this, 'add_admin_filters' ) );

		// For Ajax & other scripts
		add_action( 'wp_print_scripts', array( &$this, 'enqueue_auction_scripts' ) );

		add_action( 'admin_menu', array( &$this, 'enqueue_bid_admin_scripts' ) );
		add_action( 'admin_head', array( &$this, 'admin_css' ) );

		add_filter( 'pp_sort_options', array( &$this, 'add_sort_options' ) );
	}

	/************************************************************************************************
	 * Member functions that you must override.
	 ************************************************************************************************/

	// The fields that make up the bid form.
	// The <form> tag and a bid form header and footer are automatically generated for the class.
	// You only need to enter the fields to capture information required by your market system, eg. price.
	abstract protected function bid_form_fields( $post_id = NULL );

	// Process the bid form fields upon submission.
	abstract protected function bid_form_submit( $post_id = NULL, $bid_value = NULL, $bidder_id = NULL );

	// Validate and sanitize a bid upon submission, set bid_status and bid_message as needed
	abstract protected function validate_bid( $post_id, $bid_value, $bidder_id );

	// Psuedo abstract
	public function add_bid_table_actions( $actions, $post_id ) { return $actions; }

	// Form fields for receiving input from the edit and add new post type pages. Optionally abstract - only called if adds_post_fields flag set.
	public function post_fields() { return; }

	// Processes data taken from the post edit and add new post forms. Optionally abstract - only called if adds_post_fields flag set.
	protected function post_fields_save( $post_id, $post ) { return; }
	
	/************************************************************************************************
	 * Functions that you may wish to override, but don't need to in order to create a new market system
	 ************************************************************************************************/

	public function name() {
		return $this->name;
	}

	public function singular_name() {
		return $this->labels[ 'singular_name' ];
	}

	// The function that brings all the bid form elements together.
	public function bid_form( $post_id = NULL ) {
		global $post;

		$post_id = ( $post_id === NULL ) ? $post->ID : $post_id;
		$the_post = ( empty ( $post ) ) ? get_post( $post_id) : $post;

		if ( $this->is_post_valid( $post_id ) ) {
			$form = '<form id="bid_form-' . $post_id . '" class="bid-form" method="post" action="">';
			$form .= '<h4>' . $this->bid_form_heading . '</h4>';
			$form .= '<div class="bid-updated bid_msg" >' . $this->get_message() . '</div><div>';
			$form .= $this->bid_form_fields( $post_id );
			$form .= wp_nonce_field( __FILE__, 'bid_nonce', false, false );
			$form .= '<input type="hidden" name="post_ID" value="' . $post_id . '" id="post_ID" /> ';
			$form .= '<input name="bid_submit" type="submit" id="bid_submit" value="' . $this->labels[ 'bid_button' ] .'" />';
			$form .= '</div></form>';
		} else {
			$form = '<div id="bid_form-' . $post_id . '" class="bid-form">';
			$form .= '<div class="bid-updated bid_msg" >' . $this->get_message() . '</div>';
			$form .= '</div>';
		}

		$form = apply_filters( 'bid_form', $form );
		$form = apply_filters( $this->name . '-bid_form', $form );

		return $form;
	}
	
	public function the_bid_form( $post_id = NULL ) {
		echo '<div class="bid-container">';
		do_action( 'pre_bid_form', $post_id );
		do_action( 'pre-' . $this->name . '-bid_form', $post_id );
		echo $this->bid_form( $post_id );
		do_action( 'post_bid_form', $post_id );
		do_action( 'post-' . $this->name . '-bid_form', $post_id );
		echo '</div>';
	}

	protected function is_bid_valid( $post_id, $bid_value, $bidder_id ) {

		$this->validate_bid( $post_id, $bid_value, $bidder_id );

		if( $this->bid_status == 'invalid' )
			return false;
		else
			return true;
	}

	protected function is_post_valid( $post_id = '' ) {

		if( $this->validate_post( $post_id ) == 'valid' )
			return true;
		else
			return false;
	}

	/**
	 * Check's a post's status and verify's that it may receive bids. 
	 */
	protected function validate_post( $post_id = '' ) {
		global $post, $wpdb;

		if( empty( $post_id ))
			$post_id = $post->ID;

		// Need to be done manually to account for changes during request as wp caches post status
		$post_status = $wpdb->get_var( $wpdb->prepare( "SELECT post_status FROM $wpdb->posts WHERE ID = %d LIMIT 1", $post_id ) );

		if ( $post_status == 'completed' ){
			if( !isset( $this->message_id ) )
				$this->message_id = 12;
			do_action( 'bid_on_completed_post', $post_id );
		} elseif ( in_array( $post_status, array( 'draft', 'pending', 'future' ) ) ) {
			$this->message_id = 14;
			$post_status = 'invalid';
			do_action( 'bid_on_draft_scheduled', $post_id );
		} elseif ( $post_status === NULL ) {
			$this->message_id = 13;
			$post_status = 'invalid';
			do_action( 'bid_post_not_found', $post_id );
		} else {
			$post_status = 'valid';
		}

		apply_filters( 'pp_validate_post', $post_status );
		return $post_status;
	}

	// Returns the value of the winning bid (either new or existing)
	protected function update_bid( $bid ){
		global $wpdb;

		if( $this->bid_status == 'invalid' ) // nothing to update
			return $this->get_winning_bid_value( $bid[ 'post_id' ] );

		$bid_post[ 'post_parent' ]	= $bid[ 'post_id' ];
		$bid_post[ 'post_author' ]	= $bid[ 'bidder_id' ];
		$bid_post[ 'post_content' ]	= $bid[ 'bid_value' ];
		$bid_post[ 'post_status' ]	= $bid[ 'bid_status' ];
		$bid_post[ 'post_type' ]	= $this->bid_object_name;

		wp_insert_post( $bid_post );

		return $bid[ 'bid_value' ];
	}


	/**
	 * Gets all the details of the highest bid on a post, optionally specified with $post_id.
	 *
	 * If no post id is specified, the global $post var is used. 
	 */
	public function get_max_bid( $post_id = '' ) {
		global $post, $wpdb;

		if ( empty( $post_id ) )
			$post_id = $post->ID;

		$max_bid = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->posts WHERE post_type = %s AND post_parent = %d AND post_content = (SELECT MAX( CAST(post_content as decimal) ) FROM $wpdb->posts WHERE post_parent = %d)", $this->bid_object_name, $post_id, $post_id ) );

		return $max_bid;
	}

	/**
	 * Prints the max bid value for a post, optionally specified with $post_id. Optional also to just return the value. 
	 */
	public function the_max_bid_value( $post_id = '', $echo = true ) {
		$max_bid = ( empty( $post_id ) ) ? $this->get_max_bid() : $this->get_max_bid( $post_id );
		
		$max_bid = ( $max_bid->post_content ) ? pp_money_format( $max_bid->post_content ) : __( 'No Bids', 'prospress' );

		if ( $echo ) 
			echo $max_bid;
		else 
			return $max_bid;
	}

	/**
	 * Gets all the details of the winning bid on a post, optionally specified with $post_id.
	 *
	 * At first glance, it may seem to be redundant having functions for both "max" and "winning" bid. 
	 * However, in some market systems, the winning bid is no determined by the "max" bid. 
	 * 
	 * If no post id is specified, the global $post var is used. 
	 */
	public function get_winning_bid( $post_id = '' ) {
		global $post, $wpdb;

		if ( empty( $post_id ) )
			$post_id = $post->ID;

		$winning_bid = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->posts WHERE post_type = %s AND post_parent = %d AND post_status = %s", $this->bid_object_name, $post_id, 'winning' ) );

		$winning_bid->winning_bid_value = $this->get_winning_bid_value( $post_id );

		return $winning_bid;
	}

	/**
	 * Gets the value of the current winning bid for a post, optionally specified with $post_id.
	 */
	public function get_winning_bid_value( $post_id = '' ) {
		global $post, $wpdb;

		if ( empty( $post_id ) )
			$post_id = $post->ID;

		if ( $this->get_bid_count( $post_id ) == 0 )
			$winning_bid_value = __( 'No Bids' );
		else
			$winning_bid_value = $wpdb->get_var( $wpdb->prepare( "SELECT post_content FROM $wpdb->posts WHERE post_type = %s AND post_parent = %d AND post_status = %s", $this->bid_object_name, $post_id, 'winning' ) );

		return $winning_bid_value;
	}

	/**
	 * Prints the value of the current winning bid for a post, optionally specified with $post_id.
	 *
	 * The value of the winning bid is not necessarily equal to the maximum bid. 
	 */
	public function the_winning_bid_value( $post_id = '', $echo = true ) {
		$winning_bid = $this->get_winning_bid_value( $post_id );

		$winning_bid = ( $winning_bid == 0 ) ? __( 'No bids.', 'prospress' ) : pp_money_format( $winning_bid );

		if ( $echo ) 
			echo $winning_bid;
		else 
			return $winning_bid;
	}

	/**
	 * Prints the display name of the winning bidder for a post, optionally specified with $post_id.
	 */
	public function the_winning_bidder( $post_id = '', $echo = true ) {
		global $user_ID;

		get_currentuserinfo(); // to set $user_ID

		$winning_bid 	= $this->get_winning_bid( $post_id );
		$winning_bidder = ( isset( $winning_bid->post_author ) ) ? $winning_bid->post_author : '';

		if ( !empty( $winning_bid->post_author ) )
			$winning_bidder = ( $winning_bid->post_author == $user_ID) ? __( 'You', 'prospress' ) : get_userdata( $winning_bid->post_author )->display_name;
		else 
			$winning_bidder = 'No bids.';

		if ( $echo ) 
			echo $winning_bidder;
		else 
			return $winning_bidder;
	}

	/**
	 * Function to test if a given user is classified as a winning bidder for a given post. 
	 * 
	 * As some market systems may have multiple winners, it is important to use this function 
	 * instead of testing a user id directly against a user id provided with get_winning_bid.
	 * 
	 * Optionally takes $user_id and $post_id, if not specified, using the ID of the currently
	 * logged in user and post in the loop.
	 */
	public function is_winning_bidder( $user_id = '', $post_id = '' ) {
		global $user_ID, $post;

		if ( empty( $post_id ) )
			$post_id = $post->ID;
		
		if ( $user_id == '' )
			$user_id = $user_ID;

		return ( $user_id == $this->get_winning_bid( $post_id )->post_author ) ? true : false;
	}

	/**
	 * Gets the max bid for a post and user, optionally specified with $post_id and $user_id.
	 *
	 * If no user ID or post ID is specified, the function uses the global $post ad $user_ID 
	 * variables. 
	 */
	public function get_users_max_bid( $user_id = '', $post_id = '' ) {
		global $user_ID, $post, $wpdb;

		if ( empty( $user_id ) )
			$user_id = $user_ID;

		if ( empty( $post_id ) )
			$post_id = $post->ID;

		$users_max_bid = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->posts WHERE post_type = %s AND post_parent = %d AND post_author = %d AND post_content = (SELECT MAX( CAST(post_content as decimal) ) FROM $wpdb->posts WHERE post_type = %s AND post_parent = %d AND post_author = %d)", $this->bid_object_name, $post_id, $user_id, $this->bid_object_name, $post_id, $user_id ) );

		return $users_max_bid;
	}

	// Prints the max bid for a user on a post, optionally specified with $post_id.
	public function the_users_max_bid_value( $user_id = '', $post_id = '', $echo = true ) {
		$users_max_bid = get_users_max_bid( $user_id, $post_id );

		$users_max_bid = ( $users_max_bid->post_content ) ? $users_max_bid->post_content : __( 'No Bids.', 'prospress' );

		if ( $echo ) 
			echo $users_max_bid;
		else 
			return $users_max_bid;
	}

	// Gets the number of bids for a post, optionally specified with $post_id.
	public function get_bid_count( $post_id = '' ) {
		global $post, $wpdb;

		if ( empty( $post_id ) )
			$post_id = $post->ID;

		$bid_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->posts WHERE post_type = %s AND post_parent = %d", $this->bid_object_name, $post_id ) );

		return $bid_count;
	}

	/**
	 * Prints the number of bids on a post, optionally specified with $post_id.
	 */
	public function the_bid_count( $post_id = '', $echo = true ) {
		$bid_count = ( empty( $post_id ) ) ? $this->get_bid_count() : $this->get_bid_count( $post_id );
		
		$bid_count = ( $bid_count ) ? $bid_count : __( 'No Bids', 'prospress' );

		if ( $echo ) 
			echo $bid_count;
		else 
			return $bid_count;
	}

	/**
	 * Extracts messages passed to a bid form and prints these messages.
	 * 
	 * A message can be passed to a bid form using the URL. This function pulls any messages
	 * passed to a page containing a bid form and prints the messages. 
	 */
	protected function get_message(){

		if ( isset( $this->message_id ) )
			$message_id = $this->message_id;
		elseif ( isset( $_GET[ 'bid_msg' ] ) )
			$message_id = $_GET[ 'bid_msg' ];

		$message = '';

		if ( isset( $message_id ) ){
			switch( $message_id ) {
				case 0: //first bid
				case 1:
					$message = __( 'Congratulations, you are the winning bidder.', 'prospress' );
					break;
				case 2:
					$message = __( 'You have been outbid.', 'prospress' );
					break;
				case 3:
					$message = __( 'You must bid more than the winning bid.', 'prospress' );
					break;
				case 4:
					$message = __( 'Your maximum bid has been increased.', 'prospress' );
					break;
				case 5:
					$message = __( 'You can not decrease your maximum bid.', 'prospress' );
					break;
				case 6:
					$message = __( 'You have entered a bid equal to your current maximum bid.', 'prospress' );
					break;
				case 7:
					$message = __( 'Invalid bid. Please enter a valid number. e.g. 11.23 or 58', 'prospress' );
					break;
				case 8:
					$message = __( 'Invalid bid. Bid nonce did not validate.', 'prospress' );
					break;
				case 9:
					$message = __( 'Invalid bid. Please enter a bid greater than the starting price.', 'prospress' );
					break;
				case 10:
					$message = __( 'Bid submitted.', 'prospress' );
					break;
				case 11:
					$message = sprintf( __( 'You cannot bid on your own %s.', 'prospress' ), $this->labels[ 'singular_name' ] );
					break;
				case 12:
					$message = sprintf( __( 'This %s has finished, bids cannot be accepted.', 'prospress' ), $this->labels[ 'singular_name' ] );
					break;
				case 13:
					$message = sprintf( __( 'This %s can not be found.', 'prospress' ), $this->labels[ 'singular_name' ] );
					break;
				case 14:
					$message = sprintf( __( 'You cannot bid on a draft, scheduled or pending %s.', 'prospress' ), $this->labels[ 'singular_name' ] );
					break;
				default:
					$message = apply_filters( 'bid_message_unknown', sprintf( __( "Error: %d"), $message_id ), $message_id );
					break;
			}

			$message = apply_filters( 'bid_message', $message, $message_id );
			$message = apply_filters( $this->name . '-bid_message', $message, $message_id );

			return $message;
		}
	}


	/**
	 * Convenience wrapper for the post object's get index id function.
	 */
	public function get_index_id() {
		return $this->post->get_index_id();
	}

	/**
	 * Convenience wrapper for the post object's get index permalink function.
	 */
	public function get_index_permalink() {
		return $this->post->get_index_permalink();
	}

	
	/**
	 * Convenience wrapper for the post object's is index function.
	 */
	public function is_index() {
		return $this->post->is_index();
	}


	/**
	 * Convenience wrapper for the post object's is index function.
	 */
	public function is_single() {
		return $this->post->is_single();
	}


	/**
	 * Creates an anchor tag linking to the user's payments, optionally prints.
	 * 
	 */
	function the_bids_url( $desc = "Your Bids", $echo = '' ) {

		$bids_tag = "<a href='" . $this->get_bids_url() . "' title='$desc'>$desc</a>";

		if( $echo == 'echo' )
			echo $bids_tag;
		else
			return $bids_tag;
	}


	/**
	 * Gets the url to the user's feedback table.
	 * 
	 */
	function get_bids_url() {

		 return admin_url( 'edit.php?post_type=' . $this->bid_object_name );
	}


	/**
	 * Returns a user's role on a given post. If user has no role, false is returned.
	 * 
	 * @param $post int|array either the id of a post or a post object
	 */
	function get_users_role( $post, $user_id = NULL ) {
		global $user_ID;

		if( $user_id === NULL )
			$user_id = $user_ID;

		if ( is_numeric( $post ) )
			$post = get_post( $post );

		if ( $post->post_author == $user_id && !$is_winning_bidder )
			return __( 'Post Author', 'prospress' );
		else
			return __( 'Bidder', 'prospress' );
	}


	/************************************************************************************************
	 * Private Functions: don't worry about these, unless you want to get really tricky.
	 * Even if they're declared public, it's only because they are attached to a hook
	 ************************************************************************************************/


	/**
	 * Registers the feedback post type with WordPress
	 * 
	 **/
	public function register_bid_post_type(){

		$args = array(
				'label' 	=> sprintf( __( '%s Bids'), $this->labels[ 'name' ] ),
				'public' 	=> true,
				'show_ui' 	=> true,
				'rewrite' 	=> array( 'slug' => $this->bid_object_name, 'with_front' => false ),
				'menu_icon' => PP_PLUGIN_URL . '/images/auctions16.png',
				'show_in_nav_menus' => false,
				'exclude_from_search' => true,
				'capability_type' => 'post',
				'capabilities' => array( 'edit_post' => 'read', // Allow any registered user to bid, for now
										 'edit_posts' => 'read',
										 'publish_posts' => 'read',
										 'delete_post' => 'read'
				 						),
				'hierarchical' => true, // post parent is the post for which the bid relates
				'supports' 	=> array(
								'title',
								'editor',
								'revisions' ),
				'labels'	=> array( 'name'	=> sprintf( __( '%s Bids'), $this->labels[ 'singular_name' ] ),
								'singular_name'	=> sprintf( __( '%s Bid'), $this->labels[ 'singular_name' ] ),
								'add_new_item'	=> __( 'Place Bid', 'prospress' ),
								'edit_item'		=> __( 'Edit Bid', 'prospress' ),
								'new_item'		=> __( 'New Bid', 'prospress' ),
								'view_item'		=> __( 'View Bid', 'prospress' ),
								'search_items'	=> __( 'Search Bids', 'prospress' ),
								'not_found'		=> __( 'No Bids Found', 'prospress' ),
								'not_found_in_trash' => __( 'No Bids Found in Trash', 'prospress' ),
								'parent_item_colon' => __( 'Bid on post:' ) )
					);

			register_post_type( $this->bid_object_name, $args );

			register_post_status(
			       'outbid',
			       array('label' => _x( 'Outbid', 'post status', 'prospress' ),
						'label_count' => _n_noop( 'Outbid <span class="count">(%s)</span>', 'Outbid <span class="count">(%s)</span>', 'prospress' ),
						'public' => true,
						'show_in_admin_all' => true,
						'show_in_admin_all_list' => true,
						'show_in_admin_status_list' => true,
						'publicly_queryable' => false,
						'exclude_from_search' => true )
			);

			register_post_status(
			       'winning',
			       array('label' => __( 'Winning', 'prospress' ),
						'label_count' => _n_noop( 'Winning <span class="count">(%s)</span>', 'Winning <span class="count">(%s)</span>', 'prospress' ),
						'public' => true,
						'show_in_admin_all' => true,
						'show_in_admin_all_list' => true,
						'show_in_admin_status_list' => true,
						'publicly_queryable' => false,
						'exclude_from_search' => true )
			);
	}


	/**
	 * 	Adds bid pages to admin menu
	 * 
	 * @uses add_object_page to add "Bids" top level menu
	 * @uses add_menu_page if add object page is not available to add "Bids" menu
	 * @uses add_submenu_page to add "Bids" and "Bid History" submenus to "Bids"
	 * @uses add_options_page to add administration pages for bid settings
	 * @return false if logged in user is not the site admin
	 **/
	public function admin_pages() {
		global $submenu;

		if( false !== stripos( $_SERVER['REQUEST_URI'], 'post-new.php?post_type=' . $this->bid_object_name ) || ( isset( $_GET[ 'post' ]) && get_post_type( $_GET[ 'post' ] ) ==  $this->bid_object_name ) )
			wp_redirect( get_option('siteurl') . '/wp-admin/edit.php?post_type=' . $this->bid_object_name );

		//Remove Add New Menu
		unset( $submenu[ 'edit.php?post_type=' . $this->bid_object_name ][10] );
	}

	// Returns bid column headings for market system. Used with the built in print_column_headers function.
	public function add_bid_column_headings( $column_headings ){

		if( $_GET[ 'post_type' ] != $this->bid_object_name )
			return $column_headings;

		$columns[ 'cb' ] = '<input type="checkbox" />';
		$columns = $columns + $this->bid_table_headings;

		return $columns;
	}

	public function add_bid_column_content( $column_name, $post_id ) {

		if( $_GET[ 'post_type' ] != $this->bid_object_name )
			return;

		$bid = get_post( $post_id );

		switch ( $column_name ) {
			case 'post_id':
				$post = get_post( $bid->post_parent );
				echo "<a href='" . get_permalink( $bid->post_parent ) . "'>$post->post_title</a>";
				$actions = apply_filters( 'bid_table_actions', array(), $bid->post_parent, $bid );
				if( is_array( $actions ) && !empty( $actions ) ) {
					$action_count = count( $actions );
					$edit = '<div class="row-actions">';
					$i = 0;
					foreach ( $actions as $action => $attributes ) {
						++$i;
						( $i == $action_count ) ? $sep = '' : $sep = ' | ';
						$link = "<a href='" . add_query_arg ( array( 'action' => $action, 'post' => $post->ID ) , $attributes['url' ] ) . "'>" . $attributes['label' ] . "</a>";
						$edit .= "<span class='$action'>$link$sep</span>";
					}
					$edit .= '</div>';
					echo $edit;
				}
				break;
			case 'bid_status':
				echo ucfirst( $bid->post_status );
				break;
			case 'bid_value':
				echo pp_money_format( $bid->post_content );
				break;
			case 'winning_bid_value':
				$this->the_winning_bid_value( $bid->post_parent );
				break;
			case 'post_end':
				$post_end_date = get_post_end_time( $bid->post_parent, 'mysql', 'user' );
				echo mysql2date( __( 'g:ia d M Y' , 'prospress' ), $post_end_date );
				break;
			default:
				break;
			}
	}

	public function add_admin_filters() {
		add_action( 'restrict_manage_posts', array( &$this, 'admin_bids_filters' ) );
		add_filter( 'posts_where', array( &$this, 'admin_filter_bids' ) );
	}

	public function admin_bids_filters() {
		global $current_screen;

		if( $current_screen->post_type != $this->bid_object_name )
			return;

		$filter_name = $this->name . "-status";
		?>
		<select name='<?php echo $filter_name; ?>' id='<?php echo $filter_name; ?>' class='postform'>
			<option value="0"><?php printf( __( 'All %s', 'prospress' ), $this->labels[ 'name' ] ); ?></option>
			<option value="completed" <?php selected( @$_GET[ $filter_name ], 'completed' ); ?>><?php printf( __( 'Completed %s', 'prospress' ), $this->labels[ 'name' ] ); ?></option>
			<option value="published" <?php selected( @$_GET[ $filter_name ], 'published' ); ?>><?php printf( __( 'Published %s', 'prospress' ), $this->labels[ 'name' ] ); ?></option>
		</select>
		<?php
	}

	public function admin_filter_bids( $where ) {
		global $wpdb, $current_screen, $user_ID;

		if( !is_admin() || $current_screen->post_type != $this->bid_object_name )
			return $where;

		$filter_name = $this->name . "-status";

		if( !current_user_can( 'edit_others_prospress_posts' ) )
	    	$where .= $wpdb->prepare( " AND post_author= %d", $user_ID );

		if( isset( $_GET[ $filter_name ] ) ) {
			if( $_GET[ $filter_name ] == 'completed' ) {
			    $where .= " AND post_parent IN (SELECT ID FROM {$wpdb->posts} WHERE post_status='completed' )";
			} else if( $_GET[ $filter_name ] == 'published' ) {
			    $where .= " AND post_parent IN (SELECT ID FROM {$wpdb->posts} WHERE post_status='publish' )";
			}
		}
		return $where;
	}


	// Add market system columns to tables of posts
	public function add_post_column_headings( $column_headings ) {

		if( !( @$_GET[ 'post_type' ] == $this->name || get_post_type( @$_GET[ 'post' ] ) ==  $this->name ) )
			return $column_headings;

		foreach( $this->post_table_columns as $key => $column )
			$column_headings[ $key ] = $column[ 'title' ];

		return $column_headings;
	}

	/**
	 * Don't worry about this indecipherable function, you shouldn't need to create the bid table columns,
	 * instead you can rely on this to call the function assigned to the column through the constructor
	 **/
	public function add_post_column_content( $column_name, $post_id ) {
		if( array_key_exists( $column_name, $this->post_table_columns ) ) {
			$function = $this->post_table_columns[ $column_name ][ 'function' ];
			$this->$function( $post_id );
		}
	}

	public function enqueue_auction_scripts(){
		if( is_admin() || ( !$this->is_index() && !$this->is_single() ) )
			return;

  		wp_enqueue_script( 'bid-form-ajax', PP_BIDS_URL . '/bid-form-ajax.js', array( 'jquery' ) );
		wp_localize_script( 'bid-form-ajax', 'bidi18n', array( 'siteUrl' => get_bloginfo('wpurl') ) );
		wp_enqueue_script( 'final-countdown', PP_PLUGIN_URL . '/js/final-countdown.js', array( 'jquery' ) );
		wp_localize_script( 'final-countdown', 'bidi18n', array( 'siteUrl' => get_bloginfo('wpurl') ) );
	}

	public function enqueue_bid_admin_scripts(){
		wp_enqueue_style( 'bids', PP_BIDS_URL . '/admin.css' );
	}

	public function admin_css(){
		global $current_screen;

		if( isset( $current_screen->post_type ) && $current_screen->post_type == $this->bid_object_name ){
			echo '<style type="text/css">.add-new-h2,.actions select:first-child,#doaction,#doaction2';
			echo ( !current_user_can( 'edit_others_prospress_posts' ) ) ? ',.count' : '';
			echo '{display: none;}</style>';  
		}
	}

	// Adds bid system specific sort options to the post system sort widget, can be implemented, but doesn't have to be
	public function add_sort_options( $pp_sort_options ){
		return $pp_sort_options;
	}

	// Adds the meta box with post fields to the edit and add new post forms. 
	// This function is only called if post fields is defined.
	public function post_fields_meta_box(){
		if( function_exists( 'add_meta_box' ) ) {
			add_meta_box( 'pp-bidding-options', sprintf( __( '%s Options', 'prospress' ), $this->labels[ 'singular_name' ] ), array(&$this, 'post_fields' ), $this->name, 'normal', 'core' );
		}
	}

	/**
	 * The logic for the market system.
	 * 
	 * Handles AJAX bid submission and makes sure a user is logged in before making a bid.
	 *
	 **/
	// Hooked to init to determine if a bid has been submitted. If it has, bid_form_submit is called.
	// Takes care of the logic of the class, determining if and when to call a function.
	public function controller(){

		do_action( 'market_system_controller' );
		do_action( $this->name . '-controller' );

		// If a bid is not being submitted
		if( !isset( $_REQUEST[ 'bid_submit' ] ) )
			return;

		if ( !is_user_logged_in() ){ 
			do_action( 'bidder_not_logged_in' );
			$redirect = wp_get_referer();
			$redirect = add_query_arg( urlencode_deep( $_POST ), $redirect );
			$redirect = add_query_arg( 'bid_redirect', wp_get_referer(), $redirect );
			$redirect = wp_login_url( $redirect );
			$redirect = apply_filters( 'bid_login_redirect', $redirect );

			if( $_REQUEST[ 'bid_submit' ] == 'ajax' ){ // Bid being submitted with AJAX need to print redirect instead of using WP redirect
				echo '{"redirect":"' . $redirect . '"}';
				die();
			} else {
				wp_safe_redirect( $redirect );
				exit();
			}
		}

		// Verify bid nonce if bid is not coming from a login redirect
		if ( !isset( $_REQUEST[ 'bid_redirect' ] ) && ( !isset( $_REQUEST[ 'bid_nonce' ] ) || !wp_verify_nonce( $_REQUEST['bid_nonce' ], __FILE__) ) ) {
			$this->bid_status = 8;
		} elseif ( isset( $_GET[ 'bid_redirect' ] ) ) {
			$this->bid_form_submit( $_GET[ 'post_ID' ], $_GET[ 'bid_value' ] );
		} else {
			$this->bid_form_submit( $_POST[ 'post_ID' ], $_POST[ 'bid_value' ] );
		}

		// Redirect user back to post
		if ( !empty( $_REQUEST[ 'bid_redirect' ] ) ){
			$location = $_REQUEST[ 'bid_redirect' ];
			$location = add_query_arg( 'bid_msg', $this->message_id, $location );
			$location = add_query_arg( 'bid_nonce', wp_create_nonce( __FILE__ ), $location );
			wp_safe_redirect( $location );
			exit();
		}

		if( $_POST[ 'bid_submit' ] == 'ajax' ){
			echo $this->bid_form( $_POST[ 'post_ID' ] );
			die();
		}

		// If someone enters a URL with a bid_msg but they didn't make that bid
		if( isset( $_GET[ 'bid_msg' ] ) && isset( $_GET[ 'bid_nonce' ] ) && !wp_verify_nonce( $_GET[ 'bid_nonce' ], __FILE__ ) ){
			$redirect = remove_query_arg( 'bid_nonce' );
			$redirect = remove_query_arg( 'bid_msg', $redirect );
			wp_safe_redirect( $redirect );
			exit();
		}
	}

}

