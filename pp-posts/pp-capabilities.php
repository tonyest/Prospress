<?php
/**
 * Prospress Capabilities
 *
 * Map custom post type capabilities to augment the lack of custom post type capability checking in the WP core. 
 *
 * @package Prospress
 * @author Brent Shepherd
 * @version 0.1
 */

/**
 * Register Prospress capabilities settings
 *
 * @package Prospress
 * @since 1.01
 */
function pp_capabilities_options() {
	register_setting( 'pp_core_options' , 'pp_capabilities' , 'pp_capabilities_roleset' );
}
add_action( 'admin_init' , 'pp_capabilities_options' );

/**
 * Initialise Prospress capabilities options
 * can be left null as values are stored in roles, must be declared for use with register_setting()
 *
 * @package Prospress
 * @since 1.1
 */
function pp_capabilities_init() {
	add_option( 'pp_capabilities', array(
			'publish' => array(),
			'private' => array(),
			'media' => array()
		)
	);
}
add_action( 'pp_activation', 'pp_capabilities_init' );

/** 
 * Allow site admins to choose which roles can do what to marketplace posts.
 * 
 * Admin's may want to allow or disallow users to create, edit and delete prospress marketplace 
 * posts. To do this without relying on the built-in post capability types, Prospress creates it's 
 * own type, 'prospress_post'. This function provides an admin menu for selecting which roles can do 
 * what to posts. Meaning, subscribers & custom roles can be given the capabiltiy to publish 
 * Prospress posts.
 * 
 * @package Prospress
 * @subpackage Posts
 * @version 1.1
 * @since 0.1
 */
function pp_capabilities_settings_page() { 
	global $wp_roles;
				
	$post_type = 'Auctions'; // Less confusing referring to Prospress posts as Auctions when that is the only option for a Prospress post, this will be changed once there are more options available

	$role_names = $wp_roles->get_names();
	$roles = array();

	foreach ( $role_names as $key => $value ) {
		$roles[ $key ] = get_role( $key );
		$roles[ $key ]->display_name = $value;
	}
	?>

	<div class="prospress-capabilities">
		<h3><?php _e( 'Capabilities', 'prospress' ); ?></h3>
		<p><?php printf( __( 'All registered users can make bids and only Administrators can edit and delete %s, but you can control which users are able to publish and edit %s.', 'prospress' ), $post_type, $post_type ); ?></p>

		<div class="prospress-capability">
			<h4><?php printf( __( "Publish %s", 'prospress' ), $post_type ); ?></h4>
			<?php foreach( $roles as $role ): ?>
			<label for="<?php echo $role->name; ?>-publish">
				<input type="checkbox" id="<?php echo $role->name; ?>-publish" name="pp_capabilities[publish][<?php echo $role->name; ?>]" <?php checked( @$role->capabilities[ 'publish_prospress_posts' ], 1 ); ?> />
				<?php echo $role->display_name; ?>
			</label>
			<?php endforeach; ?>
		</div>
		<div class="prospress-capability">
			<h4><?php printf( __( "View Private %s", 'prospress' ), $post_type ); ?></h4>
			<?php foreach ( $roles as $role ): ?>
			<label for="<?php echo $role->name; ?>-private">
				<input type="checkbox" id="<?php echo $role->name; ?>-private" name="pp_capabilities[private][<?php echo $role->name; ?>]" <?php checked( @$role->capabilities[ 'read_private_prospress_posts' ], 1 ); ?> />
				<?php echo $role->display_name; ?>
			</label>
			<?php endforeach; ?>
		</div>
		<div class="prospress-capability">
			<h4><?php printf( __( "Upload Media", 'prospress' ) ); ?></h4>
			<?php foreach ( $roles as $role ): ?>
			<label for="<?php echo $role->name; ?>-media">
				<input type="checkbox" id="<?php echo $role->name; ?>-media" name="pp_capabilities[media][<?php echo $role->name; ?>]" <?php checked( @$role->capabilities[ 'upload_files' ], 1 ); ?> />
				<?php echo $role->display_name; ?>
			</label>
			<?php endforeach; ?>
		</div>

	</div>
<?php
}
add_action( 'pp_core_settings_page', 'pp_capabilities_settings_page' );


/** 
 * Save capabilities when the admin page is submitted. As the settings don't need to be stored in 
 * the options table of the database, they're not added to the whitelist as is typically done with
 * this filter, instead they're added to the appropriate roles.
 * 
 * @package Prospress
 * @subpackage Posts
 * @since 0.1
 */
