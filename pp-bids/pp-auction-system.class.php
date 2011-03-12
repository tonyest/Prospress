<?php
/**
 * Auction bid system class.
 * 
 * This class extends the base Market System class to create a standard auction system.
 *
 * @package Prospress
 * @since 0.1
 */
require_once ( PP_BIDS_DIR . '/pp-market-system.class.php' ); // Base class

class PP_Auction_Bid_System extends PP_Market_System {

	/**
	 * Create the Auction System object by setting up property values that differ
	 * to the Market System defaults and hooking functions as required. 
	 **/
	public function __construct() {
		do_action( 'auction_pre_construct' );

		$args = array(
				'label' => __( 'Auctions', 'prospress' ),
				'labels' => array(
					'name' => __( 'Auctions', 'prospress' ),
					'singular_name' => __( 'Auction', 'prospress' ),
					'bid_button' => __( 'Bid!', 'prospress' ) ),
				'description' => 'The Default Prospress Standard Auction System.',
				'adds_post_fields' => true
				);

		$args = apply_filters( 'auction_args', $args );

		add_action( 'wp_print_scripts', array( &$this, 'enqueue_auction_scripts' ) );

		add_action( 'auctions-controller', array( &$this, 'buy_now_return' ) );

		add_action( 'post-auctions-bid_form', array( &$this, 'add_buy_now_form' ) );

		add_action( 'paypal_ipn_verified', array( &$this, 'buy_form_submit' ) );

		add_filter( 'bid_message_unknown', array( &$this, 'buy_now_messages' ), 10, 2 );

		do_action( 'bid_on_completed_post', array( &$this, 'fix_buy_now_msg' ) );

		do_action( 'auction_init', $args );

		parent::__construct( 'auctions', $args );
	}

	/**
	 * Adds bid form fields to the bid form. Done as a separate function to allow for bid form
	 * to be customised in market system with a filter.
	 **/
	protected function bid_form_fields( $post_id = NULL ) { 
		global $post_ID, $currency_symbol;

		$post_id = ( $post_id === NULL ) ? $post_ID : $post_id;
		$bid_count = $this->get_bid_count( $post_id );
		$bid_bid_form_fields = '';
		$dont_echo = false;

		if( $bid_count == 0 ){
			$bid_bid_form_fields .= '<div id="current_bid_val">' . __("Starting Price: ", 'prospress' ) . pp_money_format( get_post_meta( $post_id, 'start_price', true ) ) . '</div>';
		} else {
			$bid_bid_form_fields .= '<div id="current_bid_num">' . __("Number of Bids: ", 'prospress' ) . $this->the_bid_count( $post_id, $dont_echo ) . '</div>';
			$bid_bid_form_fields .= '<div id="winning_bidder">' . __("Winning Bidder: ", 'prospress' ) . $this->the_winning_bidder( $post_id, $dont_echo ) . '</div>';
			$bid_bid_form_fields .= '<div id="current_bid_val">' . __("Current Bid: ", 'prospress' ) . $this->the_winning_bid_value( $post_id, $dont_echo ) . '</div>';
		}
		$bid_bid_form_fields .= '<label for="bid_value" class="bid-label">' . __( 'Enter max bid: ', 'prospress' ) . $currency_symbol . ' </label>';
		$bid_bid_form_fields .= '<input type="text" aria-required="true" tabindex="1" size="8" value="" id="bid_value" name="bid_value"/>';
		
		return $bid_bid_form_fields;
	}

	/**
	 * Called by the market system controller to handle submission of a bid form. 
	 **/
	protected function bid_form_submit( $post_id = NULL, $bid_value = NULL, $bidder_id = NULL ){
		global $user_ID, $wpdb;

		nocache_headers();

		//Get Bid details
		if( $post_id === NULL && isset( $_REQUEST[ 'post_ID' ] ) )
			$post_id = intval( $_REQUEST[ 'post_ID' ] );

		if( $bid_value === NULL && isset( $_REQUEST[ 'bid_value' ] ) )
			$bid_value = intval( $_REQUEST[ 'bid_value' ] );

		$post_id 		= ( isset( $_REQUEST[ 'post_ID' ] ) ) ? intval( $_REQUEST[ 'post_ID' ] ) : $post_id;
		$bid_value		= ( isset( $_REQUEST[ 'bid_value' ] ) ) ? floatval( $_REQUEST[ 'bid_value' ] ) : $bid_value;
		$bidder_id 		= ( isset( $bidder_id ) ) ? $bidder_id : $user_ID;
		$bid_date 		= current_time( 'mysql' );
		$bid_date_gmt 	= current_time( 'mysql', 1 );

		$bid = compact( "post_id", "bidder_id", "bid_value", "bid_date", "bid_date_gmt" );

		do_action( 'get_auction_bid', $bid );

		if( $this->is_post_valid( $post_id ) && $this->is_bid_valid( $post_id, $bid_value, $bidder_id ) ) {
			$bid[ 'bid_status' ] = $this->bid_status; //set in is_valid call
			$bid = apply_filters( 'bid_pre_db_insert', $bid );
			$this->update_bid( $bid );
			$bid[ 'bid_status' ] = $this->bid_status; //set in is_valid calls
		} else {
			$bid[ 'bid_status' ] = $this->bid_status;
		}

		$bid[ 'message_id' ] = $this->message_id;

		return $bid;
	}

