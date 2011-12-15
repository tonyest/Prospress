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
	<div id="container" class="prospress-container">
		<div id="content" class="prospress-content">
		<?php if ( have_posts() ) while ( have_posts() ) : the_post(); ?>
			<h2 class="pp-title entry-title"><?php the_title();?></h2>
			<?php if ( function_exists( 'has_post_thumbnail' ) && has_post_thumbnail() ) : ?>
				<div class="pp-thumbnail">
				   <a href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>" >
				   <?php the_post_thumbnail(); ?>
				   </a>
				</div>
			 <?php endif; ?>
			<?php the_bid_form(); ?>
			
			<?php do_action( 'pp_single_content' ); ?>

			<div class="pp-content">
				<?php the_content(); ?>
			</div>

			<div id="nav-below" class="navigation">
				<div class="nav-index"><a href="<?php echo $market->get_index_permalink(); ?>"><?php printf( __("&larr; Return to %s Index", 'Prospress'), $market->label ); ?></a></div>
			</div>

			<?php comments_template( '', true ); ?>

		<?php endwhile; // end of the loop. ?>
		</div><!-- #content -->
	</div><!-- #container -->
	<div id="sidebar" class="prospress-sidebar">
		<ul class="xoxo">
			<!-- Add default countdown widget if no widgets currently registered in single auctions sidebar -->
			<?php if (!function_exists('dynamic_sidebar') || !dynamic_sidebar( $market->name(). '-single-sidebar' ) ) : // begin primary sidebar widgets			
				the_widget('PP_Countdown_Widget', array(
					'title' => __('Ending:','prospress')
				) );
				the_widget('PP_Feedback_Score_Widget', array(
					'title' => __('Feedback Score','prospress')
				) );
				the_widget('PP_Admin_Widget', array(
					'title' => __('Your Prospress','prospress')
				) );
			endif;?>
		</ul>
	</div><!-- #sidebar -->
<?php get_footer(); ?>
