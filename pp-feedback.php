<?php
/**
 * Prospress Feedback
 *
 * Leave feedback for other users on your prospress marketplace. 
 *
 * @package Prospress
 * @author Brent Shepherd
 * @version 0.1
 */

if ( !defined( 'PP_FEEDBACK_DB_VERSION' ))
	define ( 'PP_FEEDBACK_DB_VERSION', '0015' );
if ( !defined( 'PP_FEEDBACK_DIR' ))
	define( 'PP_FEEDBACK_DIR', PP_PLUGIN_DIR . '/pp-feedback' );
if ( !defined( 'PP_FEEDBACK_URL' ))
	define( 'PP_FEEDBACK_URL', PP_PLUGIN_URL . '/pp-feedback' );


require_once( PP_FEEDBACK_DIR . '/pp-feedback-functions.php' );

require_once( PP_FEEDBACK_DIR . '/pp-feedback-templatetags.php' );

include_once( PP_FEEDBACK_DIR . '/pp-feedback-widgets.php' );

include_once( PP_FEEDBACK_DIR . '/pp-feedback-dashboard-widget.php' );

global $wpdb;

if ( !isset( $wpdb->feedback ) || empty( $wpdb->feedback ) )
	$wpdb->feedback = $wpdb->base_prefix . 'feedback';
if ( !isset( $wpdb->feedbackmeta ) || empty( $wpdb->feedbackmeta ) )
	$wpdb->feedbackmeta = $wpdb->base_prefix . 'feedbackmeta';


/**
 * To save updating/installing the feedback tables when they already exist and are up-to-date, check 
 * the current feedback database version exists and is not of a prior version.
 * 
 * @uses pp_feedback_install to create the database table if it is not up to date
 **/
function pp_feedback_maybe_install() {
	global $wpdb;

	if ( !current_user_can( 'edit_plugins' ) )
		return false;

	if ( !get_site_option( 'pp_feedback_db_version' ) || get_site_option( 'pp_feedback_db_version' ) < PP_FEEDBACK_DB_VERSION )
		pp_feedback_install();
}
add_action( 'pp_activation', 'pp_feedback_maybe_install' );


/**
 * Set ups the feedback system by creates tables, adding options and setting sensible defaults.
 * 
 * @uses dbDelta( $sql) to execute the sql query for creating/updating tables
 **/
function pp_feedback_install() {
	global $wpdb;

	if ( !empty( $wpdb->charset) )
		$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";

	$wpdb->feedback = $wpdb->base_prefix . 'feedback';

	$sql[] = "CREATE TABLE {$wpdb->feedback} (
		  		feedback_id bigint(20) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
		  		for_user_id bigint(20) unsigned NOT NULL,
		  		from_user_id bigint(20) unsigned NOT NULL,
	  			role varchar(20) NOT NULL,
			  	feedback_score tinyint(1) NOT NULL,
			  	feedback_comment varchar(255) NOT NULL,
		  		feedback_status varchar(20) NOT NULL,
			  	feedback_date datetime NOT NULL,
			  	feedback_date_gmt datetime NOT NULL,
	  			post_id bigint(20) unsigned NOT NULL,
				blog_id bigint(20) unsigned NOT NULL default '0',
			    KEY for_user_id (for_user_id),
			    KEY from_user_id (from_user_id),
			    KEY post_id (post_id)
			   ) {$charset_collate};";

	$wpdb->feedbackmeta = $wpdb->base_prefix . 'feedbackmeta';

	$sql[] = "CREATE TABLE {$wpdb->feedbackmeta} (
				meta_id bigint(20) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
				feedback_id bigint(20) unsigned NOT NULL,
				meta_key varchar(255) NOT NULL,
				meta_value longtext NOT NULL,
			    KEY feedback_id (feedback_id),
			    KEY meta_key (meta_key)
			   ) {$charset_collate};";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );

	update_site_option( 'pp_feedback_db_version', PP_FEEDBACK_DB_VERSION );
	update_option( 'edit_feedback', '' );
}


/**
 * Adds the feedback 
 **/