	/**
	 * Process a transaction when returning from PayPal. 
	 *
	 * This is called manually in the "buy_now_return" function and also hooked to 
	 * the PayPal IPN listener.
	 **/
	public function buy_form_submit() {
		global $currency;

		$buy_now_price = get_post_meta( $_POST['item_number'], 'buy_now_price', true );

		// Payment not completed
		if( $_POST[ 'payment_status' ] != 'Completed' && $_GET[ 'return_info'] != 'success' ) {
			error_log( 'PayPal IPN Error: PayPal Payment status not completed, status = ' . print_r( $_POST[ 'payment_status' ], true ) );
			return;
		}

		// Transaction already processed
		if( $_POST[ 'txn_id' ] == get_post_meta( $_POST['item_number'], 'paypal_txn_id', true ) ) {
			error_log( 'PayPal IPN Error: PayPal Transaction already processed, txn_id = ' . print_r( $_POST[ 'txn_id' ], true ) );
			return;
		}

		// Incomplete callback?
		if( !isset( $_POST[ 'item_number' ] ) ){
			error_log( 'PayPal IPN Error: No post supplied for buy now form submission. ' );
			wp_die( 'PayPal IPN Error: No post supplied for buy now form submission.' );
		}

		// Check that receiver_email is the PayPal email of the payee/post author
		if( $_POST['receiver_email'] != pp_invoice_user_settings( 'paypal_address', get_post( $_POST[ 'item_number' ] )->post_author ) ) {
			error_log( 'PayPal IPN Error: PayPal Email not payees, receiver_email = '. print_r( $_POST[ 'receiver_email' ], true ) );
			wp_die( 'PayPal IPN Error: PayPal Email not the same as Payee\'s email.' );
		}

		// Payment amount equal to buy now price?
		if( $_POST[ 'mc_gross' ] != $buy_now_price ) { 
			error_log( 'PayPal IPN Error: Buy now price incorrect, mc_gross = ' . print_r( $_POST['mc_gross'], true ) );
			wp_die( 'PayPal IPN Error: Buy now price incorrect.' );
		}

		// Payment currency correct?
		if( $_POST[ 'mc_currency' ] != $currency ) {
			error_log( 'PayPal IPN Error: Currency incorrect, mc_currency = ' . print_r( $_POST['mc_currency'], true ) );
			wp_die( 'PayPal IPN Error: PayPal transaction is using an incorrect currency incorrect.' );
		}

		// Payment currency correct?
		if( !wp_verify_nonce( $_POST[ 'invoice' ], $_POST[ 'item_number' ] + 5 ) ){
			wp_die( 'PayPal IPN Error: Buy Now Nonce Verification Fail' );
		}

		if( !$this->is_post_valid( $_POST[ 'item_number' ] ) ) {
			wp_die( 'PayPal IPN Error: This post is not valid for buy now.' );
		}

		// Check if a user account exists for payer email, if so use that account as payer on invoice, if not, create a new user
		// email_exists() & username_exists() not loaded by default in < WP3.1
		if( !function_exists( 'email_exists' ) || !function_exists( 'username_exists' ) )
			require_once( ABSPATH . WPINC . '/registration.php' ); 

		if( email_exists( $_POST[ 'payer_email' ] ) ){
			$user_id = get_user_by_email( $_POST[ 'payer_email' ] )->ID;
		} else {
			$user_name = explode( '@', $_POST[ 'payer_email' ] );

			$inc = 1;
			while( username_exists( $user_name ) ){
				$user_name .= $inc;
				$inc++;
			}

			// Need the register_new_user() function to send an email notification & generate a password, but don't want to output the login page HTML
			ob_start();
			@require_once( ABSPATH . 'wp-login.php' );
			ob_get_clean();
			$user_id = register_new_user( $user_name[0], $_POST[ 'payer_email' ] );
		}

		update_post_meta( $_POST[ 'item_number' ], 'paypal_txn_id', $_POST[ 'txn_id' ] );

		$buy_bid = array( 'post_id' => $_POST[ 'item_number' ], 
			                'bidder_id' => $user_id,
							'bid_value' => $buy_now_price,
			                'bid_status' => 'winning',
			                'bid_date' => current_time( 'mysql' ),
			                'bid_date_gmt' => current_time( 'mysql', 1 ) );
		$this->bid_status = 'winning';
		$this->message_id = 16;
		$this->update_bid( $buy_bid );

		pp_end_post( $_POST[ 'item_number' ] ); // also generates invoice with pending status
		$invoice_id = pp_get_invoice_id( $_POST[ 'item_number' ] );
		pp_invoice_paid( $invoice_id, 'PayPal' );
	}


