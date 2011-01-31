<?php
/**
 * @package Prospress
 * @author Brent Shepherd
 * @version 0.1
 */

if( !defined( 'PP_CORE_DIR' ) )
	define( 'PP_CORE_DIR', PP_PLUGIN_DIR . '/pp-core' );
if( !defined( 'PP_CORE_URL' ) )
	define( 'PP_CORE_URL', PP_PLUGIN_URL . '/pp-core' );

if ( !defined( 'PP_BASE_CAP' ) )
	define( 'PP_BASE_CAP', apply_filters( 'pp_base_capability', 'read' ) );

include_once( PP_CORE_DIR . '/core-widgets.php' );


/**
 * Sets up Prospress environment with any settings required and/or shared across the 
 * other components. 
 *
 * @package Prospress
 * @since 0.1
 */
function pp_core_install(){
	add_option( 'currency_type', 'USD' ); //default to the mighty green back
}
add_action( 'pp_activation', 'pp_core_install' );


/**
 * Adds the Prospress admin menu item to the Site Admin tab.
 *
 * @package Prospress
 * @since 0.1
 */
function pp_add_core_admin_menu() {
	global $pp_core_admin_page, $menu;

	// Make space for Prospress menu when BuddyPress is installed
	$menu[6] = $menu[4];
	$menu[7] = $menu[5];
	unset( $menu[4] );
	unset( $menu[5] );

	$pp_core_admin_page = add_menu_page( __( 'Prospress', 'prospress' ), __( 'Prospress', 'prospress' ), 'manage_options', 'Prospress', '', PP_PLUGIN_URL . '/images/prospress16.png', 4 );
	$pp_core_settings_page = add_submenu_page( 'Prospress', __( 'Prospress Settings', 'prospress' ), __( 'General Settings', 'prospress' ), 'manage_options', 'Prospress', 'pp_settings_page' );
}
add_action( 'admin_menu', 'pp_add_core_admin_menu' );

/**
 * Register pp core opitons settings 
 *
 * @package Prospress
 * @since 1.01
 */
function register_pp_core_options(){
	register_setting( 'pp_core_options', 'currency_type' );
}
add_action( 'admin_init', 'register_pp_core_options' );

/**
 * The core component only knows about a few settings required for Prospress to run. This functions outputs those settings as a
 * central Prospress settings administration page and saves settings when it is submitted. 
 *
 * Other components, and potentially plugins for Prospress, can output their own settings on this page with the 'pp_core_settings_page'
 * hook. They can also save these by adding them to the 'pp_options_whitelist' filter. This filter works in the same was the Wordpress
 * settings page filter of the similar name.
 *
 * @package Prospress
 * @since 0.1
 */
function pp_settings_page(){
	global $currencies, $currency;
	settings_errors();
	?>
	<div class="wrap">
		<form action="options.php" method="post">
			<?php 
			settings_fields( 'pp_core_options' );//settings fields pp_core_options
			screen_icon( 'prospress' ); 
			?>
			<h2><?php _e( 'Prospress Settings', 'prospress' ) ?></h2>

			<h3><?php _e( 'Currency', 'prospress' )?></h3>
			<p><?php _e( 'Please choose a default currency for all transactions in your marketplace.', 'prospress' ); ?></p>
			<label for='currency_type'>
				<?php _e('Currency:' , 'prospress' );?>
				<select id='currency_type' name='currency_type'>
				<?php foreach( $currencies as $code => $details ) { ?>
					<option value='<?php echo $code; ?>' <?php selected( $currency, $code ); ?> >
						<?php echo $details[ 'currency_name' ]; ?> (<?php echo $code . ', ' . $details[ 'symbol' ]; ?>)
					</option>
				<?php } ?>
				</select>
			</label>
		<?php do_action( 'pp_core_settings_page' ); ?>
		<p class="submit">
			<input type="submit" value="Save" class="button-primary" name="submit">
		</p>
		</form>
	</div>
	<?php
}


/** 
 * Create and set global currency variables for sharing all currencies available in the marketplace and the currently 
 * selected currency type and symbol.
 * 
 * To make a new currency available, add an array to the global $currencies variable. The key for this array must be the currency's 
 * ISO 4217 code. The array must contain the currency name and symbol. 
 * e.g. $currencies['CAD'] = array( 'currency_name' => __('Canadian Dollar'), 'symbol' => '&#36;' ).
 * 
 * Once added, the currency will be available for selection from the admin page.
 * 
 * @package Prospress
 * @since 0.1
 * 
 * @global array currencies Prospress currency list. 
 * @global string currency The currency chosen for the marketplace. 
 * @global string currency_symbol Symbol of the marketplace's chosen currency, eg. $. 
 */
