<?php
/**
 * @package Prospress
 * @author Brent Shepherd
 * @version 0.2
 */

function pp_post_type() {
	global $bid_system;

	$defaults = array(
	    'label' => false,
	    'publicly_queryable' => null,
	    'exclude_from_search' => null,
	    '_builtin' => false,
	    '_edit_link' => 'post.php?post=%d',
	    'capability_type' => 'post',
	    'hierarchical' => false,
	    'public' => false,
	    'rewrite' => true,
	    'query_var' => true,
	    'supports' => array(),
	    'register_meta_box_cb' => null,
	    'taxonomies' => array(),
	    'show_ui' => null
	);
	/*
	$default_capabilities = array(
		'edit_post' => 'edit_post',
		'edit_posts' => 'edit_posts',
		'edit_others_posts' => 'edit_others_posts',
		'publish_posts' => 'publish_posts',
		'read_post' => 'read_post',
		'read_private_posts' => 'read_private_posts',
		'delete_post' => 'delete_post',
	);

	$bid_system_capabilities = array(
		'edit_post' => 'edit_' . $bid_system->name,
		'edit_posts' => 'edit_' . $bid_system->name,
		'publish_posts' => 'publish_' . $bid_system->name,
		'delete_post' => 'delete_' . $bid_system->name 
		);

	$capabilities = array_merge( $default_capabilities, $bid_system_capabilities );
	error_log( 'capabilities = ' . print_r( $capabilities, true ) );
	*/
	$args = array(
			'label' => ucfirst( $bid_system->name ),
			'public' => true,
			'show_ui' => true,
			'rewrite' => array( 'slug' => $bid_system->name, 'with_front' => false ),
			'capability_type' => 'post',
			//'capabilities' => $capabilities, @TODO when WP3.0 bugs are fixed come back to this.
            'supports' => array(
							'title',
							'editor',
							'thumbnail',
							'post-thumbnails',
							'comments',
							'revisions'),
					);

	register_post_type( $bid_system->name, $args );

	// Get the subscriber role & add capabilities to it
	/*
	$role = get_role( 'subscriber' );

	foreach ( $bid_system_capabilities as $cap ){
		error_log( 'cap = ' . print_r( $cap, true ) );
		$role->add_cap( $cap );
	}
	error_log( 'after add_cap, role = ' . print_r( $role, true ) );
	//$role->remove_cap( 'edit_posts' );
	$role = get_role( 'administrator' );
	error_log( 'role = ' . print_r( $role, true ) );
	$role = get_role( 'editor' );
	error_log( 'role = ' . print_r( $role, true ) );
	$role = get_role( 'subscriber' );
	error_log( 'role = ' . print_r( $role, true ) );
	*/
}
add_action('init', 'pp_post_type');