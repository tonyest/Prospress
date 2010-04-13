<?php
/**
 * @package Prospress
 * @author Brent Shepherd
 * @version 0.1
 */

/*
Plugin Name: Prospress Post Adapter
Plugin URI: http://prospress.com
Description: Transforms the WordPress blog post system into a marketplace posting system.
Author: Brent Shepherd
Version: 0.1
Author URI: http://brentshepherd.com/
Copyright (C) 2009 Prospress Pty. Ltd.

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

/**
 * @TODO have these constant declarations occur in the pp-core.php
 */
if ( !defined( 'PP_PLUGIN_DIR' ) )
	define( 'PP_PLUGIN_DIR', WP_PLUGIN_DIR . '/prospress' );
if ( !defined( 'PP_PLUGIN_URL' ) )
	define( 'PP_PLUGIN_URL', WP_PLUGIN_URL . '/prospress' );

if ( !defined( 'PP_POSTS_DIR' ) )
	define( 'PP_POSTS_DIR', PP_PLUGIN_DIR . '/pp-posts-adapter' );
if ( !defined( 'PP_POSTS_URL' ) )
	define( 'PP_POSTS_URL', PP_PLUGIN_URL . '/pp-posts-adapter' );

/**
 * Declare view files as constants
 */
if ( !defined( "PP_POST_OPTIONS"))
	define("PP_POST_OPTIONS", PP_POSTS_DIR . "/pp-post-options.php");

/**
 * Include Sort functions
 */
include( PP_POSTS_DIR . '/pp-sort.php');

/**
 * Adds meta boxes for capturing required marketplace metadata on the new/edit post page.
 * 
 * @uses add_meta_box to add meta boxes on the post page
 * 
 */
function pp_post_custom_meta_boxes() {
	if( function_exists( 'remove_meta_box' )) {
		//remove_meta_box('submitdiv', 'post', 'normal');
		remove_meta_box('postcustom', 'post', 'normal');
		remove_meta_box('trackbacksdiv', 'post', 'normal');
		remove_meta_box('postexcerpt', 'post', 'normal');
		remove_meta_box('revisionsdiv', 'post', 'normal');
	}

	if( function_exists( 'add_meta_box' )) {

		//Custom Taxonomies takes care of this.
		//add_meta_box('pp-post-details', __('Post Details'), 'pp_post_details', 'post', 'normal', 'high' );

		//Moved to bids system class
		//add_meta_box('pp-post-payment-options', __('Payment Options'), 'pp_post_payment_options', 'post', 'normal', 'core' );
		//add_meta_box('pp-post-shipping-options', __('Shipping Options'), 'pp_post_shipping_options', 'post', 'normal', 'core' );
	} else { // For Wordpress prior to 2.5
	  //add_action('dbx_post_advanced', 'myplugin_old_custom_box' );
	  //add_action('dbx_page_advanced', 'myplugin_old_custom_box' );
	}
}
add_action( 'admin_menu', 'pp_post_custom_meta_boxes' );

/**
 * Prints the template containing additional meta fields
 *
 */
function pp_post_details(){
	global $post_ID;

	echo "<p>Add post details.</p>";
	
	do_action( 'post_details_box' );
}

