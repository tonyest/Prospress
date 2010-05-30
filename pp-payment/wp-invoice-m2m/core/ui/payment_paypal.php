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
		<br class="cb" />	
		</ol>
	</fieldset>
	<div id="major-publishing-actions">
		<img alt="" style="display: none;" id="ajax-loading" src="<?php echo admin_url('images/wpspin_light.gif');?>">
		<input type="submit" value="Process PayPal Payment" accesskey="p" id="process_payment" class="button-primary" name="process_payment">
	</div>
</form>