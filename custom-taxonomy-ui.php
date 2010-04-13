<?php
/*
Plugin Name: Custom Taxonomy UI
Plugin URI: http://webdevstudios.com/support/wordpress-plugins/
Description: Admin panel for creating custom taxonomies
Author: Leonard's Ego
Version: 0.3.1
Author URI: http://leonardsego.com/
*/

// Define current version constant
define( 'CPT_VERSION', '0.3.1' );
// Define plugin URL constant
define( 'CPT_URL', get_option('siteurl') . '/wp-admin/options-general.php?page=custom-post-type-ui/custom-post-type-ui.php' );
$CPT_URL = curPageURL();

function cpt_plugin_menu() {
	$base_title = 'Taxonomies';
	//$base_page = __FILE__;
	$base_page = "custom_taxonomy";
	//create custom post type menu
	add_menu_page( $base_title, $base_title, 'administrator', $base_page, '' );

	//create submenu items
	add_submenu_page($base_page, 'Add New', 'Add New', 'administrator', $base_page, 'cpt_add_new');
	add_submenu_page($base_page, 'Manage Taxonomies', 'Manage Taxonomies', 'administrator', $base_page.'_manage', 'cpt_manage_taxonomies');
}
// create custom plugin settings menu
add_action('admin_menu', 'cpt_plugin_menu');

function cpt_create_custom_taxonomies() {
	//register custom taxonomies
	$cpt_tax_types = get_option('cpt_custom_tax_types');

	//error_log("cpt_create_custom_taxonomies, cpt_tax_types = " . print_r($cpt_tax_types, true));
	
	//check if option value is an Array before proceeding
	if (is_array($cpt_tax_types)) {
		foreach ($cpt_tax_types as $cpt_tax_type) {
	
			if (!$cpt_tax_type[1]) {
				$cpt_label = esc_html($cpt_tax_type[0]);
			}else{
				$cpt_label = esc_html($cpt_tax_type[1]);
			}
			
			//check if singular label was filled out
			if (!$cpt_tax_type[2]) {
				$cpt_singular_label = esc_html($cpt_tax_type[0]);
			}else{
				$cpt_singular_label = esc_html($cpt_tax_type[2]);
			}

			//register our custom taxonomies
			register_taxonomy( $cpt_tax_type[0], 
				$cpt_tax_type[3], 
				array( 'hierarchical' => $cpt_tax_type[4], 
				'label' => $cpt_label, 
				'show_ui' => $cpt_tax_type[5], 
				'query_var' => $cpt_tax_type[6], 
				'rewrite' => $cpt_tax_type[7], 
				'singular_label' => $cpt_singular_label 
			) );

		}	
	}
}
//process custom taxonomies if they exist
add_action( 'init', 'cpt_create_custom_taxonomies', 0 );

//delete custom post type or custom taxonomy
function cpt_delete_post_type() {
	global $CPT_URL;
	
	//check if we are deleting a custom taxonomy
	if(isset($_GET['deltax'])) {
		check_admin_referer('cpt_delete_tax');

		$delType = intval($_GET['deltax']);
		$cpt_taxonomies = get_option('cpt_custom_tax_types');

		unset($cpt_taxonomies[$delType]);

		$cpt_taxonomies = array_values($cpt_taxonomies);

		update_option('cpt_custom_tax_types', $cpt_taxonomies);

		if (isset($_GET['return'])) {
			$RETURN_URL = cpt_check_return(esc_attr($_GET['return']));
		}else{
			$RETURN_URL = $CPT_URL;
		}

		wp_redirect($RETURN_URL .'&cpt_msg=del');
	}
	
}
//call delete post function
add_action( 'admin_init', 'cpt_delete_post_type' );

