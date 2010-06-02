<?php
/**
 * Prospress Posts
 *
 * Adds a marketplace posting system along side WordPress.
 *
 * @package Prospress
 * @author Brent Shepherd
 * @version 0.1
 */

/**
 * @TODO have these constant declarations occur in the pp-core.php
 */
if ( !defined( 'PP_PLUGIN_DIR' ) )
	define( 'PP_PLUGIN_DIR', WP_PLUGIN_DIR . '/prospress' );
if ( !defined( 'PP_PLUGIN_URL' ) )
	define( 'PP_PLUGIN_URL', WP_PLUGIN_URL . '/prospress' );

if ( !defined( 'PP_POSTS_DIR' ) )
	define( 'PP_POSTS_DIR', PP_PLUGIN_DIR . '/pp-posts' );
if ( !defined( 'PP_POSTS_URL' ) )
	define( 'PP_POSTS_URL', PP_PLUGIN_URL . '/pp-posts' );

/**
 * Include Custom Post Type
 */
include( PP_POSTS_DIR . '/pp-custom-post-type.php');

/**
 * Include Sort widget
 */
include( PP_POSTS_DIR . '/pp-sort.php');

/**
 * Include Template Tags
 */
include( PP_POSTS_DIR . '/pp-posts-templatetags.php');

/**
 * Include Custom Taxonomy Component
 */
include( PP_POSTS_DIR . '/pp-custom-taxonomy.php');

global $market_system;

function pp_posts_install(){
	global $wpdb, $market_system, $wp_rewrite;

	$wp_rewrite->flush_rules(false);

	error_log('*** in pp_posts_install ***');

	// Create a page to be used as the index for Prospress posts
	if( !$wpdb->get_var( "SELECT ID FROM $wpdb->posts WHERE post_name = '" . $market_system->name() . "'" ) ){
		$index_page = array();
		$index_page['post_title'] = $market_system->display_name();
		$index_page['post_name'] = $market_system->name();
		$index_page['post_status'] = 'publish';
		$index_page['post_content'] = __( 'This is the index for your ' . $market_system->display_name() . '.' );
		$index_page['post_type'] = 'page';

		error_log('Creating page = ' . print_r( $index_page, true ) );

		wp_insert_post( $index_page );
	}

	pp_add_sidebars_widgets();
}
//register_activation_hook( __FILE__, 'pp_posts_install' );
add_action( 'pp_activation', 'pp_posts_install' );

