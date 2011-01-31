<?php
/*
Template Name: Single Prospress Post
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
				<div class="nav-index"><a href="<?php echo $market->get_index_permalink(); ?>"><?php printf( __("&larr; Return to %s Index", 'Prospress'), $market->name() ); ?></a></div>
			</div>

			<?php comments_template( '', true ); ?>

		<?php endwhile; // end of the loop. ?>
		</div>
	</div>
	<div id="sidebar" class="prospress-sidebar">
		<ul class="xoxo">
			
			<!-- Add default countdown widget if no widgets currently registered in single auctions sidebar -->
			<?php if (!function_exists('dynamic_sidebar') || !dynamic_sidebar( $market->name(). '-single-sidebar' ) && function_exists( 'the_post_end_time' ) ) : // begin primary sidebar widgets ?>
				<li id="pp_countdown-default" class="widget-container widget_pp_countdown">
					<h3 class="widget-title">Ending:</h3>
					<div class="countdown" id="' . get_post_end_time( '', 'timestamp', 'gmt' ) . '">
						<?php the_post_end_time(); ?>
					<div>
				</li>
			<?php endif;?>
			
		</ul>
	</div>
<?php get_footer(); ?>
