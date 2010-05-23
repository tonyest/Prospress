<?php

function wp_invoice_metabox_submit_payment($invoice) {
	?>
	<div id="misc-publishing-actions">
		<div class="misc-pub-section">
			<label for="post_status">Status:</label>
			Unpaid
		</div>
		
		<div class="price_information">
		Total Due: <?php echo $invoice->currency_symbol; ?><?php echo wp_invoice_currency_format($invoice->amount); ?>
		</div>
		
	</div>

<?php
}

function wp_invoice_metabox_invoice_details($invoice) {
	
	/*echo "<pre>";
	print_r($invoice);
	echo "</pre>";
	*/
	
	include WP_INVOICE_UI_PATH . 'box_invoice_details.php';

}

function wp_invoice_metabox_billing_details($invoice) {
	include WP_INVOICE_UI_PATH . 'box_billing.php';
}

function wp_invoice_metabox_payee_details($invoice) {
	
	/*
	echo "<pre>";
	print_r($invoice->payee_class);
	echo "</pre>";
	*/
	
	include WP_INVOICE_UI_PATH . 'box_payee_details.php';
}

?>