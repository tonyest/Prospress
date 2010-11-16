<?php
function pp_cubepoints_settings() {

	  if (!current_user_can('manage_options'))  {
	    wp_die( __('You do not have sufficient permissions to access this page.') );
	  }
	 	
 	if ($_POST['cp_admin_form_submit'] == 'Y') {
 	
 	$search = array('\r',' ', '"', "'", '%');
		$cp_comment_points = (int)$_POST['cp_comment_points'];
		$cp_del_comment_points = (int)$_POST['cp_del_comment_points'];
		$cp_post_points = (int)$_POST['cp_post_points'];
		$cp_reg_points = (int)$_POST['cp_reg_points'];
		$cp_prefix = $_POST['cp_prefix'];
		$cp_suffix = $_POST['cp_suffix'];
		$cp_about_text = str_replace($search, "", strip_tags(stripslashes($_POST['cp_about_text'])));
		$cp_about_posts = (bool)$_POST['cp_about_posts'];
		$cp_about_comments = (bool)$_POST['cp_about_comments'];
		$cp_donation = (bool)$_POST['cp_donation'];
		$cp_daily_points = (int)$_POST['cp_daily_points'];
		$cp_daily_points_time = (int)$_POST['cp_daily_points_time'];
		$cp_daily_points_time = abs($cp_daily_points_time);
		$cp_logs_enabled = (bool)$_POST['cp_logs_enabled'];
		$cp_ranks_enabled = (bool)$_POST['cp_ranks_enabled'];
		$cp_mypoints = (bool)$_POST['cp_mypoints'];
		$cp_cron_auth_key = trim($_POST['cp_cron_auth_key']);
		if($cp_cron_auth_key == '') {$cp_cron_auth_key = substr(md5(uniqid()),3,10);}	
		$cp_ranks_dir = trim($_POST['cp_ranks_dir']);
		update_option('cp_comment_points', $cp_comment_points);
		update_option('cp_del_comment_points', $cp_del_comment_points);
		update_option('cp_post_points', $cp_post_points);
		update_option('cp_reg_points', $cp_reg_points);
		update_option('cp_prefix', $cp_prefix);
		update_option('cp_suffix', $cp_suffix);
		update_option('cp_about_text', $cp_about_text);
		update_option('cp_about_posts', $cp_about_posts);
		update_option('cp_about_comments', $cp_about_comments);
		update_option('cp_donation', $cp_donation);
		update_option('cp_ranks_enabled', $cp_ranks_enabled);
		update_option('cp_logs_enabled', $cp_logs_enabled);
		update_option('cp_mypoints', $cp_mypoints);
		update_option('cp_daily_points', $cp_daily_points);
		update_option('cp_daily_points_time', $cp_daily_points_time);
		update_option('cp_ranks_dir', $cp_ranks_dir);
		  
		$search = array("\r","'",'"','%');

		echo '<div class="updated"><p><strong>'.__('Settings Updated','cp').'</strong></p></div>';
  	}
/*
	Add points when users bid [user set val]
		points used in bid are frozen
		points are unfrozen on loss
	add points when users win [user set val]
	add points when users sell [user set val]
	add points when users post images in auctions [user set val]
	*/
	if (get_option('pp_cubepoints_enable')) {
		  $pp_cubepoints_enable_checked = " checked='checked'";  //cubepoints-mode will disable the payment functions
	} else {
		  $pp_cubepoints_enable = "";
	}
	if (get_option('cp_pp_bid_points')) {
		$cp_pp_bid_points_checked = " checked='checked'";
	} else {
		$cp_pp_bid_points_checked = "";
	}

?>
<div class="wrap">
	<h2>CubePoints - Prospress Settings</h2>

<?php _e('Configure CubePoints to your liking!', 'cp'); ?><br /><br />
<form name="cp_admin_form" method="post" action="<?php echo cp_curPageURL("config"); ?>">
	<input type="hidden" name="cp_admin_form_submit" value="Y" />
	<h3><?php _e('General Settings','cp'); ?></h3>
	<table class="form-table">
		<tr valign="top">
			<th scope="row"><label for="cp_comment_points"><?php _e('Number of points for each comment', 'cp'); ?>:</label>
			</th>
			<td valign="middle" width="190"><input type="text" id="cp_comment_points" name="cp_comment_points" value="<?php echo get_option('cp_comment_points'); ?>" size="20" /></td>
			<td><input type="button" onclick="document.getElementById('cp_comment_points').value='0'" value="<?php _e('Do not add points for comments', 'cp'); ?>" class="button" /></td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="cp_comment_points"><?php _e('Number of points subtracted for deleting each comment','cp'); ?>:</label>
			</th>
			<td valign="middle"><input type="text" id="cp_del_comment_points" name="cp_del_comment_points" value="<?php echo get_option('cp_del_comment_points'); ?>" size="20" /></td>
			<td><input type="button" onclick="document.getElementById('cp_del_comment_points').value='0'" value="<?php _e('Do not subtract points on comment deletion','cp'); ?>" class="button" /></td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="cp_comment_points"><?php _e('Number of points for each post','cp'); ?>:</label>
			</th>
			<td valign="middle"><input type="text" id="cp_post_points" name="cp_post_points" value="<?php echo get_option('cp_post_points'); ?>" size="20" /></td>
			<td><input type="button" onclick="document.getElementById('cp_post_points').value='0'" value="<?php _e('Do not add points for new posts','cp'); ?>" class="button" /></td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="cp_comment_points"><?php _e('Number of points new members get for registering','cp'); ?>:</label>
			</th>
			<td valign="middle"><input type="text" id="cp_reg_points" name="cp_reg_points" value="<?php echo get_option('cp_reg_points'); ?>" size="20" /></td>
			<td><input type="button" onclick="document.getElementById('cp_reg_points').value='0'" value="<?php _e('Do not add points for new registrations','cp'); ?>" class="button" /></td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="cp_daily_points"><?php _e('Number of points for "daily" login','cp'); ?>:</label>
			</th>
			<td valign="middle"><input type="text" id="cp_daily_points" name="cp_daily_points" value="<?php echo get_option('cp_daily_points'); ?>" size="20" /></td>
			<td><input type="button" onclick="document.getElementById('cp_daily_points').value='0';document.getElementById('cp_daily_points_time').value='0';document.getElementById('cp_dptsel').selectedIndex=1;" value="<?php _e('Do not add points for daily login','cp'); ?>" class="button" /></td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="cp_donation"><?php _e('Enable user-to-user donations','cp'); ?>:</label>
			</th>
			<td valign="middle"><input id="cp_donation" name="cp_donation" type="checkbox" value="1" <?php echo $cp_donation_checked; ?> /></td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="cp_ranks_enabled"><?php _e('Enable ranks', 'cp'); ?>:</label>
			</th>
			<td valign="middle"><input id="cp_ranks_enabled" name="cp_ranks_enabled" type="checkbox" value="1" <?php echo $cp_ranks_enabled_checked; ?> /></td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="cp_logs_enabled"><?php _e('Enable logging', 'cp'); ?>:</label>
			</th>
			<td valign="middle"><input id="cp_logs_enabled" name="cp_logs_enabled" type="checkbox" value="1" <?php echo $cp_logs_enabled_checked; ?> onclick="if(!this.checked){  if(!confirm('<?php _e('Note: Turning off the logging feature would allow your users to get points by just updating posts.', 'cp'); ?>')){this.checked=1;}   }" /></td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="cp_mypoints"><?php _e('Enable "My Points" Page', 'cp'); ?>:</label>
			</th>
			<td valign="middle"><input id="cp_mypoints" name="cp_mypoints" type="checkbox" value="1" <?php echo $cp_mypoints_checked; ?> /></td>
		</tr>
	</table>


<pre style="overflow: auto; background:#eeeeee; padding:5px;margin-top:5px;margin-bottom:5px;">php -q <?php echo realpath(dirname(__FILE__).'/cron.php');?>?k=<?php echo get_option('cp_cron_auth_key'); ?>&new=100</pre> </tr>
<div style="font-size:10px;color:#555;margin-bottom:18px;">(<?php _e('Change 100 to the number of points you want to add for each user'); ?>)</div>		
	</table>
	<p class="submit">
		<input type="submit" name="Submit" value="<?php _e('Update Options','cp'); ?>" />
	</p>
</form>
</div>
<script type="text/javascript">
update_daily_1();
</script>
<?php
} ?>