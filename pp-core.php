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
if( !defined( 'PP_CORE_DIR' ) )
	define( 'PP_CORE_DIR', WP_PLUGIN_DIR . '/prospress/pp-core' );
if( !defined( 'PP_CORE_URL' ) )
	define( 'PP_CORE_URL', WP_PLUGIN_URL . '/prospress/pp-core' );

/************************************************************************************************************************/
/**** MONEY FORMAT FUNCTIONS ****/
/************************************************************************************************************************/
/** 
 * Global currencies variable for storing all currencies available in the marketplace.
 * 
 * To make a new currency available for your marketplace, add an array to this variable. 
 * The key for this array must be the currency's ISO 4217 code. The array must contain the currency 
 * name and symbol. 
 * e.g. $currencies['CAD'] = array( 'currency' => __('Canadian Dollar'), 'symbol' => '&#36;' ).
 * 
 * Once added, the currency will be available for selection from the admin page.
 * 
 * @package Prospress Currency
 * @since 0.1
 */
global $currencies, $currency, $currency_symbol;

$currencies = array(
	'AUD' => array( 'currency' => __('Australian Dollar'), 'symbol' => '&#36;' ),
	'GBP' => array( 'currency' => __('British Pound'), 'symbol' => '&#163;' ),
	'CNY' => array( 'currency' => __('Chinese Yuan'), 'symbol' => '&#165;' ),
	'EUR' => array( 'currency' => __('Euro'), 'symbol' => '&#8364;' ),
	'INR' => array( 'currency' => __('Indian Rupee'), 'symbol' => 'Rs' ),
	'JPY' => array( 'currency' => __('Japanese Yen'), 'symbol' => '&#165;' ),
	'USD' => array( 'currency' => __('United States Dollar'), 'symbol' => '&#36;' )
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


// Administration functions for choosing default currency (may be set by locale in future, like number format is already)
function pp_add_admin_pages(){
	if ( function_exists( 'add_settings_section' ) ){
		add_settings_section( 'currency', 'Currency', 'pp_currency_settings_section', 'general' );
	} else {
		$bid_settings_page = add_submenu_page( 'options-general.php', 'Currency', 'Currency', 58, 'currency', 'pp_currency_settings_section' );
	}
}
add_action( 'admin_menu', 'pp_add_admin_pages' );


// Displays the fields for handling currency default options
function pp_currency_settings_section() {
	global $currencies;
	?>
	<p><?php _e('Please choose a default currency and where the symbol for this currency should be positioned.'); ?></p>
	<table class='form-table'>
		<tr>
			<th scope="row"><?php _e('Currency Type'); ?>:</th>
			<td>
				<select id='currency_type' name='currency_type'>
				<?php
				$currency_type = get_option( 'currency_type' );
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
<?php
}

function currency_admin_option( $whitelist_options ) {
	$whitelist_options['general'][] = 'currency_type';
	return $whitelist_options;
}
add_filter( 'whitelist_options', 'currency_admin_option' );

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
