<?php
/**
 * @package Prospress
 * @author Brent Shepherd
 * @version 0.1
 */

/** 
 * A custom post type especially for Prospress posts. 
 * 
 * Admin's may want to allow or disallow users to create, edit and delete marketplace posts. 
 * To do this without relying on the post capability type, Prospress creates it's own type. 
 * 
 * @package Prospress
 * @since 0.1
 * 
 * @global PP_Market_System $market_system Prospress market system object for this marketplace.
 */
function pp_post_type() {
	global $market_system;

	$args = array(
			'label' 	=> $market_system->display_name(),
			'public' 	=> true,
			'show_ui' 	=> true,
			'rewrite' 	=> array( 'slug' => $market_system->name(), 'with_front' => false ),
			'capability_type' => 'prospress_post', //generic to cover all Prospress marketplace types
			'show_in_nav_menus' => false,
			'supports' 	=> array(
							'title',
							'editor',
							'thumbnail',
							'post-thumbnails',
							'comments',
							'revisions' ),
			'labels'	=> array( 'name'	=> $market_system->display_name(),
							'singular_name'	=> $market_system->singular_name(),
							'add_new_item'	=> sprintf( __( 'Add New %s', 'prospress' ), $market_system->singular_name() ),
							'edit_item'		=> sprintf( __( 'Edit %s', 'prospress' ), $market_system->singular_name() ),
							'new_item'		=> sprintf( __( 'New %s', 'prospress' ), $market_system->singular_name() ),
							'view_item'		=> sprintf( __( 'View %s', 'prospress' ), $market_system->singular_name() ),
							'search_items'	=> sprintf( __( 'Seach %s', 'prospress' ), $market_system->display_name() ),
							'not_found'		=> sprintf( __( 'No %s found', 'prospress' ), $market_system->display_name() ),
							'not_found_in_trash' => sprintf( __( 'No %s found in Trash', 'prospress' ), $market_system->display_name() ) )
				);

	register_post_type( $market_system->name(), $args );
}
add_action( 'init', 'pp_post_type' );