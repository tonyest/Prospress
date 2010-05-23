<dl class="payee_details clearfix">
	<dt>Email</dt>
	<dd><?php echo $invoice->payer_class->user_email; ?></dd>

	<dt>Username</dt>
	<dd><?php echo $invoice->payer_class->user_nicename; ?></dd>

	<dt>First Name</dt>
	<dd><?php echo $invoice->payer_class->first_name; ?></dd>

	<dt>Last Name</dt>
	<dd><?php echo $invoice->payer_class->last_name; ?></dd>

	<dt>Description</dt>
	<dd><?php echo $invoice->payer_class->description; ?></dd>
</dl>