/* When the post is saved, saves our custom data */
function pp_post_save_postdata( $post_id, $post ) {
	global $wpdb;

	/** @TODO validate post input */

	if( wp_is_post_revision( $post_id ) )
		$post_id = wp_is_post_revision( $post_id );

	error_log('*** In pp_post_save_postdata ***');
	//error_log('$_POST = ' . print_r($_POST, true));
	//error_log('$post = ' . print_r($post, true));
	//error_log('$post_id = ' . print_r($post_id, true));

	if ( empty( $_POST ) || 'page' == $_POST['post_type'] ) {
		error_log( 'returning from pp_post_save_postdata' );
		return $post_id;
	} else if ( !current_user_can( 'edit_post', $post_id )) {
		return $post_id;
	} else if ( !isset( $_POST['yye'] ) ){ // Make sure an end date is submitted (not submitted with quick edits etc.)
		error_log( 'returning from pp_post_save_postdata as no yye' );
		return $post_id;
	}

	$yye = $_POST['yye'];
	$mme = $_POST['mme'];
	$dde = $_POST['dde'];
	$hhe = $_POST['hhe'];
	$mne = $_POST['mne'];
	$sse = $_POST['sse'];	
	$yye = ($yye <= 0 ) ? date('Y') : $yye;
	$mme = ($mme <= 0 ) ? date('n') : $mme;
	$dde = ($dde > 31 ) ? 31 : $dde;
	$dde = ($dde <= 0 ) ? date('j') : $dde;
	$hhe = ($hhe > 23 ) ? $hhe -24 : $hhe;
	$mne = ($mne > 59 ) ? $mne -60 : $mne;
	$sse = ($sse > 59 ) ? $sse -60 : $sse;
	$post_end_date = sprintf( "%04d-%02d-%02d %02d:%02d:%02d", $yye, $mme, $dde, $hhe, $mne, $sse );
	//Take a breath

	//error_log('$post_end_date = ' . $post_end_date);

	//And do it all over again for original end date
	$original_yye = $_POST['hidden_yye'];
	$original_mme = $_POST['hidden_mme'];
	$original_dde = $_POST['hidden_dde'];
	$original_hhe = $_POST['hidden_hhe'];
	$original_mne = $_POST['hidden_mne'];
	$original_sse = $_POST['hidden_sse'];
	$original_yye = ($original_yye <= 0 ) ? date('Y') : $original_yye;
	$original_mme = ($original_mme <= 0 ) ? date('n') : $original_mme;
	$original_dde = ($original_dde > 31 ) ? 31 : $original_dde;
	$original_dde = ($original_dde <= 0 ) ? date('j') : $original_dde;
	$original_hhe = ($original_hhe > 23 ) ? $original_hhe -24 : $original_hhe;
	$original_mne = ($original_mne > 59 ) ? $original_mne -60 : $original_mne;
	$original_sse = ($original_sse > 59 ) ? $original_sse -60 : $original_sse;
	$original_post_end_date = sprintf( "%04d-%02d-%02d %02d:%02d:%02d", $original_yye, $original_mme, $original_dde, $original_hhe, $original_mne, $original_sse );

	error_log('$original_post_end_date = ' . $original_post_end_date);
	
	$now = current_time('mysql');
	$post_end_date_gmt = get_gmt_from_date($post_end_date);
	$original_post_end_date_gmt = get_gmt_from_date($original_post_end_date);
	if( !get_post_meta($post_id, 'post_end_date') || $post_end_date != $original_post_end_date ){
		error_log(' * post_end_date updated * ' );
		update_post_meta($post_id, 'post_end_date', $post_end_date);
		update_post_meta($post_id, 'post_end_date_gmt', $post_end_date_gmt);		
	}

	error_log('$post_end_date = ' . $post_end_date);
	error_log('$now = ' . $now);

	// An extension of the post.php code to set the correct post status to ended, publish, future, draft, pending or private.
	if( $post_end_date <= $now && $_POST['save'] != 'Save Draft'){
		wp_unschedule_event( strtotime( $original_post_end_date_gmt ), 'schedule_end_post', array( 'ID' => $post_id ) );
		pp_end_post( $post_id );
		error_log("end date test set post $post_id should have been unscheduled");
	} else { /*
		if($_POST['save'] == 'Save Draft'){
			$post_status = 'draft';
		} else if($_POST['post_status'] == 'pending'){
			$post_status = 'pending';
		} else if ( isset($_POST['visibility']) && $_POST['visibility'] == 'private' ) { //If private
			$post_status = 'private';
		} else if ( mysql2date('U', $post->post_date_gmt, false) > mysql2date('U', $now, false) ){
			$post_status = 'future';
		} else {
			$post_status = 'publish';
		}
		error_log("post_status set to $post_status");

		if($post_status != $post->post_status)
			$wpdb->update( $wpdb->posts, array('post_status' => $post_status), array('ID' => $post_id) );
		*/
		wp_unschedule_event( strtotime($original_post_end_date_gmt ), 'schedule_end_post', array('ID' => $post_id ) );

		if($post_status != 'draft')
			pp_schedule_end_post( $post_id,  strtotime( $post_end_date_gmt ) );

		do_action('post_end_date_changed', $post_status, $post_end_date);
	}
	error_log( '*** In pp_post_save_postdata: get_post( $post_id )->post_status = ' . get_post( $post_id )->post_status );
}
add_action( 'save_post', 'pp_post_save_postdata', 10, 2 );

