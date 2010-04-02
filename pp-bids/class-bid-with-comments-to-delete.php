<?php
/**
 * Holds Most of the Prospress Bid classes, including the base bid system class.
 *
 * @package Prospress
 */

/**
 * Bid system base class.
 *
 * This class forms the basis for all bid systems. It provides a framework for creating new bid systems
 * and is extended to implement the core auction and reverse auction formats that ship with Prospress.
 * 
 * Extend this class to create a new bid system. PP_Bid_System::form_fields(), 
 * PP_Bid_System::form_submission(), PP_Bid_System::form_validation(), PP_Bid_System::view_details(),
 * PP_Bid_System::view_list() and PP_Bid_System::post_fields() must be over-ridden.
 *
 * @package Prospress
 * @since 0.1
 */
/** @TODO Make abstract class when PHP4.x is no longer supported */
class PP_Bid_System {

	//Requirements:
	//Bid Form: for a user to enter a bid and featuring a range of fields that will vary depending on the marketplace format.
	//Bid Logic: processing bid form submission e.g. writing data to database.
	//Bid Details View: to see the details of just one bid.
	//Bid List View: to see the details of a group of bids (for example by user, by post).
	//Post Fields: certain bid formats may want parameters for each post, e.g. starting price, reserve price, price range, buy it now price
	//?Admin Fields: for setting options for the instance of the class, e.g. for Auction instance, "allow sequential, open etc."

	/**
	 * Public name of the bid system e.g. "Auction".
	 *
	 * @since 0.1
	 * @access public
	 */
	var $name;

	/**
	 * Title for the bid form.
	 *
	 * @since 0.1
	 * @access public
	 * @var string
	 */
	var $bid_form_title;

	/**
	 * Text used on the submit button of the bid form.
	 *
	 * @since 0.1
	 * @access public
	 * @var string
	 */
	var $bid_button_value;

	// Constructors you'll need to call.

	/**
	 * PHP4 constructor
	 */
	function PP_Bid_System( $name, $bid_form_title = __("Make a bid"), $bid_button_value = __("Bid now!") ) {
		$this->__construct( $name, $bid_form_title, $bid_button_value );
	}

	/**
	 * PHP5 constructor
	 * 
	 * @param string $name Name for the bid system. Displayed on the configuration page.
	 * @param string $bid_form_title Optional used as the title text of the bid form. Defaults to "Make a bid".
	 * @param string $bid_button_value Optional used on the button of the bid form.Defaults to "Bid now!".
	 */
	function __construct( $name, $bid_form_title = __("Make a bid"), $bid_button_value = __("Bid now!") ) {
		$this->name = $name;
		$this->$bid_form_title = $bid_form_title;
		$this->bid_button_value = $bid_button_value;
	}

	// Member functions that you must override.

	/** 
	 * The custom fields for the bid form.
	 *
	 * The bid form is presented to users so they can make a bid on a post. This class provides a template
	 * for the form header and footer, but you can include additional fields using this function.
	 * 
	 * Subclasses must override this function to generate the bid form for their bid system. It is also
	 * recommended that bid forms include a bid bar so that users can customise the form with bid widgets.
	 */
	function form_fields() {
		die('function PP_Bid_System::form_fields() must be over-ridden in a sub-class.');
	}

	/** 
	 * Process the bid form on submission.
	 *
	 * The bid form is presented to users so they may bid of a post.
	 * 
	 * Subclasses must override this function to generate the bid form for their bid system.
	 *
	 */
	function form_submission(){
		die('function PP_Bid_System::form_submission() must be over-ridden in a sub-class.');
	}
	
	/**
	 * Validates a bid when a bid form is submitted. 
	 *
	 * Subclasses must override this function for their bid system.
	 *
	 * @since 0.1
	 *
	 * @return false Returns false if no form validation bids appear
	 */
	function form_validation(){
		die('function PP_Bid_System::form_validation() must be over-ridden in a sub-class.');
	}
	
