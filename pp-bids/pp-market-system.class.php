<?php
/**
 * The Core Market System. 
 * 
 * This is where it get's exciting... and a little messy. 
 * 
 * @package Prospress
 * @author Brent Shepherd
 * @version 0.1
 */

class PP_Market_System {

	var $name;					// Public name of the market system e.g. "Auction".
	var $singular_name;			// Public name of the market system e.g. "Auction".
	var $bid_form_title;		// Title for the bid form.
	var $bid_button_value;		// Text used on the submit button of the bid form.
	var $post_fields;			// Array of flags representing the fields which the market system implements e.g. array( 'post_fields' )
	var $post_table_columns;	// Array of arrays, each array is used to create a column in the post tables. By default it adds two columns, 
								// one for number of bids on the post and the other for the current winning bid on the post 
								// e.g. 'current_bid' => array( 'title' => 'Winning Bid', 'function' => 'get_winning_bid' ), 'bid_count' => array( 'title => 'Number of Bids', 'function' => 'get_bid_count' )
	var $bid_table_headings;	// Array of name/value pairs to be used as column headings when printing table of bids. 
								// e.g. 'bid_id' => 'Bid ID', 'post_id' => 'Post', 'bid_value' => 'Amount', 'bid_date' => 'Date'

	function __construct( $name, $singular_name, $bid_form_title = "", $bid_button_value = "", $post_fields = array(), $post_table_columns = array(), $bid_table_headings = array() ) {

		$this->name 			= (string)$name;
		$this->singular_name 	= (string)$singular_name;
		$this->bid_form_title 	= empty( $bid_form_title ) ? __("Make a bid", 'prospress' ) : $bid_form_title;
		$this->bid_button_value	= empty( $bid_button_value ) ? __("Bid now!", 'prospress' ) : $bid_button_value;

		if( empty( $post_table_columns ) || !is_array( $post_table_columns ) ){
			$this->post_table_columns = array (	'current_bid' => array( 'title' => 'Price', 'function' => 'the_winning_bid_value' ),
												'bid_count' => array( 'title' => 'Number of Bids', 'function' => 'the_bid_count' ) );
		} else {
			$this->post_table_columns = $post_table_columns;
		}

		if( empty( $bid_table_headings ) || !is_array( $bid_table_headings ) ){
			$this->bid_table_headings = array( 
										'post_id' => 'Post', 
										'post_status' => 'Post Status', 
										'bid_value' => 'Amount',
										'bid_status' => 'Bid Status', 
										'bid_date' => 'Bid Date',
										'post_end' => 'Post End Date'
										);
		} else {
			$this->bid_table_headings = $bid_table_headings;
		}

		if( !is_array( $post_fields ) ){
			$this->post_fields = array();
		} else {
			$this->post_fields = $post_fields;
		}

		if( !empty( $this->post_fields ) && in_array( 'post_fields', $this->post_fields ) ){
			add_action( 'admin_menu', array( &$this, 'post_fields_meta_box' ) );
			add_action( 'save_post', array( &$this, 'post_fields_submit' ), 10, 2 );
		}

		if( !empty( $this->post_table_columns ) && is_array( $this->post_table_columns ) ){
			add_filter( 'manage_posts_columns', array( &$this, 'add_post_column_headings' ) );
			add_action( 'manage_posts_custom_column', array( &$this, 'add_post_column_contents' ), 10, 2 );
			//add_filter( 'manage_' . $this->name() . '_columns', array( &$this, 'add_post_column_headings' ) );
			//add_action( 'manage_' . $this->name() . '_custom_column', array( &$this, 'add_post_column_contents' ), 10, 2 );
		}

		// Determine if bid form submission function should be called
		add_action( 'init', array( &$this, 'bid_controller' ) );

		// Adds columns for printing bid history table
		add_action( 'admin_menu', array( &$this, 'add_admin_pages' ) );

		// Adds columns for printing bid history table
		add_filter( 'manage_' . $this->name() . '_columns', array( &$this, 'get_column_headings' ) );

		// For adding Ajax & other scripts
		add_action( 'wp_print_scripts', array( &$this, 'enqueue_bid_form_scripts' ) );
		add_action( 'admin_menu', array( &$this, 'enqueue_bid_admin_scripts' ) );
		
		add_filter( 'pp_sort_options', array( &$this, 'add_sort_options' ) );

	}

	/************************************************************************************************
	 * Member functions that you must override.
	 ************************************************************************************************/

