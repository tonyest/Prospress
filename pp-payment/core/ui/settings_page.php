
<div class="wrap">
<?php screen_icon( 'prospress' ); ?>
<h2><?php _e( 'Payment Settings', 'prospress') ?></h2>
<form id='pp_invoice_settings_page' method='POST'>
<table class="form-table">
	<tr>
		<th><?php _e('Default Tax Label:', 'prospress'); ?></th>
		<td>
			<?php echo pp_invoice_draw_inputfield('pp_invoice_custom_label_tax', get_option('pp_invoice_custom_label_tax')); ?>
			<?php _e( 'The name of tax in your country. eg. VAT, GST or Sales Tax.', 'prospress'); ?>
		</td>
	</tr>		
	<tr>
		<th>Using Godaddy Hosting</th>
		<td>
			<?php echo pp_invoice_draw_select('pp_invoice_using_godaddy',array("yes" => __('Yes', 'prospress'), "no" => __('No', 'prospress')), get_option('pp_invoice_using_godaddy')); ?>
			<?php _e( 'A special proxy must be used for credit card transactions on GoDaddy servers.', 'prospress'); ?>
		</td>
	</tr>
	<tr>
		<th><?php _e('Enforce HTTPS:', 'prospress' ); ?></a></th>
		<td>
		<select  name="pp_invoice_force_https">
		<option value="true" style="padding-right: 10px;"<?php if(get_option('pp_invoice_force_https') == 'true') echo 'selected="yes"';?>><?php _e('Yes', 'prospress' ); ?></option>
		<option value="false" style="padding-right: 10px;"<?php if(get_option('pp_invoice_force_https') == 'false') echo 'selected="yes"';?>><?php _e('No', 'prospress' ); ?></option>
		</select> 
		<?php _e('If enforced, Prospress will reload the invoice page into a secure mode.', 'prospress' ); ?>
		</td>
	</tr>
</table>
<h3>Email Templates</h3>
<table class="form-table pp_invoice_email_templates">
	<tr>
		<th><?php _e("<b>Invoice Notification</b> Subject", 'prospress') ?></th>
		<td><?php echo pp_invoice_draw_inputfield('pp_invoice_email_send_invoice_subject', get_option('pp_invoice_email_send_invoice_subject')); ?></td>
	</tr>
	<tr>
		<th><?php _e("<b>Invoice Notification</b> Content", 'prospress') ?></th>
		<td><?php echo pp_invoice_draw_textarea('pp_invoice_email_send_invoice_content', get_option('pp_invoice_email_send_invoice_content')); ?></td>
	</tr>
	<tr><td colspan="2">&nbsp;</td></tr>
	<tr>
		<th><?php _e("<b>Reminder</b> Subject", 'prospress') ?></th>
		<td><?php echo pp_invoice_draw_inputfield('pp_invoice_email_send_reminder_subject', get_option('pp_invoice_email_send_reminder_subject')); ?></td>
	</tr>
		<tr>
		<th><?php _e("<b>Reminder</b> Content", 'prospress') ?></th>
		<td><?php echo pp_invoice_draw_textarea('pp_invoice_email_send_reminder_content', get_option('pp_invoice_email_send_reminder_content')); ?></td>
	</tr>
	<tr><td colspan="2">&nbsp;</td></tr>
	<tr>
		<th><?php _e("<b>Receipt</b> Subject", 'prospress') ?></th>
		<td><?php echo pp_invoice_draw_inputfield('pp_invoice_email_send_receipt_subject', get_option('pp_invoice_email_send_receipt_subject')); ?></td>
	</tr>
		<tr>
		<th><?php _e("<b>Receipt</b> Content", 'prospress') ?></th>
		<td><?php echo pp_invoice_draw_textarea('pp_invoice_email_send_receipt_content', get_option('pp_invoice_email_send_receipt_content')); ?></td>
	</tr>
</table>
<div class="clear"></div>
<p class="submit">
	<input type="submit" value="Save Settings" class="button-primary">
</p>
</form>
</div>