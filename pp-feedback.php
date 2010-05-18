<?php
/**
 * @package Prospress
 * @author Brent Shepherd
 * @version 0.2
 */
/*
Plugin Name: Prospress Feedback
Plugin URI: http://prospress.com
Description: Leave feedback for other users on your prospress marketplace. 
Author: Brent Shepherd
Version: 0.2
Author URI: http://brentshepherd.com/
*/

if ( !defined( 'PP_FEEDBACK_DB_VERSION'))
	define ( 'PP_FEEDBACK_DB_VERSION', '0015' );
if ( !defined( 'PP_FEEDBACK_DIR'))
	define( 'PP_FEEDBACK_DIR', WP_PLUGIN_DIR . '/prospress/pp-feedback' );
if ( !defined( 'PP_FEEDBACK_URL'))
	define( 'PP_FEEDBACK_URL', WP_PLUGIN_URL . '/prospress/pp-feedback' );

if ( file_exists( PP_FEEDBACK_DIR . '/feedback-functions.php' ) )
	require_once ( PP_FEEDBACK_DIR . '/feedback-functions.php' );

// For testing
//include_once ( PP_FEEDBACK_DIR . '/feedback-tests.php' );

global $wpdb;

if ( !isset( $wpdb->feedback ) || empty( $wpdb->feedback ) )
	$wpdb->feedback = $wpdb->base_prefix . 'feedback';
if ( !isset( $wpdb->feedbackmeta ) || empty( $wpdb->feedbackmeta ) )
	$wpdb->feedbackmeta = $wpdb->base_prefix . 'feedbackmeta';

//**************************************************************************************************//
// INSTALLATION FUNCTIONS
//**************************************************************************************************//

/**
 * 	Checks the Feedback database tables are set up and options set, if not, calls install function to set them up.
 * 
 * @uses get_site_option() to check the current database version  (**WPMU_FUNCTION**)
 * @uses pp_feedback_install() to create the database table if it is not up to date
 * @return false if logged in user is not the site admin
 **/
function pp_feedback_maybe_install() {
	global $wpdb;

	if ( !current_user_can('edit_plugins') )
		return false;

	if ( !get_site_option('pp_feedback_db_version') || get_site_option('pp_feedback_db_version') < PP_FEEDBACK_DB_VERSION )
		pp_feedback_install();
}
register_activation_hook( __FILE__, 'pp_feedback_maybe_install' );

function pp_feedback_deactivate() {
	global $wpdb;

	if ( !current_user_can('edit_plugins') || !function_exists( 'delete_site_option') )
		return false;

	delete_site_option( 'pp_feedback_db_version' );
}
register_deactivation_hook( __FILE__, 'pp_feedback_deactivate' );

/**
 * Set up the Prospress Feedback plugin on single blog. Creates table, and add site options to sitemeta DB.
 * 
 * @param blog_id optional, the id of the marketplace on which to install. Defaults to 0, in which case installed on current blog.
 * 
 * @uses dbDelta($sql) to execute the sql query for creating tables
 * @uses update_option(name, value) to set the database version
 **/
function pp_feedback_install() {
	global $wpdb;

	if ( !empty($wpdb->charset) )
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
}

//**************************************************************************************************//
// FEEDBACK FUNCTIONS
//**************************************************************************************************//

/**
 * Feedback controller: determines what functions are called and ultimately 
 * what is printed to the screen. This function is called from the admin menu hook.
 * 
 **/
function pp_feedback_controller() {
	global $wpdb, $user_ID;

	$title = __( 'Feedback' );

	if( $_POST[ 'feedback_submit' ] ){
		extract( pp_feedback_form_submit( $_POST ) );
		include_once( PP_FEEDBACK_DIR . '/feedback-form-view.php' );
		return;
	} elseif ( $_GET[ 'action' ] == 'give-feedback' ){
		extract( pp_edit_feedback( $_GET[ 'post' ], $_GET[ 'blog' ] ) );
		include_once( PP_FEEDBACK_DIR . '/feedback-form-view.php'  );
		return;
	} elseif ( $_GET[ 'action' ] == 'edit-feedback' ){
		$feedback = pp_edit_feedback( $_GET[ 'post' ], $_GET[ 'blog' ] );
		extract( pp_edit_feedback( $_GET[ 'post' ], $_GET[ 'blog' ] ) );
		include_once( PP_FEEDBACK_DIR . '/feedback-form-view.php'  );
		return;
	} elseif ( $_GET[ 'action' ] == 'view-feedback' ){
		pp_feedback_history_admin( $user_ID );
		return;
	} else {
		pp_feedback_history_admin( $user_ID );
		return;
	}
}