function pp_add_feedback_admin_pages() {
	if ( function_exists( 'add_submenu_page' ) )
		add_users_page( 'Feedback', 'Feedback', 'read', 'feedback', 'pp_feedback_controller' );
}
add_action( 'admin_menu', 'pp_add_feedback_admin_pages' );


/** 
 * Enqueues scripts and styles to the head of feedback admin pages.
 */
function pp_feedback_admin_head() {

	if( strpos( $_SERVER[ 'REQUEST_URI' ], 'wp-admin/index.php' ) !== false || preg_match ( '/wp-admin\/$/', $_SERVER[ 'REQUEST_URI' ] ) )
		wp_enqueue_style( "prospress-feedback", PP_FEEDBACK_URL . "/pp-feedback.css" );
}
add_action( 'admin_menu', 'pp_feedback_admin_head' );


/** 
 * Adds feedback history column headings to the built in print_column_headers function for the feedback admin page. 
 *
 * @see get_column_headers()
 */
function pp_feedback_columns_admin(){

 	if( strpos( $_SERVER[ 'REQUEST_URI' ], 'given' ) !== false ) {
		$feedback_columns[ 'for_user_id' ] = __( 'For', 'prospress' );
	} else {
		$feedback_columns[ 'from_user_id' ] = __( 'From', 'prospress' );
	}

	$feedback_columns = array_merge( $feedback_columns, array(
		'role' => __( 'Your Role', 'prospress' ),
		'feedback_score' => __( 'Score', 'prospress' ),
		'feedback_comment' => __( 'Comment', 'prospress' ),
		'feedback_date' => __( 'Date', 'prospress' ),
		'post_id' => __( 'Post', 'prospress' )
	) );

	if ( is_multisite() ) 
		$feedback_columns[ 'blog_id' ] = __( 'Site', 'prospress' );
	
	return $feedback_columns;
}
add_filter( 'manage_feedback_columns', 'pp_feedback_columns_admin' );


/** 
 * Outputs all the feedback items for the feedback admin page. 
 *
 * @param feedback array optional the feedback for a user
 */
function pp_feedback_rows( $feedback = '' ){
	global $user_ID;

	if( !empty( $feedback ) ){
		$style = '';
		foreach ( $feedback as $feedback_item ) {
			if( function_exists( 'switch_to_blog' ) )
				switch_to_blog( $feedback_item[ 'blog_id' ] );

			extract( $feedback_item );
			echo "<tr class='feedback $style' >";
			echo "<td scope='row'>";
		 	if( strpos( $_SERVER[ 'REQUEST_URI' ], 'given' ) == false )
				echo ( ( $user_ID == $from_user_id ) ? 'You' : get_userdata( $from_user_id )->user_nicename ) . pp_users_feedback_link( $from_user_id );
			else
				echo ( ( $user_ID == $for_user_id ) ? 'You' : get_userdata( $for_user_id )->user_nicename ) . pp_users_feedback_link( $for_user_id );
			echo "</td>";
			echo "<td>" . ucfirst( $role ) . "</td>";
			echo "<td>" . (( $feedback_score == 2) ? __("Positive", 'prospress' ) : (( $feedback_score == 1) ? __("Neutral", 'prospress' ) : __("Negative", 'prospress' ))) . "</td>";
			echo "<td>$feedback_comment</td>";
			echo "<td>" . mysql2date( __( 'd M Y', 'prospress' ), $feedback_date ) . "</td>";
			echo "<td><a href='" . get_permalink( $post_id ) . "' target='blank'>" . get_post( $post_id )->post_title . "</a></td>";
			if( is_multisite() )
				echo "<td><a href='" . get_blogaddress_by_id( $blog_id ) . "' target='blank'>" . get_bloginfo( 'name' ) . "</a></td>";
			echo "</tr>";
			$style = ( 'alternate' == $style ) ? '' : 'alternate';

			if( function_exists( 'restore_current_blog' ) )
				restore_current_blog();
		}
	} else {
		echo '<tr><td colspan="5">You have no feedback.</td>';
	}
}