	// The fields that make up the bid form.
	// The <form> tag and a bid form header and footer are automatically generated for the class.
	// You only need to enter the tags to capture information required by your market system.
	function bid_form_fields( $post_id = NULL ) {
		die( 'function PP_Market_System::bid_form_fields() must be over-ridden in a sub-class.' );
	}

	// Process the bid form fields upon submission.
	function bid_form_submit( $post_id = NULL, $bid_value = NULL, $bidder_id = NULL ){
		die( 'function PP_Market_System::bid_form_submit() must be over-ridden in a sub-class.' );
	}

	// Validate a bid when the bid form.
	function bid_form_validate(){
		die( 'function PP_Market_System::bid_form_validate() must be over-ridden in a sub-class.' );
	}


	/************************************************************************************************
	 * Functions that you may override, but shouldn't need to be changed to create a new market system.
	 ************************************************************************************************/

	/**
	 * A getter for the market system's name. This is called from various places to refer to the both the market system and
	 * posts within that market system. 
	 * 
	 * By default this function returns the name of the marketplace system, as stored in the $name member variables, with an 
	 * upper case first letter; however, other market systems require additional words or operations performed on the name 
	 * member variable.
	 **/
	function name() {
		return $this->name;
	}

	function singular_name() {
		return ucfirst( $this->singular_name );
	}

	function display_name() {
		return ucfirst( $this->name );
	}

	// The function that brings all the bid form elements together.
	function bid_form( $post_id = NULL ) {
		global $post;

		$post_id = ( $post_id === NULL ) ? $post->ID : $post_id;
		$the_post = ( empty ( $post ) ) ? get_post( $post_id) : $post;

		if ( $the_post->post_status == 'completed' ) {
			$form .= '<p class="bid-form">' . __( 'This post has ended. Bidding is closed.', 'prospress' ) . '</p>';
		} else {
			$form .= '<form id="bid_form-' . $post_id . '" class="bid-form" method="post" action="">';
			$form .= '<em class="bid-updated bid_msg" >' . $this->get_bid_message() . '</em>';
			$form .= ( $post->post_status != 'completed' ) ? $this->bid_form_fields( $post_id ) : '<p>' . __( 'This post has ended. Bidding is closed.', 'prospress' ) . '</p>';
			apply_filters( 'bid_form_hidden_fields', $form );
			$form .= wp_nonce_field( __FILE__, 'bid_nonce', false, false );
			$form .= '<input type="hidden" name="post_ID" value="' . $post_id . '" id="post_ID" /> ';
			$form .= '<input name="bid_submit" type="submit" id="bid_submit" value="' . $this->bid_button_value .'" />';
			$form .= '</form>';

			$form = apply_filters( 'bid_form', $form );
		}

		return $form;		
	}

	// Form fields to taking input from the edit and add new post forms.
	function post_fields(){
		error_log( 'post_fields method' );
	}

	// Processes data taken from the post edit and add new post forms.
	function post_fields_submit(){
		error_log( 'no_post_submit method' );
	}

	// Adds the meta box with post fields to the edit and add new post forms. 
	// This function is hooked in the constructor and is only called if post fields is defined. 
	function post_fields_meta_box(){
		if( function_exists( 'add_meta_box' )) {
			add_meta_box( 'pp-bidding-options', __( 'Bidding Options', 'prospress' ), array(&$this, 'post_fields' ), $this->name(), 'normal', 'core' );
		}
	}

	/**
	 * Check's a post's status and verify's that it may receive bids. 
	 */
	function verify_post_status( $post_id = '' ) {
		global $post, $wpdb;
		
		if( empty( $post_id ))
			$post_id = $post->ID;

		$post_status = $wpdb->get_var( $wpdb->prepare( "SELECT post_status FROM $wpdb->posts WHERE ID = %d", $post_id ) );

		/** @TODO Have a more graceful failure on varied post status */
		if ( $post_status === NULL ) {
			do_action( 'bid_post_not_found', $post_id);
			wp_die( __( 'Sorry, this post can not be found.' ) );
			exit;
		} elseif ( in_array( $post_status, array( 'draft', 'pending' ) ) ) {
			do_action( 'bid_on_draft', $post_id);
			wp_die( __( 'Sorry, but you can not bid on a draft or pending post.' ) );
			exit;
		} elseif ( $post_status == 'ended' ){ // || $bid_date_gmt < post_end_date_gmt
			do_action( 'bid_on_ended', $post_id);
			wp_die( __( 'Sorry, this post has ended.' ) );
			exit;
		}

		return $post_status;
	}

