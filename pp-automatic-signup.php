<?php
/**
 * @package PP-Automatic-Signup
 * @author Brent Shepherd
 * @version 0.2
 */

/*
Plugin Name: WPMU Automatic Signup
Plugin URI: http://prospress.org
Description: Got a WordPress MU site? Your visitors no longer have to be manually added a blog other than the dashboard blog. With this plugin, when your visitors click on a "register" link, they will automatically be assigned a role for that blog.
Author: Brent Shepherd
Version: 0.2
Author URI: http://brentshepherd.com/
*/

/* @TODO: Prospress might require a function that adds a user to ALL blogs/marketplaces.  
 * This is probably best done creating a new meta entry with 'generic' capabillitise/role.  
 * Then having this meta entry checked when logging in.
 */

/* @TODO: Write a function for when an existing user wants to register with a different blog.
 * Should only need to click the register link, can user maybe_add_existing_user_to_blog
 * function or add_existing_user_to_blog function from wpmu-functions.php. 
 */

/**
 * Determines the id of a blog based on the referring URL.
 * 
 * @uses wp_get_referer() to get the referral URL
 * @uses $wpdb to get site's path from site table in database
 * @uses get_blog_id_from_url() to update primary_blog meta
 */
function pp_get_blog_id_from_refer(){
	global $wpdb;
	$ref_blog_url = wp_get_referer();

	$ref_blog_url = explode('//', $ref_blog_url); //strip http:// of https://
	$referrer_array = explode('/', $ref_blog_url[1], 2); //split into domain and path
	$ref_blog_domain = $referrer_array[0];
	$site_path = $wpdb->get_var("SELECT path FROM $wpdb->site WHERE Id=1"); //get site folder path, if any

	if(defined('VHOST') && constant( "VHOST" ) == 'no' ){ //if site is using subfolders, get correct blog path
		$ref_blog_path = '/' . $referrer_array[1]; //get the path
		$referrer_path_array = explode($site_path, $ref_blog_path); //separate site URL from path
		$ref_blog_path = $referrer_path_array[1];
		preg_match('/((?:[a-z][a-z0-9_]*))(\\/)/is', $ref_blog_path, $blog_path_array);
		$ref_blog_path = $blog_path_array[0];
		$ref_blog_path = $site_path . $blog_path_array[0];
	} else $ref_blog_path = $site_path; //else just use site's path

	if(function_exists('get_blog_id_from_url')) //if function exists, get blog id using WPMU function
		return get_blog_id_from_url($ref_blog_domain, $ref_blog_path);

	$ref_blog_id = $wpdb->get_var($wpdb->prepare("SELECT blog_id FROM $wpdb->blogs WHERE domain=%s AND path=%s", strtolower($ref_blog_domain), strtolower($ref_blog_path))); //if function doesn't exist, try to get it manually

	if(!is_null($ref_blog_id)) //if query found a blog id (blog id not NULL)
		return $ref_blog_id;

	return 0; //failed to get blog id
}


/**
 * Outputs blog id in a hidden extra field of the registration form.
 * 
 * @uses pp_get_blog_id_from_refer() to get the id of the referring blog
 */
function pp_show_form_extras(){
		$ref_blog_id = pp_get_blog_id_from_refer();
		//echo "<p>Referral Blog Id: $ref_blog_id </p>";
		echo '<input type="hidden" name="ref_blog_id" value="' . $ref_blog_id . '" />';
}
add_action('signup_extra_fields', 'pp_show_form_extras');


/**
 * Add referring blog id from the signup form in the meta column of the $wpdb-signups table.
 * 
 * @param array $meta. Set in the function this function hooks to. 
 * @return array. The meta array with a new key and value for the referral blog id. 
 */
function pp_signup_user_extender($meta='') {
	if(isset($_POST['ref_blog_id']))
		$meta["ref_blog_id"] = (int) $_POST['ref_blog_id'];
	return $meta;
}
add_filter('add_signup_meta', 'pp_signup_user_extender');


