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

	$args = array(
			'label' => __('Auctions'),
			'public' => true,
			'show_ui' => true,
			'rewrite' => array( 'slug' => $bid_system->name, 'with_front' => false ),
             'supports' => array(
							'title',
							'editor',
							'thumbnail',
							'post-thumbnails',
							'comments',
							'revisions')
	);

	register_post_type( $bid_system->name, $args );
}
add_action('init', 'pp_post_type');