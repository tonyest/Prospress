<form id='wp_invoice_draft_form' action="#" method='POST' >

	<input type="hidden" name="action" value="wp_invoice_process_draft">
	<input type="hidden" name="user_id" value="<?php echo $invoice->payer_class->ID; ?>">
	<input type="hidden" name="invoice_id" value="<?php echo $invoice->id; ?>">
	
 	<?php wp_nonce_field( 'wp_invoice_process_cc_' . $invoice->id, 'wp_invoice_process_cc' , false ); ?>

 	<fieldset id="credit_card_information" class="clearfix">
		<ol>
		<li>
			<p style="padding-left: 220px; font-size: 1.2em;"><?php echo wp_invoice_user_settings('draft_text', $invoice->payee_class->ID); ?></p>
		</li>
		
		<li class="clearfix" style="height: 100px;">
		<label class="inputLabel" for="card_code">&nbsp;</label>
		<textarea id="draft_message"  name="draft_message" style="width: 300px; height: 100px;" /></textarea>
		</li>
		
		</ol>
	</fieldset>
 	<div id="major-publishing-actions">
		<input type="submit" value="Pay by Draft" accesskey="p" id="process_draft_payment" class="button-primary" name="process_draft_payment">
 	</div>
</form>