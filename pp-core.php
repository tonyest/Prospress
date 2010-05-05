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

/* Place your custom code (actions/filters) in a file called /plugins/pp-custom.php and it will be loaded before anything else. */
//if ( file_exists( WP_PLUGIN_DIR . '/pp-custom.php' ) )
//	require( WP_PLUGIN_DIR . '/pp-custom.php' );


if( !defined( 'PP_CORE_DIR' ) )
	define( 'PP_CORE_DIR', WP_PLUGIN_DIR . '/prospress/pp-core' );

// Include currency functions, class and global vars
// ** THIS CLASS CREATED A BAZOOKA FOR KILLING ANTS... A FEW SIMPLER FUNCTIONS HAVE BEEN ADDED TO THIS FILE
//if ( file_exists( PP_CORE_DIR . '/money.class.php' ) )
//	require_once( PP_CORE_DIR . '/money.class.php' );


/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * REMOVING WP DASHBOARD WIDGETS TO MAKE SPACE FOR PROSPRESS WIDGETS - WHICH ARE ADDED IN EACH COMPONENT
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
function pp_remove_wp_dashboard_widgets() {
	global $wp_meta_boxes;

	// Remove the main column widgets
	//unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_right_now']);
	//unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_recent_comments']);
	//unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_incoming_links']);
	unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_plugins']);

	// Remove the side column widgets
	unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_quick_press']);
	//unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_recent_drafts']);
	unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_primary']);
	unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_secondary']);

}
add_action('wp_dashboard_setup', 'pp_remove_wp_dashboard_widgets' );

/* * * * * * * * * * * * * EXAMPLE CODE TO ADD WIDGETS * * * * * * * * * * * * * * * * * *
// The function to output the contents of PP Dashboard Widget
function example_dashboard_widget_function() {
	// Display whatever it is you want to show
	echo "Hello World, I'm a great Dashboard Widget";
}

// Create the function use in the action hook
function example_add_dashboard_widgets() {
	wp_add_dashboard_widget('example_dashboard_widget', 'Example Dashboard Widget', 'example_dashboard_widget_function');	
} 

// Hoook into the 'wp_dashboard_setup' action to register our other functions
add_action('wp_dashboard_setup', 'example_add_dashboard_widgets' );


* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */


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
	//add_theme_page( page_title, menu_title, capability, handle, [function]);
	$theme_adapter_page = add_theme_page( __( 'Prospress Theme Tailor' ), __( 'Prospress Tailor' ), 'edit_themes', 'theme-tailor', 'pp_theme_tailor_page' );
	add_action('admin_print_styles-' . $theme_adapter_page, create_function( '', 'wp_enqueue_style( "theme-install" );' ) );

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

function pp_theme_tailor_page(){
	
	$pp_template_tags = array(	'post_end_time_filter' => array( 'label' => __( 'End Date:' ), 
										'supported_filters' =>array( 'the_title' => 'The Title', 
																	'single_post_title' => 'Single Post Title', 
																	'the_tags' => 'Tag List', 
																	'the_category' => 'Category List', 
																	'get_the_date' => 'The Date',
																	'get_the_time' => 'The Time'
																	)
										),
								'get_post_end_countdown'=> array( 'label' => __( 'Count down:' ),
										'supported_filters' =>array( 'the_title' => 'The Title', 
																	'single_post_title' => 'Single Post Title', 
																	'the_tags' => 'Tag List', 
																	'the_category' => 'Category List', 
																	'get_the_date' => 'The Date',
																	'get_the_time' => 'The Time'
																	)
										)
								);
	$pp_template_tags = apply_filters( 'pp_template_tags', $pp_template_tags );
	//error_log('$pp_template_tags = ' . print_r($pp_template_tags, true));
	$applied_filters = get_option( 'pp_theme_filters' );
	//error_log('$applied_filters = ' . print_r($applied_filters, true));
	?>
	<div class="wrap feedback-history">
	<?php screen_icon(); ?>
	<h2><?php _e( 'Prospress Theme Tailor' ); ?></h2>
	<p><?php _e( 'Tailor your existing theme for use as a Prospress marketplace. Simply select which marketplace details you want to display and where.' ); ?></p>
	<form action="" method="post">
	<div class="feature-filter">
		<?php foreach( $pp_template_tags as $key => $value ){ ?>
			<div class="feature-name"><?php echo $value['label']; ?></div>
			<ol class="feature-group">
			<?php foreach( $value['supported_filters'] as $filter => $label ){ ?>
				<li>
					<input type="checkbox" name="<?php echo $key; ?>[ ]" value="<?php echo $filter; ?>" id="<?php echo $key . '-' . $filter; ?>" <?php checked( @in_array( $filter, $applied_filters[$key] ), true ); ?>>
					<label for="" ><?php echo $label; ?></label>
				</li>
			<?php } ?>
			</ol>
			<br class="clear">
		<?php } ?>
	</div>
	<p><input type="submit" class="button" name="theme-tailer" value="Save"></p>
	<input type="hidden" name="page" value="theme-tailor">
	</form>
	</div>
	<?php
}

if( isset( $_POST[ 'theme-tailer' ] ) )
	pp_theme_tailor_save();

function pp_theme_tailor_save(){

	$theme_filters = $_POST;
	unset( $theme_filters[ 'page' ] );
	unset( $theme_filters[ 'theme-tailer' ] );
	foreach( $theme_filters as $key => &$filters ){
		foreach( $filters as &$filter ){
			$filter = strip_tags( $filter );
		}
		unset($filter);
	}
	update_option( 'pp_theme_filters', $theme_filters );
}

function pp_add_filters(){
	$applied_filters = get_option( 'pp_theme_filters' );

	//error_log('$applied_filters = ' . print_r($applied_filters, true));
	foreach( $applied_filters as $function => $filters ){
	//	error_log('$function = ' . print_r($function, true));
	//	error_log('$filters = ' . print_r($filters, true));
		foreach( $filters as $filter ){
	//		error_log('$filter = ' . print_r($filter, true));
			add_filter( $filter, $function );
		}
	}

}
add_action( 'init', 'pp_add_filters' );
