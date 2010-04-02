<?php
/**
 * A class, global variable and helper functions for dealing with currency and money in WordPress. 
 *
 * For obvious reasons, WordPress doesn't have functionality for dealing with money and currency. 
 * For even more obvious reasons, Prospress does. In the spirit of open source and object oriented
 * programming, this file should be usable for any WordPress plugin needing to deal with money and/or
 * multiple currencies. 
 *
 * @package Prospress
 * @since 0.1
 */

/** 
 * Global currencies variable for storing all currencies available in the marketplace.
 * 
 * To add a currency to a new blog/marketplace, add an array to this variable. They key for the array 
 * should be the currency's ISO 4217 code. The array should contain the currency name and symbol. 
 * e.g. $currencies['CAD'] = array( 'currency' => __('Canadian Dollar'), 'symbol' => '&#36;' )
 * 
 * Once added, the currency will be available to select from the admin page and for use in the PP_Money 
 * class.
 * 
 * @package Prospress Currency
 * @since 0.1
 */
global $currencies;

$currencies = array(
	'AUD' => array( 'currency' => __('Australian Dollar'), 'symbol' => '&#36;' ),
	'GBP' => array( 'currency' => __('British Pound'), 'symbol' => '&#163;' ),
	'CNY' => array( 'currency' => __('Chinese Yuan'), 'symbol' => '&#165;' ),
	'EUR' => array( 'currency' => __('Euro'), 'symbol' => '&#8364;' ),
	'INR' => array( 'currency' => __('Indian Rupee'), 'symbol' => 'Rs' ),
	'JPY' => array( 'currency' => __('Japanese Yen'), 'symbol' => '&#165;' ),
	'USD' => array( 'currency' => __('United States Dollar'), 'symbol' => '&#36;' )
	);

/**
 * Money class for storing, converting and displaying monetary values consistently.
 * 
 * This class is important for 3 reasons:
 * 1. Money is a quantity with a type, it is not just a float.
 * 2. Centralising money functions means less code up front and less code maintenance
 * 
 * Reinventing the wheel to make it more round...
 *
 * @package Prospress
 * @since 0.1
 */
class PP_Money {
	var $code;			// string ID related with the currency (ex : language)
	var $sym;			// Printable symbol e.g. $
	var $decimals;		// Number of decimals places to display
	var $sym_pos;		// Currency symbol position, left or right of value, optionally with space
	var $value;			// Float value of the currency e.g. 123.58

	// PHP4 Constructor
	function PP_Money( $value = 0, $code = '' ){
		$this->__construct( $value, $code );
	}
	
	// PHP5 Constructor
	function __construct( $value = 0, $code = '' ){
		global $currencies, $wp_locale;

		if ( false === ( $code_default = get_option( 'currency_type' ) ) )	// No default set in options DB, default to USD
			$code_default = 'USD';

		$code = strtoupper( $code );

		if ( empty( $code ) || !array_key_exists( $code, $currencies ) )
			$code = $code_default;

		$this->code = $code;
		$this->sym = $currencies[ $this->code ][ 'symbol' ];
		$this->value = $this->to_float( $value );		
		$this->decimals = $this->decimal_places( $value );
		$this->sym_pos = ( $sym_pos = get_option( 'currency_sign_location' ) ) ? $sym_pos : 1;
	}

	// Format a monetary value to a float for safe MySQL storage & consistent internal format
	// This function is called in the constructor to store monetary value internally. 
	// It is not necessary to call this function when storing a value in the DB. 
	// It is sufficient, and more efficient, to call get_value(). 
	function to_float( $value = 0 ){
		global $wp_locale;
		
		if ( empty( $value ) || $value === true ) // boundary cases, empty value can be passed and true treated as '1' when converted to float by PHP
			$value = 0;
		elseif ( $value > 1.8e308 || $value < -1.8e308 ) // boundary case, numbers too big for PHP float
			return "Wow, too much money! I can't handle numbers > 1.8e308 or < -1.8e308";

		// If $value has thousand separators. 
		if ( strpos( $value, $wp_locale->number_format[ 'thousands_sep' ] ) !== false )
			// Strip thousands separator. PHP's float conversion functions truncate after string symbols, including thousand separators.
			$value = str_replace( $wp_locale->number_format[ 'thousands_sep' ], "", $value );

		// Ensure decimal point is '.'
		if ( '.' != $wp_locale->number_format['decimal_point'] )
			$value = str_replace( $wp_locale->number_format['thousands_sep'], "", $value );

		return floatval( $value );
	}

