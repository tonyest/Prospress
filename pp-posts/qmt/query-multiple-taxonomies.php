<?php
/*
The Fantastic Query Multiple Taxonomies Plugin by scribu http://scribu.net/wordpress/query-multiple-taxonomies/ heavily moded for Prospress taxonomies.
Version 1.1.1
*/

class PP_QMT_Core {

	private static $post_ids = array();
	private static $actual_query = array();
	private static $url = '';

	function init() {
		add_action( 'init', array( __CLASS__, 'builtin_tax_fix' ) );

		//Hook function to 
		add_action('parse_query', array( __CLASS__, 'query' ) );

		//Hook function to change title of multitax search pages to include the taxonomies being queried
		add_filter( 'wp_title', array( __CLASS__, 'set_title' ), 10, 3);

		remove_action('template_redirect', 'redirect_canonical');
	}

	function get_actual_query() {
		return self::$actual_query;
	}

	function get_canonical_url() {
		return self::$url;
	}

	//Sets the title of the webpage to the query taxonomy attributes
	function set_title( $title, $sep, $seplocation = '' ) {

		if ( !is_pp_multitax() )
			return $title;

		$newtitle[] = self::get_title();
		$newtitle[] = " $sep ";

		if ( ! empty($title) )
			$newtitle[] = $title;

		if ( 'right' != $seplocation )
			$newtitle = array_reverse($newtitle);

		return implode('', $newtitle);
	}

	function get_title() {
		$title = array();
		foreach ( self::$actual_query as $tax => $value ) {
			$key = get_taxonomy($tax)->label;
			$value = explode('+', $value);
			foreach ( $value as &$slug )
				$slug = get_term_by('slug', $slug, $tax)->name;
			$value = implode('+', $value);

			$title[] .= "$key: $value";
		}

		return implode('; ', $title);
	}

	function builtin_tax_fix() {
		$tmp = array(
			'post_tag' => 'tag',
			'category' => 'category_name'
		);

		foreach ( get_taxonomies(array('_builtin' => true), 'object') as $taxname => $taxobj )
			if ( isset($tmp[$taxname]) )
				$taxobj->query_var = $tmp[$taxname];
	}

	function query( $wp_query ) {
		global $market_systems;
		
		$market = $market_systems['auctions'];

		self::$url = $market->get_index_permalink();

		$post_type = $market->name();

		$query = array();
		foreach ( get_object_taxonomies($post_type) as $taxname ) {
			$taxobj = get_taxonomy($taxname);

			if ( ! $qv = $taxobj->query_var )
				continue;

			if ( ! $value = $wp_query->get($qv) )
				continue;

			self::$actual_query[$taxname] = $value;
			self::$url = add_query_arg($qv, $value, self::$url);

			foreach ( explode(' ', $value) as $slug )
				$query[] = array($slug, $taxname);

		}

		if ( empty($query) )
			return;

		// Prepending marketplace index to url don't actually want to return it
		unset($wp_query->query['pagename']);
		$wp_query->set('pagename', '');

		if ( ! self::find_posts($query, $post_type ) ){
			return $wp_query->set_404();
		}

		$is_feed = $wp_query->is_feed;
		$paged = $wp_query->get('paged');

		$wp_query->init_query_flags();

		$wp_query->is_feed = $is_feed;
		$wp_query->set('paged', $paged);

		$wp_query->set('post_type', $post_type);
		$wp_query->set('post__in', self::$post_ids);

		$wp_query->is_pp_multitax = true;
		$wp_query->is_archive = true;
	}

	private function find_posts( $query, $post_type ) {
		global $wpdb;

		// get an initial set of ids, to intersect with the others
		if ( ! $ids = self::get_objects( array_shift( $query ) ) )
			return false;

		foreach ( $query as $qv ) {

			if ( ! $posts = self::get_objects($qv) )
				return false;

			$ids = array_intersect($ids, $posts);
		}

		if ( empty($ids) )
			return false;

		// select only published posts
		$post_type = esc_sql($post_type);
		$ids = $wpdb->get_col("
			SELECT ID FROM $wpdb->posts 
			WHERE post_type = '$post_type'
			AND post_status = 'publish' 
			AND ID IN (" . implode(',', $ids). ")
		");

		if ( empty($ids) )
			return false;

		self::$post_ids = $ids;

		return true;
	}

	private function get_objects($qv) {

		list($term_slug, $tax) = $qv;

		if ( ! $term = get_term_by('slug', $term_slug, $tax) )
			return false;

		$terms = array($term->term_id);
		
		$terms = array_merge($terms, get_term_children($term->term_id, $tax));

		$ids = get_objects_in_term($terms, $tax);

		if ( empty($ids) )
			return false;

		return $ids;
	}

	function get_terms( $tax ) {
		if ( empty( self::$post_ids ) )
			return get_terms( $tax );

		global $wpdb;

		$query = $wpdb->prepare("
			SELECT DISTINCT term_id
			FROM $wpdb->term_relationships
			JOIN $wpdb->term_taxonomy USING (term_taxonomy_id)
			WHERE taxonomy = %s
			AND object_id IN (" . implode(',', self::$post_ids) . ")
		", $tax);

		$term_ids = $wpdb->get_col($query);

		return get_terms($tax, array('include' => implode(',', $term_ids)));
	}

	public function get_url( $key, $value, $base = '' ) {
		global $wpdb;

		if ( empty( $base ) )
			$base = self::$url;

		if ( empty( $value ) )
			return remove_query_arg( $key, $base );

		$value = trim( implode( '+', $value ), '+' );

		return add_query_arg($key, $value, $base);
	}
}
PP_QMT_Core::init();


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


function pp_qmt_walk_terms( $taxonomy, $args = '' ) {
	$terms = PP_QMT_Core::get_terms( $taxonomy );

	if ( empty( $terms ) )
		return 'No ' . $taxonomy . ' terms assigned.';

	$walker = new PP_QMT_Term_Walker( $taxonomy );

	$args = wp_parse_args( $args, array(
		'style' => 'list',
		'use_desc_for_title' => false,
		'addremove' => true,
	) );

	return $walker->walk( $terms, 0, $args );
}


/**
 * When a taxonomy name is changed, its name should also be changed in any filter widgets referring to it.
 *
 * @since 1.0.3
 **/
function pp_qmt_update_widget( $old_tax_name, $new_tax_name ){

	$taxonomy_filter_widgets = get_option( 'widget_taxonomy-filter' );

	foreach( $taxonomy_filter_widgets as &$details )
		if( $details[ 'taxonomy' ] == $old_tax_name )
			$details[ 'taxonomy' ] = $new_tax_name;

	update_option( 'widget_taxonomy-filter', $taxonomy_filter_widgets );
}
add_action( 'pp_taxonomy_edit', 'pp_qmt_update_widget', 10, 2 );


/**
 * When a taxonomy is deleted, any filter widgets referring to it should also be deleted.
 *
 * @since 1.0.3
 **/
function pp_qmt_delete_widget( $deleted_taxonomy ){

	$taxonomy_filter_widgets = get_option( 'widget_taxonomy-filter' );

	foreach( $taxonomy_filter_widgets as $key => $details )
		if( $details[ 'taxonomy' ] == $deleted_taxonomy )
			unset( $taxonomy_filter_widgets[ $key ] );

	update_option( 'widget_taxonomy-filter', $taxonomy_filter_widgets );
}
add_action( 'pp_taxonomy_delete', 'pp_qmt_delete_widget' );


function _is_pp_multitax() {
	global $wp_query;

	return @$wp_query->is_pp_multitax;
}