<?php
$cp_modules[] = array (
	name => 'Prospress Cubepoints',
	version => '1.0.0',
	url => 'http://prospress.org.au',
	description => 'Cubepoints module for Prospress auctions',
	api_version => '1.0 (Do not change)',
	author => 'Anthony Yin-Xiong Khoo',
	author_url => 'Author URL',
	admin_function => 'cp_admin_prospress',
);

/**
 * Load cubepoints functions if cubepoints is installed
 *
 * @package Prospress
 * @since 0.1
 */
//initialise cubepoints/prospress options
//if( is_plugin_active(WP_PLUGIN_DIR.'/cubepoints/') ){
	add_option('cp_win_pts', 5);
	add_option('cp_sell_pts', 5);
	add_option('cp_bid_pts', 5);
	add_option('cp_mode_enabled', false);
	update_option('cp_mode_enabled',false);
	pp_cubepoints_mode();
	include(WP_PLUGIN_DIR.'/Prospress/pp-cubepoints/cp_admin_prospress.php');
if (get_option('cp_mode_enabled')== false){
	add_action('get_auction_bid','add_cp_bid_points');
	add_action( 'generate_invoice', 'cp_win_pts' );
}

//}

/**
 * In Cubepoints-mode Prospress uses cubepoints as currency.
 * Standard invoices and payments are disabled and points automatically deducted from
 * user totals.  Standard Prospress Cubepoints actions are disabled.
 *
 * @package Prospress
 * @subpackage pp-cubepoints
 * @since 0.1
 *
 * @uses 
 */
function pp_cubepoints_mode(){
	//  ADD ACTION, AUTO SET CURRENCY TYPE TO CUBEPOINTS

	if (get_option('cp_mode_enabled')== true){
		remove_action('get_auction_bid','add_cp_bid_points');
		remove_action('post_completed', 'cp_win_pts' );
		remove_action('post_completed', 'pp_generate_invoice' );
		add_filter('pp_money_format','cp_currency_type');//add cubepoints format
		add_filter('pp_set_currency','cp_add_currency_type');//add cubepoints type
		add_filter('validate_bid','cp_validate_bid',1,4);
		add_filter('payments_settings_visibility','hide_payments_settings');
	}
}
//hides payments settings
function hide_payments_settings(){
	return 'hidden';
}
/**
 * Return formatted url for cp_admin_ in prospress module
 * 
 *
 * @package Prospress
 * @subpackage pp-cubepoints
 * @since 0.1
 *
 * @uses 
 */
