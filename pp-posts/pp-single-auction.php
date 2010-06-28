<?php
/*
Template Name: Single Prospress page
*/
/**
 * This the default template for displaying a single Prospress post.
 * It includes all the basic elements for a Prospress post in a very 
 * neutral style.
 *
 * @package Prospress
 * @subpackage Theme
 * @since 0.1
 */
global $market_system;

wp_enqueue_style( 'prospress',  PP_CORE_URL . '/prospress.css' );

?>
<?php get_header(); ?>
	<div id="container">
		<div id="content">
		<?php if ( have_posts() ) while ( have_posts() ) : the_post(); ?>
			<h2 class="pp-title entry-title"><?php the_title();?></h2>

			<?php the_bid_form(); ?>

			<div class="pp-content">
				<?php the_content(); ?>
			</div>

			<div id="nav-below" class="navigation">
				<div class="nav-index"><a href="<?php pp_get_index_permalink(); ?>"><?php printf( __("&larr; Return to %s Index", 'Prospress'), $market_system->name() ); ?></a></div>
			</div>

			<?php comments_template( '', true ); ?>

		<?php endwhile; // end of the loop. ?>
		</div>
	</div>

	<div id="sidebar" class="prospress-sidebar">
		<ul class="xoxo">
			<?php dynamic_sidebar( $market_system->name() . '-single-sidebar' ); ?>
		</ul>
		<h3><?php _e( 'Details', 'prospress' ); ?></h3>
		<div class="pp-taxonomies">
			<?php pp_get_the_term_list(); ?>
		</div>
		<div class="pp-author">
			<?php _e('Seller: ', 'prospress' ); ?>
			<?php the_author() ?>
		</div>
	</div>
<?php get_footer(); ?>