	/**
	 * For a bid to be accepted, it must fulfil a number of criteria. A custom message ID
	 * must also be set to provide information about the bid, e.g. first bid on an auction. 
	 * This function performs both of these tasks. 
	 **/
	protected function validate_bid( $post_id, $bid_value, $bidder_id ){

		$post_max_bid		= $this->get_max_bid( $post_id );
		$bidders_max_bid	= $this->get_users_max_bid( $bidder_id, $post_id );

		if( isset( $_GET[ 'buy_now' ] ) && $_GET[ 'buy_now' ] == 'paypal' ){
			$this->message_id = 16;
			$this->bid_status = 'winning';
			do_action( 'buy_now_success' );
		} elseif ( $bidder_id == get_post( $post_id )->post_author ) {
			$this->message_id = 11;
			$this->bid_status = 'invalid';
		} elseif ( empty( $bid_value ) || $bid_value === NULL || !preg_match( '/^[0-9]*\.?[0-9]*$/', $bid_value ) ) {
			$this->message_id = 7;
			$this->bid_status = 'invalid';
		} elseif ( $bidder_id != @$this->get_winning_bid( $post_id )->post_author ) { // bidder not current winning bidder
			$current_winning_bid_value = $this->get_winning_bid_value( $post_id );
			if ( $this->get_bid_count( $post_id ) == 0 ) { // first bid
				$start_price = get_post_meta( $post_id, 'start_price', true );
				if ( $bid_value < $start_price ){
					$this->message_id = 9;
					$this->bid_status = 'invalid';
				} else {
					$this->message_id = 0;
					$this->bid_status = 'winning';
					do_action( 'first_auction_bid', $post_id, $bid_value, $bidder_id );
				}
			} elseif ( $bid_value > $post_max_bid->post_content ) { // Bid above winning bid
				$this->message_id = 1;
				$this->bid_status = 'winning';
				do_action( 'auction_outbid', $post_id, $bid_value, $bidder_id, $post_max_bid );
			} elseif ( $bid_value <= $current_winning_bid_value ) { // Bid below current winning bid
				$this->message_id = 3;
				$this->bid_status = 'invalid';
			} elseif ( $bid_value <= $post_max_bid->post_content ) { // Bid below current max bid
				$this->message_id = 2;
				$this->bid_status = 'outbid';
				do_action( 'auction_auto_outbid', $post_id, $bid_value, $bidder_id, $post_max_bid );
			}
		} elseif ( $bid_value > $bidders_max_bid->post_content ){ //bidder increasing max bid
			$this->message_id = 4;
			$this->bid_status = 'winning';
			do_action( 'auction_increase_bid', $post_id, $bid_value, $bidder_id, $post_max_bid );
		} elseif ( $bid_value < $bidders_max_bid->post_content ) { //bidder trying to decrease max bid
			$this->message_id = 5;
			$this->bid_status = 'invalid';
		} else {  //bidder entering bid equal to her current max bid
			$this->message_id = 6;
			$this->bid_status = 'invalid';
		}

		$bid_status_msg = array( 'bid_status' => $this->bid_status, 'message_id' => $this->message_id );
		do_action( 'auction_validate_bid', $bid_status_msg, $post_id, $bid_value, $bidder_id, $post_max_bid );
		return $bid_status_msg;
	}

