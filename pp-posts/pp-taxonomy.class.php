<?php

class PP_Taxonomy {

	public $name;
	public $admin_url;
	public $market_type;
	private $labels;	// An array with details of the market system to which this taxonomy belongs
	protected $add_tax;
	protected $edit_tax;

	public function __construct( $name, $args ) {

		$this->name 		= $name . '_tax';
		$this->market_type	= $name;
		$this->labels 		= $args[ 'labels' ];
		$this->admin_url	= admin_url( '/admin.php?page=' . $this->name );
		$this->add_tax		= 'add_' . $this->name . '_tax';
		$this->edit_tax		= 'edit_' . $this->name . '_tax';

		add_action( 'admin_menu', array( &$this, 'add_menu_page' ) );

		add_action( 'init', array( &$this, 'register_taxonomies' ), 0 );
	}

	public function add_menu_page() {
		$page_title = sprintf( __( 'Custom %s Taxonomies', 'prospress' ), $this->labels[ 'singular_name' ] );
		$menu_title = sprintf( __( '%s Taxonomies', 'prospress' ), $this->labels[ 'singular_name' ] );
		$menu_slug = $this->name;

		add_submenu_page( 'Prospress', $page_title, $menu_title, 'manage_categories', $menu_slug, array( &$this, 'controller' ) );
	}

	public function controller() {

		if( isset( $_POST[ $this->add_tax ] ) || isset( $_POST[ $this->edit_tax ] ) )
			$this->edit_taxonomies();
		elseif( isset( $_GET[ 'action' ] ) && $_GET[ 'action' ] == 'add_new' )
			$this->edit_tax_page();
		elseif( isset( $_GET[ 'deltax' ] ) )
			$this->delete_taxonomy();
		else
			$this->manage_taxonomies();
	}

	public function get_taxonomies() {
		return get_option( $this->name );
	}

	public function manage_taxonomies( $message = '' ) {
		?>
		<div class="wrap">
			<?php
			//check for success/error messages
			if ( !empty( $message ) ) { ?>
			    <div id="message" class="updated">
					<p><?php echo $message; ?></p>
			    </div>
			    <?php
			}
			screen_icon( 'prospress' );
			$add_url = add_query_arg( 'action', 'add_new', $this->admin_url );
			?>
			<h2><?php printf( __( '%s Taxonomies', 'prospress' ), $this->labels[ 'singular_name' ] ) ?><a href="<?php echo $add_url; ?>" class="button add-new-h2"><?php _e( "Add New", 'prospress' ); ?></a></h2>
			<?php 
			$taxonomy_types = get_option( $this->name );
			if( !empty( $taxonomy_types ) ) { ?>
		        <table class="widefat taxonomies fixed">
					<thead>
			        	<tr>
			            	<th><strong><?php _e('Name', 'prospress' );?></strong></th>
			                <th><strong><?php _e('Label', 'prospress' );?></strong></th>
			                <th><strong><?php _e('Singular Label', 'prospress' );?></strong></th>
			            	<th><strong><?php _e('Actions', 'prospress' );?></strong></th>
			            </tr>
					</thead>
					<tfoot>
			        	<tr>
			            	<th><strong><?php _e('Name', 'prospress' );?></strong></th>
			                <th><strong><?php _e('Label', 'prospress' );?></strong></th>
			                <th><strong><?php _e('Singular Label', 'prospress' );?></strong></th>
			            	<th><strong><?php _e('Actions', 'prospress' );?></strong></th>
			            </tr>
					</tfoot>
					<tbody>
			        <?php
					foreach ( $taxonomy_types as $tax_name => $tax_type ) {
						$del_url = add_query_arg( 'deltax', $tax_name, $this->admin_url );
						$del_url = ( function_exists('wp_nonce_url') ) ? wp_nonce_url( $del_url, 'pp_delete_tax' ) : $del_url;
						$edit_url = add_query_arg( 'edittax', $tax_name, $add_url );
						$edit_url = ( function_exists('wp_nonce_url') ) ? wp_nonce_url( $edit_url, 'pp_custom_taxonomy' ) : $edit_url;
						$edit_types_url = add_query_arg( array( 'taxonomy' => $tax_name, 'post_type' => $this->name ), admin_url( 'edit-tags.php' ) );
					?>
			        	<tr>
			            	<td valign="top"><?php echo stripslashes( $tax_name ); ?></td>
			                <td valign="top"><?php echo stripslashes( $tax_type[ 'label' ] ); ?></td>
			                <td valign="top"><?php echo stripslashes( $tax_type[ 'labels' ][ 'singular_label' ] ); ?></td>
			            	<td valign="top">
								<div class="prospress-actions">
									<ul class="actions-list">
										<li class="base"><?php _e( 'Take Action:', 'prospress' );?></li>
										<li class="action"><a href="<?php echo $edit_url; ?>"><?php _e( 'Edit Taxonomy', 'prospress' );?></a></li>
										<li class="action"><a href="<?php echo $del_url; ?>"><?php _e( 'Delete Taxonomy', 'prospress' );?></a></li>
										<li class="action"><a href="<?php echo $edit_types_url; ?>"><?php printf( __( 'Add/Edit %s', 'prospress' ), $tax_type[ 'label' ] );?></a></li>
									</ul>
								</div>	
			            	</td>
						</tr>
					<?php
					} ?>
					</tbody>
				</table>
				<p><?php printf( __( 'Note: Deleting a taxonomy does not delete the %s or terms associated with it.', 'prospress' ), $this->labels[ 'name' ] ); ?></p>
			<?php
			}else{ ?>
				<p><?php printf( __( 'Taxonomies provide a way to categorise items based on unique characteristics. Well thought out taxonomies make it easier for buyers to find an item matching specific criteria.', 'prospress' ), $this->labels[ 'name' ] ) ?></p>
				<p><?php _e( 'For example, auctions of Dutch Masterpieces could use an <i>Artist</i> taxonomy, which includes <i>Vermeer</i>, <i>Rembrandt</i> and <i>Cuyp</i>.', 'prospress' ) ?></p>
				<p><a href="<?php echo $add_url; ?>" class="button add-new-h2"><?php _e( "Add New", 'prospress' ); ?></a></p>
			<?php
			}
		echo '</div>';
	}