/**
 * Schedules a post to end at a given post end time. 
 *
 * @uses $post_id for identifing the post
 * @uses global $wpdb object for update function
 */
function pp_schedule_end_post($post_id, $post_end_time) {
	wp_schedule_single_event( $post_end_time, 'schedule_end_post', array('ID' => $post_id) );
}

/**
 * Changes the status of a given post to 'ended'.
 *
 * @uses $post_id for identifing the post
 * @uses global $wpdb object for update function
 */
function pp_end_post( $post_id ) {
	global $wpdb;

	if( wp_is_post_revision( $post_id ) )
		$post_id = wp_is_post_revision( $post_id );

	$post_status = apply_filters( 'post_end_status', 'ended' );
	
	$wpdb->update( $wpdb->posts, array( 'post_status' => $post_status ), array( 'ID' => $post_id ) );
	error_log( "pp_end_post function called with Arg: $post_id" );
	do_action( 'post_ended' );
}
add_action('schedule_end_post', 'pp_end_post');

/**
 * Unschedule the end of a post.
 *
 * @uses $post_id for identifing the post
 * @uses global $wpdb object for update function
 */
function pp_unschedule_post_end( $post_id ) {
	$next = wp_next_scheduled( 'schedule_end_post', array('ID' => $post_id) );
	wp_unschedule_event( $next, 'schedule_end_post', array('ID' => $post_id) );
	error_log("pp_unschedule_post_end successfully called for post $post_id");
}
// Unschedule end of a post when a post is deleted. 
add_action( 'deleted_post', 'pp_unschedule_post_end' );



//**************************************************************************************************//
// CREATE CUSTOM POST STATUS AND ADD END DATE TO POST SUBMIT META BOX
//**************************************************************************************************//

/**
 * Create an "ended" status to designate to posts upon their completion. 
 *
 * @uses pp_register_ended_status functiion
 * @param object $post
 */
function pp_register_ended_status() {

	register_post_status(
	       'ended',
	       array('label' => _x('Ended Posts', 'post'),
				'label_count' => _n_noop('Ended <span class="count">(%s)</span>', 'Ended <span class="count">(%s)</span>'),
				'show_in_admin_all' => false,
				'show_in_admin_all_list' => false,
				'show_in_admin_status_list' => true,
				'public' => false,
				//'private' => true,
				'publicly_queryable' => false,
				'exclude_from_search' => true,
	       )
	);
}
add_action('init', 'pp_register_ended_status');

/**
 * Display custom Prospress post submit form fields, mainly the 'end date' box.
 *
 * This code is sourced from the edit-form-advanced.php file. Additional code is added for 
 * dealing with 'ended' post status. The HTML has also split from the php code for more
 * readable poetry.
 *
 * @uses global $wpdb to get post meta, including post end time
 * @param object $post
 */
