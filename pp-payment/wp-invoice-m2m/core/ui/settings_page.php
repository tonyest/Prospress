
<div class="wrap">
<form method='POST'>
<h2><?php _e("WP-Invoice M2M Global Settings", WP_INVOICE_TRANS_DOMAIN) ?></h2>
<div id="wp_invoice_settings_page" class="wp_invoice_tabbed_content"> 
  <ul class="wp_invoice_settings_tabs"> 
    <li><a class="selected" href="#tab1"><?php _e("Basic Settings") ?></a></li> 
    <li><a href="#tab2"><?php _e("Display Settings") ?></a></li> 
    <li><a href="#tab3"><?php _e("Payment Settings") ?></a></li> 
    <li><a href="#tab4"><?php _e("E-Mail Templates") ?></a></li> 
  </ul> 
  <div id="tab1" class="wp_invoice_tab" >
		<table class="form-table">
			<tr>
				<th>Minimum User Level to Use WP-Invoice</a>:</th>
				<td>
				<?php echo wp_invoice_draw_select('wp_invoice_user_level',array("level_0" => "Subscriber","level_1" => "Contributor","level_2" => "Author","level_5" => "Editor","level_8" => "Administrator"), get_option('wp_invoice_user_level')); ?>
				</td>
			</tr>
		</table>
  </div> 
  <div id="tab2"  class="wp_invoice_tab">
  		<table class="form-table">
			<tr>
				<th width="200"><a class="wp_invoice_tooltip"  title=""><?php _e('Tax Label:', WP_INVOICE_TRANS_DOMAIN); ?></a></th><td>
				<?php echo wp_invoice_draw_inputfield('wp_invoice_custom_label_tax', get_option('wp_invoice_custom_label_tax')); ?>
				</td>
			</tr>		
		
			<tr>
				<th width="200"><a class="wp_invoice_tooltip"  title="What to display for states on checkout page."><?php _e('State Display:', WP_INVOICE_TRANS_DOMAIN); ?></a></th><td>
				<?php echo wp_invoice_draw_select('wp_invoice_fe_state_selection',array("Dropdown" => __('Dropdown', WP_INVOICE_TRANS_DOMAIN), "Input_Field" => __('Input Field', WP_INVOICE_TRANS_DOMAIN), "Hide" => __('Hide Completely', WP_INVOICE_TRANS_DOMAIN)), get_option('wp_invoice_fe_state_selection')); ?>
				</td>
			</tr>
		</table>  
  </div> 
  <div id="tab3"  class="wp_invoice_tab">
    	<table class="form-table">
			<tr>
				<th><?php _e("Default Currency:");?></th>
				<td>
				<?php echo wp_invoice_draw_select('wp_invoice_default_currency_code',wp_invoice_currency_array(),get_option('wp_invoice_default_currency_code')); ?>
				</td>
			</tr>
			<tr>
				<th><a class="wp_invoice_tooltip"  title="Special proxy must be used to process credit card transactions on GoDaddy servers.">Using Godaddy Hosting</a></th>
				<td>
				<?php echo wp_invoice_draw_select('wp_invoice_using_godaddy',array("yes" => __('Yes', WP_INVOICE_TRANS_DOMAIN), "no" => __('No', WP_INVOICE_TRANS_DOMAIN)), get_option('wp_invoice_using_godaddy')); ?>
				</td>
			</tr>
		</table>
	</div>
	<div id="tab4"  class="wp_invoice_email_templates wp_invoice_tab">
		<table class="form-table" >
			<tr>
				<th><?php _e("<b>Invoice Notification</b> Subject", WP_INVOICE_TRANS_DOMAIN) ?></th>
				<td><?php echo wp_invoice_draw_inputfield('wp_invoice_email_send_invoice_subject', get_option('wp_invoice_email_send_invoice_subject')); ?></td>
			</tr>
				<tr>
				<th><?php _e("<b>Invoice Notification</b> Content", WP_INVOICE_TRANS_DOMAIN) ?></th>
				<td><?php echo wp_invoice_draw_textarea('wp_invoice_email_send_invoice_content', get_option('wp_invoice_email_send_invoice_content')); ?></td>
			</tr>
			<tr><td colspan="2">&nbsp;</td></tr>
			<tr>
				<th><?php _e("<b>Reminder</b> Subject", WP_INVOICE_TRANS_DOMAIN) ?></th>
				<td><?php echo wp_invoice_draw_inputfield('wp_invoice_email_send_reminder_subject', get_option('wp_invoice_email_send_reminder_subject')); ?></td>
			</tr>
				<tr>
				<th><?php _e("<b>Reminder</b> Content", WP_INVOICE_TRANS_DOMAIN) ?></th>
				<td><?php echo wp_invoice_draw_textarea('wp_invoice_email_send_reminder_content', get_option('wp_invoice_email_send_reminder_content')); ?></td>
			</tr>
			<tr><td colspan="2">&nbsp;</td></tr>
			<tr>
				<th><?php _e("<b>Receipt</b> Subject", WP_INVOICE_TRANS_DOMAIN) ?></th>
				<td><?php echo wp_invoice_draw_inputfield('wp_invoice_email_send_receipt_subject', get_option('wp_invoice_email_send_receipt_subject')); ?></td>
			</tr>
				<tr>
				<th><?php _e("<b>Receipt</b> Content", WP_INVOICE_TRANS_DOMAIN) ?></th>
				<td><?php echo wp_invoice_draw_textarea('wp_invoice_email_send_receipt_content', get_option('wp_invoice_email_send_receipt_content')); ?></td>
			</tr>
			<tr>
				<td colspan="2"><input type="checkbox" id="wp_invoice_load_original_email_templates" name="wp_invoice_load_original_email_templates"> <legend for="wp_invoice_load_original_email_templates">Load Original Content</legend></td>
			</tr>
		</table>
	</div>	
</div> 
<script type="text/javascript"> 
  jQuery("#wp_invoice_settings_page ul").idTabs(); 
</script>
<div id="poststuff" class="metabox-holder">
	<div id="submitdiv" class="postbox" style="">	
		<div class="inside">
			<div id="major-publishing-actions">
				<div id="publishing-action">
					<input type="submit" value="Save All Settings" class="button-primary"></div>
					<div class="clear"></div>
				</div>
			</div>
		</div>
	</div>
</form>
</div>