/**
 * After activation, assign the user a role and permissions for the referring blog.
 *
 * @uses add_user_to_blog($blog_id, $user_id, $role) to add user to referring blog
 * @uses get_site_option() to get default role for users on referral blogs and to see if primary blog should be changed
 * @uses update_usermeta() to update primary_blog meta
 * 
 * @param int $user_id. Passed from the function this function hooks to. 
 * @param string $password. Passed from the function this function hooks to. 
 * @param array $meta. Passed from the function this function hooks to. 
 */
function pp_register_user_with_blog($user_id, $password, $meta){

	$ref_blog_id = $meta['ref_blog_id']; //get referral blog ID from signups meta 
	add_user_to_blog($ref_blog_id, $user_id, get_site_option('default_user_role_ref', 'subscriber'));	

	if(get_site_option('change_primary_blog')){
		update_usermeta( $user_id, 'primary_blog', $ref_blog_id );
	}
}
add_action('wpmu_activate_user', 'pp_register_user_with_blog', 10, 3);

// Print Automatic Signup options with the 'Site Admin | Options' menu
function pp_targetted_signup_options(){
	?>
	<h3><?php _e('Automatic Signup Settings') ?></h3> 
	<table class="form-table">

		<tr valign="top">
			<th scope="row"><?php _e("Make Primary") ?></th> 
			<td>
				<input name="change_primary_blog" type="radio" id="change_primary_blog_yes" value='1' <?php echo get_site_option('change_primary_blog') == 1 ? 'checked="checked"' : ''; ?> /> <?php _e('Yes'); ?><br />
				<input name="change_primary_blog" type="radio" id="change_primary_blog_no" value='0' <?php echo get_site_option('change_primary_blog') == 0 ? 'checked="checked"' : ''; ?> /> <?php _e('No'); ?><br />
				<?php _e("Make the referring blog the user's primary blog. By default, a user's primary blog is the dashboard blog.") ?>
			</td> 
		</tr>
		<tr valign="top">
			<th scope="row"><?php _e('User Default Role on Referral Blog') ?></th> 
			<td>
				<select name="default_user_role_ref" id="role_ref">
					<?php
					wp_dropdown_roles( get_site_option( 'default_user_role_ref', 'subscriber' ) );
					?>
				</select>
				<br />
				<?php _e( "The default role for new user on the blog with which they registered." ); ?>
			</td> 
		</tr>
		<tr valign="top">
			<th scope="row"><?php _e("Add New Users to All Blogs") ?></th> 
			<td>
				<input name="add_to_all_blogs" type="radio" id="add_to_all_blogs_yes" value='1' <?php echo get_site_option('add_to_all_blogs') == 1 ? 'checked="checked"' : ''; ?> disabled="disabled" /> <?php _e('Yes (disabled)'); ?><br />
				<input name="add_to_all_blogs" type="radio" id="add_to_all_blogs_no" value='0' <?php echo get_site_option('add_to_all_blogs') == 0 ? 'checked="checked"' : ''; ?> /> <?php _e('No'); ?><br />
				<?php _e("Add new users to all blogs in your WPMU installation.") ?>
			</td> 
		</tr>
	</table>
<?php
}
add_action('wpmu_options', 'pp_targetted_signup_options');


//Update Automatic Signup options on submission of "Options" form
function pp_targetted_signup_options_update(){
	if(isset($_POST['default_user_role_ref']))
		update_site_option('default_user_role_ref', $_POST[ 'default_user_role_ref' ]);
	if(isset($_POST['change_primary_blog']))
		update_site_option('change_primary_blog', $_POST['change_primary_blog'] );
	if(isset($_POST['add_to_all_blogs']))
		update_site_option('add_to_all_blogs', $_POST['add_to_all_blogs'] );
}
add_action('update_wpmu_options', 'pp_targetted_signup_options_update');


//Create and set default values for Automatic Signup options on plugin activation
function pp_auto_signup_activate() {
	add_site_option('default_user_role_ref', get_site_option('default_user_role', 'subscriber'));
	add_site_option('change_primary_blog', 0);
	add_site_option('add_to_all_blogs', 0);
}
register_activation_hook( __FILE__, 'pp_auto_signup_activate');

?>