function pp_curPageURL($page = "prospress") {

	$link = "?page=cp_admin_modules&cp_module=cp_admin_".$page;
	$pageURL = 'http';
	if ($_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
	$pageURL .= "://";
	if ($_SERVER["SERVER_PORT"] != "80") {
		$pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["PHP_SELF"].$link;
	} else {
		$pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"].$link;
	}
	return $pageURL;
}

/**
 * Format cubepoints currency type around auction value [prefix, value, suffix]
 * 
 *
 * @package Prospress
 * @subpackage pp-cubepoints
 * @since 0.1
 *
 * @uses 
 */
function cp_currency_type($currency){
	return (get_option( 'currency_type' )=='CPS')?array(get_option('cp_prefix'),$currency[1],get_option('cp_suffix')):$currency;
}


/**
 * Load cubepoints custom currency type
 * 
 *
 * @package Prospress
 * @subpackage pp-cubepoints
 * @since 0.1
 *
 * @uses 
 */
function cp_add_currency_type($currencies){
	$currencies['CPS'] = array( 'currency_name' => __('Cubepoints'), 'symbol' => get_option('cp_prefix').' '.get_option('cp_suffix') );
	return $currencies;
}

	
/**
 * Hooks into an auction a successful bid and awards points
 * 
 *
 * @package Prospress
 * @subpackage pp-cubepoints
 * @since 0.1
 *
 * @uses is_user_logged_in,get_option,cp_log,cp_currentUser
 */
function add_cp_bid_points ($bid){
	if(function_exists('cp_alterPoints')&&is_user_logged_in()&&get_option('cp_mode_enabled')==true){
			//iterate through users for current winning bidder
		foreach ( $userids as $id ) {
			$id = (int) $id;
			if(is_winning_bidder($id, $bid['post_id'] )){
				cp_alterPoints($id, get_winning_bid_value($bid['post_id']));//refund amount to current winner
			}//if
			cp_alterPoints($bid['bidder_id'],-$bid['bid_value']); //deduct points from new winner
			cp_log('Winning bid:points held while winning',$bid['bidder_id'],$bid['bid_value'], 'http://example.com/?p='.$bid['post_id']);
		}//foreach
	}elseif(function_exists('cp_alterPoints')&&is_user_logged_in()&&get_option('currency_type')=='CPS'&&$bid['bid_status']=='winning'){

			if(!is_winning_bidder('', $bid['post_id'] )){
				$cp_bid_pts = get_option('cp_bid_pts');
				cp_alterPoints(cp_currentUser(), $cp_bid_pts);
				cp_log('winning bid', cp_currentUser(), $cp_bid_pts, is_winning_bidder(cp_currentUser(), $bid['post_id'] ));
			}//if

	}//endif
	return;
}
/**
 * Hooks into validate_bid to check sufficient Cubepoints for bid
 * 
 *
 * @package Prospress
 * @subpackage pp-cubepoints
 * @since 0.1
 *
 * @uses CUBEPOINTS MODE
 */
function cp_validate_bid($check,$post_id, $bid_value, $bidder_id ){
	if(cp_getPoints($bidder_id)<$bid_value)
	return false;
	else
	return true;
}

/**
 * Hooks to completed auction, 
 * Cubepoints mode disabled: iterates through registered users and allocates
 * pre-specified amount of cubepoints to winning users.
 * Cubepoints mode enabled: iterates through registered users and deducts amount
 * credits amount to the seller
 *
 * @package Prospress
 * @subpackage pp-cubepoints
 * @since 0.1
 *
 * @uses is_winning_bidder,cp_alterPoints,cp_log,is_user_logged_in
 */
function cp_win_pts($args){	
	if( function_exists('cp_alterPoints') && is_user_logged_in()){
		if (get_option('cp_mode_enabled')== true){
			$userids = $_REQUEST['users'];
			foreach ( $userids as $id ) {
				$id = (int) $id;
				if(is_winning_bidder( $user_id, $args['post_id'] )){
					cp_alterPoints($id,-$args['amount']);
					cp_log('Winning bid - points deducted', $id,-$args['amount'], 'bid');
				}
			cp_alterPoints($args['payee_id'],$args['amount']);
			cp_log('Item sold', $id,$args['amount'], 'bid');
			}
		}else{	
			$userids = $_REQUEST['users'];
			$cp_win_pts = get_option('cp_win_pts');
			foreach ( $userids as $id ) {
				$id = (int) $id;
				if(is_winning_bidder( $user_id, $args['post_id'] )){
					cp_alterPoints($id,$cp_win_pts);
					cp_log('winning bid', $id,$cp_win_pts, 'bid');
				}
			continue;
			}
		}
	}
}

/**
 * remove bid points
 * 
 *
 * @package Prospress
 * @subpackage pp-cubepoints
 * @since 0.1
 *
 * @uses 
 */
function rm_cp_bid_points (){
		if( function_exists('cp_alterPoints') && is_user_logged_in()&&get_option('currency_type')=='CPS'){
			cp_alterPoints(cp_currentUser(), get_option('cp_bid_points'));
			update_option('cp_bid_subtotal',0);
			cp_log('hey', cp_currentUser(), get_option('cp_bid_points'), 'bid');
	}
	return;
}

add_action('admin_init','cp_scripts');
function cp_scripts(){
	$handle='pp-cubepoints-mode';
	$src = WP_PLUGIN_URL . '/Prospress/pp-cubepoints/';
	$deps = false;//default
	$ver = false;//default
	$in_footer = false;//default
	wp_enqueue_script( $handle, $src.'pp-cubepoints-mode.js', $deps, $ver, $in_footer );
	wp_enqueue_style('pp-cubepoints-sytle',$src.'pp-cubepoints-style.css',$deps, $ver);
}


?>