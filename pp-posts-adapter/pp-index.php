<?php
/*
Template Name: Auctions Index
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
		<?php error_log( 'starting Auctions page if.' ); ?>
		<?php if ( have_posts() ) while ( have_posts() ) : the_post(); ?>
			<h1><?php the_title(); ?></h1>
			<p><?php the_content(); ?></p>
		<?php endwhile; ?>
		<?php error_log( 'ended Auctions page query, starting next query.' ); ?>
			<?php $loop = new WP_Query( array( 'post_type' => $bid_system->name ) ); ?>
			<?php //query_posts( array( 'post_type' => $bid_system->name ) ); ?>
			<?php if ( $loop->have_posts() ) : while ( $loop->have_posts() ) : $loop->the_post(); ?>
			<?php //if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>

				<h2 class="pp-title"><a href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title_attribute(); ?>"><?php the_title(); ?></a></h2>
				<div class="pp-dates">
					<div class="pp-publish-date"><?php  _e( 'Published: '); the_time('F jS, Y'); ?></div>
					<div class="pp-end-date"><?php _e( 'Ending: '); the_post_end_date(); ?></div>
				</div>
				<div class="pp-price"><?php _e( 'Current Bid: '); $bid_system->the_winning_bid_value(); ?></div>
				<?php the_content(); ?>

				<p class="postmetadata">Posted in <?php the_category(', '); ?></p>

			<?php endwhile; else: ?>

				<p>No marketplace listings yet.</p>

			<?php endif; ?>
		</div>
	</div>

	<div id="pp-sidebar" class="pp-sidebar">
		<ul class="xoxo">
			<?php dynamic_sidebar( $bid_system->name . '-sidebar' ); ?>
		</ul>
	</div>

<?php //get_sidebar(); ?>
<?php get_footer(); ?>
