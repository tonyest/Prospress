<?php
/*
Template Name: Single Prospress page
*/
/**
 * The main template file for marketplace listings.
 *
 * @package Prospress
 * @subpackage Theme
 * @since 0.7
 */
global $market_system;

wp_enqueue_style( 'prospress',  PP_CORE_URL . '/prospress.css' );

?>
<?php get_header(); ?>
	<div id="container">
		<div id="content">
		<?php if ( have_posts() ) while ( have_posts() ) : the_post(); ?>
			<h2 class="pp-title entry-title"><?php the_title();?></h2>
			<div class=""><?php _e('Ending: ', 'prospress' ); the_post_end_date(); ?></div>
			<div class=""><?php _e('Published: ', 'prospress' );  the_time('F jS, Y'); _e(' by '); the_author() ?></div>

			<?php the_bid_form(); ?>

			<div class="pp-the-content">
				<?php the_content(); ?>
			</div>

			<p class="postmetadata">Posted in <?php the_category(', '); ?></p>

			<div id="nav-below" class="navigation">
				<div class="nav-index"><a href="<?php pp_get_index_permalink(); ?>"><?php printf( __("&larr; Return to %s Index", 'Prospress'), ucfirst( $market_system->name ) ); ?></a></div>
			</div>

			<?php comments_template( '', true ); ?>

		<?php endwhile; // end of the loop. ?>
		</div>
	</div>

	<div id="sidebar" class="prospress-sidebar">
		<ul class="xoxo">
			<?php dynamic_sidebar( $market_system->name . '-sidebar' ); ?>
		</ul>
	</div>
<?php get_footer(); ?>
