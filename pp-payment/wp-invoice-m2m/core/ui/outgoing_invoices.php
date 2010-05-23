<div class="wrap">
	<script>
	 pagenow = 'toplevel_page_outgoing_invoices';
	</script>
	<form id="invoices-filter" action="" method="post" >
	<h2><?php _e('Invoices to Send', WP_INVOICE_TRANS_DOMAIN); ?></h2>
	
	<?php if($message): ?>
	<div class="updated fade">
		<p><?php echo $message; ?></p>
	</div>
	<?php endif; ?>

	<div class="tablenav clearfix">
	
	<div class="alignleft">
	<select id="wp_invoice_action" name="wp_invoice_action">
		<option value="-1" selected="selected"><?php _e('-- Actions --', WP_INVOICE_TRANS_DOMAIN); ?></option>
		<option value="archive_invoice" name="archive" ><?php _e('Archive Invoice(s)', WP_INVOICE_TRANS_DOMAIN); ?></option>
		<option value="unrachive_invoice" name="unarchive" ><?php _e('Un-Archive Invoice(s)', WP_INVOICE_TRANS_DOMAIN); ?></option>
		<option value="mark_as_sent" name="mark_as_sent" ><?php _e('Mark as Sent', WP_INVOICE_TRANS_DOMAIN); ?></option>
		<option value="mark_as_paid" name="mark_as_paid" ><?php _e('Mark as Paid', WP_INVOICE_TRANS_DOMAIN); ?></option>
		<option value="mark_as_unpaid" name="mark_as_unpaid" ><?php _e('Unset Paid Status', WP_INVOICE_TRANS_DOMAIN); ?></option>
		
		<?php /*
		<option value="send_invoice" name="sendit" ><?php _e('Send Invoice(s)', WP_INVOICE_TRANS_DOMAIN); ?></option>
		<option value="send_reminder" name="sendit" ><?php _e('Send Reminder(s)', WP_INVOICE_TRANS_DOMAIN); ?></option>
		<option  value="delete_invoice" name="deleteit" ><?php _e('Delete', WP_INVOICE_TRANS_DOMAIN); ?></option>
		*/ ?>
	</select>
	<input type="submit" value="Apply" id="submit_bulk_action" class="button-secondary action" />
	</div>

	<div class="alignright">
		<ul class="subsubsub" style="margin:0;">
		<li><?php _e('Filter:', WP_INVOICE_TRANS_DOMAIN); ?></li>
		<li><a href='#' class="" id="">All Invoices</a> |</li>
		<li><a href='#'  class="paid" id="">Paid</a> |</li>
		<li><a href='#'  class="sent" id="">Unpaid</a> |</li>
		<li><?php _e('Custom: ', WP_INVOICE_TRANS_DOMAIN); ?><input type="text" id="FilterTextBox" class="search-input" name="FilterTextBox" /> </li>
		</ul>
	</div>
	</div>
	<br class="clear" />
	
	<table class="widefat fixed" cellspacing="0"  id="invoice_sorter_table">
	<thead>
	<tr class="thead">
	<?php print_column_headers('toplevel_page_outgoing_invoices') ?>
	</tr>
	</thead>

	<tfoot>
	<tr class="thead">
	<?php print_column_headers('toplevel_page_outgoing_invoices', false) ?>
	</tr>
	</tfoot>

	<tbody id="invoices" class="list:invoices invoice-list">
	<?php
	$style = '';
	$x_counter = 0;
	foreach ($outgoing_invoices as $invoice_id) {			
		$style = ( ' class="alternate"' == $style ) ? '' : ' class="alternate"';
		$invoice_class = new wp_invoice_get($invoice_id);
		echo "\n\t" . wp_invoice_invoice_row($invoice_class->data, 'outgoing');
		$x_counter++;
	}
	
	if($x_counter == 0) { ?>
	<tr><td colspan="00" align="center"><div style="padding: 20px;"><?php _e('You don\'t have any invoices to send out.', WP_INVOICE_TRANS_DOMAIN); ?></div></td></tr>
	<?php }	?>
	
	</tbody>
	</table>

	<a href="" id="wp_invoice_show_archived">Show / Hide Archived</a>
	</form> 
	<div class="wp_invoice_stats">Total of Displayed Invoices: <span id="wp_invoice_total_owed"></span></div>

</div>