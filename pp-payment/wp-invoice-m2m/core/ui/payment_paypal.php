 <form action="https://www<?php if($invoice->payee_class->wp_invoice_settings[paypal_sandbox] == 'true') echo ".sandbox"; ?>.paypal.com/cgi-bin/webscr" method="post" class="clearfix">
	<input type="hidden" name="cmd" value="_xclick">
 	<input type="hidden" name="business" value="<?php echo $invoice->payee_class->wp_invoice_settings[paypal_address]; ?>">
	<input type="hidden" name="item_name" value="<?php echo $invoice->post_title; ?>">	
     <input type="hidden" name="no_note" value="1">
	<input type="hidden" name="currency_code" value="<?php echo $invoice->currency_code; ?>">

 	<input type="hidden" name="no_shipping" value="1">
	<input type="hidden" name="upload" value="1">
	<input type="hidden" name="return"  value="<?php echo $invoice->pay_link; ?>&return_info=success">
	<input type="hidden" name="cancel_return"  value="<?php echo $invoice->pay_link; ?>&return_info=cancel">
	<input type="hidden" name="notify_url"  value="<?php echo $invoice->pay_link; ?>">
	
	<?php if($invoice->tax_total == 0) : ?>
	<input type="hidden" name="tax"  value="<?php echo $invoice->tax_total; ?>">
	<?php endif; ?>
	<input type="hidden" name="rm" value="2">
	<input type="hidden" name="amount"  value="<?php echo $invoice->amount; ?>">
	<input type="hidden" name="cbt"  value="Mark Invoice as Paid">
	<input  type="hidden" name="invoice" id="id"  value="<?php echo  $invoice->id; ?>">
 
	<fieldset id="paypal_information">
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

	<?php
	/*
	
		list($day_phone_a, $day_phone_b, $day_phone_c) = split('[/.-]', $invoice->payer_class->phonenumber);
		?>
		<li>
		<label for="day_phone_a"><?php _e('Phone Number', 'prospress'); ?></label>
		<?php echo wp_invoice_draw_inputfield("night_phone_a",$day_phone_a,' style="width:25px;" size="3" maxlength="3" '); ?>
		<?php echo wp_invoice_draw_inputfield("night_phone_b",$day_phone_b,' style="width:25px;" size="3" maxlength="3" '); ?>
		<?php echo wp_invoice_draw_inputfield("night_phone_c",$day_phone_c,' style="width:35px;" size="4" maxlength="4" '); ?>
		</li>

		<li>
		<label for="address"><?php _e('Address', 'prospress'); ?></label>
		<?php echo wp_invoice_draw_inputfield("address1",$invoice->payer_class->streetaddress); ?>
		</li>

		<li>
		<label for="city"><?php _e('City', 'prospress'); ?></label>
		<?php echo wp_invoice_draw_inputfield("city",$invoice->payer_class->city); ?>
		</li>

		<?php if(get_option('wp_invoice_fe_state_selection') != 'Hide') { ?>
		<li id="state_field">
		<label for="state"><?php _e('State', 'prospress'); ?></label>
	<?php if(get_option('wp_invoice_fe_state_selection') == 'Dropdown') { ?>
		<?php print wp_invoice_draw_select('state',wp_invoice_state_array(),$invoice->payer_class->state);  ?>
	<?php } ?>
	<?php if(get_option('wp_invoice_fe_state_selection') == 'Input_Field') { ?>
		<?php echo wp_invoice_draw_inputfield("state",$invoice->payer_class->state); ?>
	<?php } ?>
		</li>
		<?php } ?>

		<li>
		<label for="zip"><?php _e('Zip Code', 'prospress'); ?></label>
		<?php echo wp_invoice_draw_inputfield("zip",$invoice->payer_class->zip); ?>
		</li>

		<li>
		<label for="country"><?php _e('Country', 'prospress'); ?></label>
		<?php echo wp_invoice_draw_select('country',wp_invoice_country_array(),$invoice->payer_class->country); ?>
		</li>
		*/ ?>

		<br class="cb" />	
		</ol>
	</fieldset>
	<div id="major-publishing-actions">
		<img alt="" style="display: none;" id="ajax-loading" src="<?php echo admin_url('images/wpspin_light.gif');?>">
		<input type="submit" value="Process PayPal Payment" accesskey="p" id="process_payment" class="button-primary" name="process_payment">
	</div>
</form>