function pp_post_submit_meta_box() {
	global $action, $wpdb, $post;
	
	if( strstr( $_SERVER[ 'REQUEST_URI' ], 'post_type' ) ){
		error_log( "** On add new page for custom post type. Post type is " . get_post_type( $_GET[ 'post' ] ) . " ** " );
		return;
	} elseif ( isset( $_GET[ 'post' ] ) && get_post_type( $_GET[ 'post' ] ) != 'post' ){
		error_log( "** On page of custom post type. Post type is " . get_post_type( $_GET[ 'post' ] ) . " ** " );
		return;
	}

	// translators: Publish box end date format, see http://php.net/date
	$datef = __( 'M j, Y @ G:i' );

	//Set up post end date label
	if ( 'ended' == $post->post_status ) // already finished
		$end_stamp = __('Ended: <b>%1$s</b>');
	else
		$end_stamp = __('End on: <b>%1$s</b>');

	//Set up post end date and time variables
	if ( 0 != $post->ID ) {
		$post_end = get_post_meta( $post->ID, 'post_end_date', true );

		if ( !empty( $post_end ) && '0000-00-00 00:00:00' != $post_end )
			$end_date = date_i18n( $datef, strtotime( $post_end ) );
	}

	if ( !isset( $end_date ) ) {
		$end_date = date_i18n( $datef, strtotime( gmdate( 'Y-m-d H:i:s', ( time() + 604800 + ( get_option( 'gmt_offset' ) * 3600 ) ) ) ) );
	}

?>
	<div class="misc-pub-section curtime misc-pub-section-last">
		<span id="endtimestamp">
		<?php printf($end_stamp, $end_date); ?></span>
		<a href="#edit_endtimestamp" class="edit-endtimestamp hide-if-no-js" tabindex='4'><?php ('ended' != $post->post_status) ? _e('Edit') : _e('Extend'); ?></a>
		<div id="endtimestampdiv" class="hide-if-js">
			<?php touch_end_time(($action == 'edit'),5); ?>
		</div>
	</div><?php
}
add_action('post_submitbox_misc_actions', 'pp_post_submit_meta_box');

/**
 * Copy of the "touch_time" template function for use with end time, instead of start time
 *
 * @since unknown
 *
 * @param unknown_type $edit
 * @param unknown_type $for_post
 * @param unknown_type $tab_index
 * @param unknown_type $multi
 */