	/**
	 * Takes a new bid and updates the status of prior bids accordingly.
	 **/
	protected function update_bid( $bid ){
		global $wpdb;

		$current_winning_bid_value 	= $this->get_winning_bid_value( $bid[ 'post_id' ] );

		// No need to update winning bid for invalid bids, bids too low
		if ( $bid[ 'bid_status' ] == 'invalid' )
			return $current_winning_bid_value;

		$posts_max_bid			= $this->get_max_bid( $bid[ 'post_id' ] );
		$current_winning_bid_id	= @$this->get_winning_bid( $bid[ 'post_id' ] )->ID;

		switch( $this->message_id ){
			case 0 : // First bid
				$start_price = get_post_meta( $bid[ 'post_id' ], 'start_price', true );
				$new_winning_bid_value = ( (float)$start_price != 0 ) ? $start_price : $this->get_bid_increment( $bid[ 'bid_value' ] );
				break;
			case 1 : // Bid value is over max bid & bidder different to current winning bidder
				$bid_value_incremented = $posts_max_bid->post_content + $this->get_bid_increment( $posts_max_bid->post_content );
				$new_winning_bid_value = ( $bid[ 'bid_value' ] > $bid_value_incremented ) ? $bid_value_incremented : $bid[ 'bid_value' ];
				break;
			case 2 :
				$bid_value_incremented = $bid[ 'bid_value' ] + $this->get_bid_increment( $bid[ 'bid_value' ] );
				$new_winning_bid_value = ( $posts_max_bid->post_content > $bid_value_incremented ) ? $bid_value_incremented : $posts_max_bid->post_content;
				break;
			case 4 : // Bidder increasing max bid, set their previous bid as 'outbid'
				$new_winning_bid_value = $current_winning_bid_value;
				break;
			case 16 :
				$new_winning_bid_value = $bid[ 'bid_value' ];
				break;
		}

		parent::update_bid( $bid );

		if( $this->message_id != 2 ){ // valid bid, over existing max, change winning bid id and bid value in meta table
			$wpdb->update( $wpdb->posts, array( 'post_status' => 'outbid' ), array( 'ID' => $current_winning_bid_id ) );
			delete_post_meta( $current_winning_bid_id, 'winning_bid_value' );
			$new_winning_bid_id = $this->get_winning_bid( $bid[ 'post_id' ] )->ID;
			update_post_meta( $new_winning_bid_id, 'winning_bid_value', $new_winning_bid_value );
			do_action( 'new_winning_bid', $bid );
		} else { // current winning bid is still winning bid, just need to update winning bid value
			update_post_meta( $current_winning_bid_id, 'winning_bid_value', $new_winning_bid_value );
			do_action( 'updating_winning_bid', $bid );
		}

		return $new_winning_bid_value;
	}
	
	/**
	 * Bid increment
	 * 
	 * Provides a function and filter to alter the format of bid increments.  The basic form is a simple math 'linear equation' arranged to give a percentage increase.
	 *
	 * @package Prospress
	 * @since 0.1
	 */
	protected function get_bid_increment( $bid_value ) {

		$coefficient	= 0.05; // Default 5% increase
		$constant		= 0;
		$increment 		= $bid_value * $coefficient + $constant;
		$eqn 			= apply_filters( 'increment_bid_value' , array( 'increment' => $increment , 'bid_value' => $bid_value , 'coefficient' => $coefficient , 'constant' => $constant ) );

		return $eqn[ 'increment' ];
	}