function cpt_register_settings() {
	global $cpt_error, $CPT_URL;

	if (isset($_POST['cpt_edit_tax'])) {
		//edit a custom taxonomy
		check_admin_referer('cpt_add_custom_taxonomy');

		//custom taxonomy to edit
		$cpt_edit = intval($_POST['cpt_edit_tax']);

		//edit the custom taxonomy
		$cpt_form_fields = $_POST['cpt_custom_tax'];

		//load custom posts saved in WP
		$cpt_options = get_option('cpt_custom_tax_types');

		if (is_array($cpt_options)) {

			unset($cpt_options[$cpt_edit]);

			//insert new custom post type into the array
			array_push($cpt_options, $cpt_form_fields);

			$cpt_options = array_values($cpt_options);

			//save custom post types
			update_option('cpt_custom_tax_types', $cpt_options);

			if (isset($_GET['return'])) {
				$RETURN_URL = cpt_check_return(esc_attr($_GET['return']));
			}else{
				$RETURN_URL = $CPT_URL;
			}
			wp_redirect($RETURN_URL);
		}
	}elseif(isset($_POST['cpt_add_tax'])) {
		//create new custom taxonomy
		check_admin_referer('cpt_add_custom_taxonomy');

		//retrieve new custom taxonomy values
		$cpt_form_fields = $_POST['cpt_custom_tax'];
		//error_log('*** $_POST = ' . print_r($_POST, true));
		$cpt_form_fields[3] = 'post'; 	// Object type
		$cpt_form_fields[4] = true; 	// Hierarchical
		$cpt_form_fields[5] = true; 	// Show UI
		$cpt_form_fields[6] = $cpt_form_fields[0]; 	// Query Var
		$cpt_form_fields[7] = $cpt_form_fields[0]; 	// Rewrite
		//error_log('*** $cpt_form_fields = ' . print_r($cpt_form_fields, true));

		//verify required fields are filled out
		if ( empty( $cpt_form_fields[0] ) ) {
			$RETURN_URL = ( isset( $_GET['return'] ) ) ? cpt_check_return( esc_attr( $_GET['return'] ) ) : $RETURN_URL = $CPT_URL;
			wp_redirect( $RETURN_URL .'&cpt_error=2' );
			exit();
		}

		//load custom taxonomies saved in WP
		$cpt_options = get_option('cpt_custom_tax_types');

		//check if option exists, if not create an array for it
		if (!is_array($cpt_options)) {
			$cpt_options = array();
		}

		//insert new custom taxonomy into the array
		array_push($cpt_options, $cpt_form_fields);

		//save new custom taxonomy array in the CPT option
		update_option('cpt_custom_tax_types', $cpt_options);

		if (isset($_GET['return'])) {
			$RETURN_URL = cpt_check_return(esc_attr($_GET['return']));
		}else{
			$RETURN_URL = $CPT_URL;
		}

		wp_redirect($RETURN_URL .'&cpt_msg=2');
		
	}
}
//call register settings function
add_action( 'admin_init', 'cpt_register_settings' );

