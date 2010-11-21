<?php

class PP_Taxonomy_Filter_Widget extends WP_Widget {

	function PP_Taxonomy_Filter_Widget() {
		global $market_systems;

		$this->defaults = array(
			'title' => '',
			'taxonomy' => ''
		);

		$widget_ops = array(
			'description' => sprintf( __( 'Filter %s by your custom taxonomies' ), $market_system[ 'auctions' ]->label )
		);

		parent::WP_Widget( 'taxonomy-filter', __( 'Prospress Taxonomy Filter', 'prospress' ), $widget_ops );
	}

	function widget($args, $instance) {
		extract($args);
		extract(wp_parse_args($instance, $this->defaults));

		echo $before_widget;

		if ( empty($taxonomy) ) {
			echo '<h6>' . __('No taxonomy selected.', 'prospress' ) . '</a>';
		}
		else {
			if ( empty($title) )
				$title = get_taxonomy($instance['taxonomy'])->label;
			$title = apply_filters('widget_title', $title, $instance, $this->id_base);

			$query = PP_QMT_Core::get_actual_query();
			if ( isset($query[$taxonomy]) ) {
				$new_url = PP_QMT_Core::get_url($taxonomy, '');
				$title .= " <a class='clear-taxonomy' href='$new_url'>(-)</a>";
			}

			if ( ! empty($title) )
				echo $before_title . $title . $after_title;

			echo '<ul>' . pp_qmt_walk_terms( $taxonomy ) . '</ul>';
		}

		echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance[ 'title' ] = strip_tags( $new_instance[ 'title' ] );
		$instance[ 'taxonomy' ] = strip_tags( $new_instance[ 'taxonomy' ] );

		return $instance;
	}

	function form($instance) {
		global $market_systems; 

		if ( empty($instance) )
			$instance = $this->defaults;
		?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'prospress' ) ?></label>
		<input type="text" class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" value="<?php if (isset ( $instance['title'])) {echo esc_attr( $instance['title'] );} ?>" /></p>
		<?php

		$current_taxonomy = ( !empty( $instance[ 'taxonomy' ] ) && taxonomy_exists( $instance[ 'taxonomy' ] ) ) ? $instance[ 'taxonomy' ] : '';
		?>
		<p><label for="<?php echo $this->get_field_id('taxonomy'); ?>"><?php _e('Taxonomy:', 'prospress' ) ?></label>
		<select class="widefat" id="<?php echo $this->get_field_id('taxonomy'); ?>" name="<?php echo $this->get_field_name('taxonomy'); ?>">
		<?php foreach ( get_object_taxonomies( $market_systems[ 'auctions' ]->name() ) as $taxonomy ) :
				$tax = get_taxonomy( $taxonomy );
		?>
			<option value="<?php echo esc_attr($taxonomy) ?>" <?php selected( $taxonomy, $current_taxonomy ) ?>><?php echo $tax->labels->name; ?></option>
		<?php endforeach; ?>
		</select></p><?php
	}
}
add_action( 'widgets_init', create_function( '', 'return register_widget( "PP_Taxonomy_Filter_Widget" );' ) );

function pp_qmt_walk_terms( $taxonomy, $args = '' ) {
	$terms = PP_QMT_Core::get_terms( $taxonomy );

	if ( empty( $terms ) )
		return '';

	$walker = new PP_QMT_Term_Walker( $taxonomy );

	$args = wp_parse_args( $args, array(
		'style' => 'list',
		'use_desc_for_title' => false,
		'addremove' => true,
	) );

	return $walker->walk( $terms, 0, $args );
}

class PP_QMT_Term_Walker extends Walker_Category {

	public $tree_type = 'term';

	private $taxonomy;
	private $query;

	public $selected_terms = array();

	function __construct($taxonomy) {
		$this->taxonomy = $taxonomy;
		$this->qv = get_taxonomy($taxonomy)->query_var;

		$this->query = PP_QMT_Core::get_actual_query();

		$this->selected_terms = explode(' ', @$this->query[$taxonomy]);
	}

	function start_el(&$output, $term, $depth, $args) {
		global $market_systems;
		extract($args);

		$term_name = esc_attr($term->name);
		$link = '<a href="' . get_term_link($term, $this->taxonomy) . '" ';
		if ( $use_desc_for_title == 0 || empty($term->description) )
			$link .= 'title="' . sprintf(__( 'View all %s filed under %s', 'prospress' ), $market_system[ 'auctions' ]->label, $term_name) . '"';
		else
			$link .= 'title="' . esc_attr( strip_tags( $term->description ) ) . '"';
		$link .= '>';
		$link .= $term_name . '</a>';

		if ( $args['addremove'] ) {
			$tmp = $this->selected_terms;
			$i = array_search($term->slug, $tmp);
			if ( false !== $i ) {
				unset($tmp[$i]);

				$new_url = PP_QMT_Core::get_url($this->qv, $tmp);
				$link .= " <a class='remove-term' href='$new_url'>(-)</a>";
				
			}
			else {
				$tmp[] = $term->slug;

				$new_url = PP_QMT_Core::get_url($this->qv, $tmp);
				$link .= " <a class='add-term' href='$new_url'>(+)</a>";
			}
		}

		if ( 'list' == $args['style'] ) {
			$output .= "\t<li";
			$class = 'term-item term-item-'.$term->term_id;
			if ( in_array($term->slug, $this->selected_terms) )
				$class .=  ' current-term';
//			elseif ( $term->term_id == $_current_term->parent )
//				$class .=  ' current-term-parent';
			$output .=  ' class="'.$class.'"';
			$output .= ">$link\n";
		} else {
			$output .= "\t$link<br />\n";
		}
	}
}

