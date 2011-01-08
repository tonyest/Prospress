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

	// Constructors

	public function __construct() {
		do_action( 'auction_pre_construct' );

		if ( !defined( 'AUCTION_BID_INCREMENT' ) )
			define( 'AUCTION_BID_INCREMENT', '0.05' );

		$args = array(
				'label' => __( 'Auctions', 'prospress' ),
				'labels' => array(
					'name' => __( 'Auctions', 'prospress' ),
					'singular_name' => __( 'Auction', 'prospress' ),
					'bid_button' => __( 'Bid!', 'prospress' ) ),
				'description' => 'The Default Prospress Standard Auction System.',
				'adds_post_fields' => true
				);

		add_action( 'auctions-controller', array( &$this, 'buy_now_return' ) );

		add_action( 'post-auctions-bid_form', array( &$this, 'add_buy_now_form' ) );

		add_filter( 'bid_message_unknown', array( &$this, 'buy_now_messages' ), 10, 2 );

		do_action( 'auction_init', $args );

		parent::__construct( 'auctions', $args );
	}

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

	protected function bid_form_submit( $post_id = NULL, $bid_value = NULL, $bidder_id = NULL ){
		global $user_ID;
		nocache_headers();

		//Get Bid details
		if( $post_id === NULL && isset( $_REQUEST[ 'post_ID' ] ) )
			$post_id = intval( $_REQUEST[ 'post_ID' ] );

		if( $bid_value === NULL && isset( $_REQUEST[ 'bid_value' ] ) )
			$bid_value = intval( $_REQUEST[ 'bid_value' ] );

		$bidder_id 		= ( isset( $bidder_id ) ) ? $bidder_id : $user_ID;
		$bid_date 		= current_time( 'mysql' );
		$bid_date_gmt 	= current_time( 'mysql', 1 );

		$bid = compact( "post_id", "bidder_id", "bid_value", "bid_date", "bid_date_gmt" );

		do_action( 'get_auction_bid', $bid );

		if( $this->is_post_valid( $post_id ) && $this->is_bid_valid( $post_id, $bid_value, $bidder_id ) ) {
			$bid[ 'bid_status' ] = $this->bid_status; //set in is_valid call
			$bid = apply_filters( 'bid_pre_db_insert', $bid );
			$this->update_bid( $bid );
		} else {
			$bid[ 'bid_status' ] = $this->bid_status;
		}

		$bid[ 'message_id' ] = $this->message_id;
		error_log( 'bid_form_submit bid = ' . print_r( $bid, true ) );
		return $bid;
	}
	
	protected function buy_form_submit( $post_id = NULL, $bid_value = NULL, $bidder_id = NULL ) {
		global $user_ID;

		if( $post_id == NULL || empty( $post_id ) )
			$post_id = ( isset( $_REQUEST[ 'pid' ] ) ) ? intval( $_REQUEST[ 'pid' ] ) : 0;

		if( $bidder_id == NULL || empty( $bidder_id ) ) // Allow anonymous buyers
			$bidder_id = ( is_user_logged_in() ) ? $user_ID : 0;

		$bid_value = get_post_meta( $post_id, 'buy_now_price', true );

		return $this->bid_form_submit( $post_id, $bid_value, $bidder_id );
	}

	protected function validate_bid( $post_id, $bid_value, $bidder_id ){

		$post_max_bid		= $this->get_max_bid( $post_id );
		$bidders_max_bid	= $this->get_users_max_bid( $bidder_id, $post_id );

		if( isset( $_GET[ 'buy_now' ] ) && $_GET[ 'buy_now' ] == 'paypal' ){
			$this->message_id = 16;
			$this->bid_status = 'winning';
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
			} elseif ( $bid_value > $post_max_bid->post_content ) { // bid above winning bid
				$this->message_id = 1;
				$this->bid_status = 'winning';
				do_action( 'auction_outbid', $post_id, $bid_value, $bidder_id, $post_max_bid );
			} elseif ( $bid_value <= $current_winning_bid_value ) {
				$this->message_id = 3;
				$this->bid_status = 'invalid';
			} elseif ( $bid_value <= $post_max_bid->post_content ) {
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

	protected function update_bid( $bid ){
		global $wpdb;

		$current_winning_bid_value 	= $this->get_winning_bid_value( $bid[ 'post_id' ] );

		// No need to update winning bid for invalid bids, bids too low
		if ( $bid[ 'bid_status' ] == 'invalid' )
			return $current_winning_bid_value;

		$posts_max_bid			= $this->get_max_bid( $bid[ 'post_id' ] );
		$current_winning_bid_id	= @$this->get_winning_bid( $bid[ 'post_id' ] )->ID;

		switch( $this->message_id ){
			case 0:
				$start_price = get_post_meta( $bid[ 'post_id' ], 'start_price', true );
				$new_winning_bid_value = ( (float)$start_price != 0 ) ? $start_price :  ( $bid[ 'bid_value' ] * AUCTION_BID_INCREMENT );
				break;
			case 1:
				$new_winning_bid_value = ( $bid[ 'bid_value' ] > ( $posts_max_bid->post_content * ( AUCTION_BID_INCREMENT + 1 ) ) ) ? $new_winning_bid_value = $posts_max_bid->post_content * ( AUCTION_BID_INCREMENT + 1 ) : $bid[ 'bid_value' ];
				break;
			case 2:
				$bid_value_incremented = $bid[ 'bid_value' ] * ( 1 + AUCTION_BID_INCREMENT );
				$new_winning_bid_value = ( $posts_max_bid->post_content > $bid_value_incremented ) ? $bid_value_incremented : $posts_max_bid->post_content;
				break;
			case 4:
				$new_winning_bid_value = $current_winning_bid_value;
				break;
			case 16:
				$new_winning_bid_value = $bid[ 'bid_value' ];
				break;
		}

		parent::update_bid( $bid );

		if( $this->message_id != 2 ){ // valid bid, over existing max, change winning bid id and bid value in meta table
			$wpdb->update( $wpdb->posts, array( 'post_status' => 'outbid' ), array( 'ID' => $current_winning_bid_id ) );
			delete_post_meta( $current_winning_bid_id, 'winning_bid_value' );
			$new_winning_bid_id = $this->get_winning_bid( $bid[ 'post_id' ] )->ID;
			update_post_meta( $new_winning_bid_id, 'winning_bid_value', $new_winning_bid_value );
		} else { // current winning bid is still winning bid, just need to update winning bid value
			update_post_meta( $current_winning_bid_id, 'winning_bid_value', $new_winning_bid_value );
		}

		return $new_winning_bid_value;
	}

	public function post_fields(){
		global $post_ID, $currency_symbol, $user_ID;

		$start_price = get_post_meta( $post_ID, 'start_price', true );
		$buy_now_price = get_post_meta( $post_ID, 'buy_now_price', true );

		$disabled = ( $this->get_bid_count( $post_ID ) ) ? 'disabled="disabled" ' : '';
		$disable_buy = ( $this->get_bid_count( $post_ID ) > 0 && $old_buy_price >= $this->get_winning_bid_value( $post_ID ) ) ? 'disabled="disabled" ' : '';

		$accepted_payments = pp_invoice_user_accepted_payments( $user_ID );

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
		<?php if( true == $accepted_payments[ 'paypal_allow' ] ) {?>
	</tr>
	<tr>
		<td class="left">
			<label for="buy_now_price"><?php echo __( "Buy Now Price: ", 'prospress' ) . $currency_symbol; ?></label>
		</td>
		<td>
	 		<input type="text" name="buy_now_price" value="<?php echo number_format_i18n( $buy_now_price, 2 ); ?>" size="20" <?php echo $disabled; ?>/>
			<?php if( $disabled != '' ) echo '<span>' . $disabled_msg . '</span>'; ?>
		</td>
		<?php } ?>
	</tr>
</tbody>
</table>
		<?php
	}

	public function post_fields_save( $post_id, $post ){
		global $wpdb, $wp_locale;

		if( wp_is_post_revision( $post_id ) )
			$post_id = wp_is_post_revision( $post_id );

		if( $this->name != @$_POST[ 'post_type' ] || !current_user_can( 'edit_post', $post_id ) ){
			return $post_id;
		} elseif( $this->get_bid_count( $post_ID ) || !isset( $_POST[ 'selling_options_nonce' ] ) || !wp_verify_nonce( $_POST[ 'selling_options_nonce' ], __FILE__ ) ){
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

	// Called on bid_table_actions hook
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

	// Adds bid system specific sort options to the post system sort widget
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
			// Need to do this manually as get_winning_bid() call this function
			$winning_bid = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->posts WHERE post_type = %s AND post_parent = %d AND post_status = %s", $this->bid_object_name, $post_id, 'winning' ) );

			$winning_bid_value = get_post_meta( $winning_bid->ID, 'winning_bid_value', true );

			if( empty( $winning_bid_value ) )
				$winning_bid_value = $winning_bid->post_content;
		}

		return $winning_bid_value;
	}
	
	public function add_buy_now_form( $post_id = NULL ) {
		global $currency, $post;

		$post_id = ( $post_id === NULL ) ? $post->ID : (int)$post_id;

		if ( !$this->is_post_valid( $post_id ) )
			return;

		// If auction author has setup PayPal & set a "Buy Now" price for the auction > 0 and current price
		$post_details = get_post( $post_id );
		$accepted_payments = pp_invoice_user_accepted_payments( $post_details->post_author );

		// Create mock invoice object for setting up PayPal form
		$invoice->payee_class = get_userdata( $post_details->post_author );
		$invoice->amount = get_post_meta( $post_id, 'buy_now_price', true );

		if( $invoice->amount == 0 || $invoice->amount < $this->get_winning_bid_value( $post_id ) || true != $accepted_payments[ 'paypal_allow' ] || !is_email( $invoice->payee_class->pp_invoice_settings[ 'paypal_address' ] ) )
			return;

		$invoice->post_title = $post_details->post_title;
		$invoice->pay_link 	= get_permalink( $post_id );
		$invoice->pay_link 	= add_query_arg( 'buy_now', 'paypal', $invoice->pay_link );
		$invoice->pay_link 	= add_query_arg( 'pid', $post_id, $invoice->pay_link );
		//$invoice->id 		= $post_id; // prevent duplicate payments on post?
		$invoice->currency_code = $currency;

		$button = 'buy';
		$class = 'buy-form';
		$form_extras = '<h6 class="buy-title">' . __( 'Buy Now', 'prospress' ) . '</h6>';
		if( in_array( $this->message_id, array( 15, 16 ) ) )
			$form_extras .= '<div class="bid-updated bid_msg" >' . $this->get_message() . '</div>';
		$form_extras .= '<div class="buy-price">';
		$form_extras .= sprintf( __( 'Price: %s', 'prospress' ), pp_money_format( $invoice->amount ) );
		$form_extras .= '</div>';

		include_once( PP_INVOICE_UI_PATH . "payment_paypal.php" );
	}

	public function buy_now_return() {

		if( !isset( $_GET[ 'buy_now' ] ) || $_GET[ 'buy_now' ] != 'paypal' )
			return;

		if( isset( $_GET[ 'return_info'] ) )
			switch( $_GET[ 'return_info'] ) {
				case 'cancel':
					$this->message_id = 15;
					break;
				case 'success':
					$post_id = intval( $_GET[ 'pid' ] );
					$output = $this->buy_form_submit( $post_id );
					pp_end_post( $post_id ); // also generates invoice with pending status
					$invoice_id = pp_get_invoice_id( $post_id );
					pp_invoice_paid( $invoice_id, 'PayPal' );
					break;
			}
	}

	public function buy_now_messages( $message, $message_id ) {
		if( $message_id == 15 )
			$message = __( 'Purchase Cancelled.', 'prospress' );
		elseif( $message_id == 16 )
			$message = __( 'Purchase successful, congratulations.', 'prospress' );
		return $message;
	}

}
