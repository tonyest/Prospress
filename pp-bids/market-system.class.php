<?php

class PP_Market_System {

	var $name;					// Public name of the bid system e.g. "Auction".
	//var $description;			// Location of class file on server
	//var $file;					// Location of class file on server
	var $bid_form_title;		// Title for the bid form.
	var $bid_button_value;		// Text used on the submit button of the bid form.
	var $post_fields;			// Array of flags representing the fields which the bid system implements e.g. array( 'post_fields' )
	var $post_table_columns;	// Array of arrays, each array is used to create a column in the post tables. By default it adds two columns, one for number of bids on the post and the other for the current winning bid on the post e.g. 'current_bid' => array( 'title' => 'Winning Bid', 'function' => 'get_winning_bid'), 'bid_count' => array( 'title => 'Number of Bids', 'function' => 'get_bid_count')
	var $bid_table_headings;	// Array of name/value pairs to be used as column headings when printing table of bids. e.g. 'bid_id' => 'Bid ID', 'post_id' => 'Post', 'bid_value' => 'Amount', 'bid_date' => 'Date'

	// Constructors
	function PP_Market_System( $name, $bid_form_title = "", $bid_button_value = "", $post_fields = array(), $post_table_columns = array(), $bid_table_headings = array() ) {
		$this->__construct( $name, $bid_form_title, $bid_button_value, $post_fields, $post_table_columns, $bid_table_headings );
	}

	function __construct( $name, $bid_form_title = "", $bid_button_value = "", $post_fields = array(), $post_table_columns = array(), $bid_table_headings = array() ) {
		$this->name = (string)$name;
		//$this->description = (string)$description;
		//$this->file = (string)$file;
		$this->bid_form_title = empty( $bid_form_title ) ? __("Make a bid") : $bid_form_title;
		$this->bid_button_value = empty( $bid_button_value ) ? __("Bid now!") : $bid_button_value;
		//$this->post_fields = $post_fields;

		if( !is_array( $post_fields ) ){
			$this->post_fields = array();
		} else {
			$this->post_fields = $post_fields;
		}

		if( empty( $post_table_columns ) || !is_array( $post_table_columns ) ){
			$this->post_table_columns = array (	'current_bid' => array( 'title' => 'Winning Bid', 'function' => 'the_winning_bid_value' ),
												'bid_count' => array( 'title' => 'Number of Bids', 'function' => 'the_bid_count' ) );
		} else {
			$this->post_table_columns = $post_table_columns;
		}

		if( empty( $bid_table_headings ) || !is_array( $bid_table_headings ) ){
			$this->bid_table_headings = array( 
										'bid_value' => 'Amount', 
										'bid_date' => 'Bid Date',
										'post_id' => 'Post', 
										'post_status' => 'Post Status', 
										'post_end' => 'Post End Date'
										);
		} else {
			$this->bid_table_headings = $bid_table_headings;
		}

		if( !empty( $this->post_fields ) && in_array( 'post_fields', $this->post_fields ) ){
			add_action( 'admin_menu', array( &$this, 'post_fields_meta_box' ) );
			add_action( 'save_post', array( &$this, 'post_fields_submit' ), 10, 2 );
		}

		if( !empty( $this->post_table_columns ) && is_array( $this->post_table_columns ) ){
			add_filter( 'manage_posts_columns', array( &$this, 'add_post_column_headings' ) );
			add_action( 'manage_posts_custom_column', array( &$this, 'add_post_column_contents' ), 10, 2 );
		}

		// Determine if bid form submission function should be called
		add_action( 'init', array( &$this, 'bid_controller' ) );

		// Attaches the bid from to content, so when the_content() function is called, it includes the bid form.
		add_filter( 'the_content', array( &$this, 'form_filter' ) );

		// Adds columns for printing bid history table
		add_filter( 'manage_' . $this->name . '_columns', array( &$this, 'get_column_headings' ) );
		
		// For adding Ajax & other scripts
		add_action('wp_print_scripts', array( &$this, 'enqueue_bid_scripts' ) );
		
	}

	// Member functions that you must override.

	// The fields that make up the bid form.
	// The <form> tag and a bid form header and footer are automatically generated for the class.
	// You only need to enter the tags to capture information required by your bid system.
	function bid_form_fields( $post_id = NULL ) {
		die('function PP_Market_System::bid_form_fields() must be over-ridden in a sub-class.');
	}