//Function to ensure current user can give/edit the feedback for a transaction, it also determines the contents of the feedback form.
function pp_edit_feedback( $post_id, $blog_ID = '' ) {
  	global $wpdb, $user_ID, $bid_system, $blog_id;

	$post_id = (int)$post_id;
	$blog_ID = ( empty( $blog_ID ) ) ? $blog_id : (int)$blog_ID;

	if( function_exists( 'switch_to_blog' ) ) //if multisite
		switch_to_blog( $blog_ID );

	get_currentuserinfo();
	$from_user_id = $user_ID;

	$post = get_post( $post_id );

	$bidder_id = $bid_system->get_winning_bid( $post_id )->bidder_id;

	if( empty( $bidder_id ) )
		wp_die( __("Error: could not determine winning bidder.") );

	$is_winning_bidder = $bid_system->is_winning_bidder( $from_user_id, $post_id );

	if ( ( !$is_winning_bidder && $from_user_id != $post->post_author ) || NULL == $post->post_status || 'completed' != $post->post_status ) {
		wp_die( __('You can not leave feedback on this post.') );
	} elseif ( pp_has_feedback( $post_id, $from_user_id ) ) { //user already left feedback for post
		$feedback = pp_get_feedback_item( $post_id, $from_user_id );
		if( get_option( 'edit_feedback' ) != 'true' ){
			$feedback[ 'disabled' ] = __( 'disabled="disabled"' );
			$feedback[ 'title' ] = __( 'Feedback' );
		} else {
			$feedback[ 'title' ] = __( 'Edit Feedback' );
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
		$title = __( 'Give Feedback' );
		$disabled = '';
		$feedback = compact( 'post_id', 'from_user_id', 'for_user_id', 'role', 'disabled', 'title' );
	}
	$feedback[ 'blog_id' ] = $blog_ID;

	if( function_exists( 'restore_current_blog' ) )
		restore_current_blog();

	return $feedback;
}

//**************************************************************************************************//
// VALIDATE FEEDBACK & SUBMIT FEEDBACK FORM
//**************************************************************************************************//

/** Performs submission process for the feedback form. **/
function pp_feedback_form_submit( $feedback ) {
	global $wpdb, $bid_system;

	$feedback = pp_feedback_sanitize( $feedback );
	
	if( function_exists( 'switch_to_blog' ) ) //if multisite
		switch_to_blog( $feedback_details[ 'blog_id' ] );

	$post = get_post( $feedback[ 'post_id' ] );
	$is_winning_bidder = $bid_system->is_winning_bidder( $feedback[ 'from_user_id' ], $feedback[ 'post_id' ] );

	if ( ( !$is_winning_bidder && $feedback[ 'from_user_id' ] != $post->post_author ) || NULL == $post->post_status || 'completed' != $post->post_status ) {
		if( !$is_winning_bidder )
			wp_die( __( 'You can not leave feedback on this post. You are not the winning bidder.' ) );
		if( $feedback[ 'from_user_id' ] != $post->post_author)
			wp_die( __( 'You can not leave feedback on this post. As you are not the post author.' ) );
		if( NULL == $post->post_status )
			wp_die( __( "You can not leave feedback on this post as it's status is null" ) );
		if( 'completed' != $post->post_status )
			wp_die( __( "You can not leave feedback on this post as it's status is not set to completed." ) );

		wp_die( __('You can not leave feedback on this post.') );
	}

	$feedback[ 'feedback_status' ] = 'publish';

	if( pp_has_feedback( $feedback[ 'post_id' ], $feedback[ 'from_user_id' ] ) ){
	
		if( get_option( 'edit_feedback' ) != 'true' ){ // user trying to edit feedback when not allowed
			$feedback = pp_get_feedback_item( $feedback[ 'post_id' ], $feedback[ 'from_user_id' ] );
			$feedback[ 'feedback_msg' ] = __('You are not allowed to edit this feedback');
			return $feedback;
		}

		//if user is updating feedback, deprecate status of previous feedback entry
		$wpdb->update( $wpdb->feedback, array( 'feedback_status' => 'deprecated' ), 
						array( 'post_id' => $feedback[ 'post_id' ], 
								'from_user_id' => $feedback[ 'from_user_id' ],
								'feedback_status' => $feedback_status ) );
	}

	pp_update_feedback( $feedback );

	$feedback[ 'feedback_msg' ] = __('Feedback Submitted');

	if( function_exists( 'restore_current_blog' ) )
		restore_current_blog();

	return $feedback;
}

/** Validates given feedback items. **/
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

// Updates an entry in the feedback table.
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

//**************************************************************************************************//
// Functions to add feedback pages to admin interface
//**************************************************************************************************//
function pp_add_feedback_admin_pages() {
	if ( function_exists('add_submenu_page') ) {
		$feedback_page = add_submenu_page( 'users.php', 'Feedback', 'Feedback', 'read', 'feedback', 'pp_feedback_controller' );
	}
}
add_action( 'admin_menu', 'pp_add_feedback_admin_pages' );

//the place for enqueuing scripts, styles and other assorted admin head functions
function pp_feedback_admin_head() {

	/** @TODO Figure out a more efficient way to add this style only to dashboard page */
	wp_enqueue_style( "dashboard-feedback", PP_FEEDBACK_URL . "/dashboard-feedback.css" );
}
add_action( 'admin_menu', 'pp_feedback_admin_head' );


function pp_feedback_action( $actions, $post_id ) {
	global $user_ID, $bid_system, $blog_id;
 
	$post = get_post( $post_id );

	$is_winning_bidder = $bid_system->is_winning_bidder( $user_ID, $post_id );

	$feedback_url = ( is_admin() ) ? 'users.php?page=feedback' : 'profile.php?page=feedback';

	if ( !$is_winning_bidder && $user_ID != $post->post_author ) { // admin viewing all ended posts

		if( pp_has_feedback( $post_id ) ){
			$actions[ 'view' ] = array('label' => __( 'View Feedback' ), 
										'url' => add_query_arg( array( 'post' => $post_id, 'blog' => $blog_id ), $feedback_url ) );
		}
		return $actions;
	}

	if ( !pp_has_feedback( $post_id, $user_ID ) ) {
		$actions[ 'give-feedback' ] = array('label' => __( 'Give Feedback' ),
											'url' => add_query_arg( array( 'post' => $post_id, 'blog' => $blog_id ), $feedback_url ) );
											//'url' => 'users.php?page=feedback' );
	} else if ( get_option( 'edit_feedback' ) == 'true' ) {
		$actions[ 'edit-feedback' ] = array('label' => __( 'Edit Feedback' ),
											'url' => add_query_arg( array( 'post' => $post_id, 'blog' => $blog_id ), $feedback_url ) );
											//'url' => 'users.php?page=feedback' );
	} else {
		$actions[ 'view-feedback' ] = array('label' => __( 'View Feedback' ),
											'url' => $feedback_url );
											//'url' => 'users.php?page=feedback' );
	}
	return $actions;
}
add_filter( 'completed_post_actions', 'pp_feedback_action', 10, 2 );


//**************************************************************************************************//
// Printing the history admin page
//**************************************************************************************************//
//Function to print the feedback history for a user. If no user is specified, currently logged in user is used.
function pp_feedback_history_admin( $user_id ) {
  	global $wpdb;

	get_currentuserinfo(); //get's ID of currently logged in user and puts into global $user_id

	if( isset( $_GET[ 'uid' ] ) && $_GET[ 'uid' ] != $user_id ){
		$_GET[ 'uid' ] = (int)$_GET[ 'uid' ];
		if( isset( $_GET[ 'post' ] ) ){
			$_GET[ 'post' ] = (int)$_GET[ 'post' ];
			$feedback = pp_get_feedback_user( $_GET[ 'uid' ], array( 'post' => $_GET[ 'post' ] ) );
			$title = sprintf( __( 'Feedback for %1$s on Post %2$d' ), get_userdata( $_GET[ 'uid' ] )->user_nicename, $_GET[ 'post' ] );
		} else if( $_GET[ 'filter' ] == 'given' ){
			$feedback = pp_get_feedback_user( $_GET[ 'uid' ], array( 'given' => 'true' ) );
			$title = sprintf( __( 'Feedback Given by %s' ), get_userdata( $_GET[ 'uid' ] )->user_nicename );
		} else {
			$feedback = pp_get_feedback_user( $_GET[ 'uid' ], array( 'received' => 'true' ) );
			$title = sprintf( __( 'Feedback Received by %s' ), get_userdata( $_GET[ 'uid' ] )->user_nicename );
		}
		$user_id = $_GET[ 'uid' ];
	} else if( isset( $_GET[ 'post' ] ) ){
		$_GET[ 'post' ] = (int)$_GET[ 'post' ];
		$feedback = pp_get_feedback_user( $user_id, array( 'post' => $_GET[ 'post' ] ) );
		$title = __( 'Feedback on Post ' . $_GET[ 'post' ] );
	} else if( $_GET[ 'filter' ] == 'given' ){
		$feedback = pp_get_feedback_user( $user_id, array( 'given' => 'true' ) );
		$title = __( "Feedback You've Given" );
	} else {
		$feedback = pp_get_feedback_user( $user_id, array( 'received' => 'true' ) );
		$title = __( 'Your Feedback' );
	}

	include_once( PP_FEEDBACK_DIR . '/feedback-table-view.php' );
}

//Echos a count of all the feedback given and received by a user, encapsulated in a link to a table of that user's feedback.
function pp_users_feedback_link( $user_id ){
	return "<a href='" . add_query_arg ( array( 'uid' => $user_id ), 'users.php?page=feedback' ) . "'> (" . pp_users_feedback_count( $user_id ) . ")</a>";
}

//Function to add feedback history column headings to the built in print_column_headers function
function pp_feedback_columns_admin(){

	$feedback_columns = array( 'cb' => '<input type="checkbox" />' );
	
 	if( strpos( $_SERVER['REQUEST_URI'], 'given' ) !== false ) {
		$feedback_columns[ 'for_user_id' ] = __('For');
	} else {
		$feedback_columns[ 'from_user_id' ] = __('From');
	}

	$feedback_columns = array_merge( $feedback_columns, array(
		'role' => __('Your Role'),
		'feedback_score' => __('Score'),
		'feedback_comment' => __('Comment'),
		'feedback_date' => __('Date'),
		'post_id' => __('Post')
	) );

	if ( is_multisite() ) 
		$feedback_columns[ 'blog_id' ] = __( 'Site' );
	
	return $feedback_columns;
}
add_filter('manage_feedback_columns','pp_feedback_columns_admin');

//Function to print feedback rows for
function pp_feedback_rows($feedback = ''){
	global $user_ID;

	if( !empty( $feedback ) ){
		$style = '';
		foreach ( $feedback as $feedback_item ) {
			if( function_exists( 'switch_to_blog' ) )
				switch_to_blog( $feedback_item[ 'blog_id' ] );

			extract( $feedback_item );
			echo "<tr $style >";
			echo "<th class='check-column' scope='row'><input type='checkbox' value='$feedback_id' name='$feedback_id'></th>";
		 	if( strpos( $_SERVER['REQUEST_URI'], 'given' ) == false )
				echo "<td>" . ( ( $user_ID == $from_user_id ) ? 'You' : get_userdata( $from_user_id )->user_nicename ) . pp_users_feedback_link( $from_user_id ) . "</td>";
			else
				echo "<td>" . ( ( $user_ID == $for_user_id ) ? 'You' : get_userdata( $for_user_id )->user_nicename ) . pp_users_feedback_link( $for_user_id ) . "</td>";
			echo "<td>" . ucfirst( $role ) . "</td>";
			echo "<td>" . (($feedback_score == 2) ? __("Positive") : (($feedback_score == 1) ? __("Neutral") : __("Negative"))) . "</td>";
			echo "<td>$feedback_comment</td>";
			echo "<td>" . mysql2date( __('d M Y'), $feedback_date ) . "</td>";
			echo "<td><a href='" . get_permalink( $post_id ) . "' target='blank'>" . get_post( $post_id )->post_title . "</a></td>";
			if( is_multisite() )
				echo "<td><a href='" . get_blogaddress_by_id( $blog_id ) . "' target='blank'>" . get_bloginfo( 'name' ) . "</a></td>";
			echo "</tr>";
			$style = ( ' class="alternate"' == $style ) ? '' : ' class="alternate"';

			if( function_exists( 'restore_current_blog' ) )
				restore_current_blog();
		}
	} else {
		echo '<tr><td colspan="5">You have no feedback.</td>';
	}
	
}

// Administration functions for choosing default currency (may be set by locale in future, like number format is already)
function pp_add_feedback_admin_option(){
	if ( function_exists( 'add_settings_section' ) ){
		add_settings_section( 'feedback', 'Feedback', 'pp_feedback_settings_section', 'general' );
	} else {
		$bid_settings_page = add_submenu_page( 'options-general.php', 'Feedback', 'Feedback', 57, 'feedback', 'pp_feedback_settings_section' );
	}
}
add_action( 'admin_menu', 'pp_add_feedback_admin_option' );

// Displays the fields for handling currency default options
function pp_feedback_settings_section() { ?>
	<table class='form-table'>
		<tr>
			<th scope="row"><?php echo _e('Allow traders to edit feedback');?>:</th>
			<td>
				<?php
				$edit_feedback = get_option( 'edit_feedback' );
				?>
				<input type='checkbox' value='true' name='edit_feedback' id='ef1' <?php checked( (boolean)$edit_feedback ); ?> />
			</td>
		</tr>
	</table>
<?php
}

function feedback_admin_option( $whitelist_options ) {
	$whitelist_options['general'][] = 'edit_feedback';

	return $whitelist_options;
}
add_filter( 'whitelist_options', 'feedback_admin_option' );

//**************************************************************************************************//
// ADD DASHBOARD WIDGETS 
//**************************************************************************************************//

/**
 * Outputs the contents of Prospress Feedback Dashboard Widget
 * 
 * @uses 
 **/
function pp_feedback_dashboard_widget() {
	global $user_ID;

	echo "\n\t".'<p class="sub">' . __('Overview') . '</p>';
	echo "\n\t".'<div class="table">'."\n\t".'<table>';
	echo "\n\t".'<tr class="first">';

	// Feedback
	$total_feedback = pp_users_feedback_count( $user_ID );
	$num = number_format_i18n( $total_feedback );
	$text = __( 'Feedback' );
	if ( $total_feedback > 0 ) {
		$feedback_url = add_query_arg( array( 'page' => 'feedback' ), 'users.php' );
		$num = "<a href='$feedback_url'>$num</a>";
		$text = "<a href='$feedback_url'>$text</a>";
	}
	echo "<td class='first b b-posts'>$num</td>";
	echo "<td class='t posts'>$text</td>";

	// Feedback break down
	// Feedback Received
	$received = pp_users_feedback_count( $user_ID, 'received' );
	$num = '<span class="total-count">' . number_format_i18n( $received ) . '</span>';
	$text = __( 'Received' );
	if ( $received > 0 ) {
		$received_url = add_query_arg( array( 'filter' => 'received' ), 'users.php?page=feedback' );
		$num = "<a href='$received_url'>$num</a>";
		$text = "<a href='$received_url'>$text</a>";
	}
	echo "<td class='b b-comments'>$num</td>";
	echo "<td class='last t comments'>$text</td>";

	// Feedback Given
	$given = pp_users_feedback_count( $user_ID, 'given' );
	$num = '<span class="total-count">' . number_format_i18n( $given ) . '</span>';
	$text = __( 'Given' );
	if ( $given > 0 ) {
		$given_url = add_query_arg( array( 'filter' => 'given' ), 'users.php?page=feedback' );
		$num = "<a href='$given_url'>$num</a>";
		$text = "<a href='$given_url'>$text</a>";
	}
	echo "<td class='b b-comments'>$num</td>";
	echo "<td class='last t comments'>$text</td>";

	if ( $total_feedback > 0 ) {
		echo '</tr><tr>';
		// Feedback Received Breakdown
		// Positive Feedback Received
		$positive = pp_users_positive_feedback( $user_ID, 'received' );
		$num = number_format_i18n( $positive );
		$text = __( 'Positive Feedback Received' );
		if ( $positive > 0 ) {
			$given_url = add_query_arg( array( 'filter' => 'received' ), 'users.php?page=feedback' );
			$num = "<a href='$feedback_url'>$num</a>";
			$text = "<a href='$feedback_url'>$text</a>";
		}
		echo "<td class='first b b-posts'>$num</td>";
		echo "<td class='t posts'>$text</td>";

		// Neutral Feedback Received
		$neutral = pp_users_neutral_feedback( $user_ID, 'received' );
		$num = '<span class="total-count">' . number_format_i18n( $neutral ) . '</span>';
		$text = __( 'Neutral Feedback Received' );
		if ( $neutral > 0 ) {
			$given_url = add_query_arg( array( 'filter' => 'received' ), 'users.php?page=feedback' );
			$num = "<a href='$received_url'>$num</a>";
			$text = "<a href='$received_url'>$text</a>";
		}
		echo "<td class='b b-comments'>$num</td>";
		echo "<td class='last t comments'>$text</td>";

		// Negative Feedback Received
		$negative = pp_users_negative_feedback( $user_ID, 'received' );
		$num = '<span class="total-count">' . number_format_i18n( $negative ) . '</span>';
		$text = __( 'Negative Feedback Received' );
		if ( $negative > 0 ) {
			$given_url = add_query_arg( array( 'filter' => 'received' ), 'users.php?page=feedback' );
			$num = "<a href='$given_url'>$num</a>";
			$text = "<a href='$given_url'>$text</a>";
		}
		echo "<td class='b b-comments'>$num</td>";
		echo "<td class='last t comments'>$text</td>";

		echo '</tr><tr>';
		// Feedback Given Breakdown
		// Positive Feedback Given
		$positive = pp_users_positive_feedback( $user_ID, 'given' );
		$num = number_format_i18n( $positive );
		$text = __( 'Positive Feedback Given' );
		if ( $positive > 0 ) {
			$received_url = add_query_arg( array( 'filter' => 'given' ), 'users.php?page=feedback' );
			$num = "<a href='$feedback_url'>$num</a>";
			$text = "<a href='$feedback_url'>$text</a>";
		}
		echo "<td class='first b b-posts'>$num</td>";
		echo "<td class='t posts'>$text</td>";

		// Neutral Feedback Given
		$neutral = pp_users_neutral_feedback( $user_ID, 'given' );
		$num = '<span class="total-count">' . number_format_i18n( $neutral ) . '</span>';
		$text = __( 'Neutral Feedback Given' );
		if ( $neutral > 0 ) {
			$received_url = add_query_arg( array( 'filter' => 'given' ), 'users.php?page=feedback' );
			$num = "<a href='$received_url'>$num</a>";
			$text = "<a href='$received_url'>$text</a>";
		}
		echo "<td class='b b-comments'>$num</td>";
		echo "<td class='last t comments'>$text</td>";

		// Negative Feedback Given
		$negative = pp_users_negative_feedback( $user_ID, 'given' );
		$num = '<span class="total-count">' . number_format_i18n( $negative ) . '</span>';
		$text = __( 'Negative Feedback Given' );
		if ( $negative > 0 ) {
			$given_url = add_query_arg( array( 'filter' => 'given' ), 'users.php?page=feedback' );
			$num = "<a href='$given_url'>$num</a>";
			$text = "<a href='$given_url'>$text</a>";
		}
		echo "<td class='b b-comments'>$num</td>";
		echo "<td class='last t comments'>$text</td>";
	}

	echo '</tr>';
	echo "\n\t</table>\n\t</div>";

	if ( $received > 0 ) {
		echo "\n\t".'<div class="versions">';

		$latest = pp_get_latest_feedback( $user_ID );

		echo "\n\t<p>";
		_e( 'Recent Comment: ' );
		echo '<quote class="sub">' . $latest['feedback_comment'] . '</quote>';
		echo '</p>';
		echo "\n\t<p>";
		_e( 'From: ' );
		echo get_userdata( $latest['from_user_id'] )->user_nicename;
		echo '</p>';
		echo "\n\t".'<br class="clear" /></div>';
	}
	do_action( 'feedback_box_end' );
}

/**
 * Creates the Widget function to use in the dashboard set-up action hook if current user has required privileges
 * 
 * @uses wp_add_dashboard_widget
 **/
function pp_feedback_add_dashboard_widgets() {
	global $wp_meta_boxes;

	if ( current_user_can('read') ){
		$wp_meta_boxes['dashboard']['side']['core']['dashboard_feedback'] = array(
											'id' => 'dashboard_feedback',
											'title' => 'Feedback',
											'callback' => 'pp_feedback_dashboard_widget',
											'args' => ''
		                                );
	}
}
// Hook into the 'wp_dashboard_setup' action to register our other functions
add_action('wp_dashboard_setup', 'pp_feedback_add_dashboard_widgets' );