/**
 * Central controller to determine which functions are called and what view is output to the screen.
 * 
 * @uses pp_feedback_form_submit() to process a feedback form submission
 * @uses pp_edit_feedback() to add or edit feedback items upon submission
 **/
function pp_feedback_controller() {
	global $wpdb, $user_ID;

	$title = __( 'Feedback', 'prospress' );

	if( $_POST[ 'feedback_submit' ] ){

		extract( pp_feedback_form_submit( $_POST ) );
		include_once( PP_FEEDBACK_DIR . '/pp-feedback-form-view.php' );

	} elseif ( $_GET[ 'action' ] == 'give-feedback' ){
		
		extract( pp_edit_feedback( $_GET[ 'post' ], $_GET[ 'blog' ] ) );
		include_once( PP_FEEDBACK_DIR . '/pp-feedback-form-view.php'  );

	} elseif ( $_GET[ 'action' ] == 'edit-feedback' ){

		$feedback = pp_edit_feedback( $_GET[ 'post' ], $_GET[ 'blog' ] );
		extract( pp_edit_feedback( $_GET[ 'post' ], $_GET[ 'blog' ] ) );
		include_once( PP_FEEDBACK_DIR . '/pp-feedback-form-view.php'  );

	} elseif ( $_GET[ 'action' ] == 'view-feedback' ){
		pp_feedback_history_admin( $user_ID );
	} else {
		pp_feedback_history_admin( $user_ID );
	}
}


/**
 * Ensures the logged in user can give feedback on a post and that the post status is such that 
 * a feedback item is due. 
 *
 * @todo wp_die is a pretty nasty way to handle this simple error, better to just output an error message on the feedback page.
 *
 * @param bidder_id int the id of the winning bidder.
 * @param from_user_id int the user who is trying to give feedback.
 * @param post object the post for which the users should be validated against, including post status and post author.
 **/
function pp_can_edit_feedback( $bidder_id, $from_user_id, $post ) {

	if( empty( $bidder_id ) )
		wp_die( __( 'Error: could not determine winning bidder.', 'prospress' ) );
	if( $bidder_id == $from_user_id && $from_user_id == $post->post_author )
		wp_die( __( 'You can not leave feedback for yourself, in fact, you should not have even been able to win your own post!', 'prospress' ) );
	if ( $from_user_id != $post->post_author && !is_winning_bidder( $from_user_id, $post->ID ) )
		wp_die( __( 'You can not leave feedback for this post. It appears you are neither the author author of the post nor the winning bidder.', 'prospress' ) );
	if ( NULL == $post->post_status || 'completed' != $post->post_status )
		wp_die( __( 'You can not leave feedback for this post. The post has either not completed or does not exist.', 'prospress' ) );
}


/**
 * There are a number of constraints on leaving feedback to make sure a valid transaction has occured. 
 * This function checks the constraints to ensure the logged in user can give/edit the feedback for a 
 * transaction and then determines the contents of the feedback form.  
 **/
function pp_edit_feedback( $post_id, $blog_ID = '' ) {
  	global $wpdb, $user_ID, $blog_id;

	$post_id = (int)$post_id;
	$blog_ID = ( empty( $blog_ID ) ) ? $blog_id : (int)$blog_ID;

	if( function_exists( 'switch_to_blog' ) ) //if multisite
		switch_to_blog( $blog_ID );

	get_currentuserinfo();

	$from_user_id = $user_ID;

	$post = get_post( $post_id );

	$bidder_id = get_winning_bidder( $post_id );

	$is_winning_bidder = is_winning_bidder( $from_user_id, $post_id );

	pp_can_edit_feedback( $bidder_id, $from_user_id, $post );

	if ( pp_post_has_feedback( $post_id, $from_user_id ) ) { //user already left feedback for post
		$feedback = pp_get_feedback_item( $post_id, $from_user_id );
		if( get_option( 'edit_feedback' ) != 'true' ){
			$feedback[ 'disabled' ] = 'disabled="disabled"';
			$feedback[ 'title' ] = __( 'Feedback', 'prospress' );
		} else {
			$feedback[ 'title' ] = __( 'Edit Feedback', 'prospress' );
			$disabled = '';
		}
	} else {
		if ( $post->post_author == $user_ID && !$is_winning_bidder ){
			$role = 'post author';
			$for_user_id = $bidder_id;
		} else { // bidder
			$role = 'bidder';
			$for_user_id = $post->post_author;
		}
		$title = __( 'Give Feedback', 'prospress' );
		$disabled = '';
		$feedback = compact( 'post_id', 'from_user_id', 'for_user_id', 'role', 'disabled', 'title' );
	}
	$feedback[ 'blog_id' ] = $blog_ID;

	if( function_exists( 'restore_current_blog' ) )
		restore_current_blog();

	return $feedback;
}