	/**
	 * Add start price & buy now fields to the "Add/Edit Auction" form. 
	 **/
	public function post_fields(){
		global $post_ID, $currency_symbol, $user_ID;

		$start_price = floatval( get_post_meta( $post_ID, 'start_price', true ) );
		$buy_now_price = floatval( get_post_meta( $post_ID, 'buy_now_price', true ) );

		$disabled = '';
		$disabled_msg = '';

		if( $this->get_bid_count( $post_ID ) > 0 ){
			$disabled = 'disabled="disabled" ';
			$disabled_msg = __( 'Bids have been made on your auction, you cannot change this price.', 'prospress' );
		}

		$accepted_payments = pp_invoice_user_accepted_payments( $user_ID );

		if( true != @$accepted_payments[ 'paypal_allow' ] ) {
			$disable_buy = 'disabled="disabled" ';
			$disabled_buy_msg = sprintf( __( 'To offer buy now, you must <a href="%s">setup PayPal</a> as a payment method.', 'prospress' ), admin_url( 'admin.php?page=user_settings_page' ) );
		} else {
			$disable_buy = $disabled;
			$disabled_buy_msg = $disabled_msg;
		}

		wp_nonce_field( __FILE__, 'selling_options_nonce', false ) ?>
<table>
<tbody>
	<tr>
		<td class="left">
			<label for="start_price"><?php echo __( "Starting Price: ", 'prospress' ) . $currency_symbol; ?></label>
		</td>
		<td>
	 		<input type="text" name="start_price" value="<?php echo number_format_i18n( $start_price, 2 ); ?>" size="20" <?php echo $disabled; ?>/>
			<?php if( $disabled != '' ) echo '<span>' . $disabled_msg . '</span>'; ?>
		</td>
	</tr>
	<tr>
		<td class="left">
			<label for="buy_now_price"><?php echo __( "Buy Now Price: ", 'prospress' ) . $currency_symbol; ?></label>
		</td>
		<td>
	 		<input type="text" name="buy_now_price" value="<?php echo number_format_i18n( $buy_now_price, 2 ); ?>" size="20" <?php echo $disable_buy; ?>/>
			<?php if( $disable_buy != '' ) echo '<span>' . $disabled_buy_msg . '</span>'; ?>
		</td>
	</tr>
</tbody>
</table>
		<?php
	}

	/**
	 * Save the start price & buy now fields when an auction is published/updated.
	 **/
	public function post_fields_save( $post_id, $post ){
		global $wpdb, $wp_locale;

		if( wp_is_post_revision( $post_id ) )
			$post_id = wp_is_post_revision( $post_id );

		if( $this->name != @$_POST[ 'post_type' ] || !current_user_can( 'edit_post', $post_id ) ){
			return $post_id;
		} elseif( $this->get_bid_count( $post_id ) || !isset( $_POST[ 'selling_options_nonce' ] ) || !wp_verify_nonce( $_POST[ 'selling_options_nonce' ], __FILE__ ) ){
			return $post_id;
		}

		$ts = preg_quote( $wp_locale->number_format['thousands_sep'] );

		if( !$this->get_bid_count( $post_id ) ) {
			$_POST[ 'start_price' ] = floatval( preg_replace( "/$ts|\s/", "", $_POST[ 'start_price' ] ) );
			update_post_meta( $post_id, 'start_price', $_POST[ 'start_price' ] );
		}

		$old_buy_price = get_post_meta( $post_id, 'buy_now_price', true );

		if( isset( $_POST[ 'buy_now_price' ] ) && ( $this->get_bid_count( $post_id ) == 0 || $old_buy_price >= $this->get_winning_bid_value( $post_id ) ) ) {
			$buy_now_price = floatval( preg_replace( "/$ts|\s/", "", $_POST[ 'buy_now_price' ] ) );
			$buy_now_price = ( $buy_now_price < $_POST[ 'start_price' ] ) ? $_POST[ 'start_price' ] : $buy_now_price;
			update_post_meta( $post_id, 'buy_now_price', $buy_now_price );
		}
	}

	/**
	 * Called on bid_table_actions hook
	 **/
	public function add_bid_table_actions( $actions, $post_id, $bid ) {
		global $user_ID;

		$post = get_post( $post_id );

		if( $post->post_status == 'completed' || $user_ID != $bid->post_author )
			return;

		$permalink = get_permalink( $post_id );

		$actions[ 'edit-bid' ] = array( 'label' =>  __( 'Increase Bid', 'prospress' ),
										'url' => $permalink );

		return $actions;
	}

