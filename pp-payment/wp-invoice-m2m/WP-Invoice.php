<?php
/*
Plugin Name: Web Invoicing and Billing - M2M Version
Plugin URI: http://twincitiestech.com/services/wp-invoice/
Description: Send itemized web-invoices directly to your clients.  Credit card payments may be accepted via Authorize.net, MerchantPlus NaviGate, or PayPal account. Recurring billing is also available via Authorize.net's ARB. Visit <a href="admin.php?page=invoice_settings">WP-Invoice Settings Page</a> to setup.
Author: TwinCitiesTech.com
Version: 0.7
Author URI: http://twincitiestech.com/

Copyright 2009  TwinCitiesTech.com Inc.   (email : andy.potanin@twincitiestech.com)
*/
/*
	Created by TwinCitiesTech.com
	(website: twincitiestech.com       email : support@twincitiestech.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; version 3 of the License.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

// define("WP_INVOICE_UI_PATH", ABSPATH . "wp-content/plugins/wp-invoice-m2m/core/ui/");
define("WP_INVOICE_UI_PATH", PP_INVOICE_DIR . "/core/ui/");

require_once("core/functions.php");
require_once("core/display.php");
require_once("core/frontend.php");
require_once("core/invoice_class.php");

$wp_invoice_debug = false;

class WP_Invoice {

	var $Invoice;
	var $wp_invoice_user_level = 0;
	var $uri;
	var $the_path;
	var $frontend_path;

	function the_path() {
		$path =	PP_PAYMENT_URL."/".basename(dirname(__FILE__));
		return $path;
	}

	function frontend_path() {
		$path =	PP_PAYMENT_URL."/".basename(dirname(__FILE__));
		if(get_option('wp_invoice_force_https') == 'true') $path = str_replace('http://','https://',$path);
		return $path;
	}

	function WP_Invoice() {
		global $user_ID;

		$version = get_option('wp_invoice_version');

		$this->path = dirname(__FILE__);
		$this->file = basename(__FILE__);
		$this->directory = basename($this->path);
		$this->uri = PP_PAYMENT_URL."/".$this->directory;
		$this->the_path = $this->the_path();

		$this->frontend_path = $this->frontend_path();

		add_action('wp_ajax_wp_invoice_process_cc_ajax', 'wp_invoice_process_cc_ajax');

		// Global admin that can configure plugin-wide settings
		$this->admin_user_level = 10;

		add_action('init',  array($this, 'init'),0);

 		add_action('profile_update','wp_invoice_profile_update');
		add_action('edit_user_profile', 'wp_invoice_user_profile_fields');
		add_action('show_user_profile', 'wp_invoice_user_profile_fields');
		add_action('admin_menu', array($this, 'wp_invoice_add_pages'));
		add_action('wp_head', 'wp_invoice_frontend_header'); 
 		add_action('admin_init', array($this, 'admin_init'));

		add_action('deleted_post', 'wp_invoice_delete_post');

		add_action('contextual_help', 'wp_invoice_contextual_help_list');

		add_shortcode('wp-invoice-lookup', 'wp_invoice_lookup');

		// Only run the content script if we are not using the replace_tag method.  We want to avoid running the function twice
		if(get_option('wp_invoice_where_to_display') != 'replace_tag') { add_filter('the_content', 'wp_invoice_the_content');  } else { add_shortcode('wp-invoice', 'wp_invoice_the_content'); 	}

		$this->SetUserAccess(get_option('wp_invoice_user_level'));

		// Load invoice lookup widget
		//add_action('widgets_init', create_function('', 'return register_widget("InvoiceLookupWidget");'));
		// load user's invoice history widget
		//add_action('widgets_init', create_function('', 'return register_widget("InvoiceHistoryWidget");'));

	}

	function SetUserAccess($level = 8) {
		$this->wp_invoice_user_level = $level;
	}

	function wp_invoice_add_pages() {
		global $_wp_last_object_menu, $wp_invoice_page_names, $screen_layout_columns;

		//necessary to insert the page link correctly into admin menu
		$_wp_last_object_menu++;

		// outgoing_invoices is currently sent to main
		$unsent_invoices = (count($this->unsent_invoices) > 0 ? "(" . count($this->unsent_invoices) . ")" : "");
		$unpaid_invoices = (count($this->unpaid_invoices) > 0 ? "(" . count($this->unpaid_invoices) . ")" : "");

		$wp_invoice_page_names['web_invoice'] 			= add_menu_page('Payments and Accounts', 'Accounts',  $this->wp_invoice_user_level,'outgoing_invoices', array(&$this,'outgoing_invoices'),$this->uri."/core/images/wp_invoice.png", $_wp_last_object_menu);		
		$wp_invoice_page_names['outgoing_invoices'] 	= add_submenu_page( 'outgoing_invoices', "Incoming Payments $unsent_invoices", "Incoming Payments $unsent_invoices", $this->wp_invoice_user_level, 'outgoing_invoices', array(&$this,'outgoing_invoices'));
		$wp_invoice_page_names['incoming_invoices'] 	= add_submenu_page( 'outgoing_invoices', "Outgoing Payments $unpaid_invoices", "Outgoing Payments $unpaid_invoices", $this->wp_invoice_user_level, 'incoming_invoices', array(&$this,'incoming_invoices'));
		$wp_invoice_page_names['user_settings'] 		= add_submenu_page( 'outgoing_invoices', "Settings", "Settings", $this->wp_invoice_user_level, 'user_settings_page', array(&$this,'user_settings_page'));
		$wp_invoice_page_names['global_settings'] 		= add_submenu_page( 'outgoing_invoices', "Global Settings", "Global Settings", $this->admin_user_level, 'invoice_settings', array(&$this,'settings_page'));

		$wp_invoice_page_names['make_payment'] = add_submenu_page( 'hidden', "View Invoice", "View Invoice", $this->wp_invoice_user_level, 'make_payment', array(&$this,'make_payment'));
		$wp_invoice_page_names['send_invoice'] = add_submenu_page( 'hidden', "Send Invoice", "Send Invoice", $this->wp_invoice_user_level, 'send_invoice', array(&$this,'send_invoice'));
		$wp_invoice_page_names['save_and_preview'] = add_submenu_page( 'hidden', "Save and Preview", "Save and Preview", $this->wp_invoice_user_level, 'save_and_preview', array(&$this,'save_and_preview'));

		foreach($wp_invoice_page_names as $name => $menu) {
 			add_action("admin_print_scripts-$menu", array($this, 'admin_print_scripts'));
			//add_action("admin_print_styles-$menu", array($this, 'admin_print_styles'));
		}

		//Make Payment Page Metaboxes
		add_meta_box('wp_invoice_metabox_invoice_details', __('Invoice Details',WP_INVOICE_TRANS_DOMAIN), 'wp_invoice_metabox_invoice_details', 'admin_page_make_payment', 'normal', 'high');
		add_meta_box('wp_invoice_metabox_billing_details', __('Billing Details',WP_INVOICE_TRANS_DOMAIN), 'wp_invoice_metabox_billing_details', 'admin_page_make_payment', 'normal', 'high');
		add_meta_box('wp_invoice_metabox_payee_details', __('Payment Recipient',WP_INVOICE_TRANS_DOMAIN), 'wp_invoice_metabox_payee_details','admin_page_make_payment', 'side', 'default');

		//Send Payment Page Metaboxes
		add_meta_box('wp_invoice_metabox_history', __('Invoice History',WP_INVOICE_TRANS_DOMAIN), 'wp_invoice_metabox_history','admin_page_send_invoice', 'normal', 'default');
		add_meta_box('wp_invoice_metabox_invoice_details', __('Invoice Details',WP_INVOICE_TRANS_DOMAIN), 'wp_invoice_metabox_invoice_details','admin_page_send_invoice', 'normal', 'default');
  		add_meta_box('wp_invoice_metabox_payer_details', __('Recipient',WP_INVOICE_TRANS_DOMAIN), 'wp_invoice_metabox_payer_details','admin_page_send_invoice', 'side', 'default');

		add_filter('screen_layout_columns', array(&$this, 'on_screen_layout_columns'), 10, 2);		

		register_column_headers("web-invoice_page_incoming_invoices", array(
			'cb' => '<input type="checkbox" />',
			'subject' => __('Subject'),
			'balance' => __('Balance'),
			'user_email' => __('User Email'),
			'user' => __('User Name'),
			'display_name' => __('Name'),
			'company_name' => __('Company Name'),
			'status' => __('Status'),
			'date_sent' => __('Date Received'),
			'due_date' => __('Due Date'),
			'invoice_id' => __('Invoice ID')
		));	

		register_column_headers("toplevel_page_outgoing_invoices", array(
			'cb' => '<input type="checkbox" />',
			'subject' => __('Subject'),
			'balance' => __('Balance'),
			'user_email' => __('User Email'),
			'user' => __('User Name'),
			'display_name' => __('Name'),
			'company_name' => __('Company Name'),
			'status' => __('Status'),
			'date_sent' => __('Date Sent'),
			'due_date' => __('Due Date'),
			'invoice_id' => __('Invoice ID')
		));		
 	}

/*
	Add columns to invoice editing page
*/
	function on_screen_layout_columns($columns, $screen) {
		global $wp_invoice_page_names;

			//$columns[$wp_invoice_page_names['make_payment']] = '2';

		return $columns;
	}	

	function admin_print_scripts() {
		wp_enqueue_script('common');
		wp_enqueue_script('wp-lists');
		wp_enqueue_script('postbox');

		wp_enqueue_script('jquery.cookie',$this->uri."/core/js/jquery.cookie.js", array('jquery'));
		wp_enqueue_script('jquery.livequery',$this->uri."/core/js/jquery.livequery.js", array('jquery'));
		wp_enqueue_script('jquery.formatCurrency',$this->uri."/core/js/jquery.formatCurrency.js", array('jquery'));
		wp_enqueue_script('jquery.idTabs',$this->uri."/core/js/jquery.idTabs.min.js", array('jquery'));
		wp_enqueue_script('jquery.impromptu',$this->uri."/core/js/jquery-impromptu.1.7.js", array('jquery'));
		wp_enqueue_script('jquery.field',$this->uri."/core/js/jquery.field.min.js", array('jquery'));
		wp_enqueue_script('jquery.calculation',$this->uri."/core/js/jquery.calculation.min.js", array('jquery'));
		wp_enqueue_script('jquery.tablesorter',$this->uri."/core/js/jquery.tablesorter.min.js", array('jquery'));
		wp_enqueue_script('jquery.autogrow-textarea',$this->uri."/core/js/jquery.autogrow-textarea.js", array('jquery') );
		wp_enqueue_script('wp-invoice',$this->uri."/core/js/wp-invoice-2.0.js", array('jquery') );		

   		wp_enqueue_style('wp_invoice_css', $this->uri . "/core/css/wp_admin-2.0.css");
		wp_print_styles();

		?>

	<?php
	}

	function send_invoice() {
		global $user_ID, $wpdb, $page_now, $wp_invoice_page_names, $screen_layout_columns;
		echo $page_now;
		$invoice_id = $_REQUEST['invoice_id'];
		$has_invoice_permissions = wp_invoice_user_has_permissions($invoice_id, $user_id);

		if($has_invoice_permissions) {
			$invoice_class = new wp_invoice_get($invoice_id);
			$errors = $invoice_class->error;
			$invoice = $invoice_class->data;

			// Get invoice reporting information
			if($invoice->is_paid) {
				$paid_data = $wpdb->get_row("SELECT value, time_stamp FROM  ".$wpdb->payments_log." WHERE action_type = 'paid' AND invoice_id = '".$invoice_id."' ORDER BY time_stamp DESC LIMIT 0, 1");			
				$paid_date = date_i18n(get_option('date_format').' '.get_option('time_format'), strtotime($paid_data->time_stamp));
			}

			if(!$invoice->is_paid && $has_invoice_permissions == 'payer')
				$messages[] = "You have not yet paid this invoice.";

			if(!$invoice->is_paid && $has_invoice_permissions == 'payee')
				$messages[] = "{$invoice->payer_class->user_nicename} has not paid this invoice yet.";

			if($invoice->is_paid)
				$messages[] = "{$paid_data->value} on $paid_date.";

			// UI Modifications
			// Remove payment metabox if current user is not the payer, or if the invoice has already been paid
			if($has_invoice_permissions != 'payer' || $invoice->is_paid) {
				remove_meta_box('wp_invoice_metabox_billing_details', $wp_invoice_page_names['make_payment'], 'normal');
				remove_meta_box('wp_invoice_metabox_billing_details', $wp_invoice_page_names['make_payment'], 'normal');
 			}

			include WP_INVOICE_UI_PATH . 'metaboxes/send_invoice.php';			
			include WP_INVOICE_UI_PATH . 'send_invoice.php';		
		} else {
			wp_invoice_backend_wrap("Error", "You are not allowed to view this invoice.");
		}

	}

	function make_payment() {
		global $user_ID, $wpdb, $page_now, $wp_invoice_page_names, $screen_layout_columns;
		echo $page_now;
		$invoice_id = $_REQUEST['invoice_id'];
		$has_invoice_permissions = wp_invoice_user_has_permissions($invoice_id, $user_id);

		if($has_invoice_permissions) {

			// Invoice Update Actions:

			// Draft Message
			if (wp_verify_nonce($_REQUEST['wp_invoice_process_cc'], 'wp_invoice_process_cc_' . $invoice_id)) {
				$draft_message = nl2br($_REQUEST['draft_message']);
				wp_invoice_update_invoice_meta($invoice_id,'paid_status','paid');
				wp_invoice_update_log($invoice_id,'paid',"Invoice paid via draft. <br />Message: {$draft_message}");			
			}

			// PayPal return
			if($_REQUEST['return_info'] == 'cancel') {
				$errors[] = "Your PayPal payment has not been processed.";
			}

			if($_REQUEST['return_info'] == 'success') {
				//$messages[] = "Invoice paid, receipt id: {$_REQUEST['receipt_id']}";
				wp_invoice_update_invoice_meta($invoice_id,'paid_status','paid');
				wp_invoice_update_log($invoice_id,'paid',"Invoice paid via PayPal. <br />Receipt id: {$_REQUEST['receipt_id']}");
 			}

			// Load invoice
			$invoice_class = new wp_invoice_get($invoice_id);
			$errors = $invoice_class->error;
			$invoice = $invoice_class->data;

			// Get invoice reporting information
			if($invoice->is_paid) {
				$paid_data = $wpdb->get_row("SELECT value, time_stamp FROM  ".$wpdb->payments_log." WHERE action_type = 'paid' AND invoice_id = '".$invoice_id."' ORDER BY time_stamp DESC LIMIT 0, 1");			
				$paid_date = date_i18n(get_option('date_format').' '.get_option('time_format'), strtotime($paid_data->time_stamp));
			}

			if(!$invoice->is_paid && $has_invoice_permissions == 'payer')
				$messages[] = "You have not yet paid this invoice.";

			if(!$invoice->is_paid && $has_invoice_permissions == 'payee')
				$messages[] = "{$invoice->payer_class->user_nicename} has not paid this invoice yet.";

			if($invoice->is_paid)
				$messages[] = "{$paid_data->value}. <br />Processed on $paid_date.";

			// UI Modifications
			// Remove payment metabox if current user is not the payer, or if the invoice has already been paid
			if($has_invoice_permissions != 'payer' || $invoice->is_paid) {
				remove_meta_box('wp_invoice_metabox_billing_details', $wp_invoice_page_names['make_payment'], 'normal');
 			}

			include WP_INVOICE_UI_PATH . 'metaboxes/make_payment.php';			
			include WP_INVOICE_UI_PATH . 'make_payment.php';		
		} else {
			wp_invoice_backend_wrap("Error", "You are not allowed to view this invoice.");
		}

	}

	function save_and_preview() {

	global $user_ID, $wpdb,$wp_invoice_email_variables;
		echo $page_now;
		$invoice_id = $_REQUEST['invoice_id'];
		$has_invoice_permissions = wp_invoice_user_has_permissions($invoice_id, $user_id);

		if($has_invoice_permissions) {

			// Update invoice settings that can be modified at invoice management page

			if(is_array($_REQUEST['wp_invoice'])) {
				$nonce = $_REQUEST['wp_invoice_update_single'];
			 	if (!wp_verify_nonce($nonce, 'wp_invoice_update_single_' . $invoice_id) ) die('Security check');

				foreach($_REQUEST['wp_invoice'] as $updated_item_key => $updated_item_value)
					wp_invoice_update_invoice_meta($invoice_id, $updated_item_key, $updated_item_value);
			}

			$invoice_class = new wp_invoice_get($invoice_id);
			$errors = $invoice_class->error;
			$invoice = $invoice_class->data;

			$wp_invoice_email_variables = wp_invoice_email_variables($invoice_id);

			include WP_INVOICE_UI_PATH . 'save_and_preview.php';
		} else {
			wp_invoice_backend_wrap("Error", "You are not allowed to view this invoice.");
		}

	}

	function incoming_invoices() {
 		global $wpdb, $user_ID, $wp_invoice_page_names;

		// Bulk options
		if(isset($_REQUEST['wp_invoice_action'])) {
			$action = $_REQUEST['wp_invoice_action'];
			$invoice_array = $_REQUEST['multiple_invoices'];

			switch($action) {

				case 'archive_invoice':				
					$message[] = wp_invoice_archive($invoice_array);
				break;		

				case 'unrachive_invoice':
					$message[] = wp_invoice_unarchive($invoice_array);
				break;		

			}
		}

		$incoming_invoices = $wpdb->get_col("SELECT id FROM ".$wpdb->payments." WHERE payer_id = '$user_ID'");

		//wpi_qc($incoming_invoices);
		include WP_INVOICE_UI_PATH . 'incoming_invoices.php';
 	}

	function outgoing_invoices() {		

		$needs_to_setup_billing = wp_invoice_user_settings('all');
		//wpi_qc($_REQUEST);

		// Bulk options
		if(isset($_REQUEST['wp_invoice_action'])) {
			$action = $_REQUEST['wp_invoice_action'];
			$invoice_array = $_REQUEST['multiple_invoices'];

			switch($action) {

				case 'archive_invoice':				
					$message[] = wp_invoice_archive($invoice_array);
				break;		

				case 'unrachive_invoice':
					$message[] = wp_invoice_unarchive($invoice_array);
				break;		

				case 'mark_as_sent':
					$message[] = wp_invoice_mark_as_sent($invoice_array);
				break;		

				case 'mark_as_paid':
					$message[] = wp_invoice_mark_as_paid($invoice_array);
				break;		

				case 'mark_as_unpaid':
					$message[] = wp_invoice_mark_as_unpaid($invoice_array);
				break;		
			}
		}

		if($_REQUEST[action] == 'post_save_and_preview') {
			$invoice_id = $_REQUEST['invoice_id'];
 			if($_REQUEST['wp_invoice_action'] == 'Email to Client') {
				wp_invoice_update_invoice_meta($invoice_id, 'email_payment_request', $_REQUEST['wp_invoice_payment_request']['email_message_content']);
				$message = wp_send_single_invoice($invoice_id);
			}			

			if($_REQUEST['wp_invoice_action'] == 'Save for Later') {			
				// Do nothing, invoice was already by visiting the save_and_preview page
			}		
		}

		global $wpdb, $user_ID, $wp_invoice_page_names;
		$outgoing_invoices = $wpdb->get_col("SELECT id FROM ".$wpdb->payments." WHERE payee_id = '$user_ID'");
		include WP_INVOICE_UI_PATH . 'outgoing_invoices.php';
	}

	function user_settings_page() {
		global $user_ID;

		$user_settings = wp_invoice_user_settings('all', $user_ID);
		//wpi_qc($_REQUEST[wp_invoice_user_settings]);
		//wpi_qc($user_settings);

		// Save settings
		if(count($_REQUEST[wp_invoice_user_settings]) > 1) {
			$user_settings = $_REQUEST[wp_invoice_user_settings];
			update_usermeta($user_ID, 'wp_invoice_settings', $user_settings);		

		} else {			

			if(!$user_settings) {
				$user_settings = wp_invoice_load_default_user_settings($user_ID);

				}
		}

		// The wp_invoice_user_settings() needs to be ran, it converts certain text values into bool values
		$user_settings = wp_invoice_user_settings('all', $user_ID);
		include WP_INVOICE_UI_PATH . 'user_settings_page.php';	
	}

	function settings_page() {
		global $wpdb;

		if(!empty($_REQUEST[wp_invoice_user_level]))
			update_option('wp_invoice_user_level', $_REQUEST[wp_invoice_user_level]);
		if(!empty($_REQUEST[wp_invoice_custom_label_tax]))
			update_option('wp_invoice_custom_label_tax', $_REQUEST[wp_invoice_custom_label_tax]);
		if(!empty($_REQUEST[wp_invoice_fe_state_selection]))
			update_option('wp_invoice_fe_state_selection', $_REQUEST[wp_invoice_fe_state_selection]);
		if(!empty($_REQUEST[wp_invoice_default_currency_code]))
			update_option('wp_invoice_default_currency_code', $_REQUEST[wp_invoice_default_currency_code]);
		if(!empty($_REQUEST[wp_invoice_using_godaddy]))
			update_option('wp_invoice_using_godaddy', $_REQUEST[wp_invoice_using_godaddy]);
		if(!empty($_REQUEST[wp_invoice_email_send_invoice_subject]))
			update_option('wp_invoice_email_send_invoice_subject', $_REQUEST[wp_invoice_email_send_invoice_subject]);
		if(!empty($_REQUEST[wp_invoice_email_send_invoice_content]))
			update_option('wp_invoice_email_send_invoice_content', $_REQUEST[wp_invoice_email_send_invoice_content]);
		if(!empty($_REQUEST[wp_invoice_email_send_reminder_subject]))
			update_option('wp_invoice_email_send_reminder_subject', $_REQUEST[wp_invoice_email_send_reminder_subject]);
		if(!empty($_REQUEST[wp_invoice_email_send_reminder_content]))
			update_option('wp_invoice_email_send_reminder_content', $_REQUEST[wp_invoice_email_send_reminder_content]);
		if(!empty($_REQUEST[wp_invoice_email_send_receipt_subject]))
			update_option('wp_invoice_email_send_receipt_subject', $_REQUEST[wp_invoice_email_send_receipt_subject]);
		if(!empty($_REQUEST[wp_invoice_email_send_receipt_content]))
			update_option('wp_invoice_email_send_receipt_content', $_REQUEST[wp_invoice_email_send_receipt_content]);

		if(!$wpdb->query("SHOW TABLES LIKE '".$wpdb->paymentsmeta."';") || !$wpdb->query("SHOW TABLES LIKE '".$wpdb->payments."';") || !$wpdb->query("SHOW TABLES LIKE '".$wpdb->payments_log."';")) { $warning_message = "The plugin database tables are gone, deactivate and reactivate plugin to re-create them."; }if($warning_message) echo "<div id=\"message\" class='error' ><p>$warning_message</p></div>";

		include WP_INVOICE_UI_PATH . 'settings_page.php';

	}

	function admin_init() {

		// Admin Redirections. Has to go here to load before headers
		if( $_REQUEST['wp_invoice_action'] == __('Continue Editing', WP_INVOICE_TRANS_DOMAIN)) {		
			wp_redirect(admin_url("admin.php?page=new_invoice&wp_invoice_action=doInvoice&invoice_id={$_REQUEST['invoice_id']}"));
			die();
		}

	}

	function init() {
		global $wpdb, $wp_version, $user_ID;

		// Load default user settings if none exist
		if(!get_usermeta($user_ID, 'wp_invoice_settings')) {
			wp_invoice_load_default_user_settings($user_ID);
		}

		// Load these variables early
		$this->incoming_invoices = $wpdb->get_col("SELECT id FROM ".$wpdb->payments." WHERE payer_id = '$user_ID'");
		$this->outgoing_invoices = $wpdb->get_col("SELECT id FROM ".$wpdb->payments." WHERE payee_id = '$user_ID'");

		foreach($this->incoming_invoices as $incoming_id) {
			$invoice_class = new wp_invoice_get($incoming_id);

			// Don't include archived invoices in the counts
			if($invoice_class->data->is_archived)
				continue;

			// Don't include paid invocies either
			if($invoice_class->data->is_paid)
				continue;

			if(!$invoice_class->data->is_paid)
				$this->unpaid_invoices[$incoming_id] = true;
		}

		foreach($this->outgoing_invoices as $outgoing_id) {

			// Don't add this invoice to unset array if it was just sent
			if($_REQUEST['wp_invoice_action'] == 'Email to Client' && $_REQUEST['invoice_id'] == $outgoing_id)
				continue;

			$invoice_class = new wp_invoice_get($outgoing_id);			

			// Don't include archived invoices in the counts
			if($invoice_class->data->is_archived)
				continue;

			// Don't include paid invocies either
			if($invoice_class->data->is_paid)
				continue;

			if(!$invoice_class->data->is_sent) 
				$this->unsent_invoices[$outgoing_id] = true;

		}

		if (version_compare($wp_version, '2.6', '<')) // Using old WordPress
        	load_plugin_textdomain(WP_INVOICE_TRANS_DOMAIN, PLUGINDIR.'/'.dirname(plugin_basename(__FILE__)).'/languages');
        else
        	load_plugin_textdomain(WP_INVOICE_TRANS_DOMAIN, PLUGINDIR.'/'.dirname(plugin_basename(__FILE__)).'/languages', dirname(plugin_basename(__FILE__)).'/languages');

			// Make sure proper MD5 is being passed (32 chars), and strip of everything but numbers and letters
			if(isset($_GET['invoice_id']) && strlen($_GET['invoice_id']) != 32) unset($_GET['invoice_id']); 
			$_GET['invoice_id'] = preg_replace('/[^A-Za-z0-9-]/', '', $_GET['invoice_id']);

			if(!empty($_GET['invoice_id'])) {

				$md5_invoice_id = $_GET['invoice_id'];

				// Convert MD5 hash into Actual Invoice ID
				$all_invoices = $wpdb->get_col("SELECT invoice_num FROM ".$wpdb->payments." ");
				foreach ($all_invoices as $value) { if(md5($value) == $md5_invoice_id) {$invoice_id = $value;} }		

				//Check if invoice exists, SSL enforcement is setp, and we are not currently browing HTTPS,  then reload page into HTTPS 
				if(!function_exists('wp_https_redirect')) {
					if(wp_invoice_does_invoice_exist($invoice_id) && get_option('wp_invoice_force_https') == 'true' && $_SERVER['HTTPS'] != "on") {  header("Location: https://" . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']); exit;}
				}

			}

			if(isset($_POST['wp_invoice_id_hash'])) {

				$md5_invoice_id = $_POST['wp_invoice_id_hash'];

				// Convert MD5 hash into Actual Invoice ID
				$all_invoices = $wpdb->get_col("SELECT invoice_num FROM ".$wpdb->payments." ");
				foreach ($all_invoices as $value) { if(md5($value) == $md5_invoice_id) {$invoice_id = $value;} }

				//Check to see if this is a credit card transaction, if so process
				if(wp_invoice_does_invoice_exist($invoice_id)) { wp_invoice_process_cc_transaction($_POST); exit; }
				}				

		if(empty($_GET['invoice_id'])) unset($_GET['invoice_id']);
		}

		function install() {
			global $wpdb;

			$current_db_version = get_option( 'PP_PAYMENTS_DB_VERSION' );

			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

			if($wpdb->get_var("SHOW TABLES LIKE '". $wpdb->payments ."'") != $wpdb->payments || $current_db_version < PP_PAYMENTS_DB_VERSION ) {
				$sql_main = "CREATE TABLE $wpdb->payments (
						id int(11) NOT NULL auto_increment,
						post_id bigint(20) NOT NULL,
						payer_id bigint(20) NOT NULL,
						payee_id bigint(20) NOT NULL,
						amount float(16,6) default '0',
						status int(255) NOT NULL,
						type varchar(255) NOT NULL,
						blog_id int(11) NOT NULL,
						PRIMARY KEY (id));";
				//error_log('$sql_main = ' . $sql_main );
				dbDelta($sql_main);
			}

			if($wpdb->get_var("SHOW TABLES LIKE '". $wpdb->paymentsmeta ."'") != $wpdb->paymentsmeta || $current_db_version < PP_PAYMENTS_DB_VERSION ) {
				$sql_meta= "CREATE TABLE $wpdb->paymentsmeta (
					meta_id bigint(20) NOT NULL AUTO_INCREMENT,
					invoice_id bigint(20) NOT NULL default '0',
					meta_key varchar(255) default NULL,
					meta_value longtext,
					PRIMARY KEY (meta_id)
					);";
				//error_log('$sql_meta = ' . $sql_meta );
				dbDelta($sql_meta);
			}

			if($wpdb->get_var("SHOW TABLES LIKE '". $wpdb->payments_log ."'") != $wpdb->payments_log || $current_db_version < PP_PAYMENTS_DB_VERSION ) {
				$sql_log = "CREATE TABLE $wpdb->payments_log (
					id bigint(20) NOT NULL auto_increment,
					invoice_id int(11) NOT NULL default '0',
					action_type varchar(255) NOT NULL,
					value longtext NOT NULL,
					time_stamp timestamp NOT NULL,
					PRIMARY KEY (id)
					);";
				//error_log('$sql_log = ' . $sql_log );
				dbDelta($sql_log);
			}

			update_option( 'PP_PAYMENTS_DB_VERSION', PP_PAYMENTS_DB_VERSION );

			// Help with updating
			if(get_option('wp_invoice_paypal_address','') != '') update_option('wp_invoice_paypal_allow', 'yes');
			if(get_option('wp_invoice_gateway_username','') != '') update_option('wp_invoice_cc_allow', 'yes');

			// Localization Labels
			add_option('wp_invoice_custom_label_tax', "Tax");

			// WP-Invoice Lookup
			add_option('wp_invoice_lookup_text', "Pay Your Invoice");
			add_option('wp_invoice_lookup_submit', "Lookup");

			// Frontend Customization
			add_option('wp_invoice_fe_paypal_link_url', "https://www.paypal.com/en_US/i/btn/btn_paynow_LG.gif");
			add_option('wp_invoice_fe_state_selection', "Dropdown");

			add_option('wp_invoice_email_address',get_bloginfo('admin_email'));
			add_option('wp_invoice_business_name', get_bloginfo('blogname'));
			add_option('wp_invoice_business_address', '');
			add_option('wp_invoice_show_business_address', 'no');
			add_option('wp_invoice_payment_method','');
			add_option('wp_invoice_user_level','level_8');
			add_option('wp_invoice_web_invoice_page','');
			add_option('wp_invoice_where_to_display','overwrite');
			add_option('wp_invoice_paypal_address','');
			add_option('wp_invoice_moneybookers_address','');
			add_option('wp_invoice_googlecheckout_address','');
			add_option('wp_invoice_default_currency_code','USD');
			add_option('wp_invoice_reminder_message','This is a reminder.');

			add_option('wp_invoice_show_quantities','Hide');
			add_option('wp_invoice_use_css','yes');
			add_option('wp_invoice_force_https','false');
			add_option('wp_invoice_send_thank_you_email','no');

			add_option('wp_invoice_use_recurring','yes');

			//Authorize.net Gateway  Settings
			add_option('wp_invoice_client_change_payment_method','yes');
			add_option('wp_invoice_gateway_username','');
			add_option('wp_invoice_gateway_tran_key','');
			add_option('wp_invoice_gateway_delim_char',',');
			add_option('wp_invoice_gateway_encap_char','');
			add_option('wp_invoice_gateway_merchant_email',get_bloginfo('admin_email'));
			add_option('wp_invoice_recurring_gateway_url','https://api.authorize.net/xml/v1/request.api');
			add_option('wp_invoice_gateway_url','https://gateway.merchantplus.com/cgi-bin/PAWebClient.cgi');
			add_option('wp_invoice_gateway_MD5Hash','');

			add_option('wp_invoice_gateway_test_mode','FALSE');
			add_option('wp_invoice_gateway_delim_data','TRUE');
			add_option('wp_invoice_gateway_relay_response','FALSE');
			add_option('wp_invoice_gateway_email_customer','FALSE');

			wp_invoice_load_email_template_content();
	}

}