/**
 * Performs submission process for the feedback form including validation. 
 *
 * @param feedback array with feedback fields conforming to the column structure of the feedback table.
 **/
function pp_feedback_form_submit( $feedback ) {
	global $wpdb;

	$feedback = pp_feedback_sanitize( $feedback );

	if( function_exists( 'switch_to_blog' ) ) //if multisite
		switch_to_blog( $feedback_details[ 'blog_id' ] );

	$post = get_post( $feedback[ 'post_id' ] );

	$is_winning_bidder = is_winning_bidder( $feedback[ 'from_user_id' ], $feedback[ 'post_id' ] );

	pp_can_edit_feedback( get_winning_bidder( $post_id ), $feedback[ 'from_user_id' ], $post );
	
	$feedback[ 'feedback_status' ] = 'publish';

	if( pp_post_has_feedback( $feedback[ 'post_id' ], $feedback[ 'from_user_id' ] ) ){
	
		if( get_option( 'edit_feedback' ) != 'true' ){ // user trying to edit feedback when not allowed
			$feedback = pp_get_feedback_item( $feedback[ 'post_id' ], $feedback[ 'from_user_id' ] );
			$feedback[ 'feedback_msg' ] = __( 'You are not allowed to edit this feedback', 'prospress' );
			return $feedback;
		}

		//if user is updating feedback, deprecate status of previous feedback entry
		$wpdb->update( $wpdb->feedback, array( 'feedback_status' => 'deprecated' ), 
						array( 'post_id' => $feedback[ 'post_id' ], 
								'from_user_id' => $feedback[ 'from_user_id' ],
								'feedback_status' => $feedback_status ) );
	}

	pp_update_feedback( $feedback );

	$feedback[ 'feedback_msg' ] = __( 'Feedback Submitted', 'prospress' );

	if( function_exists( 'restore_current_blog' ) )
		restore_current_blog();

	return $feedback;
}


/**
 * @link http://xkcd.com/327/ 
*
 * @param feedback_details array with feedback fields conforming to the column structure of the feedback table.
 **/
function pp_feedback_sanitize( $feedback_details ){

	$feedback_details[ 'for_user_id' ] 		= (int)$feedback_details[ 'for_user_id' ];
	$feedback_details[ 'from_user_id' ] 	= (int)$feedback_details[ 'from_user_id' ];
	$feedback_details[ 'role' ] 			= $feedback_details[ 'role' ];
	$feedback_details[ 'feedback_score' ] 	= (int)$feedback_details[ 'feedback_score' ];
	$feedback_details[ 'feedback_comment' ] = esc_attr( $feedback_details[ 'feedback_comment' ] );
	$feedback_details[ 'feedback_status' ] 	= ( !empty( $feedback_details[ 'feedback_status' ] ) ) ? esc_attr( $feedback_details[ 'feedback_comment' ] ) : apply_filters( 'new_feedback_status', 'publish' );
	$feedback_details[ 'post_id' ] 			= (int)$feedback_details[ 'post_id' ];
	$feedback_details[ 'blog_id' ] 			= (int)$feedback_details[ 'blog_id' ];
	$feedback[ 'feedback_date' ] 			= current_time( 'mysql' );
	$feedback[ 'feedback_date_gmt' ] 		= current_time( 'mysql', 1 );

	$feedback_details = apply_filters( 'validated_feedback', $feedback_details );

	return $feedback_details;
}


/**
 * Adds an entry in the feedback table. 
 *
 * @param feedback array with feedback fields conforming to the column structure of the feedback table.
 * @return int the ID of the newly inserted feedback item.
 **/
