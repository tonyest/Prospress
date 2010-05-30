
<div class="wrap">
<?php screen_icon( 'prospress' ); ?>
<h2><?php _e( 'Payment Settings', 'prospress') ?></h2>
<form id='wp_invoice_settings_page' method='POST'>
<table class="form-table">
	<tr>
		<th><?php _e('Default Tax Label:', 'prospress'); ?></th>
		<td>
			<?php echo wp_invoice_draw_inputfield('wp_invoice_custom_label_tax', get_option('wp_invoice_custom_label_tax')); ?>
			<?php _e( 'The name of tax in your country. eg. VAT, GST or Sales Tax.', 'prospress'); ?>
		</td>
	</tr>		
	<tr>
		<th>Using Godaddy Hosting</th>
		<td>
			<?php echo wp_invoice_draw_select('wp_invoice_using_godaddy',array("yes" => __('Yes', 'prospress'), "no" => __('No', 'prospress')), get_option('wp_invoice_using_godaddy')); ?>
			<?php _e( 'A special proxy must be used for credit card transactions on GoDaddy servers.', 'prospress'); ?>
		</td>
	</tr>
</table>
<h3>Email Templates</h3>
<table class="form-table wp_invoice_email_templates">
	<tr>
		<th><?php _e("<b>Invoice Notification</b> Subject", 'prospress') ?></th>
		<td><?php echo wp_invoice_draw_inputfield('wp_invoice_email_send_invoice_subject', get_option('wp_invoice_email_send_invoice_subject')); ?></td>
	</tr>
	<tr>
		<th><?php _e("<b>Invoice Notification</b> Content", 'prospress') ?></th>
		<td><?php echo wp_invoice_draw_textarea('wp_invoice_email_send_invoice_content', get_option('wp_invoice_email_send_invoice_content')); ?></td>
	</tr>
	<tr><td colspan="2">&nbsp;</td></tr>
	<tr>
		<th><?php _e("<b>Reminder</b> Subject", 'prospress') ?></th>
		<td><?php echo wp_invoice_draw_inputfield('wp_invoice_email_send_reminder_subject', get_option('wp_invoice_email_send_reminder_subject')); ?></td>
	</tr>
		<tr>
		<th><?php _e("<b>Reminder</b> Content", 'prospress') ?></th>
		<td><?php echo wp_invoice_draw_textarea('wp_invoice_email_send_reminder_content', get_option('wp_invoice_email_send_reminder_content')); ?></td>
	</tr>
	<tr><td colspan="2">&nbsp;</td></tr>
	<tr>
		<th><?php _e("<b>Receipt</b> Subject", 'prospress') ?></th>
		<td><?php echo wp_invoice_draw_inputfield('wp_invoice_email_send_receipt_subject', get_option('wp_invoice_email_send_receipt_subject')); ?></td>
	</tr>
		<tr>
		<th><?php _e("<b>Receipt</b> Content", 'prospress') ?></th>
		<td><?php echo wp_invoice_draw_textarea('wp_invoice_email_send_receipt_content', get_option('wp_invoice_email_send_receipt_content')); ?></td>
	</tr>
</table>
<div class="clear"></div>
<p class="submit">
	<input type="submit" value="Save Settings" class="button-primary">
</p>
</form>
</div>