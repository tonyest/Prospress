<?php
/*
Template Name: Auctions Taxonomy Index
*/
/**
 * The template for displaying a list of marketplace listings by taxonomy.
 *
 * @package Prospress
 * @subpackage Theme
 * @since 0.1
 */
?>

<?php
//get taxonomy breadcrumb tags
$taxonomy = esc_attr( get_query_var( 'taxonomy' ) );
$tax_obj = get_taxonomy( $taxonomy );
$term_obj = get_term_by( 'slug', esc_attr( get_query_var( 'term' ) ), $taxonomy );
$term_description = term_description( $term_obj->term_id, $taxonomy );
?>

<?php get_header(); ?>
	<div id="container" class="prospress-container">
		<div id="content" class="prospress-content">
			<?php error_log( 'tax_obj = ' . print_r( $tax_obj, true ) ); ?>
			<?php error_log( 'term_obj = ' . print_r( $term_obj, true ) ); ?>
			<h1 class="prospress-title entry-title">
				<?php printf( '%s &raquo; %s', $tax_obj->labels->name, $term_obj->name ); ?>
			</h1>
			<?php 
			if ( !empty( $term_description ) )
				echo '<div class="prospress-archive-meta">' . $term_description . '</div>';
			?>
			<div class="end-header"><?php _e( 'Ending', 'prospress' ); ?></div>
			<div class="price-header"><?php _e( 'Price', 'prospress' ); ?></div>

		<?php if ( have_posts() ): while ( have_posts() ) : the_post(); ?>

			<div class="pp-post">
				<div class="pp-post-content"> 
					<div class='pp-end' id="<?php echo get_post_end_time( $post_id, 'timestamp', 'gmt' ); ?>">
						<?php the_post_end_time( '', 2, '<br/>' ); ?>
					</div>
					<div class="pp-price"><?php the_winning_bid_value(); ?></div>
					<h2 class="pp-title entry-title">
						<a href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title_attribute(); ?>">
							<?php the_title(); ?>
						</a>
					</h2>
					<?php if ( function_exists( 'has_post_thumbnail' ) && has_post_thumbnail() ) : ?>
						<div class="pp-thumbnail">
						   <a href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>" >
						   <?php the_post_thumbnail( array( 100,100 )); ?>
						   </a>
						</div>
					 <?php endif; ?>
					<div class="pp-excerpt">
						<?php the_excerpt(); ?>
						<a href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title_attribute(); ?>">
							<?php printf( __( 'View %s &raquo', 'prospress' ), $market->labels[ 'singular_name' ] ); ?>
						</a>
					</div>
					<div class="pp-publish-details">
						<?php  _e( 'Published: ', 'prospress' ); the_time('F jS, Y'); ?>
						<?php _e( 'by ', 'prospress'); the_author(); ?>
					</div>
				</div>
			</div>

			<?php endwhile; else: ?>

				<p>No <?php echo $market->label; ?>.</p>

			<?php endif; ?>
			<div class="navigation">
				<div class="alignleft"><?php previous_posts_link('&laquo; '.__( 'Previous Items', 'prospress' )) ?></div>
				<div class="alignright"><?php next_posts_link( __( 'Next Items', 'prospress' ).' &raquo;','') ?></div>
			</div>
		</div>
	</div>

	<div id="sidebar" class="prospress-sidebar">
		<ul class="xoxo">
			<?php dynamic_sidebar( $market->name() . '-index-sidebar' ); ?>
		</ul>
	</div>
<?php get_footer(); ?>