function pp_capabilities_roleset( $pp_capabilities ) {
	global $wp_roles;

	if( isset( $pp_capabilities ) && empty( $pp_capabilities ) )
		$pp_capabilities = array();

	$roles = $wp_roles->get_names();

	foreach ( $roles as $role => $value ):
		//Publish Auctions
		if ( isset( $pp_capabilities['publish'][$role] ) ) {
			$wp_roles->add_cap( $role , 'edit_prospress_posts');
			$wp_roles->add_cap( $role , 'publish_prospress_posts');
		} else {
			$wp_roles->remove_cap( $role , 'publish_prospress_posts' );
			$wp_roles->remove_cap( $role , 'edit_prospress_posts' );
		}

		//View Private Auctions
		if ( isset( $pp_capabilities['private'][$role] ) ) {
			$wp_roles->add_cap( $role , 'read_private_prospress_posts' );
		} else {
			$wp_roles->remove_cap( $role , 'read_private_prospress_posts' );
		}

		//Upload Media
		if ( isset( $pp_capabilities['media'][$role] ) ) {
			$wp_roles->add_cap( $role , 'upload_files' );
		} else {
			$wp_roles->remove_cap( $role , 'upload_files' );
		}
	endforeach;
}

/** 
 * Custom Post meta capabilities are not mapped by WordPress, so need to manually
 * map them.
 * 
 * @package Prospress
 * @subpackage Posts
 * @version 1.1
 * @since 0.1
 */
function pp_map_meta_cap( $caps, $cap, $user_id, $args ){

	if( $cap == 'edit_prospress_post' ) {

		$author_data = get_userdata( $user_id );

		$post = get_post( $args[0], OBJECT );
		$post = (object)$post; // overcome bug in WP returning post as array even when OBJECT specified

		$post_type = get_post_type_object( $post->post_type );

		$post_author_data = get_userdata( $post->post_author );

		if ( is_object( $post_author_data ) && $user_id == $post_author_data->ID ) {

			if ( 'publish' == $post->post_status ) {
				$caps[0] = 'edit_published_prospress_posts';
			} elseif ( 'private' == $post->post_status ) {
				$caps[0] = 'edit_private_prospress_posts';
			} elseif ( 'trash' == $post->post_status ) {
				if ('publish' == get_post_meta($post->ID, '_wp_trash_meta_status', true) )
					$caps[0] = 'edit_published_prospress_posts';
			} elseif ( 'completed' == $post->post_status ) {
				$caps[0] = 'edit_completed_prospress_posts';
			} else {
				$caps[0] = 'edit_prospress_posts';
				$caps[] = 'publish_prospress_posts';
			}
		} else {
			$caps[0] = 'edit_others_prospress_posts';

			if ( 'publish' == $post->post_status )
				$caps[] = 'edit_published_prospress_posts';
			elseif ( 'private' == $post->post_status )
				$caps[] = 'edit_private_prospress_posts';
		}
	} elseif( $cap == 'delete_prospress_post' ) {
		$author_data = get_userdata( $user_id );
		$post = get_post( $args[0] );

		if ( '' != $post->post_author ) {
			$post_author_data = get_userdata( $post->post_author );
		} else {
			//No author, default to current user
			$post_author_data = $author_data;
		}

		if ( is_object( $post_author_data ) && $user_id == $post_author_data->ID ) {

			if ( 'publish' == $post->post_status ) {
				$caps[0] = 'delete_published_prospress_posts';
			} elseif ( 'trash' == $post->post_status ) {
				if ('publish' == get_post_meta($post->ID, '_wp_trash_meta_status', true) )
					$caps[0] = 'delete_published_prospress_posts';
			} else {
				$caps[0] = 'delete_prospress_posts';
			}
		} else {
			$caps[0] = 'edit_others_prospress_posts';

			if ( 'publish' == $post->post_status || 'private' == $post->post_status )
				$caps[] = 'delete_published_prospress_posts';
			else 
				$caps[] = 'delete_prospress_posts';
		}
	} elseif( $cap == 'read_prospress_post' ) {
		$post = get_post( $args[0] );

		if ( 'private' != $post->post_status ) {
			$caps[0] = 'read';
		} else {
			$author_data = get_userdata( $user_id );
			$post_author_data = get_userdata( $post->post_author );
			if ( is_object( $post_author_data ) && $user_id == $post_author_data->ID )
				$caps[0] = 'read';
			else
				$caps[0] = 'read_private_prospress_posts';
		}
	}

	return $caps;
}
add_filter( 'map_meta_cap', 'pp_map_meta_cap', 10, 4 );