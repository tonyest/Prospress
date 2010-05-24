<table class="form-table" id="wp_invoice_main_info">

	<tr class="invoice_main">
		<th><?php _e("Post Title", 'prospress') ?></th>
		<td><?php echo $invoice->post_title; ?></td>
	</tr>
	<tr class="invoice_main">
		<th><?php _e("Post Content", 'prospress') ?></th>
		<td><?php echo $invoice->post_content; ?></td>	
	</tr>
	<tr class="invoice_main">
		<th><?php _e("Total Amount", 'prospress') ?></th>
		<td><?php echo $invoice->display_amount; ?></td>	
	</tr>
	
</table>
 