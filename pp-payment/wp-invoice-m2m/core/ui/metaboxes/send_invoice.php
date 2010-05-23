<?php
	function wp_invoice_metabox_history($ic) {
	?>
		<ul id="invoice_history_log">
		<?php 
		if($ic->log):
			foreach ($ic->log as $single_status) {
				$time =  date(get_option('date_format') . ' ' . get_option('time_format'),  strtotime($single_status->time_stamp));
				echo "<span class='wp_invoice_tamp_stamp'>" . $time . "</span>{$single_status->value} <br />";
			}
		else: ?>
		No history events for this invoice.
		
		<?php endif; ?>
		</ul>
	</div>

	<?php  }

function wp_invoice_metabox_publish($ic) { ?>
	<div id="minor-publishing">

<div id="misc-publishing-actions">
<table class="form-table">

	<tr class="invoice_main">
		<th>Invoice ID </th>
		<td style="font-size: 1.1em; padding-top:7px;"><?php echo $ic->id; ?></td>
	</tr>

	<tr class="invoice_main">
		<th>Tax </th>
		<td style="font-size: 1.1em; padding-top:7px;">
			<input style="width: 35px;"  name="wp_invoice[tax]" id="wp_invoice_tax" autocomplete="off" value="<?php echo $ic->tax ?>">%</input>
		</td>
	</tr>

	<tr class="">
		<th>Currency</th>
		<td>
 
			<select name="wp_invoice[currency_code]">
				<?php 
				if(!isset($ic->currency_code))
					$ic->currency_code = wp_invoice_user_settings('default_currency_code');

				foreach(wp_invoice_currency_array() as $value=>$currency_x) {
				echo "<option value='$value'"; if($ic->currency_code == $value) echo " SELECTED"; echo ">$value - $currency_x</option>\n";
				}
				?>
			</select> 
		</td>
	</tr>
	
	<tr class="">
		<th>Due Date</th>
		<td>
			<div id="timestampdiv" style="display:block;">
			<select id="mm" name="wp_invoice[due_date_month]">
			<option></option>
			<option value="1" <?php if($ic->due_date_month == '1') echo " selected='selected'";?>>Jan</option>
			<option value="2" <?php if($ic->due_date_month == '2') echo " selected='selected'";?>>Feb</option>
			<option value="3" <?php if($ic->due_date_month == '3') echo " selected='selected'";?>>Mar</option>
			<option value="4" <?php if($ic->due_date_month == '4') echo " selected='selected'";?>>Apr</option>
			<option value="5" <?php if($ic->due_date_month == '5') echo " selected='selected'";?>>May</option>
			<option value="6" <?php if($ic->due_date_month == '6') echo " selected='selected'";?>>Jun</option>
			<option value="7" <?php if($ic->due_date_month == '7') echo " selected='selected'";?>>Jul</option>
			<option value="8" <?php if($ic->due_date_month == '8') echo " selected='selected'";?>>Aug</option>
			<option value="9" <?php if($ic->due_date_month == '9') echo " selected='selected'";?>>Sep</option>
			<option value="10" <?php if($ic->due_date_month == '10') echo " selected='selected'";?>>Oct</option>
			<option value="11" <?php if($ic->due_date_month == '11') echo " selected='selected'";?>>Nov</option>
			<option value="12" <?php if($ic->due_date_month == '12') echo " selected='selected'";?>>Dec</option>
			</select>
			<input type="text" id="jj" name="wp_invoice[due_date_day]" value="<?php echo $ic->due_date_day; ?>" size="2" maxlength="2" autocomplete="off" />, 
			<input type="text" id="aa" name="wp_invoice[due_date_year]" value="<?php echo $ic->due_date_year; ?>" size="4" maxlength="5" autocomplete="off" />
			
			<div>
				<span onclick="wp_invoice_add_time(7);" class="wp_invoice_click_me">In One Week</span> | 
				<span onclick="wp_invoice_add_time(30);" class="wp_invoice_click_me">In 30 Days</span> |
				<span onclick="wp_invoice_add_time('clear');" class="wp_invoice_click_me">Clear</span>
			</div>
			</div> 
		</td>
	</tr>

</table>
</div>
<div class="clear"></div>
</div>

<div id="major-publishing-actions">

<div id="publishing-action">
	<input type="submit"  name="save" class="button-primary" value="Preview and Send"> 	
</div>
<div class="clear"></div>
</div>

<?php }

function wp_invoice_metabox_invoice_details($ic) { 
?>
	
	<table class="form-table" id="wp_invoice_main_info">

	<tr class="invoice_main">
		<th><?php _e("Post Title", WP_INVOICE_TRANS_DOMAIN) ?></th>
		<td><?php echo $ic->post_title; ?></td>
	</tr>
	<tr class="invoice_main">
		<th><?php _e("Post Content", WP_INVOICE_TRANS_DOMAIN) ?></th>
		<td><?php echo $ic->post_content; ?></td>	
	</tr>
	<tr class="invoice_main">
		<th><?php _e("Total Amount", WP_INVOICE_TRANS_DOMAIN) ?></th>
		<td><?php echo $ic->display_amount; ?></td>	
	</tr>
	
	</table>
	
	<?php
}

function wp_invoice_metabox_payer_details($invoice) {
	include WP_INVOICE_UI_PATH . 'box_payer_details.php';
}
