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
global $market_system;

wp_enqueue_style( 'prospress',  PP_CORE_URL . '/prospress.css' );

?>
<?php get_header(); ?>
	<div id="container">
		<div id="content">
		<?php error_log( 'starting Auctions page if.' ); ?>
		<?php if ( have_posts() ) while ( have_posts() ) : the_post(); ?>
			<h1><?php the_title(); ?></h1>
			<p><?php the_content(); ?></p>
		<?php endwhile; ?>
		<?php error_log( 'ended Auctions page query, starting next query.' ); ?>
			<?php $pp_loop = new WP_Query( array( 'post_type' => $market_system->name ) ); ?>
			<?php //query_posts( array( 'post_type' => $market_system->name ) ); ?>
			<?php if ( $pp_loop->have_posts() ) : while ( $pp_loop->have_posts() ) : $pp_loop->the_post(); ?>
			<?php //if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>

				<h2 class="pp-title"><a href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title_attribute(); ?>"><?php the_title(); ?></a></h2>
				<div class="pp-dates">
					<div class="pp-publish-date"><?php  _e( 'Published: ', 'prospress' ); the_time('F jS, Y'); ?></div>
					<div class="pp-end-date"><?php _e( 'Ending: ', 'prospress' ); the_post_end_date(); ?></div>
				</div>
				<div class="pp-price"><?php _e( 'Current Bid: ', 'prospress' ); $market_system->the_winning_bid_value(); ?></div>
				<?php the_content(); ?>

				<p class="postmetadata">Posted in <?php the_category(', '); ?></p>

			<?php endwhile; else: ?>

				<p>No marketplace listings yet.</p>

			<?php endif; ?>
		</div>
	</div>

	<div id="pp-sidebar" class="pp-sidebar">
		<ul class="xoxo">
			<?php dynamic_sidebar( $market_system->name . '-sidebar' ); ?>
		</ul>
	</div>
<?php get_footer(); ?>
