<?php
/**
 * @package Prospress
 * @author Brent Shepherd
 * @version 0.1
 */

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * GLOBAL CONSTANTS
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
/* Define the path and url of the Prospress plugins directory */
//define( 'PP_PLUGIN_DIR', WP_PLUGIN_DIR . '/prospress' );
//define( 'PP_PLUGIN_URL', WP_PLUGIN_URL . '/prospress' );

/* Define the current version number for checking if DB tables are up to date. */
//define( 'PP_CORE_DB_VERSION', '0001' );

//define('PP_DB_PREFIX', 'pp_');

if( !defined( 'PP_PLUGIN_DIR' ) )
	define( 'PP_PLUGIN_DIR', WP_PLUGIN_DIR . '/prospress' );
if( !defined( 'PP_PLUGIN_URL' ) )
	define( 'PP_PLUGIN_URL', WP_PLUGIN_URL . '/prospress' );
if( !defined( 'PP_CORE_DIR' ) )
	define( 'PP_CORE_DIR', PP_PLUGIN_DIR . '/pp-core' );
if( !defined( 'PP_CORE_URL' ) )
	define( 'PP_CORE_URL', PP_PLUGIN_URL . '/pp-core' );


function pp_remove_wp_dashboard_widgets() {
	global $wp_meta_boxes;

	if( is_super_admin() )
		return;

	// Remove the side column widgets to make space for Prospress widgets
	unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_primary']);
	unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_secondary']);
}
add_action('wp_dashboard_setup', 'pp_remove_wp_dashboard_widgets' );

/**
 * Adds the "Prospress" admin menu item to the Site Admin tab.
 *
 * @package Prospress
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @global $wpdb WordPress DB access object.
 * @uses is_site_admin() returns true if the current user is a site admin, false if not
 * @uses add_submenu_page() WP function to add a submenu item
 */
function pp_add_core_admin_menu() {
	global $pp_core_admin_page;

	/* Add the administration tab under the "Site Admin" tab for site administrators */
	$pp_core_admin_page = add_menu_page( __( 'Prospress', 'prospress' ), __( 'Prospress', 'prospress' ), 10, 'Prospress', '', PP_PLUGIN_URL . '/images/prospress-16x16.png', 3 );
	$pp_core_settings_page = add_submenu_page( 'Prospress', __( 'Prospress Settings', 'prospress' ), __( 'Settings', 'prospress' ), 10, 'Prospress', 'pp_settings_page' );
}
add_action( 'admin_menu', 'pp_add_core_admin_menu' );

function pp_add_icon_css() {

	if ( strpos( $_SERVER['REQUEST_URI'], 'Prospress' ) !== false ||  strpos( $_SERVER['REQUEST_URI'], 'custom_taxonomy_manage' ) !== false ) {
		echo "<style type='text/css'>";
		echo "#icon-prospress{background: url(" . PP_PLUGIN_URL . "/images/prospress-35x35.png) no-repeat center transparent}";
		echo "</style>";
	}
}
add_action( 'admin_head', 'pp_add_icon_css' );

function pp_settings_page(){
	global $currencies;
	error_log('pp_settings_page being called.');
	error_log('POST = ' . print_r( $_POST, true ) );
	if( isset( $_POST[ 'submit' ] ) && $_POST[ 'submit' ] == 'Save' ){

		$pp_options_whitelist = apply_filters( 'pp_options_whitelist', array( 'general' => array( 'currency_type' ) ) );

		error_log( "pp_options_whitelist = " . print_r( $pp_options_whitelist, true ) );
		foreach ( $pp_options_whitelist[ 'general' ] as $option ) {
			$option = trim($option);
			$value = null;
			if ( isset($_POST[$option]) )
				$value = $_POST[$option];
			if ( !is_array($value) )
				$value = trim($value);
			$value = stripslashes_deep($value);
			error_log( "value = " . print_r( $value, true ) );
			update_option($option, $value);
		}
	}
	?>
	<div class="wrap">
		<?php screen_icon( 'prospress' ); ?>
		<h2><?php _e( 'Prospress Settings', 'prospress' ) ?></h2>
		<form action="" method="post">
			<h3><?php _e( 'Currency', 'prospress' )?></h3>
			<p><?php _e( 'Please choose a currency for transactions in your marketplace.', 'prospress' ); ?></p>
			<table class='form-table'>
				<tr>
					<th scope="row"><?php _e('Currency:', 'prospress' ); ?></th>
					<td>
						<select id='currency_type' name='currency_type'>
						<?php $currency_type = get_option( 'currency_type' );
						foreach( $currencies as $code => $currency ) {
						?>
							<option value='<?php echo $code; ?>' <?php selected( $currency_type, $code ); ?> >
								<?php echo $currency[ 'currency' ]; ?> (<?php echo $code . ', ' . $currency['symbol']; ?>)
							</option>
				<?php	} ?>
						</select>
					</td>
				</tr>
			</table>
		<?php do_action( 'pp_core_settings_page' ); ?>
		<p class="submit">
			<input type="submit" value="Save" class="button-primary" name="submit">
		</p>
		</form>
	</div>
	<?php
}