	// Process the bid form fields upon submission.
	function bid_form_submit( $post_id = NULL, $bid_value = NULL, $bidder_id = NULL ){
		die('function PP_Market_System::bid_form_submit() must be over-ridden in a sub-class.');
	}

	// Validate a bid when the bid form.
	function bid_form_validate(){
		die('function PP_Market_System::bid_form_validate() must be over-ridden in a sub-class.');
	}

	// Functions that you may override, but do not need changes to make a new market system.

	// The function that brings all the bid form elements together.
	function bid_form( $post_id = NULL ) {
		global $post;

		$post_id = ( $post_id === NULL ) ? $post->ID : $post_id;
		$the_post = ( empty ( $post ) ) ? get_post( $post_id) : $post;

		//error_log('in bid system bid_form(), post = ' . print_r($post,true));
		//error_log('in bid system bid_form(), post_id = ' . print_r($post_id,true));

		$form = '<div id="bid">';
		$form .= '<h3>' . $this->bid_form_title . '</h3>';

		if ( $the_post->post_status != 'ended' ) {
			//$form .= $this->form_header();
			$form .= $this->get_bid_message();
			$form .= '<form id="bid_form" method="post" action="">';

			$form .= ( $post->post_status != 'ended' ) ? $this->bid_form_fields( $post_id ) : '<p>' . __( 'This post has ended. Bidding is closed.' ) . '</p>';
			
			/** @TODO Implement bid bar in PP_Market_System::bid_form()*/

			//$form .= $this->form_footer();
			apply_filters( 'bid_form_hidden_fields', $form );

			$form .= wp_nonce_field( __FILE__, 'bid_nonce', false, false );
			$form .= '<input type="hidden" name="post_ID" value="' . $post_id . '" id="post_ID" />';
			$form .= '<input name="bid_submit" type="submit" id="bid_submit" value="' . $this->bid_button_value .'" />';
			$form .= '</form>';

			$form = apply_filters( 'bid_form', $form );
		} else {
			$form .= '<p>' . __( 'This post has ended. Bidding is closed.' ) . '</p>';
		}

		$form .= '</div>';

		return $form;		
	}

	// Applied to "the_content" filter to add the bid form to the content of a page when viewed on single.
	// You may wish to override this funtion to show the bid form on other, or all pages.
	// Adding the form to the content via filter means all the beautiful WP themes that exist can be used with Prospress, without customisation.
	function form_filter( $content ) {

		//error_log("**in form_filter after unset & session destory pp_bid_status = " . print_r($pp_bid_status,true));
		//error_log('** in form_filter, $_REQUEST = ' . print_r( $_REQUEST, true ) );

		if( is_single() )
			$content .= $this->bid_form();

		return $content;
	}

