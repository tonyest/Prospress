<?php
/**
 * View for Prospress post submit meta box
 *
 */

// don't load directly
if ( !defined('ABSPATH') )
	die('-1');

// ************ Taken from pp_post_submit_meta_box function ***************************** //
global $action, $wpdb;

$can_publish = current_user_can('publish_posts');
//$ended_post_status = 'ended';

if ( 0 != $post->ID ) { //if not a new post, get the $post_end date
	$post_end = get_post_meta($post->ID, 'post_end_date', true);
}

if ( 'publish' == $post->post_status ) { //Determine preview button
	$preview_link = esc_url(get_permalink($post->ID));
	$preview_button = __('Preview Changes', 'prospress' );
} else {
	$preview_link = esc_url(apply_filters('preview_post_link', add_query_arg('preview', 'true', get_permalink($post->ID))));
	$preview_button = __('Preview', 'prospress' );
}

switch ( $post->post_status ) { //set post_status_display var
	case 'private':
		$post_status_display = __('Privately Published', 'prospress' );
		break;
	case 'publish':
		$post_status_display = __('Published', 'prospress' );
		break;
	case 'future':
		$post_status_display = __('Scheduled', 'prospress' );
		break;
	case 'pending':
		$post_status_display = __('Pending Review', 'prospress' );
		break;
	case 'draft':
		$post_status_display = __('Draft', 'prospress' );
		break;
	case 'ended':
		$post_status_display = __('Ended', 'prospress' );
		break;
}

if ( 'private' == $post->post_status ) {
	$post->post_password = '';
	$visibility = 'private';
	$visibility_trans = __('Private', 'prospress' );
} elseif ( !empty( $post->post_password ) ) {
	$visibility = 'password';
	$visibility_trans = __('Password protected', 'prospress' );
} elseif ( is_sticky( $post->ID ) ) {
	$visibility = 'public';
	$visibility_trans = __('Public, Sticky');
} else {
	$visibility = 'public';
	$visibility_trans = __('Public', 'prospress' );
}

//Set up publish date and time variables
// translators: Publish box date format, see http://php.net/date
$datef = __( 'M j, Y @ G:i' );
if ( 0 != $post->ID ) {
	if ( 'future' == $post->post_status ) { // scheduled for publishing at a future date
		$publish_stamp = __('Scheduled for: <b>%1$s</b>', 'prospress' );
	} else if ( 'publish' == $post->post_status || 'private' == $post->post_status || 'ended' == $post->post_status ) { // already published
		$publish_stamp = __('Published on: <b>%1$s</b>', 'prospress' );
	} else if ( '0000-00-00 00:00:00' == $post->post_date_gmt ) { // draft, 1 or more saves, no date specified
		$publish_stamp = __('Publish <b>immediately</b>', 'prospress' );
	} else if ( time() < strtotime( $post->post_date_gmt . ' +0000' ) ) { // draft, 1 or more saves, future date specified
		$publish_stamp = __('Schedule for: <b>%1$s</b>', 'prospress' );
	} else { // draft, 1 or more saves, date specified
		$publish_stamp = __('Publish on: <b>%1$s</b>', 'prospress' );
	}
	$publish_date = date_i18n( $datef, strtotime( $post->post_date ) );
} else { // draft (no saves, and thus no date specified)
	$publish_stamp = __('Publish <b>immediately</b>', 'prospress' );
	$publish_date = date_i18n( $datef, strtotime( current_time('mysql') ) );
}

//Set up post end date and time variables
if ( 0 != $post->ID ) {
	if ( 'ended' == $post->post_status ) { // already finished
		$end_stamp = __('Ended  on: <b>%1$s</b>', 'prospress' );
	} else if ( '0000-00-00 00:00:00' == $post_end || empty($post_end)) { // draft, 1 or more saves, no date specified
		$end_stamp = __('End <b>immediately</b>', 'prospress' );
	} else if ( time() < strtotime( $post_end . ' +0000' ) ) { // draft, 1 or more saves, future date specified
		$end_stamp = __('End on: <b>%1$s</b>', 'prospress' );
	} else { // draft, 1 or more saves, date specified
		$end_stamp = __('End on: <b>%1$s</b>', 'prospress' );
	}
	$end_date = date_i18n( $datef, strtotime( $post_end ) );
} else { // draft (no saves so no date specified, set to end in 7 days )
	$end_stamp = __('End <b class="hide-if-no-js">immediately</b>');
	$end_date = date_i18n( $datef, strtotime( current_time('mysql') ));
}

//Determine publish button text
if ( !in_array( $post->post_status, array('publish', 'future', 'private') ) || 0 == $post->ID ) {
	if ( current_user_can('publish_posts') ) {
		 if ('ended' == $post->post_status ) {
			$original_publish = __('Repost', 'prospress' );
		} else if ( !empty($post->post_date_gmt) && time() < strtotime( $post->post_date_gmt . ' +0000' ) ) {
			$original_publish = __('Schedule', 'prospress' );
		} else {
			$original_publish = __('Publish', 'prospress' );
		}
	} else {
		$original_publish = __('Submit for Review', 'prospress' );
	}
} else {
	$original_publish = __('Update', 'prospress' );
}

