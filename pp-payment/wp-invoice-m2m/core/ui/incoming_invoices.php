<div class="wrap">
	<script>
	 pagenow = 'web-invoice_page_incoming_invoices';
	</script>
	<form id="invoices-filter" action="" method="post" >
	<h2><?php _e('Invoices to Pay', WP_INVOICE_TRANS_DOMAIN); ?></h2>
	<div class="tablenav clearfix">
	
	<div class="alignleft">
	<select id="wp_invoice_action" name="wp_invoice_action">
		<option value="-1" selected="selected"><?php _e('-- Actions --', WP_INVOICE_TRANS_DOMAIN); ?></option>
		<option value="archive_invoice" name="archive" ><?php _e('Archive Invoice(s)', WP_INVOICE_TRANS_DOMAIN); ?></option>
		<option value="unrachive_invoice" name="unarchive" ><?php _e('Un-Archive Invoice(s)', WP_INVOICE_TRANS_DOMAIN); ?></option>
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
	<?php print_column_headers('web-invoice_page_incoming_invoices') ?>
	</tr>
	</thead>

	<tfoot>
	<tr class="thead">
	<?php print_column_headers('web-invoice_page_incoming_invoices', false) ?>
	</tr>
	</tfoot>

	<tbody id="invoices" class="list:invoices invoice-list">
	<?php
	$style = '';
	if( !empty( $incoming_invoices ) ){
		foreach ($incoming_invoices as $invoice_id) {			
			$style = ( ' class="alternate"' == $style ) ? '' : ' class="alternate"';
			$invoice_class = new wp_invoice_get($invoice_id);
			echo "\n\t" . wp_invoice_invoice_row($invoice_class->data, 'incoming');
		}
	} else { ?>
		<tr>
			<td colspan="10" align="center">
				<div>
					<?php _e('You have no invoices to pay.', WP_INVOICE_TRANS_DOMAIN); ?>
				</div>
			</td>
		</tr>
	<?php }	?>

	</tbody>
	</table>

	<a href="" id="wp_invoice_show_archived">Show / Hide Archived</a>
	</form> 
	<div class="wp_invoice_stats">Total of Displayed Invoices: <span id="wp_invoice_total_owed"></span></div>

</div>