	// Calculates the value of the new winning bid and updates it in the DB if necessary
	// Returns the value of the winning bid (either new or existing)
	// function update_winning_bid( $bid_ms, $post_id, $bid_value, $bidder_id ){
	function update_bid( $bid, $bid_ms ){
		global $wpdb;

		if ( $bid_ms[ 'bid_status' ] == 'invalid' ) // nothing to update
			return $current_winning_bid_value;

		$wpdb->insert( $wpdb->bids, $bid );

		return $bid[ 'bid_value' ];
	}


	/**
	 * Get's all the details of the highest bid on a post, optionally specified with $post_id.
	 *
	 * If no post id is specified, the global $post var is used. 
	 */
	function get_max_bid( $post_id = '' ) {
		global $post, $wpdb;

		if ( empty( $post_id ) )
			$post_id = $post->ID;

		$max_bid = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->bids WHERE post_id = %d AND bid_value = (SELECT MAX(bid_value) FROM $wpdb->bids WHERE post_id = %d)", $post_id, $post_id ) );

		return $max_bid;
	}

	/**
	 * Prints the max bid value for a post, optionally specified with $post_id. Optional also to just return the value. 
	 */
	function the_max_bid_value( $post_id = '', $echo = true ) {
		$max_bid = ( empty( $post_id ) ) ? $this->get_max_bid() : $this->get_max_bid( $post_id );
		
		$max_bid = ( $max_bid->bid_value ) ? pp_money_format( $max_bid->bid_value ) : __( 'No Bids', 'prospress' );

		//( $echo ) ? echo $max_bid; : return $max_bid;
		if ( $echo ) 
			echo $max_bid;
		else 
			return $max_bid;
	}

	/**
	 * Get's all the details of the winning bid on a post, optionally specified with $post_id.
	 *
	 * At first glance, it may seem to be redundant having functions for both "max" and "winning" bid. 
	 * However, in some market systems, the winning bid is no determined by the "max" bid. 
	 * 
	 * If no post id is specified, the global $post var is used. 
	 */
	function get_winning_bid( $post_id = '' ) {
		global $post, $wpdb;

		if ( empty( $post_id ) )
			$post_id = $post->ID;

		$winning_bid = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->bids WHERE post_id = %d AND bid_status = %s", $post_id, 'winning' ) );
		
		return $winning_bid;
	}

	/**
	 * Gets the value of the current winning bid for a post, optionally specified with $post_id.
	 *
	 * The value of the winning bid is not necessarily equal to the bid's value. The winning bid
	 * value is calculated with the bid increment over the current second highest bid. It is then
	 * stored in the bidsmeta table. This function pulls the value from this table. 
	 * 
	 * If no winning value is stored in the bidsmeta table, then the function uses the winning bids
	 * value, which is equal to the maximum bid for that user on this post.
	 */
	function get_winning_bid_value( $post_id = '' ) {
		global $post, $wpdb;

		if ( empty( $post_id ) )
			$post_id = $post->ID;

		if ( $this->get_bid_count( $post_id ) == 0 ){
			$winning_bid_value = get_post_meta( $post_id, 'start_price', true );
		} else {
			$winning_bid = $this->get_winning_bid( $post_id );

			$winning_bid_value = $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM $wpdb->bidsmeta WHERE bid_id = %d AND meta_key = %s", $winning_bid->bid_id, 'winning_bid_value' ) );

			// If no winning bid value in meta table, default to max value of winning bid.
			if( empty( $winning_bid_value ) )
				$winning_bid_value = $winning_bid->bid_value;
		}

		return $winning_bid_value;
	}