	public function edit_tax_page( $error = '', $label = '', $singular_label = '' ) {

		if ( isset( $_GET[ 'edittax' ] ) ) {
			check_admin_referer( 'pp_custom_taxonomy' );
			$submit_name = __( 'Edit Taxonomy', 'prospress' );
			$tax_to_edit = $_GET[ 'edittax' ];
			$taxonomies = get_option( $this->name );
			$label = $taxonomies[ $tax_to_edit ][ 'label' ];
			$singular_label = $taxonomies[ $tax_to_edit ][ 'labels' ][ 'singular_label' ];
			$pp_add_or_edit = $this->edit_tax;
		} else {
			$submit_name = __( 'Create Taxonomy', 'prospress' );
			$tax_to_edit = '';
			$pp_add_or_edit = $this->add_tax;
		}
		?>
		<div class="wrap">
		<?php 
			if ( !empty( $error ) ){?>
				<div id="message" class="error">
					<p><?php echo $error ?><p>
				</div>
		<?php }
		screen_icon( 'prospress' );
		?>
		<h2><?php echo $submit_name; ?></a></h2>
		<table border="0" cellspacing="10">
			<tr>
		        <td valign="top">
		        	<p><?php _e('Only a <strong>Taxonomy Name</strong> is required to create a custom taxonomy; however, label and singular labels are recommended.' );?></p>
		            <form method="post" action="">
		                <?php if ( function_exists('wp_nonce_field') )
		                    wp_nonce_field('pp_custom_taxonomy'); ?>
		                <?php if ( isset( $_GET[ 'edittax' ] ) ) { ?>
		                <input type="hidden" name="pp_edit_tax" value="<?php echo $tax_to_edit; ?>" />
		                <?php } ?>
		                <table class="form-table">
							<tr valign="top">
								<th scope="row"><?php _e( 'Taxonomy Name', 'prospress' ) ?> <span style="color:red;">*</span></th>
								<td>
									<input type="text" name="pp_custom_tax" tabindex="21" value="<?php echo esc_attr( $tax_to_edit ); ?>" />
									<label><?php _e( 'A URL-friendly name. It should be all lowercase and contain only letters, numbers, and hyphens. e.g. artist', 'prospress' ); ?></label>
								</td>
							</tr>

							<tr valign="top">
								<th scope="row"><?php _e( 'Plural Label', 'prospress' ) ?></th>
								<td>
									<input type="text" name="label" tabindex="22" value="<?php echo esc_attr( $label ); ?>" />
									<label><?php _e( 'Used to refer to multiple items of this type. e.g. Artists', 'prospress' ); ?></label>
								</td>
							</tr>

							<tr valign="top">
								<th scope="row"><?php _e( 'Singular Label', 'prospress' ) ?></th>
								<td>
									<input type="text" name="singular_label" tabindex="23" value="<?php echo esc_attr( $singular_label ); ?>" />
									<label><?php _e( 'Used to refer to a single item of this type. e.g. Artist', 'prospress' ); ?></label>
								</td>
							</tr>
						</table>
						<p class="submit">
							<input type="submit" class="button-primary" tabindex="24" name="<?php echo $pp_add_or_edit; ?>" value="<?php echo $submit_name; ?>" />
						</p>
		            </form>
		        </td>
			</tr>
		</table>
		</div>
		<?php 
	}