//manage custom taxonomies page
function cpt_manage_taxonomies() {
	global $CPT_URL;
	
	$MANAGE_URL = esc_url(get_option('siteurl').'/wp-admin/admin.php?page=custom-post-type-ui/custom-post-type-ui.php_cpt_add_new');
?>
<div class="wrap">
<?php
//check for success/error messages
if (isset($_GET['cpt_msg']) && $_GET['cpt_msg']=='del') { ?>
    <div id="message" class="updated">
    	<?php _e('Custom taxonomy deleted successfully', 'cpt-plugin'); ?>
    </div>
    <?php
}
?>
<h2><?php _e('Manage Custom Taxonomies', 'cpt-plugin') ?></h2>
<p><?php _e('Deleting custom taxonomies does <strong>NOT</strong> delete any content added to those taxonomies.  You can easily recreate your taxonomies and the content will still exist.', 'cpt-plugin') ?></p>
<?php
	$cpt_tax_types = get_option('cpt_custom_tax_types');

	if (is_array($cpt_tax_types)) {
		?>
        <table width="100%">
        	<tr>
            	<td><strong><?php _e('Action', 'cpt-plugin');?></strong></td>
            	<td><strong><?php _e('Name', 'cpt-plugin');?></strong></td>
                <td><strong><?php _e('Label', 'cpt-plugin');?></strong></td>
                <td><strong><?php _e('Singular Label', 'cpt-plugin');?></strong></td>
                <td><strong><?php _e('Post Type Name', 'cpt-plugin');?></strong></td>
                <td><strong><?php _e('Hierarchical', 'cpt-plugin');?></strong></td>
                <td><strong><?php _e('Show UI', 'cpt-plugin');?></strong></td>
                <td><strong><?php _e('Query Var', 'cpt-plugin');?></strong></td>
                <td><strong><?php _e('Rewrite', 'cpt-plugin');?></strong></td>
            </tr>
        <?php
		$thecounter=0;
		foreach ($cpt_tax_types as $cpt_tax_type) {
			$del_url = $CPT_URL .'&deltax=' .$thecounter .'&return=tax';
			$del_url = ( function_exists('wp_nonce_url') ) ? wp_nonce_url($del_url, 'cpt_delete_tax') : $del_url;

			$edit_url = $MANAGE_URL .'&edittax=' .$thecounter .'&return=tax';
			$edit_url = ( function_exists('wp_nonce_url') ) ? wp_nonce_url($edit_url, 'cpt_edit_tax') : $edit_url;
		?>
        	<tr>
            	<td valign="top"><a href="<?php echo $del_url; ?>">Delete</a> / <a href="<?php echo $edit_url; ?>">Edit</a></td>
            	<td valign="top"><?php echo stripslashes($cpt_tax_type[0]); ?></td>
                <td valign="top"><?php echo stripslashes($cpt_tax_type[1]); ?></td>
                <td valign="top"><?php echo stripslashes($cpt_tax_type[2]); ?></td>
                <td valign="top"><?php echo stripslashes($cpt_tax_type[3]); ?></td>
                <td valign="top"><?php echo disp_boolean($cpt_tax_type[4]); ?></td>
                <td valign="top"><?php echo disp_boolean($cpt_tax_type[5]); ?></td>
                <td valign="top"><?php echo disp_boolean($cpt_tax_type[6]); ?></td>
                <td valign="top"><?php echo disp_boolean($cpt_tax_type[7]); ?></td>
            </tr>
            <tr>
            	<td colspan="11"><hr /></td>
            </tr>
		<?php
		$thecounter++;
		}
		?></table>
		</div>
		<?php
	}
}

