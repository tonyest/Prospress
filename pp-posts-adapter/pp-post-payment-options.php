<?php 
/* This is the form used by pp_posts_adapter to print payment options for a marketplace post.
 *
 */

// don't load directly
if ( !defined('ABSPATH') )
	die('-1');
?>
<input type="hidden" name="pp_payment_nonce" id="pp_payment_nonce" value="<?php echo wp_create_nonce(PP_POST_PAYMENT_OPTIONS); ?>" />

<table>
	<tbody>
		<tr>
			<td class="left">
				<label for="pp_payment_methods">
					<strong>
						<?php _e("Accepted Payment Methods:", 'prospress_text_domain' ); ?>
					</strong>
				</label>
			</td>
		</tr>
		<tr>
			<td>
				<fieldset id="pp_payment_methods">
					<input type="checkbox" name="pp_payment_methods[]" value="bank" <?php echo (in_array('bank', $pp_payment_methods) ? 'checked="checked"' : '' ); ?> />
						<?php _e("Bank Deposit", 'prospress_text_domain' ); ?><br />
					<input type="checkbox" name="pp_payment_methods[]" value="google" <?php echo (in_array('google', $pp_payment_methods) ? 'checked="checked"' : '' ); ?> />
						<?php _e("Google Checkout", 'prospress_text_domain' ); ?><br />
					<input type="checkbox" name="pp_payment_methods[]" value="paypal" <?php echo (in_array('paypal', $pp_payment_methods) ? 'checked="checked"' : '' ); ?> />
						<?php _e("PayPal", 'prospress_text_domain' ); ?><br />
					<input type="checkbox" name="pp_payment_methods[]" value="other" <?php echo (in_array('other', $pp_payment_methods) ? 'checked="checked"' : '' ); ?> />
						<?php _e("Other", 'prospress_text_domain' ); ?><br />
				</fieldset>
			</td>
		</tr>
	</tbody>
</table>
