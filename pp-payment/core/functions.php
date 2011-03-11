<?php 
/*
	Created by TwinCitiesTech.com
	(website: twincitiestech.com       email : support@twincitiestech.com)
*/

// Hide errors if using PHP4, otherwise we get many html_entity_decode() errors
if ( phpversion() <= 5 ) { ini_set('error_reporting', 0); }

//Get the ID of a post's invoice
function pp_get_invoice_id( $post_id ) {
	global $wpdb;

	$invoice_id = $wpdb->get_var("SELECT id FROM ".$wpdb->payments."  WHERE post_id = '$post_id'" );

	return $invoice_id;
}


//New function for sending invoices 
function pp_send_single_invoice( $invoice_id, $message = false ) {
	$invoice_class = new pp_invoice_get( $invoice_id );
	$invoice = $invoice_class->data;

	if( wp_mail( $invoice->payer_class->user_email, "Invoice: {$invoice->post_title}", $invoice_class->data->email_payment_request, "From: {$invoice->payee_class->display_name} <{$invoice->payee_class->user_email}>\r\n" ) ) {
		pp_update_invoice_meta( $invoice_id, "sent_date", date("Y-m-d", time()));
		pp_invoice_update_log( $invoice_id,'contact',"Invoice emailed to {$invoice->payer_class->user_email}" ); 
		return "Invoice sent.";
	} else {
		return "There was a problem sending the invoice, please try again.";
	}

}


//Converts payment venue slug into nice name
function pp_invoice_payment_nicename( $slug) {

	switch( $slug) {
		case 'paypal':
			return "PayPal";
		break;
		case 'cc':
			return "Credit Card";
		break;
		case 'draft':
			return "Bank Transfer";
		break;

	}

}


// Return a user's Prospress Payment settings, if no user_id is passed, the current user is used
function pp_invoice_user_settings( $what, $user_id = false ) {
	global $user_ID;

	if( $user_id === false )
		$user_id = $user_ID;

	// Load user settings
	$user_settings = get_user_meta( $user_id, 'pp_invoice_settings' );
	$default_settings[0] = pp_invoice_load_default_user_settings( $user_id );
	$user_settings = wp_parse_args( $user_settings[0], $default_settings[0] );

	// Remove slashes from entire array
	$user_settings = stripslashes_deep( $user_settings );

	// Replace "false" and "true" strings with boolean values
	foreach( $user_settings as $setting_name => $setting_value ) {

		if( $setting_value == 'true' )
			$user_settings[ $setting_name ] = true;

		if( $setting_value == 'false' )
			$user_settings[ $setting_name ] = false;
	}

	if( $what != 'all' ) 
		return $user_settings[ $what ];

	if( $what == 'all' ) 
		return $user_settings;

	return false;
}

// Load default user options into a users settings. Some settings are generated based on user account settings
function pp_invoice_load_default_user_settings( $user_id ) {

	$user_data = get_userdata( $user_id );

	$settings[ 'show_address_on_invoice' ] 		= false;
	$settings[ 'paypal_allow' ] 				= false;
	$settings[ 'cc_allow' ] 					= false;
	$settings[ 'draft_allow' ] 					= false;
	$settings[ 'paypal_sandbox' ] 				= false;
	$settings[ 'payment_received_notification' ] = false;

	$settings[ 'business_name' ] 				= $user_data->display_name;
	$settings[ 'user_email' ] 					= $user_data->user_email;
	$settings[ 'reminder_message' ] 			= "This is a reminder to pay your invoice.";
	$settings[ 'tax_label' ] 					= "Tax";

	$settings[ 'paypal_address' ] 				= '';
	$settings[ 'gateway_username' ] 			= '';
	$settings[ 'gateway_tran_key' ] 			= '';
	$settings[ 'gateway_url' ] 					= '';
	$settings[ 'gateway_delim_char' ] 			= '';
	$settings[ 'gateway_encap_char' ] 			= '';
	$settings[ 'gateway_MD5Hash' ] 				= '';
	$settings[ 'gateway_delim_data' ] 			= '';
	$settings[ 'draft_text' ] 					= '';

	return $settings;
}


function pp_invoice_create( $args, $meta = '' ) {
	global $blog_id, $wpdb;

	$defaults = array(
		'post_id' => false,
		'payer_id' => false, 
		'payee_id' => false,
		'amount' => false,
		'status' => 'pending',
		'type' => false,
		'blog_id' => $blog_id
		);

	$meta_defaults = array(
		'due_date_day' => date('j'),
		'due_date_month' => date('n'),
		'due_date_year' => date('Y')
		);

	$args = wp_parse_args( $args, $defaults );
	$meta = wp_parse_args( $meta, $meta_defaults );
	extract( $args, EXTR_SKIP );

	if( !$post_id || !$payee_id || !$amount ) // payer can be 0 for buy now
		return;

	if( $wpdb->query( "INSERT INTO " . $wpdb->payments . " ( post_id,payer_id,payee_id,amount,status,type,blog_id )	VALUES ('$post_id','$payer_id','$payee_id','$amount','$status','$type','$blog_id' )" ) ) {
		$error = false;
		$message = __( "New Invoice saved for $post_id.", 'prospress' );
		$invoice_id = $wpdb->insert_id;
		pp_invoice_update_log( $invoice_id, 'created', "Invoice created from post ( $post_id )." );
		foreach( $meta as $key => $value )
			$wpdb->insert( $wpdb->paymentsmeta, array( 'invoice_id' => $invoice_id, 'meta_key' => $key, 'meta_value' => $value ) );
	} else {
		$error = true; 
		$message = __("There was a problem saving invoice.  Try deactivating and reactivating plugin.", 'prospress' ); 
	}

	return compact( 'error', 'message', 'invoice_id' );
}


