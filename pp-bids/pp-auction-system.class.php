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

	// PHP5 constructor
	function __construct() {
		if ( !defined( 'BID_INCREMENT' ) )
			define( 'BID_INCREMENT', '0.05' );

		parent::__construct( __( 'auctions', 'prospress' ), __( 'auction', 'prospress' ), __( 'Auction Bid', 'prospress' ), __('Bid!', 'prospress' ), array( 'post_fields' ) );
		add_filter( 'winning_bid_actions', array( &$this, 'add_winning_bid_actions' ), 10, 2 );
	}

	function bid_form_fields( $post_id = NULL ) { 
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

	function bid_form_submit( $post_id = NULL, $bid_value = NULL, $bidder_id = NULL ){
		global $user_ID, $wpdb, $blog_id;
		nocache_headers();

		//Get Bid details
		$post_id 		= ( isset( $_REQUEST[ 'post_ID' ] ) ) ? intval( $_REQUEST[ 'post_ID' ] ) : $post_id;
		$bid_value		= ( isset( $_REQUEST[ 'bid_value' ] ) ) ? str_replace( ',', '', trim( $_REQUEST[ 'bid_value' ] ) ) : $bid_value;
		$bidder_id 		= ( isset( $bidder_id ) ) ? $bidder_id : $user_ID;
		$bid_date 		= current_time( 'mysql' );
		$bid_date_gmt 	= current_time( 'mysql', 1 );

		do_action( 'get_auction_bid', $post_id, $bid_value, $bidder_id, $_GET );

		$post_status	= $this->verify_post_status( $post_id );
		$bid_ms			= $this->bid_form_validate( $post_id, $bid_value, $bidder_id );

		if ( $bid_ms[ 'bid_status' ] != 'invalid' ) {
			$bid = compact("post_id", "bidder_id", "bid_value", "bid_date", "bid_date_gmt" );
			$bid[ 'bid_status' ] = $bid_ms[ 'bid_status' ];
			$bid = apply_filters( 'bid_pre_db_insert', $bid );
			$this->update_bid( $bid, $bid_ms, $post_id );
		}
		return $bid_ms[ 'bid_msg' ];
	}

	function bid_form_validate( $post_id, $bid_value, $bidder_id ){
		$post_max_bid		= $this->get_max_bid( $post_id );
		$bidders_max_bid	= $this->get_users_max_bid( $bidder_id, $post_id );

		if ( $bidder_id == get_post( $post_id )->post_author ) {
			$bid_msg = 11;
			$bid_status = 'invalid';
		} else if ( empty( $bid_value ) || $bid_value === NULL || !preg_match( '/^[0-9]*\.?[0-9]*$/', $bid_value ) ) {
			$bid_msg = 7;
			$bid_status = 'invalid';
		} elseif ( $bidder_id != $this->get_winning_bid( $post_id )->bidder_id ) {
			$current_winning_bid_value = $this->get_winning_bid_value( $post_id );
			if ( $this->get_bid_count( $post_id ) == 0 ) {
				$start_price = get_post_meta( $post_id, 'start_price', true );
				if ( $bid_value < $start_price ){
					$bid_msg = 9;
					$bid_status = 'invalid';
				} else {
					$bid_msg = 0;
					$bid_status = 'winning';
				}
			} elseif ( $bid_value > $post_max_bid->bid_value ) {
				$bid_msg = 1;
				$bid_status = 'winning';
			} elseif ( $bid_value <= $current_winning_bid_value ) {
				$bid_msg = 3;
				$bid_status = 'invalid';
			} elseif ( $bid_value <= $post_max_bid->bid_value ) {
				$bid_msg = 2;
				$bid_status = 'outbid';
			}
		} elseif ( $bid_value > $bidders_max_bid->bid_value ){ //user increasing max bid
			$bid_msg = 4;
			$bid_status = 'winning';
		} elseif ( $bid_value < $bidders_max_bid->bid_value ) { //user trying to decrease max bid
			$bid_msg = 5;
			$bid_status = 'invalid';
		} else {
			$bid_msg = 6;
			$bid_status = 'invalid';
		}
		return compact( 'bid_status', 'bid_msg' );
	}

	function update_bid( $bid, $bid_ms, $post_id ){
		global $wpdb;

		$current_winning_bid_value 	= $this->get_winning_bid_value( $bid[ 'post_id' ] );

		// No need to update winning bid for invalid bids, bids too low
		if ( $bid_ms[ 'bid_status' ] == 'invalid' ) // nothing to update
			return $current_winning_bid_value;

		$posts_max_bid			= $this->get_max_bid( $bid[ 'post_id' ] );
		$current_winning_bid_id	= $this->get_winning_bid( $bid[ 'post_id' ] )->bid_id;

		if( $bid_ms[ 'bid_msg' ] == 0 ) { //if first bid
			$start_price = get_post_meta( $post_id, 'start_price', true );
			if( $start_price ){ // If start price is greater than 0
				$new_winning_bid_value = $start_price;
			} else {
				$new_winning_bid_value = ( $bid[ 'bid_value' ] * BID_INCREMENT );
			}
		} elseif ( $bid_ms[ 'bid_msg' ] == 1 ) { //Bid value is over max bid & bidder different to current winning bidder
			if ( $bid[ 'bid_value' ] > ( $posts_max_bid->bid_value * ( BID_INCREMENT + 1 ) ) ) {
				$new_winning_bid_value = $posts_max_bid->bid_value * ( BID_INCREMENT + 1 );
			} else {
				$new_winning_bid_value = $bid[ 'bid_value' ];
			}
		} elseif ( $bid_ms[ 'bid_msg'] == 2 ) {
			$bid_value_incremented = $bid[ 'bid_value' ] * ( 1 + BID_INCREMENT );
			if ( $posts_max_bid->bid_value > $bid_value_incremented ) {
				$new_winning_bid_value = $bid_value_incremented;
			} else {
				$new_winning_bid_value = $posts_max_bid->bid_value;
			}
		} elseif ( $bid_ms[ 'bid_msg'] == 4 ) { // bidder increasing max bid, just need to set their previous bid as 'outbid'
			$wpdb->update( $wpdb->bids, array( 'bid_status' => 'outbid' ), array( 'bid_id' => $current_winning_bid_id ) );
			$new_winning_bid_value = $current_winning_bid_value;
		}

		$wpdb->insert( $wpdb->bids, $bid );

		if( $bid_ms[ 'bid_msg'] != 2 ){ // valid bid, over existing max, change winning bid id and bid value in bids meta table
			$wpdb->update( $wpdb->bids, array( 'bid_status' => 'outbid' ), array( 'bid_id' => $current_winning_bid_id ) );
			$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->bidsmeta WHERE bid_id = %d AND meta_key = 'winning_bid_value'", $current_winning_bid_id ) );
			$new_winning_bid_id = $this->get_winning_bid( $bid[ 'post_id' ] )->bid_id;
			$wpdb->insert( $wpdb->bidsmeta, array( 'bid_id' => $new_winning_bid_id, 'meta_key' => 'winning_bid_value', 'meta_value' => $new_winning_bid_value ) );
		} else { // current winning bid is still winning bid, just need to update winning bid value
			$wpdb->update( $wpdb->bidsmeta, array( 'meta_value' => $new_winning_bid_value ), array( 'bid_id' => $current_winning_bid_id, 'meta_key' => 'winning_bid_value' ) );
		}

		return $new_winning_bid_value;
	}

	function post_fields(){
		global $post_ID, $currency_symbol;
		$start_price = get_post_meta( $post_ID, 'start_price', true );

		wp_nonce_field( __FILE__, 'selling_options_nonce', false ) ?>
		<table>
		  <tbody>
				<tr>
				  <td class="left">
					<label for="start_price"><?php echo __("Starting Price: ", 'prospress' ) . $currency_symbol; ?></label>
					</td>
					<td>
				 		<input type="text" name="start_price" value="<?php echo number_format_i18n( $start_price, 2 ); ?>" size="20" />
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	function post_fields_submit( $post_id, $post ){
		global $wpdb;

		if(wp_is_post_revision($post_id))
			$post_id = wp_is_post_revision($post_id);

		if ( 'page' == $_POST['post_type'] )
			return $post_id;
		elseif ( !current_user_can( 'edit_post', $post_id ))
			return $post_id;

		/** @TODO casting start_price as a float and removing ',' and ' ' will cause a bug for international currency formats. */
		$_POST[ 'start_price' ] = (float)str_replace( array(",", " "), "", $_POST[ 'start_price' ]);

		// Verify options nonce because save_post can be triggered at other times
		if ( !isset( $_POST[ 'selling_options_nonce' ] ) || !wp_verify_nonce( $_POST['selling_options_nonce'], __FILE__) ) {
			return $post_id;
		} else { //update post options
			update_post_meta( $post_id, 'start_price', $_POST[ 'start_price' ] );
		}
	}

	// Called on winning_bid_actions hook
	function add_winning_bid_actions( $actions, $post_id ) {
		global $user_ID, $market_system, $blog_id;

		$post = get_post( $post_id );
		
		if( $post->post_status == 'completed' )
			return;

		$permalink = get_permalink( $post_id );

		$actions[ 'increase-bid' ] = array('label' => __( 'Increase Bid', 'prospress' ),
												'url' => $permalink );
		return $actions;
	}
}