	/**
	 * Adds bid system specific sort options to the post system sort widget
	 **/
	public function add_sort_options( $sort_options ){
		$sort_options['price-asc' ] = __( 'Price: low to high', 'prospress' );
		$sort_options['price-desc' ] = __( 'Price: high to low', 'prospress' );

		return $sort_options;
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
	public function get_winning_bid_value( $post_id = '' ) {
		global $post, $wpdb;

		if ( empty( $post_id ) )
			$post_id = $post->ID;

		if ( $this->get_bid_count( $post_id ) == 0 ){
			$winning_bid_value = get_post_meta( $post_id, 'start_price', true );
		} else {
			// Need to do this manually as get_winning_bid() calls this function
			$winning_bid = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->posts WHERE post_type = %s AND post_parent = %d AND post_status = %s", $this->bid_object_name, $post_id, 'winning' ) );

			if( !is_object( $winning_bid ) )
				return false;

			$winning_bid_value = get_post_meta( $winning_bid->ID, 'winning_bid_value', true );

			if( empty( $winning_bid_value ) )
				$winning_bid_value = $winning_bid->post_content;
		}

		return $winning_bid_value;
	}

	/**
	 * The buy now form is kept separate from the main bid form, partly as a test of Prospress'
	 * extensibility, partly because it was added at a later stage and partly because it is a
	 * distinct form to the bid form. 
	 **/
	public function add_buy_now_form( $post_id = NULL ) {
		global $currency, $post;

		$post_id = ( $post_id === NULL ) ? $post->ID : (int)$post_id;

		if ( !$this->is_post_valid( $post_id ) )
			return;

		// If auction author has setup PayPal & set a "Buy Now" price for the auction > 0 and current price
		$post_details = get_post( $post_id );
		$accepted_payments = pp_invoice_user_accepted_payments( $post_details->post_author );

		// Create temp invoice object for setting up PayPal form
		$invoice->payee_class = get_userdata( $post_details->post_author );
		$invoice->payee_class->pp_invoice_settings = pp_invoice_user_settings( 'all', $post_details->post_author );
		$invoice->amount = get_post_meta( $post_id, 'buy_now_price', true );

		if( $invoice->amount == 0 || $invoice->amount < $this->get_winning_bid_value( $post_id ) || true != $accepted_payments[ 'paypal_allow' ] || !is_email( $invoice->payee_class->pp_invoice_settings[ 'paypal_address' ] ) )
			return;

		$invoice->post_title= $post_details->post_title;
		$invoice->pay_link	= add_query_arg( array( 'buy_now' => 'paypal' ), get_permalink( $post_id ) );
		$invoice->post_id   = $post_id; // no invoice yet
		$invoice->id		= wp_create_nonce( $post_id + 5 ); // no invoice yet
		$invoice->currency_code = $currency;

		$button = 'buy';
		$id		= 'buy_form-' . $post_id;
		$class 	= 'buy-form';
		$form_extras = '<h4 class="buy-title">' . __( 'Buy Now', 'prospress' ) . '</h4>';
		if( in_array( @$this->message_id, array( 15, 16 ) ) )
			$form_extras .= '<div class="bid-updated bid_msg" >' . $this->get_message() . '</div>';
		$form_extras .= '<div class="buy-price">';
		$form_extras .= sprintf( __( 'Price: %s', 'prospress' ), pp_money_format( $invoice->amount ) );
		$form_extras .= '</div>';

		include_once( PP_INVOICE_UI_PATH . "payment_paypal.php" );
	}

	/**
	 * This function is hooked to the very beginning of the market system controller. 
	 * It checks if the page request is either made by PayPal's IPN system or is returning
	 * from 
	 **/
	public function buy_now_return() {

		// Return from make payment form
		if( !isset( $_GET[ 'buy_now' ] ) || !isset( $_POST[ 'payment_status' ] ) )
		return;

		if( isset( $_GET[ 'return_info'] ) )
			switch( $_GET[ 'return_info'] ) {
				case 'cancel':
					$this->message_id = 15;
					break;
				case 'notify': // PayPal IPN
					pp_paypal_ipn_listener(); // check response, fire paypal_ipn_$status hook for buy_now_paypal_ipn function
					break;
				case 'success':
					$this->buy_form_submit();
					break;
			}
	}

	public function buy_now_messages( $message, $message_id ) {
		if( $message_id == 15 )
			$message = __( 'Purchase Cancelled.', 'prospress' );
		elseif( $message_id == 16 )
			$message = __( 'Purchase successful, congratulations. The seller has been notified & will be in contact shortly.', 'prospress' );
		return $message;
	}
	
	/**
	 * When a user returns to the site from PayPal, if the IPN has fired already, 
	 * the post will have completed and they will receive the message "Auction Finished". 
	 *
	 * The message needs to be set to show purchase success.
	 **/
	public function fix_buy_now_msg( $post_id ){

		if( $this->is_winning_bidder( '', $post_id ) || ( isset( $_POST[ 'payment_status' ] ) && isset( $_POST[ 'payment_status' ] ) != 'Completed' ) )
			$this->message_id = 16;
	}


	public function enqueue_auction_scripts(){
		if( is_admin() || ( !$this->is_index() && !$this->is_single() ) )
			return;

		wp_enqueue_script( 'final-countdown', PP_PLUGIN_URL . '/js/final-countdown.js', array( 'jquery' ) );
		wp_localize_script( 'final-countdown', 'bidi18n', array( 'siteUrl' => get_bloginfo('wpurl') ) );
	}	
}