function pp_set_currency(){
	global $currencies, $currency, $currency_symbol;

	$currencies = apply_filters( 'pp_set_currency', array(
		'AUD' => array( 'currency_name' => __('Australian Dollar', 'prospress' ), 'symbol' => '&#36;' ),
		'GBP' => array( 'currency_name' => __('British Pound', 'prospress' ), 'symbol' => '&#163;' ),
		'EUR' => array( 'currency_name' => __('Euro', 'prospress' ), 'symbol' => '&#8364;' ),
		'USD' => array( 'currency_name' => __('United States Dollar', 'prospress' ), 'symbol' => '&#36;' )
		));

	$currency = get_option( 'currency_type', 'USD' );

	$currency_symbol = $currencies[ $currency ][ 'symbol' ];
}
add_action( 'init', 'pp_set_currency', 1 );


/** 
 * For displaying monetary numbers, it's important to transform the number to include the currency symbol and correct number of decimals. 
 * 
 * @param int | float $number the numerical value to be formatted
 * @param int | float optional $decimals the number of decimal places to return, default 2
 * @param string optional $currency ISO 4217 code representing the currency. eg. for Japanese Yen, $currency == 'JPY'.
 * @return string The formatted value with currency symbol.
 **/
function pp_money_format( $number, $decimals = '', $custom_currency = '' ){
	global $currencies, $currency_symbol, $currency;

	$number = floatval( $number );

	if( empty( $decimals ) && $number > 1000 )
		$decimals = 0;
	else
		$decimals = 2;

	if( empty( $custom_currency ) )
		$custom_currency = $currency;

	if( !array_key_exists( strtoupper( $custom_currency ), $currencies ) )
		$currency_sym = $currency_symbol;
	else
		$currency_sym = $currencies[ $custom_currency ][ 'symbol' ];

	if( $custom_currency =='EUR' )
		return implode( '', apply_filters( 'pp_money_format', array( number_format_i18n( floatval($number), $decimals ), $currency_sym ) ) );
	else
		return implode( '', apply_filters( 'pp_money_format', array( $currency_sym, number_format_i18n( floatval($number), $decimals ) ) ) );
}


/** 
 * Add admin style and scripts that are required by more than one component. 
 * 
 * @package Prospress
 * @since 0.1
 */
function pp_core_admin_head() {

	if( strpos( $_SERVER['REQUEST_URI'], 'Prospress' ) !== false || strpos( $_SERVER['REQUEST_URI'], 'invoice_settings' ) !== false || strpos( $_SERVER['REQUEST_URI'], '_tax' ) !== false || strpos( $_SERVER['REQUEST_URI'], 'completed' ) !== false  || strpos( $_SERVER['REQUEST_URI'], 'bids' ) !== false )
		wp_enqueue_style( 'prospress-admin',  PP_CORE_URL . '/prospress-admin.css' );
}
add_action('admin_menu', 'pp_core_admin_head');


/**
 * A welcome message with a few handy links to help people get started and encourage exploration of their
 * site's new Prospress features.
 *
 * @package Prospress
 * @since 0.1
 */
function pp_welcome_notice(){
	global $market_systems;

	$index_id = $market_systems['auctions']->get_index_id();

	if( get_option( 'pp_show_welcome' ) == 'false' ){
		return;
	} elseif( ( isset( $_GET[ 'pp_hide_wel' ] ) && $_GET[ 'pp_hide_wel' ] == 1 ) || 
			( isset( $_GET[ 'post_type' ] ) && $_GET[ 'post_type' ] == 'auctions' ) || 
			( isset( $_GET[ 'page' ] ) && ( $_GET[ 'page' ] == 'Prospress' || $_GET[ 'page' ] == $index_id ) ) ) {
		update_option( 'pp_show_welcome', 'false' );
		return;
	}

	echo "<div id='prospress-welcome' class='updated fade'><p><strong>".__('Congratulations.', 'prospress')."</strong> ".
	sprintf( __('Your WordPress site is now prosperous. Go add your first <a href="%1$s">auction</a>, '), "post-new.php?post_type=auctions").
	sprintf( __('modify your auctions\' <a href="%1$s">index page</a> or '), "post.php?post=$index_id&action=edit").
	sprintf( __('configure your marketplace <a href="%1$s">settings</a>. '), "admin.php?page=Prospress").
	sprintf( __('&nbsp;<a href="%1$s">&laquo; Hide &raquo;</a>'), add_query_arg( 'pp_hide_wel', '1', $_SERVER['REQUEST_URI'] ))."</p></div>";
}
add_action( 'admin_notices', 'pp_welcome_notice' );