// ************************************************************************************** //
?>

<div class="submitbox" id="submitpost">
	<?php /*
		<h4>Post End = <?php echo $post_end; ?></h4>
		<h4>Post End GMT = <?php echo $post_end; ?></h4>
		<h4>Current time = <?php echo current_time('mysql'); ?></h4>
		<h4>Post End &lt; Current time = <?php echo ($post_end < current_time('mysql')) ? 'true': 'false'; ?></h4>
		<h4>Post Status = <?php echo $post->post_status; ?></h4>
		<h4>Post Status Display = <?php echo $post_status_display; ?></h4>
	*/ ?>
	<div id="minor-publishing">
	<?php // Hidden submit button early on so that the browser chooses the right button when form is submitted with Return key ?>
		<div style="display:none;">
			<input type="submit" name="save" value="<?php esc_attr_e('Save', 'prospress' ); ?>" />
		</div>

		<div id="minor-publishing-actions">
			<div id="save-action">
			<?php //if ( 'publish' != $post->post_status && 'future' != $post->post_status && 'pending' != $post->post_status && 'ended' != $post->post_status )  { ?>
				<input type="submit" name="save" id="save-post" value="<?php esc_attr_e('Save Draft', 'prospress' ); ?>" tabindex="4" class="button button-highlighted" 
					<?php echo (in_array($post->post_status, array('private', 'publish', 'future', 'pending', 'ended') ) ) ? 'style="display:none"' : ''; ?> 
				/>
			<?php if ( 'pending' == $post->post_status && $can_publish ) { ?>
				<input type="submit" name="save" id="save-post" value="<?php esc_attr_e('Save as Pending', 'prospress' ); ?>" tabindex="4" class="button button-highlighted" />
			<?php } ?>
		</div>

		<div id="preview-action">
			<a class="preview button" href="<?php echo $preview_link; ?>" target="wp-preview" id="post-preview" tabindex="4"><?php echo $preview_button; ?></a>
			<input type="hidden" name="wp-preview" id="wp-preview" value="" />
		</div>

		<div class="clear"></div>
	</div><?php // /minor-publishing-actions ?>

	<div id="misc-publishing-actions">

		<div class="misc-pub-section<?php if ( !$can_publish ) { echo '  misc-pub-section-last'; } ?>"><label for="post_status"><?php _e('Status:', 'prospress' ) ?></label>
			<span id="post-status-display" >
				<?php echo $post_status_display; ?>
			</span>
			<?php //if ( 'ended' != $post->post_status && ('publish' == $post->post_status || 'private' == $post->post_status || $can_publish) ) { ?>
			<?php if ( ('publish' == $post->post_status || 'private' == $post->post_status || $can_publish) ) { ?>
				<a href="#post_status" <?php if ( 'private' == $post->post_status || 'ended' == $post->post_status) { ?>style="display:none;" <?php } ?>class="edit-post-status hide-if-no-js" tabindex='4'><?php _e('Edit', 'prospress' ) ?></a>
				<div id="post-status-select" class="hide-if-js">
					<input type="hidden" name="hidden_post_status" id="hidden_post_status" value="<?php echo esc_attr($post->post_status); ?>" />
					<select name='post_status' id='post_status' tabindex='4'>
						<?php if ( 'publish' == $post->post_status ) : ?>
							<option<?php selected( $post->post_status, 'publish' ); ?> value='publish'><?php _e('Published', 'prospress' ) ?></option>
						<?php elseif ( 'private' == $post->post_status ) : ?>
							<option<?php selected( $post->post_status, 'private' ); ?> value='publish'><?php _e('Privately Published', 'prospress' ) ?></option>
						<?php elseif ( 'future' == $post->post_status ) : ?>
							<option<?php selected( $post->post_status, 'future' ); ?> value='future'><?php _e('Scheduled', 'prospress' ) ?></option>
						<?php elseif ( 'ended' == $post->post_status ) : ?>
							<option<?php selected( $post->post_status, 'ended' ); ?> value='ended'><?php _e('Ended', 'prospress' ) ?></option>
						<?php endif; ?>
						<option<?php selected( $post->post_status, 'pending' ); ?> value='pending'><?php _e('Pending Review', 'prospress' ) ?></option>
						<option<?php selected( $post->post_status, 'draft' ); ?> value='draft'><?php _e('Draft', 'prospress' ) ?></option>
					</select>
					<a href="#post_status" class="save-post-status hide-if-no-js button"><?php _e('OK', 'prospress' ); ?></a>
					<a href="#post_status" class="cancel-post-status hide-if-no-js"><?php _e('Cancel', 'prospress' ); ?></a>
				</div>
			<?php } ?>
		</div><?php // /misc-pub-section ?>

		<div class="misc-pub-section " id="visibility">
			<?php _e('Visibility:', 'prospress' ); ?> <span id="post-visibility-display">
			<?php echo esc_html( $visibility_trans ); ?></span> 
			<?php if ( $can_publish ) { ?> 
				<a href="#visibility" <?php if ( 'ended' == $post->post_status) { ?>style="display:none;" <?php } ?> class="edit-visibility hide-if-no-js"><?php _e('Edit', 'prospress' ); ?></a>

				<div id="post-visibility-select" class="hide-if-js">
					<input type="hidden" name="hidden_post_password" id="hidden-post-password" value="<?php echo esc_attr($post->post_password); ?>" />
					<input type="checkbox" style="display:none" name="hidden_post_sticky" id="hidden-post-sticky" value="sticky" <?php checked(is_sticky($post->ID)); ?> />
					<input type="hidden" name="hidden_post_visibility" id="hidden-post-visibility" value="<?php echo esc_attr( $visibility ); ?>" />

					<input type="radio" name="visibility" id="visibility-radio-public" value="public" <?php checked( $visibility, 'public' ); ?> /> <label for="visibility-radio-public" class="selectit"><?php _e('Public', 'prospress' ); ?></label><br />
					<span id="sticky-span"><input id="sticky" name="sticky" type="checkbox" value="sticky" <?php checked(is_sticky($post->ID)); ?> tabindex="4" /> <label for="sticky" class="selectit"><?php _e('Stick this post to the front page', 'prospress' ) ?></label><br /></span>
					<input type="radio" name="visibility" id="visibility-radio-password" value="password" <?php checked( $visibility, 'password' ); ?> /> <label for="visibility-radio-password" class="selectit"><?php _e('Password protected', 'prospress' ); ?></label><br />
					<span id="password-span"><label for="post_password"><?php _e('Password:', 'prospress' ); ?></label> <input type="text" name="post_password" id="post_password" value="<?php echo esc_attr($post->post_password); ?>" /><br /></span>
					<input type="radio" name="visibility" id="visibility-radio-private" value="private" <?php checked( $visibility, 'private' ); ?> /> <label for="visibility-radio-private" class="selectit"><?php _e('Private', 'prospress' ); ?></label><br />

					<p>
						<a href="#visibility" class="save-post-visibility hide-if-no-js button"><?php _e('OK', 'prospress' ); ?></a>
						<a href="#visibility" class="cancel-post-visibility hide-if-no-js"><?php _e('Cancel', 'prospress' ); ?></a>
					</p>
				</div>
			<?php } ?>
		</div>

		<?php
		if ( $can_publish ) : // Contributors don't get to choose the date of publish ?>
			<div class="misc-pub-section curtime">
				<span id="timestamp">
				<?php printf($publish_stamp, $publish_date); ?></span>
				<a href="#edit_timestamp" <?php if ( 'ended' == $post->post_status) { ?>style="display:none;" <?php } ?> class="edit-timestamp hide-if-no-js" tabindex='4'><?php _e('Edit', 'prospress' ) ?></a>
				<div id="timestampdiv" class="hide-if-js"><?php touch_time(($action == 'edit'),1,4); ?></div>
			</div>
		<?php endif; ?>

		<?php