	/** 
	 * Process the bid form on submission.
	 *
	 * The bid form is presented to users so they may bid of a post.
	 * 
	 * Subclasses must override this function to generate the bid form for their bid system.
	 *
	 */
	function view_details(){
		echo '<p class="no-bid-details">' . __('No details available for this bid.') . '</p>';
		return 'no_details_view';
	}
	
	/** 
	 * Process the bid form on submission.
	 *
	 * The bid form is presented to users so they may bid of a post.
	 * 
	 * Subclasses must override this function to generate the bid form for their bid system.
	 *
	 */
	function view_list(){
		return 'no_list_view';
	}
	
	// Functions that you may choose to override.

	/** 
	 * The function that brings all the bid form elements together.
	 *
	 * The bid form is presented to users so they can make a bid on a post. This function brings together 
	 * all the bid form elements, including:
	 * - the form header and footer templates;
	 * - the custom fields you implemented with form_fields(); and
	 * - the bid bar, a side bar for the bid form which allows a marketplace administrator to use
	 *   'bid widgets' to customise the bid form. 
	 * 
	 */
	function form() {
		$this->form_header();
		$this->form_fields();
		//Something to get bid bar
		/** @TODO Implement bid bar in PP_Bid_System::form()*/
		$this->form_footer();
	}

	/** 
	 * Header template for the bid form.
	 *
	 * This function prints a standard header for the bid form. You may override this if you choose. 
	 */
	function form_header() {
	?>
		<div class="make-bid">
			<h2><?php _e('Make a Bid'); ?></h2>
			<?php print_bid_messages(); ?>
			<form id="makebidform" method="get" action="<?php pp_bid_form_action(); ?>">
	<?php
	}

	/** 
	 * Footer template for the bid form.
	 *
	 * This function prints a standard footer for the bid form, including a submit button. 
	 * You may override this function if you choose.
	 */
	function form_footer() {
	?>
		<input name="bid_submit" type="submit" id="bid_submit" value="<?php echo $bid_button_value; ?>" />
		<?php bid_hidden_fields(); ?>
		<?php bid_extra_fields(); ?>
		</form>
		</div>
	<?php
	}

	/** 
	 * Fields for taking input from the post edit and add new post forms.
	 * 
	 * Some bid systems may use parameters for each post set by the user, e.g. starting price, reserve price 
	 * or price range. This function is called in a special meta box when editing or adding a post so 
	 * that you can request this input from the user. 
	 * 
	 * You may override this function if you choose.
	 */
	function post_fields(){
		return 'no_post_fields';
	}

	/** 
	 * Processes data taken from the post edit and add new post forms.
	 * 
	 * Some bid systems may require customisable parameters for each post, e.g. starting price, reserve price 
	 * or price range. This function is called in a special "Selling Options" meta box so that a user 
	 * 
	 * 
	 * You may override this function if you choose.
	 */
	function post_fields_submit(){
		return 'no_post_submit_method';
	}


	// Functions you may want to call.

	//Function to print the feedback history for a user
	function pp_bids_history_admin() {
	  	global $wpdb, $user_ID;

		get_currentuserinfo(); //get's user ID of currently logged in user and puts into global $user_id
		/** @TODO remove hardcoded database name from bids history table this query! */
		$bids = $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->bids WHERE bidder_id = %d", $user_ID), ARRAY_A); //get feedback for user
		include_once(PP_BIDS_DIR . '/bid-history-view.php');
	}

	//Add bid history column headings to the built in print_column_headers function
	function pp_bid_history_columns_admin(){
	 	return array(
			'cb' => '<input type="checkbox" />',
			'bid_id' => __('Bid ID'),
			'post_id' => __('Post'),
			'bid_value' => __('Amount'),
			'bid_date' => __('Date'),
		);
	}
	add_filter('manage_bid_history_columns','pp_bid_history_columns_admin');



	// Private Functions. Don't worry about these.

}

