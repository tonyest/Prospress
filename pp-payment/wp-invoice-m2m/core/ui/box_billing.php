 <?php
// count how many payment options we have availble
// Create payment array
$payment_array = wp_invoice_user_accepted_payments($invoice->payee_class->ID);

//wpi_qc($payment_array, '$payment_array');
//wpi_qc($invoice);
 ?>

<script type="text/javascript">
//<![CDATA[
	function changePaymentOption(){
		var dropdown = document.getElementById("wp_invoice_select_payment_method_selector");
		var index = dropdown.selectedIndex;
		var ddVal = dropdown.options[index].value;
		var ddText = dropdown.options[index].text;

		if(ddVal == 'PayPal') {
			jQuery(".payment_info").hide();
			jQuery(".paypal_ui").show();
		}		

		if(ddVal == 'Credit Card') {
			jQuery(".payment_info").hide();
			jQuery(".cc_ui").show();
		}
		
		if(ddVal == 'Bank Draft') {
			jQuery(".payment_info").hide();
 			jQuery(".draft_ui").show();
		}
	}

//]]>
</script>		

 <style>
.payment_info {display: none;}

 	.<?php echo wp_invoice_user_settings('default_payment_venue', $invoice->payee_class->ID); ?>_ui {display: block; } 
 
</style>

<?php
//show dropdown if it is allowed, and there is more than one payment option
if(wp_invoice_user_settings('can_change_payment_method', $invoice->payee_class->ID) && count($payment_array) > 1) { ?>

<fieldset id="wp_invoice_select_payment_method">
	<ol>
		<li>
			<label for="first_name">Select Payment Method </label>
			<select id="wp_invoice_select_payment_method_selector" onChange="changePaymentOption()">
				<?php foreach ($payment_array as $payment_name => $allowed) { 
					$name =  str_replace('_allow', '', $payment_name); ?>
					<option name="<?php echo $name; ?>" <?php if(wp_invoice_user_settings('default_payment_venue', $invoice->payee_class->ID) == $name) { echo "SELECTED"; } ?>><?php echo wp_invoice_payment_nicename($name); ?></option>
				<?php } ?>
			</select>
		</li>
	</ol>
</fieldset>
<?php } ?>

<?php // Include payment-specific UI files
if( is_array( $payment_array ) ) {
	foreach ( $payment_array as $payment_name => $allowed ) { 
		$name =  str_replace( '_allow', '', $payment_name );?>
	 	<div class="<?php echo $name; ?>_ui payment_info"><?php include WP_INVOICE_UI_PATH . "payment_{$name}.php"; ?></div>
 	<?php }
} else { ?>
	The payee has not set up any billing options yet.  You cannot make a payment until this is done.  Contact the payee to resolve this.
<?php } ?>