function pp_invoice_user_has_permissions( $invoice_id, $user_id = false ) {
	global $user_ID, $wpdb;

	// Set to global variable if no user_id is passed
	if( !$user_id )
		$user_id = $user_ID;

 	// Get invoice with passed id where user is either a payee or a payer
	$invoices = $wpdb->get_row( "SELECT * FROM " . $wpdb->payments . " 
								WHERE id = '$invoice_id' 
								AND (payer_id = '$user_id'
								OR payee_id = '$user_id' )" );

	// If an invoice exists, return whether the user is payee or payer
	if( count( $invoices) > 0 ) {
		if( $invoices->payer_id == $user_id && $invoices->payee_id != $user_id )
			return 'payer';

		if( $invoices->payer_id != $user_id && $invoices->payee_id == $user_id )
			return 'payee';

		if( $invoices->payer_id == $user_id && $invoices->payee_id == $user_id )
			return 'self_invoice';	
	}

	return false;

}


function pp_invoice_number_of_invoices() {
	global $wpdb;

	$query = "SELECT COUNT(*) FROM ".$wpdb->payments."";
	$count = $wpdb->get_var( $query);

	return $count;
}


function pp_invoice_does_invoice_exist( $invoice_id ) {
	global $wpdb;

	return $wpdb->get_var("SELECT * FROM ".$wpdb->payments." WHERE id = $invoice_id" );
}

function pp_invoice_validate_cc_number( $cc_number) {
   /* Validate; return value is card type if valid. */
   $false = false;
   $card_type = "";
   $card_regexes = array(
      "/^4\d{12}(\d\d\d ){0,1}$/" => "visa",
      "/^5[12345]\d{14}$/"       => "mastercard",
      "/^3[47]\d{13}$/"          => "amex",
      "/^6011\d{12}$/"           => "discover",
      "/^30[012345]\d{11}$/"     => "diners",
      "/^3[68]\d{12}$/"          => "diners",
   );

   foreach ( $card_regexes as $regex => $type ) {
       if (preg_match( $regex, $cc_number)) {
           $card_type = $type;
           break;
       }
   }

   if (!$card_type ) {
       return $false;
   }

   /*  mod 10 checksum algorithm  */
   $revcode = strrev( $cc_number);
   $checksum = 0;

   for ( $i = 0; $i < strlen( $revcode ); $i++) {
       $current_num = intval( $revcode[$i]);
       if( $i & 1) {  /* Odd  position */
          $current_num *= 2;
       }
       /* Split digits and add. */
           $checksum += $current_num % 10; if
       ( $current_num >  9) {
           $checksum += 1;
       }
   }

   if ( $checksum % 10 == 0) {
       return $card_type;
   } else {
       return $false;
   }
}


function pp_invoice_update_log( $invoice_id, $action_type, $value )  {
	global $wpdb;

	if( isset( $invoice_id ) ) {
		$time_stamp = date( "Y-m-d h-i-s" );
		$wpdb->query("INSERT INTO ".$wpdb->payments_log." 
		(invoice_id , action_type , value, time_stamp)
		VALUES ('$invoice_id', '$action_type', '$value', '$time_stamp' );" );
	}
}


function pp_invoice_query_log( $invoice_id, $action_type ) {
	global $wpdb;

	return $wpdb->get_results("SELECT * FROM ".$wpdb->payments_log." WHERE invoice_id = '$invoice_id' AND action_type = '$action_type' ORDER BY 'time_stamp' DESC" );
}


function pp_invoice_meta( $invoice_id, $meta_key) {
	global $wpdb;
	return $wpdb->get_var("SELECT meta_value FROM `".$wpdb->paymentsmeta."` WHERE meta_key = '$meta_key' AND invoice_id = '$invoice_id'" );
}


function pp_invoice_update_status( $invoice_id, $status ) {
	global $wpdb;

	$wpdb->query( "UPDATE ".$wpdb->payments." SET status = '$status' WHERE  id = '$invoice_id'" );
}

/*
 * Updates payments meta for specified key & invoice id OR if key does not exist inserts new entry
 *
*/
function pp_update_invoice_meta( $invoice_id, $meta_key, $meta_value = '' ) {
	global $wpdb;

	if ( pp_invoice_meta( $invoice_id, $meta_key ) ) { //meta key exists

		if( empty( $meta_value ) ) {
			// Delete meta_key if no value is set
			$wpdb->query( "DELETE FROM ".$wpdb->paymentsmeta." WHERE  invoice_id = '$invoice_id' AND meta_key = '$meta_key'" ); 
		} else {
			$wpdb->update( $wpdb->paymentsmeta, array( 'meta_value' => $meta_value ), array( 'invoice_id' => $invoice_id, 'meta_key' => $meta_key ) );
		}
	} else { // meta key does not exist in paymentsmeta
		$wpdb->insert( $wpdb->paymentsmeta, array( 'invoice_id' => $invoice_id, 'meta_key' => $meta_key, 'meta_value' => $meta_value ) );
	}
}


function pp_delete_invoice_meta( $invoice_id, $meta_key = '' ) {
	global $wpdb;

	if( empty( $meta_key ) ) { 
		$wpdb->query( "DELETE FROM `".$wpdb->paymentsmeta."` WHERE invoice_id = '$invoice_id' " );
	} else { 
		$wpdb->query( "DELETE FROM `".$wpdb->paymentsmeta."` WHERE invoice_id = '$invoice_id' AND meta_key = '$meta_key'" );
	}
}


function pp_invoice_archive( $invoice_id ) {
	global $wpdb;

	// Check to see if array is passed or single.
	if(is_array( $invoice_id )) {
		$counter=0;
		foreach ( $invoice_id as $single_invoice_id ) {
		$counter++;
		pp_update_invoice_meta( $single_invoice_id, "archive_status", "archived" );
		}
		return __("$counter  invoice(s) archived.", 'prospress' );

	}
	else {
		pp_update_invoice_meta( $invoice_id, "archive_status", "archived" );
		return __('Invoice successfully archived.', 'prospress' );
	}
}


function pp_invoice_mark_as_unpaid( $invoice_id ) {
	global $wpdb;

	// Check to see if array is passed or single.
	if(is_array( $invoice_id )) {
		$counter=0;
		foreach ( $invoice_id as $single_invoice_id ) {
			$counter++;
			pp_invoice_mark_as_unpaid( $single_invoice_id );
		}
		return sprintf( _n( "Invoice marked as unpaid.", "%d invoices marked as unpaid.", $counter ), $counter );
	} else {
		pp_invoice_update_status( $invoice_id, 'pending' );
		pp_invoice_update_log( $invoice_id,'paid',"Invoice marked as un-paid" );
		return __( 'Invoice marked as unpaid.', 'prospress' );
	}
}


function pp_invoice_mark_as_paid( $invoice_id ) {
	global $wpdb;

	// Check to see if array is passed or single.
	$counter = 0;
	if( is_array( $invoice_id ) ) {
		foreach ( $invoice_id as $single_invoice_id ) {
			$counter++;
			pp_invoice_mark_as_paid( $single_invoice_id );
		}
	} else {
		$counter = 1;
		pp_invoice_update_status( $invoice_id, 'paid' );
		pp_invoice_update_log( $invoice_id,'paid',"Invoice marked as paid" );

		if(get_option('pp_invoice_send_thank_you_email' ) == 'yes' ) 
			pp_invoice_send_email_receipt( $single_invoice_id );
	}

	if( get_option('pp_invoice_send_thank_you_email' ) == 'yes' )
		return sprintf( _n( "Invoice marked as paid, and thank you email sent to customer.", "%d invoices marked as paid, and thank you emails sent to customers.", $counter ), $counter );
	else
		return sprintf( _n( "Invoice marked as paid.", "%d invoices marked as paid.", $counter ), $counter );
}


function pp_invoice_unarchive( $invoice_id ) {
	global $wpdb;

	// Check to see if array is passed or single.
	if( is_array( $invoice_id ) ) {
		$counter=0;
		foreach ( $invoice_id as $single_invoice_id ) {
			$counter++;
			pp_delete_invoice_meta( $single_invoice_id, "archive_status" );
		}
		return $counter . __(' invoice(s) unarchived.', 'prospress' );
	} else {
		pp_delete_invoice_meta( $invoice_id, "archive_status" );
		return __('Invoice successfully unarchived', 'prospress' );
	}
}


function pp_invoice_mark_as_sent( $invoice_id ) {
	global $wpdb;

	// Check to see if array is passed or single.
	if( is_array( $invoice_id ) ) {
		$counter=0;
		foreach ( $invoice_id as $single_invoice_id ) {
			$counter++;
			pp_update_invoice_meta( $single_invoice_id, "sent_date", date("Y-m-d", time()));
			pp_invoice_update_log( $single_invoice_id,'contact','Invoice Maked as eMailed' ); //make sent entry
		}
		return sprintf( _n( "Invoice marked as sent.", "%d invoices marked as sent.", $counter ), $counter );
		//return $counter .  __(' invoice(s) marked as sent.', 'prospress' );
	} else {
		pp_update_invoice_meta( $invoice_id, "sent_date", date("Y-m-d", time()));
		pp_invoice_update_log( $invoice_id,'contact','Invoice Maked as eMailed' ); //make sent entry

		return __( 'Invoice market as sent.', 'prospress' );
	}
}


function pp_invoice_paid( $invoice_id, $payment_method = '' ) {
	global $wpdb;

	if( $payment_method == '' && isset( $_SERVER['REMOTE_ADDR'] ) )
		$payment_method = $_SERVER['REMOTE_ADDR'];

	$paid_msg = ( !empty( $payment_method ) ) ? sprintf( __( "Invoice paid by %s.", 'prospress' ), $payment_method ) : __( "Invoice paid.", 'prospress' );

	pp_invoice_update_status( $invoice_id, 'paid' );
 	pp_invoice_update_log( $invoice_id,'paid', $paid_msg );
}


function pp_invoice_recurring( $invoice_id ) {
	global $wpdb;
	if(pp_invoice_meta( $invoice_id,'recurring_billing' )) return true;
}


function pp_invoice_is_paid( $invoice_id ) { //Merged with paid_status in class
	global $wpdb;

	if( 'paid' == $wpdb->get_var( "SELECT status FROM  " . $wpdb->payments . " WHERE id = '$invoice_id'" ) ) 
		return true;
}


function pp_invoice_draw_inputfield( $name,$value,$special = '' ) {

	return "<input id='$name' type='text' class='$name input_field regular-text' name='$name' value='$value' $special />";
}


function pp_invoice_draw_textarea( $name,$value,$special = '' ) {

	return "<textarea id='$name' class='$name large-text' name='$name' $special >$value</textarea>";
}


function pp_invoice_draw_select( $name,$values,$current_value = '' ) {

	$output = "<select id='$name' name='$name' class='$name'>";
	foreach( $values as $key => $value ) {
	$output .=  "<option style='padding-right: 10px;' value='$key'";
	if( $key == $current_value ) $output .= " selected";	
	$output .= ">".stripslashes( $value )."</option>";
	}
	$output .= "</select>";

	return $output;
}


function pp_invoice_send_email_receipt( $invoice_id ) {
	global $wpdb, $pp_invoice_email_variables;

	$invoice_info = new PP_Invoice_GetInfo( $invoice_id );
	$pp_invoice_email_variables = pp_invoice_email_variables( $invoice_id );

	$message = pp_invoice_show_receipt_email( $invoice_id );

	$name = get_option("pp_invoice_business_name" );
	$from = get_option("pp_invoice_email_address" );

	$headers = "From: {$name} <{$from}>\r\n";
	if (get_option('pp_invoice_cc_thank_you_email' ) == 'yes' ) {
		$headers .= "CC: {$from}\r\n";
	}

	$message = pp_invoice_show_receipt_email( $invoice_id );
	$subject = preg_replace_callback('/(%([a-z_]+)%)/', 'pp_invoice_email_apply_variables', get_option('pp_invoice_email_send_receipt_subject' ));

	if(wp_mail( $invoice_info->recipient('email_address' ), $subject, $message, $headers ) )
		pp_invoice_update_log( $invoice_id,'contact','Receipt eMailed' );

	return $message;
}


function pp_invoice_format_phone( $phone ) {

	$phone = preg_replace("/[^0-9]/", "", $phone );

	if(strlen( $phone ) == 7)
		return preg_replace("/([0-9]{3})([0-9]{4})/", "$1-$2", $phone );
	elseif(strlen( $phone ) == 10)
		return preg_replace("/([0-9]{3})([0-9]{3})([0-9]{4})/", "( $1) $2-$3", $phone );
	else
		return $phone;
}


function pp_invoice_send_email( $invoice_array, $reminder = false ) {
	global $wpdb, $pp_invoice_email_variables;

	if(is_array( $invoice_array)) {
		$counter=0;
		foreach ( $invoice_array as $invoice_id ) {
			$pp_invoice_email_variables = pp_invoice_email_variables( $invoice_id );

			$invoice_info = $wpdb->get_row("SELECT * FROM ".$wpdb->payments." WHERE id = '".$invoice_id."'" );

			$profileuser = get_user_to_edit( $invoice_info->user_id );

			if ( $reminder) {
				$message = strip_tags(pp_invoice_show_reminder_email( $invoice_id ));
				$subject = preg_replace_callback('/(%([a-z_]+)%)/', 'pp_invoice_email_apply_variables', get_option('pp_invoice_email_send_reminder_subject' ));
			} else {
				$message = strip_tags(pp_invoice_show_email( $invoice_id ));
				$subject = preg_replace_callback('/(%([a-z_]+)%)/', 'pp_invoice_email_apply_variables', get_option('pp_invoice_email_send_invoice_subject' ));
			}

			$name = get_option("pp_invoice_business_name" );
			$from = get_option("pp_invoice_email_address" );

			$headers = "From: {$name} <{$from}>\r\n";

			$message = html_entity_decode( $message, ENT_QUOTES, 'UTF-8' );

			if(wp_mail( $profileuser->user_email, $subject, $message, $headers)) {
				$counter++; // Success in sending quantified.
				pp_invoice_update_log( $invoice_id,'contact','Invoice emailed' ); //make sent entry
				pp_update_invoice_meta( $invoice_id, "sent_date", date("Y-m-d", time()));
			}
		}
		return "Successfully sent $counter Web Invoices(s).";
	}
	else {
		$invoice_id = $invoice_array;
		$pp_invoice_email_variables = pp_invoice_email_variables( $invoice_id );
		$invoice_info = $wpdb->get_row("SELECT * FROM ".$wpdb->payments." WHERE id = '".$invoice_array."'" );

		$profileuser = get_user_to_edit( $invoice_info->user_id );

		if ( $reminder) {
			$message = strip_tags(pp_invoice_show_reminder_email( $invoice_id ));
			$subject = preg_replace_callback('/(%([a-z_]+)*)/', 'pp_invoice_email_apply_variables', get_option('pp_invoice_email_send_reminder_subject' ));
		} else {
			$message = strip_tags(pp_invoice_show_email( $invoice_id ));
			$subject = preg_replace_callback('/(%([a-z_]+)%)/', 'pp_invoice_email_apply_variables', get_option('pp_invoice_email_send_invoice_subject' ));
		}

		$name = get_option("pp_invoice_business_name" );
		$from = get_option("pp_invoice_email_address" );

		$headers = "From: {$name} <{$from}>\r\n";

		$message = html_entity_decode( $message, ENT_QUOTES, 'UTF-8' );

		if(wp_mail( $profileuser->user_email, $subject, $message, $headers)) {
			pp_update_invoice_meta( $invoice_id, "sent_date", date("Y-m-d", time()));
			pp_invoice_update_log( $invoice_id,'contact','Invoice emailed' ); return "Web invoice sent successfully."; }
			else { return "There was a problem sending the invoice."; }

	}
}


function pp_invoice_array_stripslashes( $slash_array = array()) {
	if( $slash_array) {
		foreach( $slash_array as $key=>$value ) {
			if(is_array( $value )) {
				$slash_array[$key] = pp_invoice_array_stripslashes( $value );
			}
			else {
				$slash_array[$key] = stripslashes( $value );
			}
		}
	}
	return( $slash_array);
}


function pp_invoice_profile_update() {
	global $wpdb;
	$user_id =  $_REQUEST['user_id'];

	if(isset( $_POST['company_name'])) update_user_meta( $user_id, 'company_name', $_POST['company_name']);
	if(isset( $_POST['streetaddress'])) update_user_meta( $user_id, 'streetaddress', $_POST['streetaddress']);
	if(isset( $_POST['zip']))  update_user_meta( $user_id, 'zip', $_POST['zip']);
	if(isset( $_POST['state'])) update_user_meta( $user_id, 'state', $_POST['state']);
	if(isset( $_POST['city'])) update_user_meta( $user_id, 'city', $_POST['city']);
	if(isset( $_POST['phonenumber'])) update_user_meta( $user_id, 'phonenumber', $_POST['phonenumber']);

}


class PP_Invoice_Date  {

	function convert( $string, $from_mask, $to_mask='', $return_unix=false ) {
		// define the valid values that we will use to check
		// value => length
		$all = array(
			's' => 'ss',
			'i' => 'ii',
			'H' => 'HH',
			'y' => 'yy',
			'Y' => 'YYYY', 
			'm' => 'mm', 
			'd' => 'dd'
		);

		// this will give us a mask with full length fields
		$from_mask = str_replace(array_keys( $all), $all, $from_mask);

		$vals = array();
		foreach( $all as $type => $chars) {
			// get the position of the current character
			if(( $pos = strpos( $from_mask, $chars)) === false )
				continue;

			// find the value in the original string
			$val = substr( $string, $pos, strlen( $chars));

			// store it for later processing
			$vals[$type] = $val;
		}

		foreach( $vals as $type => $val) {
			switch( $type ) {
				case 's' :
					$seconds = $val;
				break;
				case 'i' :
					$minutes = $val;
				break;
				case 'H':
					$hours = $val;
				break;
				case 'y':
					$year = '20'.$val; // Year 3k bug right here
				break;
				case 'Y':
					$year = $val;
				break;
				case 'm':
					$month = $val;
				break;
				case 'd':
					$day = $val;
				break;
			}
		}

		$unix_time = mktime(
			(int)$hours, (int)$minutes, (int)$seconds, 
			(int)$month, (int)$day, (int)$year);

		if( $return_unix)
			return $unix_time;

		return date( $to_mask, $unix_time );
	}
}


function pp_invoice_year_dropdown( $sel='' ) {
	$localDate=getdate();
	$minYear = $localDate["year"];
	$maxYear = $minYear + 15;

	$output =  "<option value=''>--</option>";
	for( $i=$minYear; $i<$maxYear; $i++) {
		$output .= "<option value='". substr( $i, 2, 2) ."'".( $sel==(substr( $i, 2, 2))?' selected':'' ).
		">". $i ."</option>";
	}
	return $output;
}


function pp_invoice_month_dropdown() {

	$months = array(
		"01" => __("Jan", 'prospress' ),
		"02" => __("Feb", 'prospress' ),
		"03" => __("Mar", 'prospress' ),
		"04" => __("Apr", 'prospress' ),
		"05" => __("May", 'prospress' ),
		"06" => __("Jun", 'prospress' ),
		"07" => __("Jul", 'prospress' ),
		"08" => __("Aug", 'prospress' ),
		"09" => __("Sep", 'prospress' ),
		"10" => __("Oct", 'prospress' ),
		"11" => __("Nov", 'prospress' ),
		"12" => __("Dec", 'prospress' )
	);

	$output =  "<option value=''>--</option>";
	foreach( $months as $key => $month )
		$output .=  "<option value='$key'>$month</option>";

	return $output;
}


function pp_invoice_country_array() {
	return array("US"=> "United States","AL"=> "Albania","DZ"=> "Algeria","AD"=> "Andorra","AO"=> "Angola","AI"=> "Anguilla","AG"=> "Antigua and Barbuda","AR"=> "Argentina","AM"=> "Armenia","AW"=> "Aruba","AU"=> "Australia","AT"=> "Austria","AZ"=> "Azerbaijan Republic","BS"=> "Bahamas","BH"=> "Bahrain","BB"=> "Barbados","BE"=> "Belgium","BZ"=> "Belize","BJ"=> "Benin","BM"=> "Bermuda","BT"=> "Bhutan","BO"=> "Bolivia","BA"=> "Bosnia and Herzegovina","BW"=> "Botswana","BR"=> "Brazil","VG"=> "British Virgin Islands","BN"=> "Brunei","BG"=> "Bulgaria","BF"=> "Burkina Faso","BI"=> "Burundi","KH"=> "Cambodia","CA"=> "Canada","CV"=> "Cape Verde","KY"=> "Cayman Islands","TD"=> "Chad","CL"=> "Chile","C2"=> "China","CO"=> "Colombia","KM"=> "Comoros","CK"=> "Cook Islands","CR"=> "Costa Rica","HR"=> "Croatia","CY"=> "Cyprus","CZ"=> "Czech Republic","CD"=> "Democratic Republic of the Congo","DK"=> "Denmark","DJ"=> "Djibouti","DM"=> "Dominica","DO"=> "Dominican Republic","EC"=> "Ecuador","SV"=> "El Salvador","ER"=> "Eritrea","EE"=> "Estonia","ET"=> "Ethiopia","FK"=> "Falkland Islands","FO"=> "Faroe Islands","FM"=> "Federated States of Micronesia","FJ"=> "Fiji","FI"=> "Finland","FR"=> "France","GF"=> "French Guiana","PF"=> "French Polynesia","GA"=> "Gabon Republic","GM"=> "Gambia","DE"=> "Germany","GI"=> "Gibraltar","GR"=> "Greece","GL"=> "Greenland","GD"=> "Grenada","GP"=> "Guadeloupe","GT"=> "Guatemala","GN"=> "Guinea","GW"=> "Guinea Bissau","GY"=> "Guyana","HN"=> "Honduras","HK"=> "Hong Kong","HU"=> "Hungary","IS"=> "Iceland","IN"=> "India","ID"=> "Indonesia","IE"=> "Ireland","IL"=> "Israel","IT"=> "Italy","JM"=> "Jamaica","JP"=> "Japan","JO"=> "Jordan","KZ"=> "Kazakhstan","KE"=> "Kenya","KI"=> "Kiribati","KW"=> "Kuwait","KG"=> "Kyrgyzstan","LA"=> "Laos","LV"=> "Latvia","LS"=> "Lesotho","LI"=> "Liechtenstein","LT"=> "Lithuania","LU"=> "Luxembourg","MG"=> "Madagascar","MW"=> "Malawi","MY"=> "Malaysia","MV"=> "Maldives","ML"=> "Mali","MT"=> "Malta","MH"=> "Marshall Islands","MQ"=> "Martinique","MR"=> "Mauritania","MU"=> "Mauritius","YT"=> "Mayotte","MX"=> "Mexico","MN"=> "Mongolia","MS"=> "Montserrat","MA"=> "Morocco","MZ"=> "Mozambique","NA"=> "Namibia","NR"=> "Nauru","NP"=> "Nepal","NL"=> "Netherlands","AN"=> "Netherlands Antilles","NC"=> "New Caledonia","NZ"=> "New Zealand","NI"=> "Nicaragua","NE"=> "Niger","NU"=> "Niue","NF"=> "Norfolk Island","NO"=> "Norway","OM"=> "Oman","PW"=> "Palau","PA"=> "Panama","PG"=> "Papua New Guinea","PE"=> "Peru","PH"=> "Philippines","PN"=> "Pitcairn Islands","PL"=> "Poland","PT"=> "Portugal","QA"=> "Qatar","CG"=> "Republic of the Congo","RE"=> "Reunion","RO"=> "Romania","RU"=> "Russia","RW"=> "Rwanda","VC"=> "Saint Vincent and the Grenadines","WS"=> "Samoa","SM"=> "San Marino","ST"=> "São Tomé and Príncipe","SA"=> "Saudi Arabia","SN"=> "Senegal","SC"=> "Seychelles","SL"=> "Sierra Leone","SG"=> "Singapore","SK"=> "Slovakia","SI"=> "Slovenia","SB"=> "Solomon Islands","SO"=> "Somalia","ZA"=> "South Africa","KR"=> "South Korea","ES"=> "Spain","LK"=> "Sri Lanka","SH"=> "St. Helena","KN"=> "St. Kitts and Nevis","LC"=> "St. Lucia","PM"=> "St. Pierre and Miquelon","SR"=> "Suriname","SJ"=> "Svalbard and Jan Mayen Islands","SZ"=> "Swaziland","SE"=> "Sweden","CH"=> "Switzerland","TW"=> "Taiwan","TJ"=> "Tajikistan","TZ"=> "Tanzania","TH"=> "Thailand","TG"=> "Togo","TO"=> "Tonga","TT"=> "Trinidad and Tobago","TN"=> "Tunisia","TR"=> "Turkey","TM"=> "Turkmenistan","TC"=> "Turks and Caicos Islands","TV"=> "Tuvalu","UG"=> "Uganda","UA"=> "Ukraine","AE"=> "United Arab Emirates","GB"=> "United Kingdom","UY"=> "Uruguay","VU"=> "Vanuatu","VA"=> "Vatican City State","VE"=> "Venezuela","VN"=> "Vietnam","WF"=> "Wallis and Futuna Islands","YE"=> "Yemen","ZM"=> "Zambia" );
}


function pp_invoice_process_cc_ajax() {

	$nonce = $_REQUEST['pp_invoice_process_cc'];
	$invoice_id = $_REQUEST['invoice_id'];

 	if (! wp_verify_nonce( $nonce, 'pp_invoice_process_cc_' . $invoice_id ) ) die('Security check' ); 

	pp_invoice_process_cc_transaction();
}


function pp_invoice_process_cc_transaction( $cc_data = false ) {

	$errors = array();
	$errors_msg = null;
	$_POST['processing_problem'] = '';
	unset( $stop_transaction );
	$invoice_id = preg_replace("/[^0-9]/","", $_POST['invoice_id']);

	$invoice_class = new pp_invoice_get( $invoice_id );
	$invoice_class = $invoice_class->data;

	$wp_users_id = $_POST[ 'user_id' ];

	if( empty( $_POST['first_name'] ) ) {
		$errors[ 'first_name' ][] = "Please enter your first name.";
		$stop_transaction = true;
	}

	if( empty( $_POST['last_name' ] ) ) { 
		$errors[ 'last_name' ][] = "Please enter your last name. ";
		$stop_transaction = true;
	}

	if( empty( $_POST['email_address' ] ) ) { 
		$errors[ 'email_address' ][] = "Please provide an email address.";
		$stop_transaction = true;
	}

	if( empty( $_POST['phonenumber' ] ) ) { 
		$errors[ 'phonenumber' ][] = "Please enter your phone number.";
		$stop_transaction = true;
	}

	if( empty( $_POST['address' ] ) ) { 
		$errors[ 'address' ][] = "Please enter your address.";
		$stop_transaction = true;
	}

	if( empty( $_POST['city' ] ) ) { 
		$errors[ 'city' ][] = "Please enter your city.";
		$stop_transaction = true;
	}

	if( empty( $_POST['state' ] ) ) { 
		$errors[ 'state' ][] = "Please select your state.";
		$stop_transaction = true;
	}

	if( empty( $_POST['zip' ] ) ) { 
		$errors[ 'zip' ][] = "Please enter your ZIP code.";
		$stop_transaction = true;
	}

	if( empty( $_POST['country' ] ) ) { 
		$errors[ 'country' ][] = "Please enter your country.";
		$stop_transaction = true;
	}

	if( empty( $_POST['card_num'])) {
		$errors[ 'card_num' ][]  = "Please enter your credit card number.";	
		$stop_transaction = true;
	} elseif( !pp_invoice_validate_cc_number( $_POST['card_num' ] ) ) { 
		$errors[ 'card_num' ][] = "Please enter a valid credit card number."; 
		$stop_transaction = true; 
	}

	if( empty( $_POST['exp_month' ] ) ) { 
		$errors[ 'exp_month' ][] = "Please enter your credit card's expiration month.";
		$stop_transaction = true;
	}

	if( empty( $_POST['exp_year' ] ) ) { 
		$errors[ 'exp_year' ][] = "Please enter your credit card's expiration year.";
		$stop_transaction = true;
	}

	if( empty( $_POST['card_code' ] ) ) { 
		$errors[ 'card_code' ][] = "The <b>Security Code</b> is the code on the back of your card.";
		$stop_transaction = true;
	}

	// Charge Card
	if( !$stop_transaction ) {

		require_once('gateways/authnet.class.php' );
		require_once('gateways/authnetARB.class.php' );

		$payment = new PP_Invoice_Authnet( $invoice_class->payee_id );
		$payment->transaction( $_POST['card_num']);

		// Billing Info
		$payment->setParameter("x_card_code", $_POST['card_code']);
		$payment->setParameter("x_exp_date ", $_POST['exp_month'] . $_POST['exp_year']);
		$payment->setParameter("x_amount", $invoice_class->amount);

		// Order Info
		$payment->setParameter("x_description", $invoice_class->post_title );
		$payment->setParameter("x_invoice_num",  $invoice_id );
		$payment->setParameter("x_test_request", false );
		$payment->setParameter("x_duplicate_window", 30);

		//Customer Info
		$payment->setParameter("x_first_name", $_POST['first_name']);
		$payment->setParameter("x_last_name", $_POST['last_name']);
		$payment->setParameter("x_address", $_POST['address']);
		$payment->setParameter("x_city", $_POST['city']);
		$payment->setParameter("x_state", $_POST['state']);
		$payment->setParameter("x_country", $_POST['country']);
		$payment->setParameter("x_zip", $_POST['zip']);
		$payment->setParameter("x_phone", $_POST['phonenumber']);
		$payment->setParameter("x_email", $_POST['email_address']);
		$payment->setParameter("x_cust_id", "WP User - " . $invoice_class->payer_class->user_nicename );
		$payment->setParameter("x_customer_ip ", $_SERVER['REMOTE_ADDR']);

		$payment->process(); 

		if( $payment->isApproved() ) {

			// Returning valid nonce marks transaction as good on front-end
			echo wp_create_nonce('pp_invoice_process_cc_' . $invoice_id );

			update_user_meta( $wp_users_id,'last_name',$_POST['last_name']);
			update_user_meta( $wp_users_id,'last_name',$_POST['last_name']);
			update_user_meta( $wp_users_id,'first_name',$_POST['first_name']);
			update_user_meta( $wp_users_id,'city',$_POST['city']);
			update_user_meta( $wp_users_id,'state',$_POST['state']);
			update_user_meta( $wp_users_id,'zip',$_POST['zip']);
			update_user_meta( $wp_users_id,'streetaddress',$_POST['address']);
			update_user_meta( $wp_users_id,'phonenumber',$_POST['phonenumber']);
			update_user_meta( $wp_users_id,'country',$_POST['country']);

			//Mark invoice as paid
			pp_invoice_paid( $invoice_id );

			if( get_option('pp_invoice_send_thank_you_email' ) == 'yes' ) {
				pp_invoice_send_email_receipt( $invoice_id );
			}

		 } else {
			if( $payment->getResponseText() ) {
				$errors['processing_problem'][] .= $payment->getResponseText();
			} elseif ( $payment->getErrorMessage() ){
				foreach( preg_split( "/\n|(?<=\.)\s/", $payment->getErrorMessage() ) as $msg )
					$errors['processing_problem'][] .= $msg;
			} else {
				$errors['processing_problem'][] .= 'Processing Error. Please check your gateway settings.';
			}
			$stop_transaction = true;
		}
	}

	if( $stop_transaction && is_array( $_POST ) ) {
		foreach ( $_POST as $key => $value ) {
			if ( array_key_exists ( $key, $errors ) ) {
				foreach ( $errors [ $key ] as $k => $v ) {
					$errors_msg .= "error|$key|$v\n";
				}
			} else {
				$errors_msg .= "ok|$key\n";
			}
		}
		echo $errors_msg;
	}

	die();
}


function pp_invoice_md5_to_invoice( $md5) {
	global $wpdb, $_pp_invoice_md5_to_invoice_cache;
	if (isset( $_pp_invoice_md5_to_invoice_cache[$md5]) && $_pp_invoice_md5_to_invoice_cache[$md5]) {
		return $_pp_invoice_md5_to_invoice_cache[$md5];
	}

	$md5_escaped = mysql_escape_string( $md5);
	$all_invoices = $wpdb->get_col("SELECT id FROM ".$wpdb->payments." WHERE MD5(id ) = '{$md5_escaped}'" );
	foreach ( $all_invoices as $value ) {
		if(md5( $value ) == $md5) {
			$_pp_invoice_md5_to_invoice_cache[$md5] = $value;
			return $_pp_invoice_md5_to_invoice_cache[$md5];
		}
	}
}


function pp_invoice_user_accepted_payments( $payee_id ) {

	if( pp_invoice_user_settings( 'paypal_allow', $payee_id ) == 'true' )
		$return[ 'paypal_allow' ] = true;

	if( pp_invoice_user_settings( 'cc_allow', $payee_id ) == 'true' )
		$return[ 'cc_allow' ] = true;

	if( pp_invoice_user_settings( 'draft_allow', $payee_id ) == 'true' )
		$return[ 'draft_allow' ] = true;

	return $return;
}


function pp_invoice_accepted_payment( $invoice_id = 'global' ) {

	if( empty( $invoice_id ) )
		$invoice_id = "global";

 	if( $invoice_id == 'global' ) {

		if(get_option('pp_invoice_paypal_allow' ) == 'yes' ) { 
			$payment_array['paypal']['name'] = 'paypal'; 
			$payment_array['paypal']['active'] = true; 
			$payment_array['paypal']['nicename'] = "PayPal"; 
			if(get_option('pp_invoice_payment_method' ) == 'paypal' || get_option('pp_invoice_payment_method' ) == 'PayPal' ) $payment_array['paypal']['default'] = true; 
		}

		if(get_option('pp_invoice_cc_allow' ) == 'yes' ) { 
			$payment_array['cc']['name'] = 'cc'; 
			$payment_array['cc']['active'] = true; 
			$payment_array['cc']['nicename'] = "Credit Card"; 
			if(get_option('pp_invoice_payment_method' ) == 'cc' || get_option('pp_invoice_payment_method' ) == 'Credit Card' ) $payment_array['cc']['default'] = true; 
		}

		return $payment_array;
	} else {

		$invoice_info = new PP_Invoice_GetInfo( $invoice_id );
		$payment_array = array();
		if( $invoice_info->display('pp_invoice_payment_method' ) != '' ) { $custom_default_payment = true; } else { $custom_default_payment = false; }

		if( $invoice_info->display('pp_invoice_paypal_allow' ) == 'yes' ) {
			$payment_array['paypal']['name'] = 'paypal'; 
			$payment_array['paypal']['active'] = true; 
			$payment_array['paypal']['nicename'] = "PayPal"; 

			if( $custom_default_payment && $invoice_info->display('pp_invoice_payment_method' ) == 'paypal' || $invoice_info->display('pp_invoice_payment_method' ) == 'PayPal' ) $payment_array['paypal']['default'] = true; 
			if(!$custom_default_payment &&  empty( $payment_array['paypal']['default']) && get_option('pp_invoice_payment_method' ) == 'paypal' ) { $payment_array['paypal']['default'] = true;}

		}

		if( $invoice_info->display('pp_invoice_cc_allow' ) == 'yes' ) { 
			$payment_array['cc']['name'] = 'cc'; 
			$payment_array['cc']['active'] = true; 
			$payment_array['cc']['nicename'] = "Credit Card"; 
			if( $custom_default_payment && $invoice_info->display('pp_invoice_payment_method' ) == 'cc' || $invoice_info->display('pp_invoice_payment_method' ) == 'Credit Card' ) $payment_array['cc']['default'] = true; 
			if(!$custom_default_payment && empty( $payment_array['cc']['default']) && get_option('pp_invoice_payment_method' ) == 'cc' ) $payment_array['cc']['default'] = true; 

		}

		return $payment_array;
	}
}

function pp_invoice_email_variables( $invoice_id ) {
	global $pp_invoice_email_variables, $user_ID;

	$invoice_class = new pp_invoice_get( $invoice_id );
	$invoice_info = $invoice_class->data;

	$pp_invoice_email_variables = array(
		'business_name' => $invoice_info->payee_class->display_name,
		'recipient' => $invoice_info->payer_class->user_nicename,
  		'amount' => $invoice_info->display_amount,
 		'link' => $invoice_info->pay_link,
 		'business_email' => $invoice_info->payee_class->user_email,
		'subject' => $invoice_info->post_title,
		'description' => $invoice_info->post_content
	);

	return $pp_invoice_email_variables;
}


function pp_invoice_email_apply_variables( $matches) {
	global $pp_invoice_email_variables;

	if (isset( $pp_invoice_email_variables[$matches[2]])) {
		return $pp_invoice_email_variables[$matches[2]];
	}
	return $matches[2];
}


function pp_invoice_add_email_template_content() {

// Send invoice
		add_option('pp_invoice_email_send_invoice_subject','%subject%' );
		add_option('pp_invoice_email_send_invoice_content',
"Dear %recipient%, 

%business_name% has sent you an invoice in the amount of %amount% for:

%subject%

%description%

You may pay, view and print the invoice online by visiting the following link: 
%link%

Best regards,
%business_name% ( %business_email% )" );

		// Send reminder
		add_option('pp_invoice_email_send_reminder_subject','[Reminder] %subject%' );
		add_option('pp_invoice_email_send_reminder_content',
"Dear %recipient%, 

%business_name% has sent you a reminder for the invoice in the amount of %amount% for:

%subject%

%description%

You may pay, view and print the invoice online by visiting the following link: 
%link%.

Best regards,
%business_name% ( %business_email% )" );

		// Send receipt
		add_option('pp_invoice_email_send_receipt_subject','Receipt for %subject%' );
		add_option('pp_invoice_email_send_receipt_content',
"Dear %recipient%, 

%business_name% has received your payment for the invoice in the amount of %amount% for:

%subject%.

Thank you very much for your payment.

Best regards,
%business_name% ( %business_email% )" );

}	