	// Determines the decimal places of a given float
	function decimal_places( $value = "" ){
		if ( empty( $value ) ) 
			$value = $this->value;

		$value = $this->to_float( $value );	// Make sure value is in internal (MySQL) format

		$decimals = ( ( $dec_pos = strpos( $value, '.' ) ) === false ) ? 2 : strlen( substr( $value, $dec_pos+1 ) );

		if ( $decimals == 1 )
			$decimals = 2;
		elseif ( $decimals > 6 )
			$decimals = 6;

		return $decimals;
	}
	
	// Get raw value of currency. As values are stored internally as a mysql safe float format, calling 
	// this function is sufficient, and most efficient, for writing to DB.
	function get_value(){
		return $this->value;
	}
	
	// Display only currency value, not symbols
	function get_value_formatted( ){
		return number_format_i18n( $this->value, $this->decimals );
	}

	function display_value( ){
		echo $this->get_value_formatted();
	}

	function get_prefix( ){
		switch ( $this->sym_pos ) {
			case 1:
				return $this->sym;
			case 2:
				return $this->sym . ' ';
		}
	}

	function display_prefix( ){
		echo $this->get_prefix();
	}

	function get_suffix( ){
		switch ( $this->sym_pos ) {
			case 3:
				return $this->sym;
			case 4:
				return ' ' . $this->sym;
		}
	}

	function display_suffix( ){
		echo $this->get_suffix();
	}

	// Display full currency, including symbol
	// Using prefix/suffix for better encapsulation and centralisation despite the minor efficiency loss
	function display(){
		switch ( $this->sym_pos ) {
			case 1:
				$this->display_prefix();
				$this->display_value();
				break;
			case 2:
				$this->display_prefix();
				$this->display_value();
				break;
			case 3:
				$this->display_value();
				$this->display_suffix();
				break;
			case 4:
				$this->display_value();
				$this->display_suffix();
				break;
			default:
				$this->display_prefix();
				$this->display_value();
				$this->display_suffix();
				break;
			}
	}

	// Determines alignment when displaying currency, can be used for style property or class name
	function get_alignment(){
		if( $this->sym_pos == 1 ||  $this->sym_pos == 2 ) {
			return 'left';
		} else {
			return 'right';
		}
	}

	function display_css_alignment(){
		echo "text-align: " . $this->get_alignment();
	}

	function __toString() {
		return $this->get_prefix() . $this->get_value_formatted() . $this->get_suffix();
	}
}

// Administration functions for choosing default currency (may be set by locale in future, like number format is already)
function pp_add_currency_admin(){
	if ( function_exists( 'add_settings_section' ) ){
		add_settings_section( 'currency', 'Currency', 'pp_currency_settings_section', 'general' );
	} else {
		$bid_settings_page = add_submenu_page( 'options-general.php', 'Currency', 'Currency', 58, 'currency', 'pp_currency_settings_section' );
	}
}
add_action( 'admin_menu', 'pp_add_currency_admin' );

// Displays the fields for handling currency default options
function pp_currency_settings_section() {
	global $currencies;
	?>
	<p><?php _e('Please choose a default currency and currency symbol location for your marketplace.'); ?></p>
	<table class='form-table'>
		<tr>
			<th scope="row"><?php _e('Currency Type'); ?>:</th>
			<td>
				<select id='currency_type' name='currency_type'>
				<?php
				$currency_type = get_option( 'currency_type' );
				foreach( $currencies as $code => $currency ) {
				?>
					<option value='<?php echo $code; ?>' <?php echo ($currency_type == $code) ? 'selected="selected"': ''; ?> >
						<?php echo $currency[ 'currency' ]; ?> (<?php echo $code . ', ' . $currency['symbol']; ?>)
					</option>
		<?php	} ?>
				</select>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php echo _e('Symbol Location');?>:</th>
			<td>
				<?php
				$currency_sign_location = get_option('currency_sign_location');
				switch( $currency_sign_location ) {
					case 1:
						$csl1 = "checked ='checked'";
						break;
					case 2:
						$csl2 = "checked ='checked'";
						break;
					case 3:
						$csl3 = "checked ='checked'";
						break;
					case 4:
						$csl4 = "checked ='checked'";
						break;
					default:
						$csl1 = 'checked ="checked"';
						break;
				}
				$currency_sign = $currencies[$currency_type]['symbol'];
				?>
				<input type='radio' value='1' name='currency_sign_location' id='csl1' <?php echo $csl1; ?> /> 
				<label for='csl1'><?php echo $currency_sign; ?>100</label>
				<input type='radio' value='2' name='currency_sign_location' id='csl2' <?php echo $csl2; ?> /> 
				<label for='csl2'><?php echo $currency_sign; ?>&nbsp;100</label>
				<input type='radio' value='3' name='currency_sign_location' id='csl3' <?php echo $csl3; ?> /> 
				<label for='csl3'>100<?php echo $currency_sign; ?></label>
				<input type='radio' value='4' name='currency_sign_location' id='csl4' <?php echo $csl4; ?> /> 
				<label for='csl4'>100&nbsp;<?php echo $currency_sign; ?></label>
			</td>
		</tr>
	</table>
<?php
}