function pp_update_feedback( $feedback ) {
	global $wpdb;

	if( empty( $feedback[ 'feedback_date' ] ) )
		$feedback[ 'feedback_date' ] = current_time( 'mysql' );

	if( empty( $feedback[ 'feedback_date_gmt' ] ) )
		$feedback[ 'feedback_date_gmt' ] = get_gmt_from_date( $feedback[ 'feedback_date' ] );

	$wpdb->insert( $wpdb->feedback, array(
		'for_user_id' => $feedback[ 'for_user_id' ],
		'from_user_id' => $feedback[ 'from_user_id' ],
		'role' => $feedback[ 'role' ],
		'feedback_score' => $feedback[ 'feedback_score' ],
		'feedback_comment' => $feedback[ 'feedback_comment' ],
		'feedback_status' => $feedback[ 'feedback_status' ],
		'feedback_date' => $feedback[ 'feedback_date' ],
		'feedback_date_gmt' => $feedback[ 'feedback_date_gmt' ],
		'post_id' => $feedback[ 'post_id' ],
		'blog_id' => $feedback[ 'blog_id' ]
	) );
	
	return $wpdb->insert_id;
}


/** 
 * Certain administration pages in Prospress provide a hook for other components to add an "action" link. This function 
 * determines and then outputs an appropriate feedback action link, which may be any of give, edit or view feedback. 
 * 
 * The function receives the existing array of actions from the hook and adds to it an array with the url for 
 * performing a feedback action and label for outputting as the link text. 
 * 
 * @see completed_post_actions hook
 * @see bid_table_actions hook
 * 
 * @param actions array existing actions for the hook
 * @param post_id int for identifying the post
 * @return array of actions for the hook, including the feedback action
 */
function pp_add_feedback_action( $actions, $post_id ) {
	global $user_ID, $blog_id;
 
	$post = get_post( $post_id );

	$is_winning_bidder = is_winning_bidder( $user_ID, $post_id );

	if ( $post->post_status != 'completed' || get_bid_count( $post_id ) == false || ( !$is_winning_bidder && $user_ID != $post->post_author && !is_super_admin() ) ) 
		return $actions;

	$feedback_url = ( is_super_admin() ) ? 'users.php?page=feedback' : 'profile.php?page=feedback';

	if ( is_super_admin() && !$is_winning_bidder && $user_ID != $post->post_author ) { // Admin isn't bidder or author
		if( pp_post_has_feedback( $post_id ) )
			$actions[ 'view-feedback' ] = array( 'label' => __( 'View Feedback', 'prospress' ), 
										'url' => add_query_arg( array( 'post' => $post_id, 'blog' => $blog_id ), $feedback_url ) );
	} else if ( !pp_post_has_feedback( $post_id, $user_ID ) ) {
		$actions[ 'give-feedback' ] = array( 'label' => __( 'Give Feedback', 'prospress' ),
											'url' => add_query_arg( array( 'post' => $post_id, 'blog' => $blog_id ), $feedback_url ) );
	} else if ( get_option( 'edit_feedback' ) == 'true' ) {
		$actions[ 'edit-feedback' ] = array( 'label' => __( 'Edit Feedback', 'prospress' ),
											'url' => add_query_arg( array( 'post' => $post_id, 'blog' => $blog_id ), $feedback_url ) );
	} else {
		$actions[ 'view-feedback' ] = array( 'label' => __( 'View Feedback', 'prospress' ),
											'url' => $feedback_url );
	}
	return $actions;
}
add_filter( 'completed_post_actions', 'pp_add_feedback_action', 10, 2 );
add_filter( 'bid_table_actions', 'pp_add_feedback_action', 10, 2 );


/** 
 * A central function to determine feedback history for a user and display the table of that user's feedback. 
 * 
 * @param user_id int optional used to determine whose feedback items to get
 */
