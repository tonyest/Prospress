	<script type="text/javascript">
		//<![CDATA[
		jQuery(document).ready(function(){

			jQuery("#wp_invoice_payment_form").submit(function() {

				// Prevent doubleclick
				jQuery(':submit', this).click(function() {  
					// return false;  
				});  

				process_cc_checkout();
	 
				return false;
			});
		});

	function cc_card_pick(){
	
		numLength = jQuery('#card_num').val().length;
		number = jQuery('#card_num').val();
		if(numLength > 10)
		{
			if((number.charAt(0) == '4') && ((numLength == 13)||(numLength==16))) { jQuery('#cardimage').removeClass(); jQuery('#cardimage').addClass('visa_card'); }
			else if((number.charAt(0) == '5' && ((number.charAt(1) >= '1') && (number.charAt(1) <= '5'))) && (numLength==16)) { jQuery('#cardimage').removeClass(); jQuery('#cardimage').addClass('mastercard'); }
			else if(number.substring(0,4) == "6011" && (numLength==16)) 	{ jQuery('#cardimage').removeClass(); jQuery('#cardimage').addClass('amex'); }
			else if((number.charAt(0) == '3' && ((number.charAt(1) == '4') || (number.charAt(1) == '7'))) && (numLength==15)) { jQuery('#cardimage').removeClass(); jQuery('#cardimage').addClass('discover_card'); }
			else { jQuery('#cardimage').removeClass(); jQuery('#cardimage').addClass('nocard'); }

		}
	}

	function process_cc_checkout(){

		jQuery("#ajax-loading").show();
		jQuery("#credit_card_information input").removeClass('cc_error');
		jQuery("#credit_card_information select").removeClass('cc_error');
		jQuery("#wp_cc_response ol li").remove();

		jQuery.post ( ajaxurl, jQuery('#wp_invoice_payment_form').serialize(), function(html){

			if(html == '<?php echo wp_create_nonce('wp_invoice_process_cc_' . $invoice->id); ?>') {
				
				alert("transaction succesfful");
				//window.location = "<?php echo admin_url("admin.php?page=incoming_invoices&message=Invoice $invoice_id Paid"); ?>";
				return;
			}

			// Error occured
			var explode = html.toString().split('\n');
 			
			// Remove all errors
			jQuery(".wp_invoice_error_wrapper div").remove();

			for ( var i in explode ) {
				var explode_again = explode[i].toString().split('|');
				if (explode_again[0]=='error'){ 
 					
					var id = explode_again[1];
					var description = explode_again[2];
					var parent = jQuery("#" + id).parent();
					//jQuery(parent).css('border', '1px solid red');
					jQuery("#" + id).addClass('cc_error');
					
					jQuery("#wp_cc_response").show();
					jQuery("#wp_cc_response ol").append('<li>' + description + '</li>');
				}
				else if (explode_again[0]=='ok') {
				}
			}
	 
 		});
	}

	//]]>
	</script>

	<form id='wp_invoice_payment_form' action="#" method='POST' >

	<input type="hidden" name="action" value="wp_invoice_process_cc_ajax">
	<input type="hidden" name="user_id" value="<?php echo $invoice->payer_class->ID; ?>">
	<input type="hidden" name="invoice_id" value="<?php echo $invoice->id; ?>">
	<input type="hidden" name="amount" id="total_amount" value="<?php echo $invoice->amount; ?>" />
	<?php 
	wp_nonce_field( 'wp_invoice_process_cc_' . $invoice->id, 'wp_invoice_process_cc' , false );
	?>
	
	<input type="hidden" name="amount" value="<?php echo $invoice->amount; ?>">
 	<input type="hidden" name="email_address" value="<?php echo $invoice->payee_class->user_email; ?>">
	<input type="hidden" name="id" value="<?php echo  $invoice->id; ?>">
	<input type="hidden" name="currency_code" id="currency_code"  value="<?php echo $invoice->currency_code; ?>">
 	<fieldset id="credit_card_information" class="clearfix">
		<ol>
	<li>
		<label for="first_name"><?php _e('First Name', 'prospress'); ?></label>
		<?php echo wp_invoice_draw_inputfield("first_name",$invoice->payer_class->first_name); ?>
		</li>

		<li>
		<label for="last_name"><?php _e('Last Name', 'prospress'); ?></label>
		<?php echo wp_invoice_draw_inputfield("last_name",$invoice->payer_class->last_name); ?>
		</li>

		<li>
		<label for="email"><?php _e('Email Address', 'prospress'); ?></label>
		<?php echo wp_invoice_draw_inputfield("email_address",$invoice->payer_class->user_email); ?>
		</li>

		<li>
		<label class="inputLabel" for="phonenumber"><?php _e('Phone Number', 'prospress'); ?></label>
		<input name="phonenumber" class="input_field"  type="text" id="phonenumber" size="40" maxlength="50" value="<?php print $invoice->payer_class->phonenumber; ?>" />
		</li>

		<li>
		<label for="address"><?php _e('Address', 'prospress'); ?></label>
		<?php echo wp_invoice_draw_inputfield("address",$invoice->payer_class->streetaddress); ?>
		</li>

		<li>
		<label for="city"><?php _e('City', 'prospress'); ?></label>
		<?php echo wp_invoice_draw_inputfield("city",$invoice->payer_class->city); ?>
		</li>

		<li id="state_field">
		<label for="state"><?php _e('State', 'prospress'); ?></label>
		<?php echo wp_invoice_draw_inputfield("state",$invoice->payer_class->state); ?>
		</li>

		<li>
		<label for="zip"><?php _e('Zip Code', 'prospress'); ?></label>
		<?php echo wp_invoice_draw_inputfield("zip",$invoice->payer_class->zip); ?>
		</li>

		<li>
		<label for="country"><?php _e('Country', 'prospress'); ?></label>
		<?php echo wp_invoice_draw_select('country',wp_invoice_country_array(),$invoice->payer_class->country); ?>
		</li>

		<li class="hide_after_success">
		<label class="inputLabel" for="card_num"><?php _e('Credit Card Number', 'prospress'); ?></label>
		<input name="card_num" autocomplete="off" onkeyup="cc_card_pick();"  id="card_num" class="credit_card_number input_field"  type="text"  size="22"  maxlength="22" />
		</li>

		<li class="hide_after_success nocard"  id="cardimage" style=" background: url(<?php echo WP_Invoice::frontend_path(); ?>/core/images/card_array.png) no-repeat;">
		</li>

		<li class="hide_after_success">
		<label class="inputLabel" for="exp_month"><?php _e('Expiration Date', 'prospress'); ?></label>
		<?php _e('Month', 'prospress'); ?> <?php echo wp_invoice_draw_select('exp_month',wp_invoice_month_array()); ?>
		<?php _e('Year', 'prospress'); ?> <select name="exp_year" id="exp_year"><?php print wp_invoice_printYearDropdown(); ?></select>
		</li>

		<li class="hide_after_success">
		<label class="inputLabel" for="card_code"><?php _e('Security Code', 'prospress'); ?></label>
		<input id="card_code" autocomplete="off"  name="card_code" class="input_field"  style="width: 70px;" type="text" size="4" maxlength="4" />
		</li>
		
		</ol>
	</fieldset>
&nbsp;<div id="wp_cc_response"><ol></ol></div>
	<div id="major-publishing-actions">
		<input type="submit" value="Process Credit Card Payment" accesskey="p" id="process_payment" class="button-primary" name="process_payment">
		<img alt="" class="hidden" id="ajax-loading" src="<?php echo admin_url('images/wpspin_light.gif');?>">
	</div>
</form>