	// Fields for taking input from the edit and add new post forms.
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
			add_meta_box('pp-bidding-options', __('Bidding Options'), array(&$this, 'post_fields'), 'post', 'normal', 'core');
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
			do_action('bid_post_not_found', $post_id);
			wp_die( __( 'Sorry, this post can not be found.' ) );
			exit;
		} elseif ( in_array($post_status, array('draft', 'pending') ) ) {
			do_action('bid_on_draft', $post_id);
			wp_die( __( 'Sorry, but you can not bid on a draft or pending post.' ) );
			exit;
		} elseif ($post_status == 'ended'){ // || $bid_date_gmt < post_end_date_gmt
			do_action('bid_on_ended', $post_id);
			wp_die( __( 'Sorry, this post has ended.' ) );
			exit;
		}

		return $post_status;
	}


	// Calculates the value of the new winning bid and updates it in the DB if necessary
	// Returns the value of the winning bid (either new or existing)
	//function update_winning_bid( $bid_ms, $post_id, $bid_value, $bidder_id ){
	function update_bid( $bid, $bid_ms ){
		global $wpdb;

		if ( $bid_ms[ 'bid_status' ] == 'invalid' ) // nothing to update
			return $current_winning_bid_value;

		$wpdb->insert( $wpdb->bids, $bid );

		return $bid[ 'bid_value' ];
	}


	/**
	 * Get's the max bid for a post, optionally specified with $post_id.
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
	 * Prints the max bid for a post, optionally specified with $post_id.
	 */
	function the_max_bid_value( $post_id = '', $echo = true ) {
		$max_bid = ( empty( $post_id ) ) ? $this->get_max_bid() : $this->get_max_bid( $post_id );
		
		$max_bid = ( $max_bid->bid_value ) ? pp_money_format( $max_bid->bid_value ) : __( 'No Bids' );

		//( $echo ) ? echo $max_bid; : return $max_bid;
		if ( $echo ) 
			echo $max_bid;
		else 
			return $max_bid;
	}

	function get_winning_bid( $post_id = '' ) {
		global $post, $wpdb;

		if ( empty( $post_id ) ){
			error_log('$post_id empty, setting post_id to ' . $post->ID);
			$post_id = $post->ID;
		}

		//error_log("selecting from " . $wpdb->bids . " WHERE post_id = $post_id AND bid_status = winning");
		$winning_bid = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->bids WHERE post_id = %d AND bid_status = %s", $post_id, 'winning' ) );

		//error_log('$winning_bid = ' . print_r($winning_bid, true));
		
		return $winning_bid;
		//return get_post_meta( $post_id, 'winning_bidder_id', true );
		//return $this->get_max_bid( $post_id )->bidder_id;
	}

	/**
	 * Prints the display name of the winning bidder for a post, optionally specified with $post_id.
	 */
	function the_winning_bidder( $post_id = '', $echo = true ) {
		global $user_ID, $display_name;

		get_currentuserinfo(); // to set global $display_name

		$winning_bidder = $this->get_winning_bid( $post_id )->bidder_id;

		if ( !empty( $winning_bidder ) ){
			
			$winning_bidder = ( $winning_bidder == $user_ID) ? __( 'You.' ) : get_userdata( $winning_bidder )->display_name;

			//( $echo ) ? echo $winning_bidder : return $winning_bidder;
			if ( $echo ) 
				echo $winning_bidder;
			else 
				return $winning_bidder;
		}
	}

	/**
	 * Function to test if a given user is classified as a winning bidder for a given post. 
	 * 
	 * As some bid systems may have multiple winners, it is important to use this function 
	 * instead of testing a user id against a user id provided with get_winning_bid.
	 */
	function is_winning_bidder( $user_id = '', $post_id = '' ) {
		global $user_ID, $post;

		//error_log("is_winning_bidder called with user_id $user_id and post_id $post_id");

		if ( empty( $post_id ) )
			$post_id = $post->ID;
		
		if ( $user_id == '' )
			$user_id = $user_ID;
		
		$winner = $this->get_winning_bid( $post_id )->bidder_id;
		//error_log("winning bidder = $winner");

		return ( $user_id == $this->get_winning_bid( $post_id )->bidder_id ) ? true : false;
	}

	/**
	 * Gets the value of the current winning bid for a post, optionally specified with $post_id.
	 *
	 * The value of the winning bid is not necessarily equal to the maximum bid. The winning bid
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

		$winning_bid = $this->get_winning_bid( $post_id );

		$winning_bid_value = $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM $wpdb->bidsmeta WHERE bid_id = %d AND meta_key = %s", $winning_bid->bid_id, 'winning_bid_value' ) );
		
		// If no winning bid value in meta table, default to max value of winning bid.
		if( empty( $winning_bid_value ) )
			$winning_bid_value = $winning_bid->bid_value;

		return $winning_bid_value;
	}

	/**
	 * Prints the value of the current winning bid for a post, optionally specified with $post_id.
	 *
	 * The value of the winning bid is not necessarily equal to the maximum bid. 
	 */
	function the_winning_bid_value( $post_id = '', $echo = true ) {
		$winning_bid = $this->get_winning_bid_value( $post_id );

		$winning_bid = ( $winning_bid == 0 ) ? __( 'No bids.' ) : pp_money_format( $winning_bid );

		//( $echo ) ? echo $winning_bid : return $winning_bid;
		if ( $echo ) 
			echo $winning_bid;
		else 
			return $winning_bid;
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

		//if ( empty($users_max_bid->bid_value))
		//	$users_max_bid->bid_value = 0;

		return $users_max_bid;
	}

	// Prints the max bid for a user on a post, optionally specified with $post_id.
	function the_users_max_bid_value( $user_id = '', $post_id = '', $echo = true ) {
		$users_max_bid = get_users_max_bid( $user_id, $post_id );

		$users_max_bid = ( $users_max_bid->bid_value ) ? $users_max_bid->bid_value : __('No Bids.');

		//( $echo ) ? echo $users_max_bid : return $users_max_bid;
		if ( $echo ) 
			echo $users_max_bid;
		else 
			return $users_max_bid;
	}

	// Get's the max bid for a post, optionally specified with $post_id.
	function get_bid_count( $post_id = '' ) {
		global $post, $wpdb;

		if ( empty( $post_id ) )
			$post_id = $post->ID;

		$bid_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->bids WHERE post_id = %d", $post_id ) );

		return $bid_count;
	}

	/**
	 * Prints the max bid for a post, optionally specified with $post_id.
	 */
	function the_bid_count( $post_id = '', $echo = true ) {
		$bid_count = ( empty( $post_id ) ) ? $this->get_bid_count() : $this->get_bid_count( $post_id );
		//echo ( $bid_count ) ? $bid_count : __( 'No Bids' );
		
		$bid_count = ( $bid_count ) ? $bid_count : __( 'No Bids' );

		//( $echo ) ? echo $bid_count : return $bid_count;	
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

		//foreach ( debug_backtrace() as $key => $value ) {
			//error_log( "** " . $key . ". " . $value['function'] . "( " . str_replace( array("\n","\t",' ','(',')'), '', print_r( $value['args'], true ) ) . " )" );
		//	error_log( "** " . $key . ". " . $value['function'] . "( " . $value['args'][0] . " )" );
		//	error_log( "  ** " . $value['file'] . ":" . $value['line'] );
		//}

		if ( !is_user_logged_in() ) //|| !isset( $pp_bid_status ) )//|| !isset( $_GET[ 'bid_msg' ] ) )
			return;

		//error_log('in get_bid_message, $_REQUEST = ' . print_r($_REQUEST, true));
		global $pp_bid_status;
		//error_log('in get_bid_message, $pp_bid_status = ' . print_r($pp_bid_status, true));

		if ( isset( $pp_bid_status ) )
			$message_id = $pp_bid_status;
		elseif ( isset( $_GET[ 'bid_msg' ] ) )
			$message_id = $_GET[ 'bid_msg' ];

		if ( isset( $message_id ) ){
			//$message_id = $pp_bid_status;
			error_log('in get_bid_message, isset $message_id == true, message_id = ' . print_r($pp_bid_status, true));

			switch( $message_id ) {
				case 0:
				case 1:
					$message = __("1. You are the highest bidder.");
					break;
				case 2:
					$message = __("2. You have been outbid.");
					break;
				case 3:
					$message = __("3. You must bid higher than the current winning bid.");
					break;
				case 4:
					$message = __("4. Your maximum bid has been increased.");
					break;
				case 5:
					$message = __("5. You can not decrease your max bid.");
					break;
				case 6:
					$message = __("6. You have entered a bid equal to your current maximum bid.");
					break;
				case 7:
					$message = __("7. Invalid bid. Please enter a valid number. e.g. 11.23 or 58");
					break;
				case 8:
					$message = __("8. Invalid bid. Bid nonce did not validate.");
					break;
				case 9:
					$message = __("9. Invalid bid. Bid must be higher than starting price.");
					break;
				case 10:
					$message = __("10. Bid submitted.");
					break;
			}
			$message = apply_filters( 'bid_message', $message );
			$message = '<em class="bid-updated" id="bid_msg">' . $message . '</em>';
			$message = apply_filters( 'bid_message_html', $message );
			return $message;
		}
	}
	

	// *******************************************************************************************************************
	// Private Functions. Don't worry about these, unless you want to get really tricky
	// *******************************************************************************************************************

	// Returns bid column headings for bid system. Used with the add headings to the built in print_column_headers function.
	function get_column_headings(){
		return $this->bid_table_headings;
	}

	// Print the feedback history for a user
	function admin_history() {
	  	global $wpdb, $user_ID;

		get_currentuserinfo(); //get's user ID of currently logged in user and puts into global $user_id

		$bids = $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->bids WHERE bidder_id = %d", $user_ID), ARRAY_A);

		$bids = apply_filters( 'admin_history_bids', $bids );
		//error_log('$bids = ' . print_r($bids, true));

		$this->print_admin_bids_table( $bids, __('Bid History') );
	}

	function winning_history() {
	  	global $wpdb, $user_ID;

		get_currentuserinfo(); //get's user ID of currently logged in user and puts into global $user_id

		$bids = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->bids WHERE bidder_id = %d AND bid_status = 'winning'", $user_ID ), ARRAY_A );

		$bids = apply_filters( 'winning_history_bids', $bids );
		//error_log('$bids = ' . print_r($bids, true));

		$this->print_admin_bids_table( $bids, __('Winning Bids') );
	}

	function print_admin_bids_table( $bids, $title ){
		global $wpdb, $user_ID, $wp_locale;

		$title = ( empty( $title ) ) ? 'Bids' : $title;

		if( empty( $bids ) && !is_array( $bids ) )
			$bids = array();
		
		$bid_status = 'winning';

		?>
		<div class="wrap feedback-history">
			<?php screen_icon(); ?>
			<h2><?php echo $title; ?></h2>

			<form id="bids-filter" action="<?php echo admin_url('admin.php?page=bids'); ?>" method="get" >
				<div class="tablenav clearfix">

				<div class="alignleft">
				<ul class="subsubsub" style="margin:0;">
					<li><?php _e('Bids on:' ); ?></li>
					<li><a href='#' class="" id="">All</a> |</li>
					<li><a href='#' class="paid" id="">Published Posts</a> |</li>
					<li><a href='#' class="sent" id="">Ended Posts</a></li>
				</ul>
			</div>
			<br class="clear" />
			<div class="alignleft">
				<?php
				$arc_query = $wpdb->prepare("SELECT DISTINCT YEAR(bid_date) AS yyear, MONTH(bid_date) AS mmonth FROM $wpdb->bids WHERE bid_status = %s ORDER BY bid_date DESC", $bid_status);
				$arc_result = $wpdb->get_results( $arc_query );
				$month_count = count($arc_result);

				if ( $month_count && !( 1 == $month_count && 0 == $arc_result[0]->mmonth ) ) {
					$m = isset($_GET['m']) ? (int)$_GET['m'] : 0;
				?>
				<select name='m'>
				<option<?php selected( $m, 0 ); ?> value='0'><?php _e('Show all dates'); ?></option>
				<?php
				foreach ($arc_result as $arc_row) {
					if ( $arc_row->yyear == 0 )
						continue;
					$arc_row->mmonth = zeroise( $arc_row->mmonth, 2 );

					if ( $arc_row->yyear . $arc_row->mmonth == $m )
						$default = ' selected="selected"';
					else
						$default = '';

					echo "<option$default value='" . esc_attr("$arc_row->yyear$arc_row->mmonth") . "'>";
					echo $wp_locale->get_month($arc_row->mmonth) . " $arc_row->yyear";
					echo "</option>\n";
				}
				?>
				</select>
				<?php } ?>

				<input type="submit" value="Filter" id="submit_bulk_action" class="button-secondary action" />
				</div>

				<div class="alignright">
				</div>
				</div>

			<table class="widefat fixed" cellspacing="0">
				<thead>
					<tr class="thead">
						<?php print_column_headers( $this->name ); ?>
					</tr>
				</thead>
				<tbody id="bids" class="list:user user-list">
				<?php
					if( !empty( $bids ) ){
						$style = '';
						foreach ( $bids as $bid ) { 
							$post = get_post( $bid[ 'post_id' ] );
							$post_end_date = get_post_meta( $bid[ 'post_id' ], 'post_end_date', true );
							?>
							<tr class='<?php echo $style; ?>'>
								<td><?php echo pp_money_format( $bid[ 'bid_value' ] );//$bid_money; ?></td>
								<td><?php echo mysql2date( __( 'g:ia d M Y' ), $bid[ 'bid_date' ] ); ?></td>
								<td><a href='<?php echo get_permalink( $bid[ 'post_id' ] ); ?>'>
									<?php echo $post->post_title; ?>
								</a></td>
								<td><?php echo ucfirst( $post->post_status ); ?></td>
								<td><?php echo mysql2date( __( 'g:ia d M Y' ), $post_end_date ); ?></td>
							<tr>
							<?php
							$style = ( 'alternate' == $style ) ? '' : 'alternate';
						}
					} else {
						echo '<tr><td colspan="5">You have no bidding history.</td></tr>';
					}
				?>
				</tbody>
				<tfoot>
					<tr class="thead">
						<?php print_column_headers( $this->name ); ?>
					</tr>
				</tfoot>
			</table>
		</div>
		<?php
	}

	// Add bid system columns to tables of posts
	function add_post_column_headings( $column_headings ) {

		foreach( $this->post_table_columns as $key => $column )
			$column_headings[ $key ] = $column[ 'title' ];

		return $column_headings;
	}

	function add_post_column_contents( $column_name, $post_id ) {
		if( array_key_exists( $column_name, $this->post_table_columns ) ) {
			$function = $this->post_table_columns[ $column_name ][ 'function' ];
			$this->$function( $post_id );
		}
	}

	function enqueue_bid_scripts(){
  		wp_enqueue_script( 'bid-form-ajax', PP_BIDS_URL . '/bid-form-ajax.js', array( 'jquery' ) );
		wp_localize_script( 'bid-form-ajax', 'pppostL10n', array(
			'endedOn' => __('Ended on:'),
			'endOn' => __('End on:'),
			'end' => __('End'),
			'update' => __('Update'),
			'repost' => __('Repost'),
			));

	}

	// Called with init hook to determine if a bid has been submitted. If it has, bid_form_submit is called.
	// Takes care of the logic of the class, determining if and when to call a function.
	function bid_controller(){
		global $pp_bid_status;

		//Is user trying to submit bid
		if( isset( $_REQUEST[ 'bid_submit' ] ) ){ //|| isset( $_REQUEST[ 'ajax_bid' ] ) ){
			error_log('********************************** $_REQUEST[ bid_submit ] set **********************************');
			error_log('in bid_controller _GET = ' . print_r($_GET, true));
			error_log('in bid_controller _POST = ' . print_r($_POST, true));

			// If bidder is not logged in, redirect to login page
			if ( !is_user_logged_in() ){ 
				do_action('bidder_not_logged_in');

				//$redirect = is_ssl() ? "https://" : "http://";
				//$redirect .= $_SERVER['HTTP_HOST'] . esc_url( $_SERVER['PHP_SELF'] );
				$redirect = wp_get_referer();
				$redirect = add_query_arg( urlencode_deep( $_POST ), $redirect );
				$redirect = add_query_arg('bid_redirect', wp_get_referer(), $redirect );
				$redirect = wp_login_url( $redirect );
				$redirect = apply_filters( 'bid_login_redirect', $redirect );

				error_log('!is_user_logged_in(), $redirect = ' . $redirect);
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
				error_log('in !is_user_logged_in() even after exit');
			}

			// Verify bid nonce if bid is not coming from a login redirect (deteremined by bid_redirect)
			if ( !isset( $_REQUEST[ 'bid_redirect' ] ) && ( !isset( $_REQUEST[ 'bid_nonce' ] ) || !wp_verify_nonce( $_REQUEST['bid_nonce'], __FILE__) ) ) {
				if ( !isset( $_REQUEST[ 'bid_nonce' ] ))
					error_log('$_REQUEST[ bid_nonce ] not set' );
				if ( !wp_verify_nonce( $_REQUEST['bid_nonce'], __FILE__) )
					error_log('$_REQUEST[ bid_nonce ] not valid' );
				$bid_status = 8;
			} elseif ( isset( $_GET[ 'bid_redirect' ] ) ) {
				error_log('in bid_controller, using _GET for bid_form_submit' );
				$bid_status = $this->bid_form_submit( $_GET[ 'post_ID' ], $_GET[ 'bid_value' ] );
			} else {
				error_log('in bid_controller, using _POST for bid_form_submit' );
				$bid_status = $this->bid_form_submit( $_POST[ 'post_ID' ], $_POST[ 'bid_value' ] );
			}

			// Redirect user back to post
			if ( !empty( $_REQUEST[ 'bid_redirect' ] ) ){
				//$location = wp_get_referer();
				error_log("** REDIRECT USER BACK TO POST **");
				$location = $_REQUEST[ 'bid_redirect' ];
				error_log('location equalling _REQUEST[ \'bid_redirect\' ] = ' . $location );
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
			//if ( isset( $_POST[ 'ajax_bid' ] ) ){
			if( $_POST[ 'bid_submit' ] == 'ajax' ){
				error_log("*********** AJAX BID SET ****************");
				echo $this->bid_form( $_POST[ 'post_ID' ] );
				die();
			}
		}

		// If a user is entering a URL with a bid_msg set but that user didn't make the bid
		if( isset( $_GET[ 'bid_msg' ] ) && isset( $_GET[ 'bid_nonce' ] ) && !wp_verify_nonce( $_GET[ 'bid_nonce' ], __FILE__ ) ){
			error_log( '********* USER ENTERING A URL WITH BS FOR A BID THEY DIDNT MAKE *********' );
			if ( !isset( $_GET[ 'bid_nonce' ] ))
				error_log('$_GET[ bid_nonce ] not set' );
			if ( !wp_verify_nonce( $_GET['bid_nonce'], __FILE__) )
				error_log('$_GET[ bid_nonce ] not valid' );

			$redirect = remove_query_arg( 'bid_nonce' );
			$redirect = remove_query_arg( 'bid_msg', $redirect );
			error_log( "********* REDIRECTING TO $redirect *********" );
			wp_safe_redirect( $redirect );
			exit();
		}

	
	}

}