//*******************************************************************************
//* Post End Time
//*******************************************************************************
		// Contributors do get to choose the date of auction end
		//if ( $can_publish ) : // Contributors don't get to choose the date of publish ?>
		<div class="misc-pub-section curtime misc-pub-section-last">
			<span id="endtimestamp">
			<?php printf($end_stamp, $end_date); ?></span>
			<a href="#edit_endtimestamp" class="edit-endtimestamp hide-if-no-js" tabindex='4'><?php ('ended' != $post->post_status) ? _e('Edit', 'prospress' ) : _e('Extend', 'prospress' ); ?></a>
			<div id="endtimestampdiv" class="hide-if-js">
				<?php touch_end_time(($action == 'edit'),5); ?>
				<?php //touch_time(($action == 'edit'),1,5); ?>
			</div>
		</div><?php // end misc-pub-section ?>
		<?php //endif; ?>
<?php
//*******************************************************************************
?>
	</div>
	<div class="clear"></div>
</div>

<div id="major-publishing-actions">
	<?php do_action('post_submitbox_start'); ?>
	<div id="delete-action">
		<?php
		if ( ( 'edit' == $action ) && current_user_can('delete_post', $post->ID) ) { ?>
			<a class="submitdelete deletion" href="<?php echo wp_nonce_url("post.php?action=delete&amp;post=$post->ID", 'delete-post_' . $post->ID); ?>" onclick="if ( confirm('<?php echo esc_js(sprintf( ('draft' == $post->post_status) ? __("You are about to delete this draft '%s'\n  'Cancel' to stop, 'OK' to delete.") : __("You are about to delete this post '%s'\n  'Cancel' to stop, 'OK' to delete."), $post->post_title )); ?>') ) {return true;}return false;"><?php _e('Delete', 'prospress' ); ?></a>
		<?php } ?>
	</div>

	<div id="publishing-action">
		<input name="original_publish" type="hidden" id="original_publish" value="<?php echo $original_publish; ?>" />
		<input name="save" type="submit" class="button-primary" id="publish" tabindex="5" accesskey="p" value="<?php echo $original_publish; ?>" />
	</div>
	<div class="clear"></div>
</div>
</div>
