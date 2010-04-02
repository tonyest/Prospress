<?php
/**
 * Feedback Form.
 *
 * @package Prospress
 */

if( !isset( $disabled ) )
	$disabled = ( get_option( 'edit_feedback' ) == 'true' ) ? '' : 'disabled="disabled"';

error_log("feedback score = $feedback_score");
?>

<div class="wrap" id="give-feedback">
	<?php if( isset ( $feedback_msg ) ): //if( isset ( $_POST [ 'feedback_submit' ] ) ):?>
		<div id="message" class="updated fade">
			<p><strong>
				<?php echo $feedback_msg; //_e('Feedback Submitted!'); ?>
			</strong></p>
		</div>
	<?php endif; ?>
	<?php if ( isset( $errors ) && is_wp_error( $errors ) ) : ?>
		<div class="error">
			<ul>
			<?php foreach( $errors->get_error_messages() as $message )
				echo "<li>$message</li>";
			?>
			</ul>
		</div>
	<?php endif; ?>
	<?php screen_icon(); ?>
	<h2><?php echo esc_html( $title ); ?></h2>
	<form name="GiveFeedback_Form" id="GiveFeedback_Form" action="<?php echo esc_url( $_SERVER ['REQUEST_URI'] ); ?>" method="post">
		<input type="hidden" name="redirect_to" value="http://<?php echo $_SERVER['SERVER_NAME'] . esc_url( $_SERVER['REQUEST_URI'] ); ?>" />
		<input type="hidden" name="for_user_id" value="<?php echo $for_user_id ?>" />
		<input type="hidden" name="from_user_id" value="<?php echo $from_user_id ?>" />
		<input type="hidden" name="post_id" value="<?php echo $post_id ?>" />
		<input type="hidden" name="blog_id" value="<?php echo $blog_id ?>" />
		<input type="hidden" name="role" value="<?php echo $role ?>" />
		<input type="hidden" name="feedback_date" value="<?php echo current_time('mysql', true); ?>" />
		<table class="form-table">
			<tr id="GiveFeedback_Comment" class="">
				<th scope="row" >
					<label for="feedback_comment"><?php _e( 'Comment' ) ?></label>
				</th>
				<td>
					<input type="text" name="feedback_comment" id="feedback_comment" class="regular-text" value="<?php echo $feedback_comment; ?>" 
					<?php echo $disabled; ?> 
					/>
				</td>
			</tr>
			<tr id="GiveFeedback_Rating" class="">
				<th scope="row" >
					<label for="feedback_score"><?php _e( 'Rating' ) ?></label>
				</th>
				<td><fieldset>
					<label title="positive">
						<input name="feedback_score" type="radio" id="rating_positive" value='2' <?php echo $disabled;
						echo ( isset( $feedback_score ) && $feedback_score == 2 ) ? 'checked="checked"' : ''; ?> /> 
						<?php _e('Positive'); ?>
					</label>
					<label title="neutral">
						<input name="feedback_score" type="radio" id="rating_neutral" value='1' <?php echo $disabled;
						echo ( isset( $feedback_score ) && $feedback_score == 1 ) ? 'checked="checked"' : ''; ?> /> 
						<?php _e('Neutral'); ?>
					</label>
					<label title="negative">
						<input name="feedback_score" type="radio" id="rating_negative" value='0' <?php echo $disabled;
						echo ( isset( $feedback_score ) && $feedback_score == 0 ) ? 'checked="checked"' : ''; ?> /> 
						<?php _e('Negative'); ?>
					</label>
				</fieldset></td>
			</tr>
		</table>
		<p id="GiveFeedback_Submit" class="submit">
			<input type="submit" name="feedback_submit" id="feedback_submit" class="button-primary action" value="<?php _e('Submit Feedback'); ?>" tabindex="100"  
			<?php echo $disabled; ?> />
		</p>
	</form>
</div>