function touch_end_time( $edit = 1, $tab_index = 0, $multi = 0 ) {
	global $wp_locale, $post, $comment;

	$post_end_date_gmt = get_post_meta($post->ID, 'post_end_date_gmt', true);
	error_log("post end date gmt = $post_end_date_gmt");

	$edit = ( in_array($post->post_status, array('draft', 'pending') ) && (!$post_end_date_gmt || '0000-00-00 00:00:00' == $post_end_date_gmt ) ) ? false : true;
	error_log(($edit) ? 'true' : 'false');

	$tab_index_attribute = '';
	if ( (int) $tab_index > 0 )
		$tab_index_attribute = " tabindex=\"$tab_index\"";

	$time_adj = time() + (get_option( 'gmt_offset' ) * 3600 );
	$time_adj_end = time() + 604800 + (get_option( 'gmt_offset' ) * 3600 );
	$post_end_date = get_post_meta($post->ID, 'post_end_date', true);
	if(empty($post_end_date))
		$post_end_date = gmdate( 'Y-m-d H:i:s', ( time() + 604800 + ( get_option( 'gmt_offset' ) * 3600 ) ) );
		//$post_end_date = current_time('mysql');

	$dde = ($edit) ? mysql2date( 'd', $post_end_date, false ) : gmdate( 'd', $time_adj_end );
	$mme = ($edit) ? mysql2date( 'm', $post_end_date, false ) : gmdate( 'm', $time_adj_end );
	$yye = ($edit) ? mysql2date( 'Y', $post_end_date, false ) : gmdate( 'Y', $time_adj_end );
	$hhe = ($edit) ? mysql2date( 'H', $post_end_date, false ) : gmdate( 'H', $time_adj_end );
	$mne = ($edit) ? mysql2date( 'i', $post_end_date, false ) : gmdate( 'i', $time_adj_end );
	$sse = ($edit) ? mysql2date( 's', $post_end_date, false ) : gmdate( 's', $time_adj_end );

	$cur_dde = gmdate( 'd', $time_adj );
	$cur_mme = gmdate( 'm', $time_adj );
	$cur_yye = gmdate( 'Y', $time_adj );
	$cur_hhe = gmdate( 'H', $time_adj );
	$cur_mne = gmdate( 'i', $time_adj );
	$cur_sse = gmdate( 's', $time_adj );

	$month = "<select " . ( $multi ? '' : 'id="mme" ' ) . "name=\"mme\"$tab_index_attribute>\n";
	for ( $i = 1; $i < 13; $i = $i +1 ) {
		$month .= "\t\t\t" . '<option value="' . zeroise($i, 2) . '"';
		if ( $i == $mme )
			$month .= ' selected="selected"';
		$month .= '>' . $wp_locale->get_month_abbrev( $wp_locale->get_month( $i ) ) . "</option>\n";
	}
	$month .= '</select>';

	$day = '<input type="text" ' . ( $multi ? '' : 'id="dde" ' ) . 'name="dde" value="' . $dde . '" size="2" maxlength="2"' . $tab_index_attribute . ' autocomplete="off" />';
	$year = '<input type="text" ' . ( $multi ? '' : 'id="yye" ' ) . 'name="yye" value="' . $yye . '" size="4" maxlength="4"' . $tab_index_attribute . ' autocomplete="off" />';
	$hour = '<input type="text" ' . ( $multi ? '' : 'id="hhe" ' ) . 'name="hhe" value="' . $hhe . '" size="2" maxlength="2"' . $tab_index_attribute . ' autocomplete="off" />';
	$minute = '<input type="text" ' . ( $multi ? '' : 'id="mne" ' ) . 'name="mne" value="' . $mne . '" size="2" maxlength="2"' . $tab_index_attribute . ' autocomplete="off" />';
	/* translators: 1: month input, 2: day input, 3: year input, 4: hour input, 5: minute input */
	printf(__('%1$s%2$s, %3$s @ %4$s : %5$s'), $month, $day, $year, $hour, $minute);

	echo '<input type="hidden" id="sse" name="sse" value="' . $sse . '" />';

	if ( $multi ) return;

	echo "\n\n";
	foreach ( array('mme', 'dde', 'yye', 'hhe', 'mne', 'sse') as $timeunit ) {
		echo '<input type="hidden" id="hidden_' . $timeunit . '" name="hidden_' . $timeunit . '" value="' . $$timeunit . '" />' . "\n";
		$cur_timeunit = 'cur_' . $timeunit;
		echo '<input type="hidden" id="'. $cur_timeunit . '" name="'. $cur_timeunit . '" value="' . $$cur_timeunit . '" />' . "\n";
	}
?>

<p>
	<a href="#edit_endtimestamp" class="save-endtimestamp hide-if-no-js button"><?php _e('OK'); ?></a>
	<a href="#edit_endtimestamp" class="cancel-endtimestamp hide-if-no-js"><?php _e('Cancel'); ?></a>
</p>
<?php
}

function pp_posts_admin_head() {
	if( strpos( $_SERVER['REQUEST_URI'], 'post.php' ) !== false || strpos( $_SERVER['REQUEST_URI'], 'post-new.php' ) !== false ) {
		wp_enqueue_style( 'post-adapter',  PP_POSTS_URL . '/post-adapter.css' );
		wp_enqueue_script( 'post-adapter', PP_POSTS_URL . '/post-adapter.dev.js', array('jquery') );
		wp_localize_script( 'post-adapter', 'ppPostL10n', array(
			'endedOn' => __('Ended on:'),
			'endOn' => __('End on:'),
			'end' => __('End'),
			'update' => __('Update'),
			'repost' => __('Repost'),
			));
	}

	if( strpos( $_SERVER['REQUEST_URI'], 'ended' ) !== false ) {
		wp_enqueue_style( 'post-adapter',  PP_POSTS_URL . '/post-ended.css' );
	}
}
add_action('admin_menu', 'pp_posts_admin_head');