	public function edit_taxonomies() {
		global $wpdb;

		check_admin_referer( 'pp_custom_taxonomy' );

		$tax_name = sanitize_title( $_POST[ 'pp_custom_tax' ] );

		if ( empty( $tax_name ) ) {
			$this->edit_tax_page( __( 'Taxonomy name is required.', 'prospress' ), $_POST[ 'label' ], $_POST[ 'singular_label' ] );
			return;
		}

		$new_tax[ 'label' ] 		= ( !$_POST[ 'label' ] ) ? $tax_name : strip_tags( $_POST[ 'label' ] );
		$new_tax[ 'object_type' ] 	= $this->market_type;
		$new_tax[ 'capabilities' ] 	= array( 'assign_terms' => 'edit_prospress_posts' );
		$new_tax[ 'labels' ] 		= array();
		$new_tax[ 'labels' ][ 'singular_label' ]	= ( !$_POST[ 'singular_label' ] ) ? $tax_name : strip_tags( $_POST[ 'singular_label' ] );
		//other taxonomy properties are defined dynamically for future proofing

		$taxonomies = get_option( $this->name );

		if( isset( $_POST[ $this->add_tax ] ) ) {
			$edit_tax_url = '<a href="' . add_query_arg( array( 'post_type' => $this->name, 'taxonomy' => $tax_name ), admin_url( 'edit-tags.php' ) ) . '">' . $this->labels[ 'name' ] . '</a>';
			$msg = sprintf( __( 'Taxonomy created. You can add elements under the %s menu.', 'prospress' ), $edit_tax_url );
		} elseif ( isset( $_POST[ $this->edit_tax ] ) ) {
			$old_tax_name = $_GET['edittax'];
			// Change existing terms for this taxonomy to be associated with its new name
			$wpdb->update( $wpdb->term_taxonomy, array( 'taxonomy' => $tax_name ), array( 'taxonomy' => $old_tax_name ) );
			unset( $taxonomies[ $_GET['edittax'] ] );
			$msg = __('Taxonomy updated.', 'prospress' );
			do_action( 'pp_taxonomy_edit', $old_tax_name, $tax_name );
		}

		$taxonomies[ $tax_name ] = $new_tax;

		update_option( $this->name , $taxonomies );

		$this->register_taxonomies();
		flush_rewrite_rules(); // doesn't work for unknown reasons
		$this->manage_taxonomies( $msg );
	}

	public function delete_taxonomy() {

		check_admin_referer( 'pp_delete_tax' );

		$taxonomies = get_option( $this->name );

		unset( $taxonomies[ $_GET['deltax'] ] );

		update_option( $this->name, $taxonomies );

		do_action( 'pp_taxonomy_delete', $_GET['deltax'] );

		$this->manage_taxonomies( __('Taxonomy deleted.', 'prospress' ) );
	}

	public function register_taxonomies() {

		$taxonomy_types = get_option( $this->name );

		if ( empty( $taxonomy_types ) )
			return;

		foreach( $taxonomy_types as $tax_name => $tax_type ) {
			$object_type = $tax_type[ 'object_type' ];
			unset( $tax_type[ 'object_type' ] );

			// Define most of the taxonomy parameters dynamically to improve forward compatability
			$tax_type[ 'public' ] 			= true;
			$tax_type[ 'hierarchical' ] 	= true;
			$tax_type[ 'show_ui' ] 			= true;
			$tax_type[ 'show_tagcloud' ]	= false;
			$tax_type[ 'query_var' ] 		= $tax_name;
			$tax_type[ 'rewrite' ] 			= array( 'slug' => $object_type . '/' . $tax_name, 'with_front' => false );
			$tax_type[ 'capabilities' ] 	= array( 'assign_terms' => 'edit_prospress_posts' );
			$tax_type[ 'labels' ][ 'search_items' ] 	= sprintf( __( 'Search %s', 'prospress' ), $tax_type[ 'label' ] );
			$tax_type[ 'labels' ][ 'popular_items' ]	= sprintf( __( 'Popular %s', 'prospress' ), $tax_type[ 'label' ] );
			$tax_type[ 'labels' ][ 'all_items' ] 		= sprintf( __( 'All %s', 'prospress' ), $tax_type[ 'label' ] );
			$tax_type[ 'labels' ][ 'parent_item' ] 		= sprintf( __( 'Parent %s', 'prospress' ), $tax_type[ 'label' ] );
			$tax_type[ 'labels' ][ 'parent_item_colon' ]= sprintf( __( 'Parent %s:', 'prospress' ), $tax_type[ 'label' ] );
			$tax_type[ 'labels' ][ 'edit_item' ]		= sprintf( __( 'Edit %s', 'prospress' ), $tax_type[ 'labels' ][ 'singular_label' ] );
			$tax_type[ 'labels' ][ 'update_item' ]		= sprintf( __( 'Update %s', 'prospress' ), $tax_type[ 'labels' ][ 'singular_label' ] );
			$tax_type[ 'labels' ][ 'add_new_item' ]		= sprintf( __( 'Add New %s', 'prospress' ), $tax_type[ 'labels' ][ 'singular_label' ] );
			$tax_type[ 'labels' ][ 'new_item_name' ]	=sprintf(  __( 'New %s', 'prospress' ), $tax_type[ 'labels' ][ 'singular_label' ] );

			register_taxonomy( $tax_name, $object_type, $tax_type );
		}
	}
}