function currency_admin_option( $whitelist_options ) {
	$whitelist_options['general'][] = 'currency_type';
	$whitelist_options['general'][] = 'currency_sign_location';
	return $whitelist_options;
}
add_filter( 'whitelist_options', 'currency_admin_option' );

// Tests the PP_Money class
//add_action( 'wp_footer', 'pp_money_test' );
function pp_money_test(){
	global $currencies;
	
	// Make sure it can do the basics
	$money = new PP_Money( );
	echo $money . "<br />"; 
	$money = new PP_Money( 123123 );
	echo $money . "<br />"; 
	$money = new PP_Money( 123123.11235 );
	echo $money . "<br />"; 
	
	$money = new PP_Money( );
	error_log( 'PP_Money( ) = ' . print_r( $money, true ) );
	$money->display();
	echo "<br />";

	$money = new PP_Money( 123123.1123581321 );
	error_log( 'PP_Money( 123123.1123581321 ) = ' . print_r( $money, true ) );
	$money->display();
	echo "<br />";

	$money = new PP_Money( '123,123.1123', 'cny' );
	error_log( 'PP_Money( "123,123.1123", "cny" ) = ' . print_r( $money, true ) );
	$money->display();
	echo "<br />";

	$money = new PP_Money( '123,123.1', 'CNY' );
	error_log( 'PP_Money( "123,123.1", "CNY" ) = ' . print_r( $money, true ) );
	$money->display();
	echo "<br />";

	$money = new PP_Money( '123,123,123.1123581321' );
	error_log( 'PP_Money( "123,123,123.1123581321" ) = ' . print_r( $money, true ) );
	$money->display();
	echo "<br />";

	$money = new PP_Money( '123,123.1123581321', 'RNI' );
	error_log( 'PP_Money( "123,123.1123581321", "RNI" ) = ' . print_r( $money, true ) );
	$money->display();
	echo "<br />";

	// See how it deals with incorrect input

	$money = new PP_Money( 'string', 123 );
	error_log( 'PP_Money( "string", 123 ) = ' . print_r( $money, true ) );
	$money->display();
	echo "<br />";

	$money = new PP_Money( false, false );
	error_log( 'PP_Money( false ) = ' . print_r( $money, true ) );
	$money->display();
	echo "<br />";

	$money = new PP_Money( true );
	error_log( 'PP_Money( true ) = ' . print_r( $money, true ) );
	$money->display();
	echo "<br />";

	$money = new PP_Money( NULL );
	error_log( 'PP_Money( NULL ) = ' . print_r( $money, true ) );
	$money->display();
	echo "<br />";

	// See how it deals with signed numbers and scientific notation

	$money = new PP_Money( -1123.58 );
	error_log( 'PP_Money( -1123.58 ) = ' . print_r( $money, true ) );
	$money->display();
	echo "<br />";

	$money = new PP_Money( 1.2e3 );
	error_log( 'PP_Money( 1.2e3 ) = ' . print_r( $money, true ) );
	$money->display();
	echo "<br />";

	// And out of bounds numbers (bigger than 1.8e308)
	
	$money = new PP_Money( 1.9e309 );
	error_log( 'PP_Money( 1.9e309 ) = ' . print_r( $money, true ) );
	$money->display();
	echo "<br />";

	$money = new PP_Money( 1.8e207 );
	error_log( 'PP_Money( 1.8e207 ) = ' . print_r( $money, true ) );
	$money->display();
	echo "<br />";
	
	$money = new PP_Money( -1.9e309 );
	error_log( 'PP_Money( 1.9e309 ) = ' . print_r( $money, true ) );
	$money->display();
	echo "<br />";

	$money = new PP_Money( -1.8e207 );
	error_log( 'PP_Money( -1.8e207 ) = ' . print_r( $money, true ) );
	$money->display();
	echo "<br />";
}
?>