function pp_feedback_history_admin( $user_id = '' ) {
  	global $wpdb, $user_ID;

	if( empty( $user_id ) ){
		get_currentuserinfo(); //get's ID of currently logged in user and puts into global $user_ID
		$user_id = $user_ID;
	}

	if( isset( $_GET[ 'uid' ] ) && $_GET[ 'uid' ] != $user_id ){
		$_GET[ 'uid' ] = (int)$_GET[ 'uid' ];
		if( isset( $_GET[ 'post' ] ) ){
			$_GET[ 'post' ] = (int)$_GET[ 'post' ];
			$feedback = pp_get_feedback_user( $_GET[ 'uid' ], array( 'post' => $_GET[ 'post' ] ) );
			$title = sprintf( __( 'Feedback for %1$s on Post %2$d', 'prospress' ), get_userdata( $_GET[ 'uid' ] )->user_nicename, $_GET[ 'post' ] );
		} else if( $_GET[ 'filter' ] == 'given' ){
			$feedback = pp_get_feedback_user( $_GET[ 'uid' ], array( 'given' => 'true' ) );
			$title = sprintf( __( 'Feedback Given by %s', 'prospress' ), get_userdata( $_GET[ 'uid' ] )->user_nicename );
		} else {
			$feedback = pp_get_feedback_user( $_GET[ 'uid' ], array( 'received' => 'true' ) );
			$title = sprintf( __( 'Feedback Received by %s', 'prospress' ), get_userdata( $_GET[ 'uid' ] )->user_nicename );
		}
		$user_id = $_GET[ 'uid' ];
	} else if( isset( $_GET[ 'post' ] ) ){
		$_GET[ 'post' ] = (int)$_GET[ 'post' ];
		$feedback = pp_get_feedback_user( $user_id, array( 'post' => $_GET[ 'post' ] ) );
		$title = sprintf( __( 'Feedback on Post ', 'prospress' ), $_GET[ 'post' ] );
	} else if( $_GET[ 'filter' ] == 'given' ){
		$feedback = pp_get_feedback_user( $user_id, array( 'given' => 'true' ) );
		$title = __( "Feedback You've Given" , 'prospress' );
	} else {
		$feedback = pp_get_feedback_user( $user_id, array( 'received' => 'true' ) );
		$title = __( 'Your Feedback', 'prospress' );
	}

	include_once( PP_FEEDBACK_DIR . '/pp-feedback-table-view.php' );
}


/**
 * Displays the fields for handling feedback options in the Core Prospress Settings admin page.
 *
 * @see pp_settings_page()
 **/
function pp_feedback_settings_section() { 
	$edit_feedback = get_option( 'edit_feedback' );
	?>
	<h3><?php _e( 'Feedback' , 'prospress' )?></h3>
	<p><?php _e( 'Allowing feedback to be amended helps to make it more accurate. Mistakes happen and circumstances change.' , 'prospress' ); ?></p>
	<label for='edit_feedback'>
		<input type='checkbox' value='true' name='edit_feedback' id='edit_feedback' <?php checked( (boolean)$edit_feedback ); ?> />
		  <?php _e( 'Allow feedback amendment' , 'prospress' ); ?>
	</label>
<?php
}
add_action( 'pp_core_settings_page', 'pp_feedback_settings_section' );


/**
 * Tells Prospress core to save this item upon submission of the Prospress Settings page.
 *
 * @param array with the existing whitelist of options for the Prospress settings page.
 **/
function pp_feedback_admin_option( $whitelist_options ) {

	$whitelist_options[ 'general' ][] = 'edit_feedback';

	return $whitelist_options;
}
add_filter( 'pp_options_whitelist', 'pp_feedback_admin_option' );


/**
 * Clean up if the plugin when deleted by removing feedback related options and database tables.
 * 
 **/
function pp_feedback_uninstall() {
	global $wpdb;

	if ( !current_user_can( 'edit_plugins' ) || !function_exists( 'delete_site_option' ) )
		return false;

	delete_site_option( 'pp_feedback_db_version' );
	
	$wpdb->query( "DROP TABLE IF EXISTS $wpdb->feedback" );
	$wpdb->query( "DROP TABLE IF EXISTS $wpdb->feedbackmeta" );

}
add_action( 'pp_uninstall', 'pp_feedback_uninstall' );