//**************************************************************************************************//
// Customise columns on table of posts shown on edit.php
//**************************************************************************************************//
function pp_post_columns( $column_headings ) {

	//error_log('column_headings = ' . print_r( $column_headings, true ) );
	unset( $column_headings[ 'tags' ] );

	if( strpos( $_SERVER['REQUEST_URI'], 'ended' ) !== false ) {
		$column_headings[ 'end_date' ] = __( 'Ended' );
		$column_headings[ 'post_actions' ] = __( 'Action' );
		unset( $column_headings[ 'date' ] );
	} else {
		$column_headings[ 'date' ] = __( 'Date Published' );
		$column_headings[ 'end_date' ] = __( 'Ending' );
	}

	return $column_headings;
}
add_filter( 'manage_posts_columns', 'pp_post_columns' );

function pp_post_columns_custom( $column_name, $post_id ) {
	global $wpdb;
	
	// Need to manually populate $post var. Global $post, for an indeterminable reason, creates a bug, specifically, it contains post_status of "publish"...
	$post = $wpdb->get_row( "SELECT post_status FROM $wpdb->posts WHERE ID = $post_id" );

	if( $column_name == 'end_date' ) {
		$end_date = get_post_meta( $post_id, 'post_end_date', true );
		$end_date_gmt = get_post_meta( $post_id, 'post_end_date_gmt', true );

		if ( '0000-00-00 00:00:00' == $end_date || empty($end_date) ) {
			$t_time = $h_time = __('Not set.');
			$time_diff = 0;
		} else {
			$t_time = get_the_time(__('Y/m/d g:i:s A'));
			$m_time = $end_date;
			$time = mysql2date( 'G', $end_date_gmt );

			$time_diff = time() - $time;

			if ( $time_diff > 0 && $time_diff < 24*60*60 )
				$h_time = sprintf( __('%s ago'), human_time_diff( $time ) );
			else
				$h_time = mysql2date( __( 'g:ia d M Y' ), $m_time );
		}
		echo '<abbr title="' . $t_time . '">' . apply_filters('post_end_date_column_time', $h_time, $post, $column_name) . '</abbr>';
		echo '<br />';
	} else {
		echo '';
	}

	if( $column_name == 'post_actions' ) {
		$actions = apply_filters( 'ended_post_actions', array(), $post_id );
		if( is_array( $actions ) && !empty( $actions ) ){
		?>
			<ul id="ended_actions">
				<li class="base"><?php _e( 'Take action:' ) ?></li>
			<?php foreach( $actions as $action => $attributes )
				//$url = add_query_arg ( 'post', $post_id, $attributes['url'] );
				echo "<li class='ended-action'><a href='" . add_query_arg ( array( 'action' => $action, 'post' => $post_id ) , $attributes['url'] ) . "'>" . $attributes['label'] . "</a></li>";
			 ?>
			</ul>
		<?php
		} else {
			echo '<p>' . __('No action can be taken.') . '</p>';
		}
	}
}
add_action( 'manage_posts_custom_column', 'pp_post_columns_custom', 10, 2 );

//**************************************************************************************************//
// ADD ADMIN MENU FOR POSTS THAT HAVE ENDED
//**************************************************************************************************//
function pp_posts_add_admin_pages() {
	//$page = add_posts_page( __( 'Posts That Have Ended' ), __( 'Ended' ), 1, 'ended', 'pp_posts_ended_admin' );

	if ( strpos( $_SERVER['REQUEST_URI'], 'ended' ) !== false ){
		wp_enqueue_script( 'inline-edit-post' );
	}
}
add_action( 'admin_menu', 'pp_posts_add_admin_pages' );

// @TODO clean up this quick and dirty hack: find a server side way to better style these tables.
function pp_remove_classes() {
	if ( strpos( $_SERVER['REQUEST_URI'], 'edit.php' ) !== false ||  strpos( $_SERVER['REQUEST_URI'], 'ended' ) !== false ) {
		echo '<script type="text/javascript">';
		echo 'jQuery(document).ready( function($) {';
		echo '$("#author").removeClass("column-author");';
		echo '$("#categories").removeClass("column-categories");';
		echo '$("#tags").removeClass("column-tags");';
		echo '});</script>';
	}
}
add_action( 'admin_head', 'pp_remove_classes' );

?>