//add new custom post type / taxonomy page
function cpt_add_new() {
	global $cpt_error, $CPT_URL;

	//load custom posts saved in WP
	$cpt_options = get_option('cpt_custom_tax_types');

	if (isset($_GET['return'])) {
		$RETURN_URL = cpt_check_return(esc_attr($_GET['return']));
	}else{
		$RETURN_URL = $CPT_URL;
	}

	if (isset($_GET['edittax']) && !isset($_GET['cpt_edit'])) {
		check_admin_referer('cpt_edit_tax');

		//get post type to edit
		$editTax = intval($_GET['edittax']);

		//load custom post type values to edit
		$cpt_tax_name = $cpt_options[$editTax][0];
		$cpt_tax_label = $cpt_options[$editTax][1];
		$cpt_singular_label = $cpt_options[$editTax][2];

		$cpt_tax_submit_name = __( 'Edit Taxonomy' );
	}else{
		$cpt_tax_submit_name = __( 'Create Taxonomy' );
	}
?>
<div class="wrap">
<?php //check for success/error messages
	if (isset($_GET['cpt_msg']) && $_GET['cpt_msg']==2) { ?>
	    <div id="message" class="updated">
	    	<?php _e('Custom taxonomy created successfully' ); ?>
	    </div>
	    <?php
	} elseif (isset($_GET['cpt_error']) ){
		echo '<div class="error">';
		if ( $_GET['cpt_error'] == 2 ) {
			_e('Taxonomy name is a required field.' );
		} elseif ( $_GET['cpt_error'] == 3 ) {
			_e('Object type is a required field.' );
		}
		echo '</div>';
	}
?>
<table border="0" cellspacing="10">
	<tr>
        <td valign="top">
			<h2><?php echo $cpt_tax_submit_name; ?></a></h2>
        	<p><?php _e('if you are unfamiliar with the options below only fill out the <strong>Taxonomy Name</strong> and <strong>Object Type</strong> fields.  The other settings are set to the most common defaults for custom taxonomies.', 'cpt-plugin');?></p>
            <form method="post" action="<?php echo $RETURN_URL; ?>">
                <?php if ( function_exists('wp_nonce_field') )
                    wp_nonce_field('cpt_add_custom_taxonomy'); ?>
                <?php if (isset($_GET['edittax'])) { ?>
                <input type="hidden" name="cpt_edit_tax" value="<?php echo $editTax; ?>" />
                <?php } ?>
                <table class="form-table">
					<tr valign="top">
						<th scope="row"><?php _e('Taxonomy Name', 'cpt-plugin') ?> <span style="color:red;">*</span></th>
						<td><input type="text" name="cpt_custom_tax[]" tabindex="21" value="<?php if (isset($cpt_tax_name)) { echo esc_attr($cpt_tax_name); } ?>" /> <a href="#" title="The taxonomy name.  Used to retrieve custom taxonomy content.  Should be short and sweet" style="cursor: help;">?</a> (e.g. actors)</td>
					</tr>

					<tr valign="top">
						<th scope="row"><?php _e('Label', 'cpt-plugin') ?></th>
						<td><input type="text" name="cpt_custom_tax[]" tabindex="22" value="<?php if (isset($cpt_tax_label)) { echo esc_attr($cpt_tax_label); } ?>" /> <a href="#" title="Taxonomy label.  Used in the admin menu for displaying custom taxonomy." style="cursor: help;">?</a> (e.g. Actors)</td>
					</tr>

					<tr valign="top">
						<th scope="row"><?php _e('Singular Label', 'cpt-plugin') ?></th>
						<td><input type="text" name="cpt_custom_tax[]" tabindex="23" value="<?php if (isset($cpt_singular_label)) { echo esc_attr($cpt_singular_label); } ?>" /> <a href="#" title="Taxonomy Singular label.  Used in WordPress when a singular label is needed." style="cursor: help;">?</a> (e.g. Actor)</td>
					</tr>
				</table>

				<p class="submit">
					<input type="submit" class="button-primary" tabindex="29" name="cpt_add_tax" value="<?php echo $cpt_tax_submit_name; ?>" />
				</p>
            </form>
        </td>
	</tr>
</table>
</div>
<?php 
}

function cpt_check_return($return) {
	global $CPT_URL;

	if($return=='tax'){
		return esc_url(get_option('siteurl').'/wp-admin/admin.php?page=custom-post-type-ui/custom-post-type-ui.php_cpt_manage_taxonomies');
	}elseif($return=='add') {
		return esc_url(get_option('siteurl').'/wp-admin/admin.php?page=custom-post-type-ui/custom-post-type-ui.php_cpt_add_new');
	}else{
		return $CPT_URL;
	}
}

function disp_boolean($booText) {
	if ($booText == '0') {
		return 'false';
	}else{
		return 'true';
	}
}

function curPageURL() {
 $pageURL = 'http';
 if (!empty($_SERVER['HTTPS'])) {$pageURL .= "s";}  
 $pageURL .= "://";
 if ($_SERVER["SERVER_PORT"] != "80") {
  $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
 } else {
  $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
 }
 return $pageURL;
}
?>