/************************************************************************************************************************/
/**** MONEY FORMAT FUNCTIONS ****/
/************************************************************************************************************************/
/** 
 * Global currencies variable for storing all currencies available in the marketplace.
 * 
 * To make a new currency available for your marketplace, add an array to this variable. 
 * The key for this array must be the currency's ISO 4217 code. The array must contain the currency 
 * name and symbol. 
 * e.g. $currencies['CAD'] = array( 'currency' => __('Canadian Dollar', 'prospress' ), 'symbol' => '&#36;' ).
 * 
 * Once added, the currency will be available for selection from the admin page.
 * 
 * @package Prospress Currency
 * @since 0.1
 */
global $currencies, $currency, $currency_symbol;

$currencies = array(
	'AUD' => array( 'currency' => __('Australian Dollar', 'prospress' ), 'symbol' => '&#36;' ),
	'GBP' => array( 'currency' => __('British Pound', 'prospress' ), 'symbol' => '&#163;' ),
	'CNY' => array( 'currency' => __('Chinese Yuan', 'prospress' ), 'symbol' => '&#165;' ),
	'EUR' => array( 'currency' => __('Euro', 'prospress' ), 'symbol' => '&#8364;' ),
	'INR' => array( 'currency' => __('Indian Rupee', 'prospress' ), 'symbol' => 'Rs' ),
	'JPY' => array( 'currency' => __('Japanese Yen', 'prospress' ), 'symbol' => '&#165;' ),
	'USD' => array( 'currency' => __('United States Dollar', 'prospress' ), 'symbol' => '&#36;' )
	);

$currency = get_option( 'currency_type' );

$currency_symbol = $currencies[ $currency ][ 'symbol' ];

function pp_maybe_install(){
	error_log('*** in pp_maybe_install ***');
	if( !get_option( 'currency_type' ) )
		update_option( 'currency_type', 'USD' );
	if( !get_option( 'currency_sign_location' ) )
		update_option( 'currency_sign_location', '1' );
}

/** 
 * Function for transforming a number into a monetary formatted number, complete with currency symbol.
 * 
 * @param number int | float
 * @param decimals int | float optional number of decimal places
 * @param currency string optional ISO 4217 code representing the currency. eg. for Japanese Yen, $currency == 'JPY'. If left empty, the currency stored in the options table will be used.
 **/
// Takes an int or float representing number and returns it as a string with currency symbol and formatted in locale suitable number format
function pp_money_format( $number, $decimals = 2, $currency = '' ){
	global $currencies, $currency_symbol;

	$currency = strtoupper( $currency );

	if( empty( $currency ) || !array_key_exists( $currency, $currencies ) )
		$currency_sym = $currency_symbol;
	else
		$currency_sym = $currencies[ $currency ][ 'symbol' ];

	return $currency_sym . ' ' . number_format_i18n( $number, $decimals );
}


/************************************************************************************************************************/
/**** MONEY FORMAT FUNCTIONS ****/
/************************************************************************************************************************/
/** 
 * Add admin style and scripts that are required by more than one component. 
 * 
 * @package Prospress
 * @since 0.1
 */
function pp_core_admin_head() {
	global $market_system;

	if( strpos( $_SERVER['REQUEST_URI'], $market_system->name ) !== false || strpos( $_SERVER['REQUEST_URI'], 'bids' ) !== false )
		wp_enqueue_style( 'completed-actions',  PP_CORE_URL . '/prospress-admin.css' );
}
add_action('admin_menu', 'pp_core_admin_head');