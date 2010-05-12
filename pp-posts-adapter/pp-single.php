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
global $bid_system;

?>
<link rel="stylesheet" type="text/css" media="all" href="<?php echo PP_CORE_URL . '/prospress.css'; ?>">
<?php get_header(); ?>
	<div id="container">
		<div id="content">
			<?php if ( have_posts() ) while ( have_posts() ) : the_post(); ?>
			<h2 class="pp-title"><?php the_title();?></h2>
			<!-- Display the date (November 16th, 2009 format) and a link to other posts by this posts author. -->
			<div class="pp-end-date"><?php _e('Ending: '); the_post_end_date(); ?></div>
			<div class="publish-date"><?php _e('Published: ');  the_time('F jS, Y') ?> by <?php the_author_posts_link() ?></div>

			<?php echo $bid_system->bid_form(); ?>
			<!-- Display the Post's Content in a div box. -->
			<?php the_content(); ?>

			<p class="postmetadata">Posted in <?php the_category(', '); ?></p>
			<?php endwhile; // end of the loop. ?>
		</div>
	</div>

	<div id="pp-sidebar" class="pp-sidebar">
		<ul class="xoxo">
			<?php dynamic_sidebar( $bid_system->name . '-sidebar' ); ?>
		</ul>
	</div>
<?php get_footer(); ?>