	/**
	 * Prints the value of the current winning bid for a post, optionally specified with $post_id.
	 *
	 * The value of the winning bid is not necessarily equal to the maximum bid. 
	 */
	function the_winning_bid_value( $post_id = '', $echo = true ) {
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
	function the_winning_bidder( $post_id = '', $echo = true ) {
		global $user_ID, $display_name;

		get_currentuserinfo(); // to set global $display_name

		$winning_bidder = $this->get_winning_bid( $post_id )->bidder_id;

		if ( !empty( $winning_bidder ) ){
			
			$winning_bidder = ( $winning_bidder == $user_ID) ? __( 'You', 'prospress' ) : get_userdata( $winning_bidder )->display_name;

			if ( $echo ) 
				echo $winning_bidder;
			else 
				return $winning_bidder;
		}
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
	function is_winning_bidder( $user_id = '', $post_id = '' ) {
		global $user_ID, $post;

		if ( empty( $post_id ) )
			$post_id = $post->ID;
		
		if ( $user_id == '' )
			$user_id = $user_ID;
		
		$winner = $this->get_winning_bid( $post_id )->bidder_id;

		return ( $user_id == $this->get_winning_bid( $post_id )->bidder_id ) ? true : false;
	}

	/**
	 * Get's the max bid for a post and user, optionally specified with $post_id and $user_id.
	 *
	 * If no user ID or post ID is specified, the function uses the global $post ad $user_ID 
	 * variables. 
	 */
	function get_users_max_bid( $user_id = '', $post_id = '' ) {
		global $user_ID, $post, $wpdb;

		if ( empty( $user_id ) )
			$user_id = $user_ID;

		if ( empty( $post_id ) )
			$post_id = $post->ID;
			
		$users_max_bid = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->bids WHERE post_id = %d AND bidder_id = %d AND bid_value = (SELECT MAX(bid_value) FROM $wpdb->bids WHERE post_id = %d AND bidder_id = %d)", $post_id, $user_id, $post_id, $user_id));

		return $users_max_bid;
	}

	// Prints the max bid for a user on a post, optionally specified with $post_id.
	function the_users_max_bid_value( $user_id = '', $post_id = '', $echo = true ) {
		$users_max_bid = get_users_max_bid( $user_id, $post_id );

		$users_max_bid = ( $users_max_bid->bid_value ) ? $users_max_bid->bid_value : __( 'No Bids.', 'prospress' );

		if ( $echo ) 
			echo $users_max_bid;
		else 
			return $users_max_bid;
	}

	// Get's the number of bids for a post, optionally specified with $post_id.
	function get_bid_count( $post_id = '' ) {
		global $post, $wpdb;

		if ( empty( $post_id ) )
			$post_id = $post->ID;

		$bid_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->bids WHERE post_id = %d", $post_id ) );

		return $bid_count;
	}

	/**
	 * Prints the number of bids on a post, optionally specified with $post_id.
	 */
	function the_bid_count( $post_id = '', $echo = true ) {
		$bid_count = ( empty( $post_id ) ) ? $this->get_bid_count() : $this->get_bid_count( $post_id );
		//echo ( $bid_count ) ? $bid_count : __( 'No Bids', 'prospress' );
		
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
	function get_bid_message(){
		global $pp_bid_status;

		// Avoid showing messages passed in latent url parameters
		if ( !is_user_logged_in() ) //|| !isset( $pp_bid_status ) )//|| !isset( $_GET[ 'bid_msg' ] ) )
			return;

		if ( isset( $pp_bid_status ) )
			$message_id = $pp_bid_status;
		elseif ( isset( $_GET[ 'bid_msg' ] ) )
			$message_id = $_GET[ 'bid_msg' ];

		if ( isset( $message_id ) ){
			switch( $message_id ) {
				case 0:
				case 1:
					$message = __("Congratulations, you are the winning bidder.", 'prospress' );
					break;
				case 2:
					$message = __("You have been outbid.", 'prospress' );
					break;
				case 3:
					$message = __("You must bid more than the winning bid.", 'prospress' );
					break;
				case 4:
					$message = __("Your maximum bid has been increased.", 'prospress' );
					break;
				case 5:
					$message = __("You can not decrease your maximum bid.", 'prospress' );
					break;
				case 6:
					$message = __("You have entered a bid equal to your current maximum bid.", 'prospress' );
					break;
				case 7:
					$message = __("Invalid bid. Please enter a valid number. e.g. 11.23 or 58", 'prospress' );
					break;
				case 8:
					$message = __("Invalid bid. Bid nonce did not validate.", 'prospress' );
					break;
				case 9:
					$message = __("Invalid bid. Please enter a bid greater than the starting price.", 'prospress' );
					break;
				case 10:
					$message = __("Bid submitted.", 'prospress' );
					break;
				case 11:
					$message = __("You cannot bid on your own post.", 'prospress' );
					break;
			}
			$message = apply_filters( 'bid_message', $message );
			return $message;
		}
	}


	/************************************************************************************************
	 * Private Functions. Don't worry about these, unless you want to get really tricky
	 ************************************************************************************************/

	/**
	 * 	Adds bid pages to admin menu
	 * 
	 * @uses add_object_page to add "Bids" top level menu
	 * @uses add_menu_page if add object page is not available to add "Bids" menu
	 * @uses add_submenu_page to add "Bids" and "Bid History" submenus to "Bids"
	 * @uses add_options_page to add administration pages for bid settings
	 * @return false if logged in user is not the site admin
	 **/
	function add_admin_pages() {

		$base_page = "bids";

		$bids_title = apply_filters( 'bids_admin_title', __( 'Bids', 'prospress' ) );

		if ( function_exists( 'add_object_page' ) ) {
			add_object_page( $bids_title, $bids_title, 'read', $base_page, '', PP_PLUGIN_URL . '/images/bid-16x16.png' );
		} elseif ( function_exists( 'add_menu_page' ) ) {
			add_menu_page( $bids_title, $bids_title, 'read', $base_page, '', PP_PLUGIN_URL . '/images/bid-16x16.png' );
		}

		$winning_bids_title = apply_filters( 'winning_bids_title', __( 'Winning Bids', 'prospress' ) );
		$bid_history_title = apply_filters( 'bid_history_title', __( 'Bid History', 'prospress' ) );

	    // Add submenu items to the bids top-level menu
		if (function_exists( 'add_submenu_page' )){
		    add_submenu_page( $base_page, $winning_bids_title, $winning_bids_title, 'read', $base_page, array( &$this, 'winning_history' ) );
		    add_submenu_page( $base_page, $bid_history_title, $bid_history_title, 'read', 'bid-history', array( &$this, 'admin_history' ) );
		}
	}

	// Print the feedback history for a user
	function admin_history() {
	  	global $wpdb, $user_ID;

		get_currentuserinfo(); //get's user ID of currently logged in user and puts into global $user_id

		$order_by = 'bid_date_gmt';
		$query = $this->create_bid_page_query();

		error_log( 'In admin_history, post create query' );

		$bids = $wpdb->get_results( $query, ARRAY_A );

		error_log( 'In admin_history, post get results' );

		$bids = apply_filters( 'admin_history_bids', $bids );
		
		error_log( 'In admin_history' );

		$this->print_admin_bids_table( $bids, __( 'Bid History', 'prospress' ), 'bid-history' );
	}

	function winning_history() {
	  	global $wpdb, $user_ID;

		get_currentuserinfo(); //get's user ID of currently logged in user and puts into global $user_id

		$query = $this->create_bid_page_query( 'winning' );

		$bids = $wpdb->get_results( $query, ARRAY_A );

		$bids = apply_filters( 'winning_history_bids', $bids );

		$this->print_admin_bids_table( $bids, __( 'Winning Bids', 'prospress' ), 'bids' );
	}

	function create_bid_page_query( $bid_status = '' ){
		global $wpdb, $user_ID;
		
		$query = $wpdb->prepare( "SELECT * FROM $wpdb->bids WHERE bidder_id = %d", $user_ID );

		if( !empty( $bid_status ) )
			$query .= $wpdb->prepare( ' AND bid_status = %s', $bid_status );

		if( isset( $_GET[ 'm' ] ) && $_GET[ 'm' ] != 0 ){
			$month	= substr( $_GET[ 'm' ], -2 );
			$year	= substr( $_GET[ 'm' ], 0, 4 );
			error_log("month = $month, year = $year");
			$query .= $wpdb->prepare( ' AND MONTH(bid_date) = %d AND YEAR(bid_date) = %d ', $month, $year );
		}

		if( isset( $_GET[ 'bs' ] ) && $_GET[ 'bs' ] != 0 ){
			$query .= ' AND bid_status = ';
			switch( $_GET[ 'bs' ] ){
				case 1:
					$query .= "'outbid'";
					break;
				case 2:
					$query .= "'winning'";
					break;
				default:
					break;
				}
		}

		if( isset( $_GET[ 'sort' ] ) ){
			$query .= ' ORDER BY ';
			switch( $_GET[ 'sort' ] ){
				case 1:
					$query .= 'bid_value';
					break;
				case 2:
					$query .= 'post_id';
					break;
				case 3:
					$query .= 'bid_status';
					break;
				case 4:
					$query .= 'bid_date_gmt';
					break;
				default:
					$query .= apply_filters( 'sort_bids_by', 'bid_date_gmt' );
				}
		}

		return $query;
	}

	function print_admin_bids_table( $bids, $title, $page ){
		global $wpdb, $user_ID, $wp_locale;

		$title = ( empty( $title ) ) ? 'Bids' : $title;

		if( empty( $bids ) && !is_array( $bids ) )
			$bids = array();

		$sort = isset( $_GET[ 'sort' ] ) ? (int)$_GET[ 'sort' ] : 0;
		$bid_status = isset( $_GET[ 'bs' ] ) ? (int)$_GET[ 'bs' ] : 0;

		?>
		<div class="wrap feedback-history">
			<?php screen_icon(); ?>
			<h2><?php echo $title; ?></h2>

			<form id="bids-filter" action="" method="get" >
				<input type="hidden" id="page" name="page" value="<?php echo $page; ?>">
				<div class="tablenav clearfix">
					<div class="alignleft">
						<select name='bs'>
							<option<?php selected( $bid_status, 0 ); ?> value='0'><?php _e( 'Any bid status', 'prospress' ); ?></option>
							<option<?php selected( $bid_status, 1 ); ?> value='1'><?php _e( 'Outbid', 'prospress' ); ?></option>
							<option<?php selected( $bid_status, 2 ); ?> value='2'><?php _e( 'Winning', 'prospress' ); ?></option>
						</select>
						<?php
						if( strpos( $title, 'Winning' ) !== false )
							$arc_query = $wpdb->prepare("SELECT DISTINCT YEAR(bid_date) AS yyear, MONTH(bid_date) AS mmonth FROM $wpdb->bids WHERE bidder_id = %d AND bid_status = 'winning' ORDER BY bid_date DESC", $user_ID );
						else 
							$arc_query = $wpdb->prepare("SELECT DISTINCT YEAR(bid_date) AS yyear, MONTH(bid_date) AS mmonth FROM $wpdb->bids WHERE bidder_id = %d ORDER BY bid_date DESC", $user_ID );
						error_log( "title = $title and arc_query = $arc_query" );
						$arc_result = $wpdb->get_results( $arc_query );
						$month_count = count( $arc_result);

						if ( $month_count && !( 1 == $month_count && 0 == $arc_result[0]->mmonth ) ) {
							$m = isset( $_GET['m' ] ) ? (int)$_GET['m' ] : 0;
						?>
						<select name='m'>
						<option<?php selected( $m, 0 ); ?> value='0'><?php _e( 'Show all dates', 'prospress' ); ?></option>
						<?php
						foreach ( $arc_result as $arc_row) {
							if ( $arc_row->yyear == 0 )
								continue;
							$arc_row->mmonth = zeroise( $arc_row->mmonth, 2 );

							if ( $arc_row->yyear . $arc_row->mmonth == $m )
								$default = ' selected="selected"';
							else
								$default = '';

							echo "<option$default value='" . esc_attr("$arc_row->yyear$arc_row->mmonth") . "'>";
							echo $wp_locale->get_month( $arc_row->mmonth) . " $arc_row->yyear";
							echo "</option>\n";
						}
						?>
						</select>
						<?php } ?>
						<input type="submit" value="Filter" id="filter_action" class="button-secondary action" />

						<select name='sort'>
							<option<?php selected( $sort, 0 ); ?> value='0'><?php _e( 'Sort by', 'prospress' ); ?></option>
							<option<?php selected( $sort, 1 ); ?> value='1'><?php _e( 'Bid Value', 'prospress' ); ?></option>
							<option<?php selected( $sort, 2 ); ?> value='2'><?php _e( 'Post', 'prospress' ); ?></option>
							<option<?php selected( $sort, 3 ); ?> value='3'><?php _e( 'Bid Status', 'prospress' ); ?></option>
							<option<?php selected( $sort, 4 ); ?> value='5'><?php _e( 'Bid Date', 'prospress' ); ?></option>
						</select>
						<input type="submit" value="Sort" id="sort_action" class="button-secondary action" />
					</div>
					<br class="clear" />
				</div>

			<table class="widefat fixed" cellspacing="0">
				<thead>
					<tr class="thead">
						<?php print_column_headers( $this->name() ); // Calls get_column_headings() added by add_filter( manage_$this->name()_columns ?>
					</tr>
				</thead>
				<tbody id="bids" class="list:user user-list">
				<?php
					if( !empty( $bids ) ){
						$style = '';
						foreach ( $bids as $bid ) {
							$post = get_post( $bid[ 'post_id' ] );
							$post_end_date = get_post_meta( $bid[ 'post_id' ], 'post_end_date', true );
							$post_status = ( $post->post_status == 'publish' ) ? 'Active' : $post->post_status;
							?>
							<tr class='<?php echo $style; ?>'>
								<td><a href='<?php echo get_permalink( $bid[ 'post_id' ] ); ?>'><?php echo $post->post_title; ?></a></td>
								<td><?php echo ucfirst( $post_status ); ?></td>
								<td><?php echo pp_money_format( $bid[ 'bid_value' ] ); ?></td>
								<td><?php echo ucfirst( $bid[ 'bid_status' ] ); ?></td>
								<td><?php echo mysql2date( __( 'g:ia d M Y' , 'prospress' ), $bid[ 'bid_date' ] ); ?></td>
								<td><?php echo mysql2date( __( 'g:ia d M Y' , 'prospress' ), $post_end_date ); ?></td>
								<?php if( strpos( $_SERVER['REQUEST_URI' ], 'bids' ) !== false ){
									$actions = apply_filters( 'winning_bid_actions', array(), $post->ID );
									echo '<td>';
									if( is_array( $actions ) && !empty( $actions ) ){
									?><div class="prospress-actions">
										<ul class="actions-list">
											<li class="base"><?php _e( 'Take action:', 'prospress' ) ?></li>
										<?php foreach( $actions as $action => $attributes )
											echo "<li class='action'><a href='" . add_query_arg ( array( 'action' => $action, 'post' => $post_id ) , $attributes['url' ] ) . "'>" . $attributes['label' ] . "</a></li>";
										 ?>
										</ul>
									</div>
									<?php
									} else {
										_e( 'No action can be taken.', 'prospress' );
									}
									echo '</td>';
								}?>
							<tr>
							<?php
							$style = ( 'alternate' == $style ) ? '' : 'alternate';
						}
					} else {
						echo '<tr><td colspan="5">' . __( 'No bids.', 'prospress' ) . '</td></tr>';
					}
				?>
				</tbody>
				<tfoot>
					<tr class="thead">
						<?php print_column_headers( $this->name() ); ?>
					</tr>
				</tfoot>
			</table>
		</div>
		<?php
	}

	// Returns bid column headings for market system. Used with the built in print_column_headers function.
	function get_column_headings(){
		$column_headings = $this->bid_table_headings;
		
		if( strpos( $_SERVER['REQUEST_URI' ], 'bids' ) !== false )
			$column_headings[ 'bid_actions' ] = __( 'Action', 'prospress' );

		return $column_headings;
	}

	// Add market system columns to tables of posts
	function add_post_column_headings( $column_headings ) {

		if( !( ( $_GET[ 'post_type' ] == $this->name() ) || ( get_post_type( $_GET[ 'post' ] ) ==  $this->name() ) ) )
			return $column_headings;

		foreach( $this->post_table_columns as $key => $column )
			$column_headings[ $key ] = $column[ 'title' ];

		return $column_headings;
	}

	/**
	 * 
	 **/
	function add_post_column_contents( $column_name, $post_id ) {
		if( array_key_exists( $column_name, $this->post_table_columns ) ) {
			$function = $this->post_table_columns[ $column_name ][ 'function' ];
			$this->$function( $post_id );
		}
	}

	function enqueue_bid_form_scripts(){
		
		if( is_admin() )
			return;

  		wp_enqueue_script( 'bid-form-ajax', PP_BIDS_URL . '/bid-form-ajax.js', array( 'jquery' ) );
		wp_localize_script( 'bid-form-ajax', 'pppostL10n', array(
			'endedOn' => __( 'Ended on:', 'prospress' ),
			'endOn' => __( 'End on:', 'prospress' ),
			'end' => __( 'End', 'prospress' ),
			'update' => __( 'Update', 'prospress' ),
			'repost' => __( 'Repost', 'prospress' ),
			));
	}

	function enqueue_bid_admin_scripts(){
		wp_enqueue_style( 'bids', PP_BIDS_URL . '/admin.css' );
	}

	function add_sort_options( $pp_sort_options ){
		$pp_sort_options['price-asc' ] = __( 'Price: low to high', 'prospress' );
		$pp_sort_options['price-desc' ] = __( 'Price: high to low', 'prospress' );

		return $pp_sort_options;
	}

	// Called with init hook to determine if a bid has been submitted. If it has, bid_form_submit is called.
	// Takes care of the logic of the class, determining if and when to call a function.
	function bid_controller(){
		global $pp_bid_status;

		//Is user trying to submit bid
		if( !isset( $_REQUEST[ 'bid_submit' ] ) )
			return;
		error_log( '********************************** $_REQUEST[ bid_submit ] set **********************************' );
		error_log( 'in bid_controller _GET = ' . print_r( $_GET, true));
		error_log( 'in bid_controller _POST = ' . print_r( $_POST, true));

		// If bidder is not logged in, redirect to login page
		if ( !is_user_logged_in() ){ 
			do_action( 'bidder_not_logged_in' );

			$redirect = wp_get_referer();
			$redirect = add_query_arg( urlencode_deep( $_POST ), $redirect );
			$redirect = add_query_arg( 'bid_redirect', wp_get_referer(), $redirect );
			$redirect = wp_login_url( $redirect );
			$redirect = apply_filters( 'bid_login_redirect', $redirect );

			error_log( '!is_user_logged_in(), $redirect = ' . $redirect);
			//if( isset( $_REQUEST[ 'ajax_bid' ] ) ){
			if( $_REQUEST[ 'bid_submit' ] == 'ajax' ){
				error_log("*** AJAX BID: returning $redirect ***");
				echo '{"redirect":"' . $redirect . '"}';
				die();
			} else {
				error_log("*** NON AJAX BID: redirecting ***");
				wp_safe_redirect( $redirect );
				exit();
			}
			error_log( 'in !is_user_logged_in() even after exit' );
		}

		// Verify bid nonce if bid is not coming from a login redirect
		if ( !isset( $_REQUEST[ 'bid_redirect' ] ) && ( !isset( $_REQUEST[ 'bid_nonce' ] ) || !wp_verify_nonce( $_REQUEST['bid_nonce' ], __FILE__) ) ) {
			if ( !isset( $_REQUEST[ 'bid_nonce' ] ))
				error_log( '$_REQUEST[ bid_nonce ] not set' );
			if ( !wp_verify_nonce( $_REQUEST['bid_nonce' ], __FILE__) )
				error_log( '$_REQUEST[ bid_nonce ] not valid' );
			$bid_status = 8;
		} elseif ( isset( $_GET[ 'bid_redirect' ] ) ) {
			error_log( 'in bid_controller, using _GET for bid_form_submit' );
			$bid_status = $this->bid_form_submit( $_GET[ 'post_ID' ], $_GET[ 'bid_value' ] );
		} else {
			error_log( 'in bid_controller, using _POST for bid_form_submit' );
			$bid_status = $this->bid_form_submit( $_POST[ 'post_ID' ], $_POST[ 'bid_value' ] );
		}

		// Redirect user back to post
		if ( !empty( $_REQUEST[ 'bid_redirect' ] ) ){
			//$location = wp_get_referer();
			error_log("** REDIRECT USER BACK TO POST **");
			$location = $_REQUEST[ 'bid_redirect' ];
			error_log( 'location equalling _REQUEST[ \'bid_redirect\' ] = ' . $location );
			$location = add_query_arg( 'bid_msg', $bid_status, $location );
			error_log("location after adding bid_msg = $location");
			$location = add_query_arg( 'bid_nonce', wp_create_nonce( __FILE__ ), $location );
			error_log("location after adding bid_nonce = $location");
			error_log("location = $location");
			wp_safe_redirect( $location );
			exit();
		}

		//wp_safe_redirect( $location );
		error_log("** setting global pp_bid_status var = $bid_status");
		$pp_bid_status = $bid_status;

		// If bid was submitted using Ajax
		if( $_POST[ 'bid_submit' ] == 'ajax' ){
			error_log("*********** AJAX BID SET ****************");
			echo $this->bid_form( $_POST[ 'post_ID' ] );
			die();
		}

		// If someone enters a URL with a bid_msg but they didn't make that bid
		if( isset( $_GET[ 'bid_msg' ] ) && isset( $_GET[ 'bid_nonce' ] ) && !wp_verify_nonce( $_GET[ 'bid_nonce' ], __FILE__ ) ){
			error_log( '********* USER ENTERING A URL WITH BS FOR A BID THEY DIDNT MAKE *********' );
			if ( !isset( $_GET[ 'bid_nonce' ] ))
				error_log( '$_GET[ bid_nonce ] not set' );
			if ( !wp_verify_nonce( $_GET['bid_nonce' ], __FILE__) )
				error_log( '$_GET[ bid_nonce ] not valid' );

			$redirect = remove_query_arg( 'bid_nonce' );
			$redirect = remove_query_arg( 'bid_msg', $redirect );
			error_log( "********* REDIRECTING TO $redirect *********" );
			wp_safe_redirect( $redirect );
			exit();
		}
	}

}

