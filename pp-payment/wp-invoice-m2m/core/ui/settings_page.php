
<div class="wrap">
<?php screen_icon( 'prospress' ); ?>
<h2><?php _e( 'Payment Settings', 'prospress') ?></h2>
<form id='wp_invoice_settings_page' method='POST'>
<table class="form-table">
	<tr>
		<th><a class="wp_invoice_tooltip" title="The default name for an invoice's tax item. eg. VAT, GST or Sales Tax."><?php _e('Default Tax Label:', 'prospress'); ?></a></th><td>
		<?php echo wp_invoice_draw_inputfield('wp_invoice_custom_label_tax', get_option('wp_invoice_custom_label_tax')); ?>
		</td>
	</tr>		
	<tr>
		<th><a class="wp_invoice_tooltip" title="What to display for states on checkout page. The dropdown list only includes states in the USA."><?php _e('State Display:', 'prospress'); ?></a></th><td>
		<?php echo wp_invoice_draw_select('wp_invoice_fe_state_selection',array("Dropdown" => __('Dropdown', 'prospress'), "Input_Field" => __('Input Field', 'prospress'), "Hide" => __('Hide Completely', 'prospress')), get_option('wp_invoice_fe_state_selection')); ?>
		</td>
	</tr>
	<tr>
		<th><a class="wp_invoice_tooltip"  title="Special proxy must be used to process credit card transactions on GoDaddy servers.">Using Godaddy Hosting</a></th>
		<td>
		<?php echo wp_invoice_draw_select('wp_invoice_using_godaddy',array("yes" => __('Yes', 'prospress'), "no" => __('No', 'prospress')), get_option('wp_invoice_using_godaddy')); ?>
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
	<tr>
		<td colspan="2"><input type="checkbox" id="wp_invoice_load_original_email_templates" name="wp_invoice_load_original_email_templates"> <label for="wp_invoice_load_original_email_templates"><?php _e( 'Load Original Templates', 'prospress' ); ?></label></td>
	</tr>
</table>
<div class="clear"></div>
<input type="submit" value="Save Settings" class="button-primary">
</form>
</div>