/* When the post is saved, saves our custom data */
function pp_post_save_postdata( $post_id, $post ) {
	global $wpdb;

	/** @TODO validate post input */

	if( wp_is_post_revision( $post_id ) )
		$post_id = wp_is_post_revision( $post_id );

	if ( empty( $_POST ) || 'page' == $_POST['post_type'] ) {
		return $post_id;
	} else if ( !current_user_can( 'edit_post', $post_id )) {
		return $post_id;
	} else if ( !isset( $_POST['yye'] ) ){ // Make sure an end date is submitted (not submitted with quick edits etc.)
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

	$now_gmt = current_time( 'mysql', true ); // get current GMT
	$post_end_date_gmt = get_gmt_from_date( $post_end_date );
	$original_post_end_date_gmt = get_post_end_time( $post_id, 'mysql' );

	if( !$original_post_end_date_gmt || $post_end_date_gmt != $original_post_end_date_gmt ){
		update_post_meta( $post_id, 'post_end_date', $post_end_date );
		update_post_meta( $post_id, 'post_end_date_gmt', $post_end_date_gmt);		
	}

	if( $post_end_date_gmt <= $now_gmt && $_POST['save'] != 'Save Draft'){
		wp_unschedule_event( strtotime( $original_post_end_date_gmt ), 'schedule_end_post', array( 'ID' => $post_id ) );
		pp_end_post( $post_id );
	} else {
		wp_unschedule_event( strtotime( $original_post_end_date_gmt ), 'schedule_end_post', array( 'ID' => $post_id ) );

		if($post_status != 'draft'){
			pp_schedule_end_post( $post_id, strtotime( $post_end_date_gmt ) );
			do_action( 'publish_end_date_change', $post_status, $post_end_date );
		}
	}
}
add_action( 'save_post', 'pp_post_save_postdata', 10, 2 );

/**
 * Schedules a post to end at a given post end time. 
 *
 * @param post_id for identifing the post
 * @param post_end_time_gmt a unix time stamp of the gmt date/time the post should end
 * @uses global $wpdb object for update function
 */
function pp_schedule_end_post( $post_id, $post_end_time_gmt ) {
	wp_schedule_single_event( $post_end_time_gmt, 'schedule_end_post', array( 'ID' => $post_id ) );
}

/**
 * Changes the status of a given post to 'completed'. This function is added to the
 * schedule_end_post hook.
 *
 * @uses $post_id for identifing the post
 * @uses global $wpdb object for update function
 */
function pp_end_post( $post_id ) {
	global $wpdb;

	if( wp_is_post_revision( $post_id ) )
		$post_id = wp_is_post_revision( $post_id );

	$post_status = apply_filters( 'post_end_status', 'completed' );

	$wpdb->update( $wpdb->posts, array( 'post_status' => $post_status ), array( 'ID' => $post_id ) );
	do_action( 'post_completed' );
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
}
// Unschedule end of a post when a post is deleted. 
add_action( 'deleted_post', 'pp_unschedule_post_end' );


//**************************************************************************************************//
// CREATE CUSTOM POST STATUS AND ADD END DATE TO POST SUBMIT META BOX
//**************************************************************************************************//

/**
 * Create a "completed" status to designate to posts upon their completion. 
 *
 * @uses pp_register_completed_status functiion
 * @param object $post
 */
function pp_register_completed_status() {
	register_post_status(
	       'completed',
	       array('label' => _x('Completed Posts', 'post'),
				'label_count' => _n_noop('Completed <span class="count">(%s)</span>', 'Completed <span class="count">(%s)</span>'),
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
add_action('init', 'pp_register_completed_status');

/**
 * Display custom Prospress post submit form fields, mainly the 'end date' box.
 *
 * This code is sourced from the edit-form-advanced.php file. Additional code is added for 
 * dealing with 'completed' post status. The HTML has also split from the php code for more
 * readable poetry.
 *
 * @uses global $wpdb to get post meta, including post end time
 * @param object $post
 */
function pp_post_submit_meta_box() {
	global $action, $wpdb, $post, $market_system;

	if( !is_pp_post_admin_page() )
		return;

	$datef = __( 'M j, Y @ G:i' );

	//Set up post end date label
	if ( 'completed' == $post->post_status ) // already finished
		$end_stamp = __('Ended: <b>%1$s</b>', 'prospress' );
	else
		$end_stamp = __('End on: <b>%1$s</b>', 'prospress' );

	//Set up post end date and time variables
	if ( 0 != $post->ID ) {
		$post_end = get_post_end_time( $post->ID, 'mysql', false );

		if ( !empty( $post_end ) && '0000-00-00 00:00:00' != $post_end )
			$end_date = date_i18n( $datef, strtotime( $post_end ) );
	}

	// Default to one week if post end date is not set
	if ( !isset( $end_date ) ) {
		$end_date = date_i18n( $datef, strtotime( gmdate( 'Y-m-d H:i:s', ( time() + 604800 + ( get_option( 'gmt_offset' ) * 3600 ) ) ) ) );
	}
?>
	<div class="misc-pub-section curtime misc-pub-section-last">
		<span id="endtimestamp">
		<?php printf($end_stamp, $end_date); ?></span>
		<a href="#edit_endtimestamp" class="edit-endtimestamp hide-if-no-js" tabindex='4'><?php ('completed' != $post->post_status) ? _e('Edit', 'prospress' ) : _e('Extend', 'prospress' ); ?></a>
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

	//error_log('post = ' . print_r($post,true));
	$post_end_date_gmt = get_post_end_time( $post->ID, 'mysql' );

	$edit = ( in_array($post->post_status, array('draft', 'pending') ) && (!$post_end_date_gmt || '0000-00-00 00:00:00' == $post_end_date_gmt ) ) ? false : true;

	$tab_index_attribute = '';
	if ( (int) $tab_index > 0 )
		$tab_index_attribute = " tabindex=\"$tab_index\"";

	$time_adj = time() + ( get_option( 'gmt_offset' ) * 3600 );
	$time_adj_end = time() + 604800 + ( get_option( 'gmt_offset' ) * 3600 );

	$post_end_date = get_post_end_time( $post->ID, 'mysql', false );
	if(empty($post_end_date))
		$post_end_date = gmdate( 'Y-m-d H:i:s', ( time() + 604800 + ( get_option( 'gmt_offset' ) * 3600 ) ) );

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
	<a href="#edit_endtimestamp" class="save-endtimestamp hide-if-no-js button"><?php _e('OK', 'prospress' ); ?></a>
	<a href="#edit_endtimestamp" class="cancel-endtimestamp hide-if-no-js"><?php _e('Cancel', 'prospress' ); ?></a>
</p>
<?php
}

function pp_posts_admin_head() {

	if( !is_pp_post_admin_page() )
		return;

	if( strpos( $_SERVER['REQUEST_URI'], 'post.php' ) !== false || strpos( $_SERVER['REQUEST_URI'], 'post-new.php' ) !== false ) {
		wp_enqueue_script( 'prospress-post', PP_POSTS_URL . '/pp-post.dev.js', array('jquery') );
		wp_localize_script( 'prospress-post', 'ppPostL10n', array(
			'endedOn' => __('Ended on:', 'prospress' ),
			'endOn' => __('End on:', 'prospress' ),
			'end' => __('End', 'prospress' ),
			'update' => __('Update', 'prospress' ),
			'repost' => __('Repost', 'prospress' ),
			));
		wp_enqueue_style( 'prospress-post',  PP_POSTS_URL . '/pp-post.css' );
	}

	if ( strpos( $_SERVER['REQUEST_URI'], 'completed' ) !== false ){
		wp_enqueue_script( 'inline-edit-post' );
	}

	// @TODO replace this quick and dirty hack with a server side way to remove styles on these tables.
	if ( strpos( $_SERVER['REQUEST_URI'], 'edit.php' ) !== false ||  strpos( $_SERVER['REQUEST_URI'], 'completed' ) !== false ) {
		echo '<script type="text/javascript">';
		echo 'jQuery(document).ready( function($) {';
		echo '$("#author").removeClass("column-author");';
		echo '$("#categories").removeClass("column-categories");';
		echo '$("#tags").removeClass("column-tags");';
		echo '});</script>';
	}
}
add_action( 'admin_enqueue_scripts', 'pp_posts_admin_head' );

//**************************************************************************************************//
// ADD ADMIN MENU FOR POSTS THAT HAVE ENDED
//**************************************************************************************************//
/*
function pp_posts_add_admin_pages() {
	if ( strpos( $_SERVER['REQUEST_URI'], 'completed' ) !== false ){
		wp_enqueue_script( 'inline-edit-post' );
	}
}
add_action( 'admin_enqueue_scripts', 'pp_posts_add_admin_pages' );

// @TODO replace this quick and dirty hack with a server side way to remove styles on these tables.
function pp_remove_classes() {
	
	if( !is_pp_post_admin_page() )
		return;

	if ( strpos( $_SERVER['REQUEST_URI'], 'edit.php' ) !== false ||  strpos( $_SERVER['REQUEST_URI'], 'completed' ) !== false ) {
		echo '<script type="text/javascript">';
		echo 'jQuery(document).ready( function($) {';
		echo '$("#author").removeClass("column-author");';
		echo '$("#categories").removeClass("column-categories");';
		echo '$("#tags").removeClass("column-tags");';
		echo '});</script>';
	}
}
add_action( 'admin_enqueue_scripts', 'pp_remove_classes' );
*/

//**************************************************************************************************//
// Customise columns on table of posts shown on edit.php
//**************************************************************************************************//
function pp_post_columns( $column_headings ) {
	global $market_system;

	if( !is_pp_post_admin_page() )
		return $column_headings;

	if( strpos( $_SERVER['REQUEST_URI'], 'completed' ) !== false ) {
		$column_headings[ 'end_date' ] = __( 'Ended', 'prospress' );
		$column_headings[ 'post_actions' ] = __( 'Action', 'prospress' );
		unset( $column_headings[ 'date' ] );
	} else {
		$column_headings[ 'date' ] = __( 'Date Published', 'prospress' );
		$column_headings[ 'end_date' ] = __( 'Ending', 'prospress' );
	}

	return $column_headings;
}
add_filter( 'manage_' . $market_system->name() . '_posts_columns', 'pp_post_columns' );

function pp_post_columns_custom( $column_name, $post_id ) {
	global $wpdb;

	// Need to manually populate $post var. Global $post contains post_status of "publish"...
	$post = $wpdb->get_row( "SELECT post_status FROM $wpdb->posts WHERE ID = $post_id" );

	if( $column_name == 'end_date' ) {
		$end_time_gmt = get_post_end_time( $post_id );

		if ( $end_time_gmt == false || empty( $end_time_gmt ) ) {
			$m_time = $human_time = __('Not set.', 'prospress' );
			$time_diff = 0;
		} else {
			$human_time = human_interval( $end_time_gmt - time(), 3 );
			$human_time .= '<br/>' . get_post_end_time( $post_id, 'mysql', false );
		}
		echo '<abbr title="' . $m_time . '">';
		echo apply_filters('post_end_date_column', $human_time, $post_id, $column_name) . '</abbr>';
	}

	if( $column_name == 'post_actions' ) {
		$actions = apply_filters( 'completed_post_actions', array(), $post_id );
		if( is_array( $actions ) && !empty( $actions ) ){?>
			<div class="prospress-actions">
				<ul class="actions-list">
					<li class="base"><?php _e( 'Take action:', 'prospress' ) ?></li>
				<?php foreach( $actions as $action => $attributes )
					echo "<li class='action'><a href='" . add_query_arg ( array( 'action' => $action, 'post' => $post_id ) , $attributes['url'] ) . "'>" . $attributes['label'] . "</a></li>";
				 ?>
				</ul>
			</div>
		<?php
		} else {
			echo '<p>' . __( 'No action can be taken.', 'prospress' ) . '</p>';
		}
	}
}
add_action( 'manage_posts_custom_column', 'pp_post_columns_custom', 10, 2 );


//**************************************************************************************************//
// CUSTOM POST TYPE & TEMPLATE REDIRECTS
//**************************************************************************************************//

function pp_template_redirects() {
	global $post, $market_system;

	error_log('$post = ' . print_r( $post, true ));
	if( $post->post_name == $market_system->name() ){
		
		do_action( 'pp_index_template_redirect' );
		
		if( file_exists( TEMPLATEPATH . '/pp-index.php' ) )
			include( TEMPLATEPATH . '/pp-index.php');
		else
			include( PP_POSTS_DIR . '/pp-index.php');
		exit;

	} elseif ( $post->post_type == $market_system->name() && !isset( $_GET[ 's' ] ) ) {
		
		do_action( 'pp_single_template_redirect' );

		if( file_exists( TEMPLATEPATH . '/pp-single.php' ) )
			include( TEMPLATEPATH . '/pp-single.php');
		else
			include( PP_POSTS_DIR . '/pp-single.php');
		exit;
	}
}
add_action( 'template_redirect', 'pp_template_redirects' );

function pp_add_sidebars_widgets(){
	global $market_system;

	$sidebars_widgets = get_option( 'sidebars_widgets' );

	if( !isset( $sidebars_widgets[ $market_system->name() . '-index-sidebar' ] ) )
		$sidebars_widgets[ $market_system->name() . '-index-sidebar' ] = array();

	$sort_widget = get_option( 'widget_pp-sort' );
	if( empty( $sort_widget ) ){

		$sort_widget[] = array(
							'title' => __( 'Sort by:', 'prospress' ),
							'post-desc' => 'on',
							'post-asc' => 'on',
							'end-asc' => 'on',
							'end-desc' => 'on',
							'price-asc' => 'on',
							'price-desc' => 'on'
							);

		$sort_widget['_multiwidget'] = 1;
		update_option( 'widget_pp-sort',$sort_widget );
		array_push( $sidebars_widgets[ $market_system->name() . '-index-sidebar' ], 'pp-sort-0' );
	}

	$filter_widget = '';
	if( empty( $filter_widget ) ){

		$filter_widget[] = array( 'title' => __( 'Price:', 'prospress' ) );

		$filter_widget['_multiwidget'] = 1;
		update_option( 'widget_bid-filter', $filter_widget );
		array_push( $sidebars_widgets[ $market_system->name() . '-index-sidebar' ], 'bid-filter-0' );
	}

	update_option( 'sidebars_widgets', $sidebars_widgets );
}

function pp_posts_deactivate(){
	global $market_system;

	error_log( '** pp_posts_deactivate called **' );

	delete_option( 'widget_bid-filter' );
	delete_option( 'widget_pp-sort' );
	
	$sidebars_widgets = get_option( 'sidebars_widgets' );

	if( isset( $sidebars_widgets[ $market_system->name() . '-index-sidebar' ] ) ){
		unset( $sidebars_widgets[ $market_system->name() . '-index-sidebar' ] );
		update_option( 'sidebars_widgets', $sidebars_widgets );
	}
	if( isset( $sidebars_widgets[ $market_system->name() . '-single-sidebar' ] ) ){
		unset( $sidebars_widgets[ $market_system->name() . '-single-sidebar' ] );
		update_option( 'sidebars_widgets', $sidebars_widgets );
	}
}
//register_deactivation_hook( __FILE__, 'pp_posts_deactivate' );
add_action( 'pp_deactivation', 'pp_posts_deactivate' );


function pp_add_sidebars(){
	global $market_system;

	register_sidebar( array (
		'name' => $market_system->display_name() . ' ' . __( 'Index Sidebar', 'prospress' ),
		'id' => $market_system->name() . '-index-sidebar',
		'description' => __( "The sidebar on your Prospress posts.", 'prospress' ),
		'before_widget' => '<li id="%1$s" class="widget-container %2$s">',
		'after_widget' => "</li>",
		'before_title' => '<h3 class="widget-title">',
		'after_title' => '</h3>',
	) );
}
add_action( 'init', 'pp_add_sidebars' );

function pp_post_sort_options( $pp_sort_options ){

	$pp_sort_options['post-desc'] = __( 'Time: Newly posted', 'prospress' );
	$pp_sort_options['post-asc'] = __( 'Time: Oldest first', 'prospress' );
	$pp_sort_options['end-asc'] = __( 'Time: Ending soonest', 'prospress' );
	$pp_sort_options['end-desc'] = __( 'Time: Ending latest', 'prospress' );

	return $pp_sort_options;
}
add_filter( 'pp_sort_options', 'pp_post_sort_options' );

function is_pp_post_admin_page(){
	global $market_system, $post;

	if( $_GET[ 'post_type' ] == $market_system->name() || $_GET[ 'post' ] == $market_system->name() || $post->post_type == $market_system->name() ) //get_post_type( $_GET[ 'post' ] ) ==  $market_system->name() )
		return true;
	else
		return false;
}

//Removes the Prospress index page from the search results
function pp_remove_index( $search ){
	global $wpdb, $market_system;

	if ( isset( $_GET['s'] ) ) // only remove post from search results
		$search .= "AND ID NOT IN (SELECT ID FROM $wpdb->posts WHERE post_name = '" . $market_system->name() . "')";

	return $search;
}
add_filter( 'posts_search', 'pp_remove_index' );

// Allow site admin to choose which roles can do what to marketplace posts.
function pp_capabilities_settings_page() { 
	global $wp_roles, $market_system;

	$role_names = $wp_roles->get_names();
	$roles = array();

	foreach ( $role_names as $key => $value ) {
		$roles[ $key] = get_role( $key);
		$roles[ $key]->display_name = $value;
	}
	?>

	<?php wp_nonce_field( 'pp_capabilities_settings' ); ?>
	<div class="prospress-capabilities">
		<h3><?php _e( 'Capabilities', 'prospress' ); ?></h3>
		<p><?php printf( __( 'You can restrict interaction with %s to certain roles. Please choose which roles have the following capabilities:', 'prospress' ), $market_system->display_name() ); ?></p>
		<div class="prospress-capabilitiy create">
			<h4><?php printf( __( "Publish %s", 'prospress' ), $market_system->display_name() ); ?></h4>
			<?php foreach ( $roles as $role ): //if( $role->name == 'administrator' ) continue; ?>
			<?php //error_log( 'role = ' . print_r( $role, true ) ); ?>

			<label for="<?php echo $role->name; ?>-create">
				<input type="checkbox" id="<?php echo $role->name; ?>-publish" name="<?php echo $role->name; ?>-publish"<?php checked( $role->capabilities[ 'publish_prospress_posts' ], 1 ); ?> />
				<?php echo $role->display_name; ?>
			</label>
			<?php endforeach; ?>
		</div>
		<div class="prospress-capability edit">
			<h4><?php printf( __( "Edit %s", 'prospress' ), $market_system->display_name() ); ?></h4>
			<?php foreach ( $roles as $role ): //if( $role->name == 'administrator' ) continue; ?>
			<label for="<?php echo $role->name; ?>-edit">
			  	<input type="checkbox" id="<?php echo $role->name; ?>-edit" name="<?php echo $role->name; ?>-edit"<?php checked( $role->capabilities[ 'edit_prospress_post' ], 1 ); ?> />
				<?php echo $role->display_name; ?>
			</label>
			<?php endforeach; ?>
		</div>
		<div class="prospress-capability edit">
			<h4><?php printf( __( "Edit Other's %s", 'prospress' ), $market_system->display_name() ); ?></h4>
			<?php foreach ( $roles as $role ): //if( $role->name == 'administrator' ) continue; ?>
			<label for="<?php echo $role->name; ?>-edit-others">
				<input type="checkbox" id="<?php echo $role->name; ?>-edit-others" name="<?php echo $role->name; ?>-edit-others"<?php checked( $role->capabilities[ 'edit_others_prospress_posts' ], 1 ); ?> />
				<?php echo $role->display_name; ?>
			</label>
			<?php endforeach; ?>
		</div>
		<div class="prospress-capability delete">
			<h4><?php printf( __( "Delete %s", 'prospress' ), $market_system->display_name() ); ?></h4>
			<?php foreach ( $roles as $role ): //if( $role->name == 'administrator' ) continue; ?>
			<label for="<?php echo $role->name; ?>-delete">
				<input type="checkbox" id="<?php echo $role->name; ?>-delete" name="<?php echo $role->name; ?>-delete"<?php checked( $role->capabilities[ 'delete_prospress_post' ], 1 ) ?> />
				<?php echo $role->display_name; ?>
			</label>
			<?php endforeach; ?>
		</div>
	</div>
<?php
}
add_action( 'pp_core_settings_page', 'pp_capabilities_settings_page' );

// Save settings from capabiltiies page... as they don't need to be stored in the database, they're not added to the whitelist, instead they're added to the appropriate role
function pp_capabilities_whitelist( $whitelist_options ) {
	global $wp_roles, $market_system;

    if ( $_POST['_wpnonce' ] && check_admin_referer( 'pp_capabilities_settings' ) && current_user_can( 'manage_options' ) ){

		$role_names = $wp_roles->get_names();
		$roles = array();

		foreach ( $role_names as $key=>$value ) {
			$roles[ $key ] = get_role( $key );
			$roles[ $key ]->display_name = $value;
		}

		foreach ( $roles as $key => $role ) {

			//if( $role->name == 'administrator' )
			//	continue;

			if ( isset( $_POST[ $key . '-publish' ] )  && $_POST[ $key . '-publish' ] == 'on' ) {
				$role->add_cap( 'publish_prospress_posts' );
			} else {
				$role->remove_cap( 'publish_prospress_posts' );
			}

			if ( isset( $_POST[ $key . '-edit' ] )  && $_POST[ $key . '-edit' ] == 'on' ) {
				$role->add_cap( 'edit_prospress_post' );
				$role->add_cap( 'edit_prospress_posts' );
			} else {
				$role->remove_cap( 'edit_prospress_post' );
				$role->remove_cap( 'edit_prospress_posts' );
			}

			if ( isset( $_POST[ $key . '-edit-others' ] )  && $_POST[ $key . '-edit-others' ] == 'on' ) {
				$role->add_cap( 'edit_others_prospress_posts' );
			} else {
				$role->remove_cap( 'edit_others_prospress_posts' );
	        }

			if ( isset( $_POST[ $key . '-delete' ] )  && $_POST[ $key . '-delete' ] == 'on' ) {
				$role->add_cap( 'delete_prospress_post' );
			} else {
				$role->remove_cap( 'delete_prospress_post' );
			}
		}
    }

	return $whitelist_options;
}
add_filter( 'pp_options_whitelist', 'pp_capabilities_whitelist' );


function pp_posts_uninstall(){
	global $wpdb, $market_system;

	error_log('*** in pp_posts_uninstall ***');

	$index_page_id = $wpdb->get_var( "SELECT ID FROM $wpdb->posts WHERE post_name = '" . $market_system->name() . "'" );

	wp_delete_post( $index_page_id );
}
//register_activation_hook( __FILE__, 'pp_posts_install' );
add_action( 'pp_uninstall', 'pp_posts_uninstall' );
