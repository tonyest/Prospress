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
			<?php if (!function_exists('dynamic_sidebar') || !dynamic_sidebar( $market->name(). '-single-sidebar' ) ) : // begin primary sidebar widgets
			 	$args = array(
				'before_widget' => '<li id="pp_countdown_widget-default" class="widget-container widget-pp-countdown">',
				'after_widget'  => '</li>',
				'before_title'  => '<h3 class="widget-title">',
				'after_title'   => '</h3>' );
				$instance = array(
					'title' => 'Ending:'
				);
			the_widget('PP_Countdown_Widget', $instance ,$args);
			 	$args = array(
				'before_widget' => '<li id="pp_feedback_score_widget-default" class="widget-container pp-feedback-score">',
				'after_widget'  => '</li>',
				'before_title'  => '<h3 class="widget-title">',
				'after_title'   => '</h3>' );
				$instance = array(
					'title' => 'Feedback Score'
				);
			the_widget('PP_Feedback_Score_Widget', $instance ,$args);
			 	$args = array(
				'before_widget' => '<li id="pp_admin_widget-default" class="widget-container widget-pp-admin">',
				'after_widget'  => '</li>',
				'before_title'  => '<h3 class="widget-title">',
				'after_title'   => '</h3>' );
				$instance = array(
					'title' => 'Your Prospress'
				);
			the_widget('PP_Admin_Widget', $instance ,$args);
 			?>	
			<?php endif;?>
		</ul>
	</div>
<?php get_footer(); ?>
