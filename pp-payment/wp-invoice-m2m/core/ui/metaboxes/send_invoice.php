<?php
	function wp_invoice_metabox_history( $ic) {
	?>
		<ul id="invoice_history_log">
		<?php 
		if( $ic->log):
			foreach ( $ic->log as $single_status) {
				$time =  date(get_option('date_format') . ' ' . get_option('time_format'),  strtotime( $single_status->time_stamp));
				echo "<span class='wp_invoice_tamp_stamp'>" . $time . "</span>{$single_status->value} <br />";
			}
		else: ?>
		No history events for this invoice.
		
		<?php endif; ?>
		</ul>
	</div>

	<?php  }

function wp_invoice_metabox_publish( $ic) { ?>
	<div id="minor-publishing">

		<div id="misc-publishing-actions">
		<table class="form-table">
			<tr class="invoice_main">
				<th>Invoice ID </th>
				<td ><?php echo $ic->id; ?></td>
			</tr>
			<tr class="invoice_main">
				<th>Tax </th>
				<td>
					<input name="wp_invoice[tax]" id="wp_invoice_tax" autocomplete="off" size="5" value="<?php echo $ic->tax ?>">%</input>
				</td>
			</tr>	
			<tr class="">
				<th>Due Date</th>
				<td>
					<div id="timestampdiv" style="display:block;">
						<select id="mm" name="wp_invoice[due_date_month]">
							<option></option>
							<option value="1" <?php if( $ic->due_date_month == '1') echo " selected='selected'";?>>Jan</option>
							<option value="2" <?php if( $ic->due_date_month == '2') echo " selected='selected'";?>>Feb</option>
							<option value="3" <?php if( $ic->due_date_month == '3') echo " selected='selected'";?>>Mar</option>
							<option value="4" <?php if( $ic->due_date_month == '4') echo " selected='selected'";?>>Apr</option>
							<option value="5" <?php if( $ic->due_date_month == '5') echo " selected='selected'";?>>May</option>
							<option value="6" <?php if( $ic->due_date_month == '6') echo " selected='selected'";?>>Jun</option>
							<option value="7" <?php if( $ic->due_date_month == '7') echo " selected='selected'";?>>Jul</option>
							<option value="8" <?php if( $ic->due_date_month == '8') echo " selected='selected'";?>>Aug</option>
							<option value="9" <?php if( $ic->due_date_month == '9') echo " selected='selected'";?>>Sep</option>
							<option value="10" <?php if( $ic->due_date_month == '10') echo " selected='selected'";?>>Oct</option>
							<option value="11" <?php if( $ic->due_date_month == '11') echo " selected='selected'";?>>Nov</option>
							<option value="12" <?php if( $ic->due_date_month == '12') echo " selected='selected'";?>>Dec</option>
						</select>
						<input type="text" id="jj" name="wp_invoice[due_date_day]" value="<?php echo $ic->due_date_day; ?>" size="2" maxlength="2" autocomplete="off" />, 
						<input type="text" id="aa" name="wp_invoice[due_date_year]" value="<?php echo $ic->due_date_year; ?>" size="4" maxlength="5" autocomplete="off" />
					</div>
				</td>
			</tr>
			<tr class="hide-if-no-js">
				<th colspan="2">
					<div>
						<span onclick="wp_invoice_add_time(1);" class="wp_invoice_click_me"><?php _e( 'Today', 'prospress' ); ?></span> | 
						<span onclick="wp_invoice_add_time(7);" class="wp_invoice_click_me"><?php _e( 'In One Week', 'prospress' ); ?></span> | 
						<span onclick="wp_invoice_add_time(30);" class="wp_invoice_click_me"><?php _e( 'In 30 Days', 'prospress' ); ?></span> |
						<span onclick="wp_invoice_add_time('clear');" class="wp_invoice_click_me"><?php _e( 'Clear', 'prospress' ); ?></span>
					</div>
				</th>
			</tr>
		</table>
		</div>
		<div class="clear"></div>
		</div>

		<div id="major-publishing-actions">

		<div id="publishing-action">
			<input type="submit"  name="save" class="button-primary" value="Preview Email and Send"> 	
		</div>
		<div class="clear"></div>
	</div>

<?php }

function wp_invoice_metabox_invoice_details( $ic) { ?>
	<table class="form-table" id="wp_invoice_main_info">

	<tr class="invoice_main">
		<th><?php _e("Post Title", 'prospress') ?></th>
		<td><?php echo $ic->post_title; ?></td>
	</tr>
	<tr class="invoice_main">
		<th><?php _e("Post Content", 'prospress') ?></th>
		<td><?php echo $ic->post_content; ?></td>	
	</tr>
	<tr class="invoice_main">
		<th><?php _e("Total Amount", 'prospress') ?></th>
		<td><?php echo $ic->display_amount; ?></td>	
	</tr>
	
	</table>
	<?php
}

function wp_invoice_metabox_payer_details( $invoice ) {?>
	<dl class="payee_details clearfix">
		<dt>Email</dt>
		<dd><?php echo $invoice->payer_class->user_email; ?></dd>

		<dt>Username</dt>
		<dd><?php echo $invoice->payer_class->user_nicename; ?></dd>

		<dt>First Name</dt>
		<dd><?php echo $invoice->payer_class->first_name; ?></dd>

		<dt>Last Name</dt>
		<dd><?php echo $invoice->payer_class->last_name; ?></dd>
	</dl>
	<?php	
}
