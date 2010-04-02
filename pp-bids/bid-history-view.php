<div class="wrap feedback-history">
	<?php screen_icon(); ?>
	<h2><?php _e('Bid History'); ?></h2>

	<table class="widefat fixed" cellspacing="0">
		<thead>
			<tr class="thead">
				<?php print_column_headers('bid_history'); ?>
			</tr>
		</thead>
		<tfoot>
			<tr class="thead">
				<?php print_column_headers('bid_history'); ?>
			</tr>
		</tfoot>
		<tbody id="users" class="list:user user-list">
		<?php
			if($bids){
				$style = '';
				foreach ( $bids as $bids_item ) {
					extract($bids_item);
					echo "<tr $style ><td><input type='checkbox'></td>";
					echo "<td>$bid_id</td>";
					echo "<td>$listing_id</td>";
					echo "<td>$bid_value</td>";
					echo "<td>$bid_date</td></tr>";
					$style = ( ' class="alternate"' == $style ) ? '' : ' class="alternate"';
				}
			} else {echo '<tr><td colspan="5">You have no bidding history.</td>';
			}
		?>
		</tbody>
	</table>
</div>