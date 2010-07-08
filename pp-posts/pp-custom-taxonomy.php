<?php

define( 'PP_TAX_URL', admin_url( '/admin.php?page=pp_tax' ) );

function pp_add_tax_menu() {
	add_submenu_page( 'Prospress', __( 'Custom Prospress Taxonomies', 'prospress' ), __( 'Taxonomies', 'prospress' ), 'administrator', 'pp_tax', 'pp_taxonomy_controller' );
}
add_action('admin_menu', 'pp_add_tax_menu');

function pp_taxonomy_controller() {

	if( isset( $_POST[ 'pp_add_tax' ] ) || isset( $_POST[ 'pp_edit_tax' ] ) )
		pp_edit_taxonomies();
	elseif( isset( $_GET[ 'action' ] ) && $_GET[ 'action' ] == 'add_new' )
		pp_edit_tax_page();
	elseif( isset( $_GET[ 'deltax' ] ) )
		pp_delete_taxonomy();
	else
		pp_manage_taxonomies();
}

function pp_manage_taxonomies( $message = '' ) {
	global $market_system;
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
		$add_url = add_query_arg( 'action', 'add_new', PP_TAX_URL );
		?>
		<h2><?php echo $market_system->singular_name() . ' '; _e( 'Taxonomies', 'prospress' ) ?><a href="<?php echo $add_url ?>" class="button add-new-h2">Add New</a></h2>
		<p><?php printf( __( 'Taxonomies can be used to categorise %s based on common characteristics; thus, making it easier to find an item with a certain characteristic.', 'prospress' ), $market_system->display_name() ) ?></p>
		<p><?php _e( 'For example, an auction site for 17th Century Dutch Masterpieces could use an <i>Artist</i> taxonomy comprised of the artists <i>Vermeer</i>, <i>Rembrandt</i> and <i>Cuyp</i>.', 'prospress' ) ?></p>
		<?php 
		$pp_tax_types = get_option( 'pp_custom_taxonomies' );
		if( !empty( $pp_tax_types ) ) { ?>
	        <table class="widefat post fixed">
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
				foreach ($pp_tax_types as $tax_name => $pp_tax_type ) {
					$del_url = add_query_arg( 'deltax', $tax_name, PP_TAX_URL );
					$del_url = ( function_exists('wp_nonce_url') ) ? wp_nonce_url( $del_url, 'pp_delete_tax' ) : $del_url;
					$edit_url = add_query_arg( 'edittax', $tax_name, $add_url );
					$edit_url = ( function_exists('wp_nonce_url') ) ? wp_nonce_url( $edit_url, 'pp_custom_taxonomy' ) : $edit_url;
					$edit_types_url = add_query_arg( array( 'taxonomy' => $tax_name, 'post_type' => $market_system->name() ), admin_url( 'edit-tags.php' ) );
				?>
		        	<tr>
		            	<td valign="top"><?php echo stripslashes( $tax_name ); ?></td>
		                <td valign="top"><?php echo stripslashes( $pp_tax_type[ 'label' ] ); ?></td>
		                <td valign="top"><?php echo stripslashes( $pp_tax_type[ 'labels' ][ 'singular_label' ] ); ?></td>
		            	<td valign="top">
							<div class="prospress-actions">
								<ul class="actions-list">
									<li class="base"><?php _e( 'Take Action:', 'prospress' );?></li>
									<li class="action"><a href="<?php echo $edit_url; ?>"><?php _e( 'Edit Taxonomy', 'prospress' );?></a></li>
									<li class="action"><a href="<?php echo $del_url; ?>"><?php _e( 'Delete Taxonomy', 'prospress' );?></a></li>
									<li class="action"><a href="<?php echo $edit_types_url; ?>"><?php printf( __( 'Add New %s', 'prospress' ), $pp_tax_type[ 'labels' ][ 'singular_label' ] );?></a></li>
									<li class="action"><a href="<?php echo $edit_types_url; ?>"><?php printf( __( 'Edit %s', 'prospress' ), $pp_tax_type[ 'label' ] );?></a></li>
								</ul>
							</div>	
		            	</td>
					</tr>
				<?php
				} ?>
				</tbody>
			</table>
			<p><?php _e( 'Note: Deleting a taxonomy does not delete the posts and taxonomy types associated with it.', 'prospress' ) ?></p>
		<?php
		}else{
			echo '<p><a href="' . $add_url . '" class="button add-new-h2">' . __( "Add New", 'prospress' ) . '</a></p>';
		}
		echo '</div>';
}

function pp_edit_tax_page( $error = '', $label = '', $singular_label = '' ) {

	if ( isset( $_GET[ 'edittax' ] ) ) {
		check_admin_referer( 'pp_custom_taxonomy' );

		$submit_name = __( 'Edit Taxonomy', 'prospress' );
		$tax_to_edit = $_GET[ 'edittax' ];
		$pp_taxonomies = get_option( 'pp_custom_taxonomies' );
		$label = $pp_taxonomies[ $tax_to_edit ][ 'label' ];
		$singular_label = $pp_taxonomies[ $tax_to_edit ][ 'labels' ][ 'singular_label' ];
		$pp_add_or_edit = 'pp_edit_tax';
	} else {
		$submit_name = __( 'Create Taxonomy', 'prospress' );
		$tax_to_edit = '';
		$pp_add_or_edit = 'pp_add_tax';
	}

	?>
	<div class="wrap">
	<?php 
		if ( !empty( $error ) ){?>
			<div id="message" class="error">
				<p><?php echo $error ?><p>
			</div>
	<?php }
	?>
	<table border="0" cellspacing="10">
		<tr>
	        <td valign="top">
				<h2><?php echo $submit_name; ?></a></h2>
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
								<label><?php _e( 'Used to define the taxonomy. Make it short and sweet. e.g. artist', 'prospress' ); ?></label>
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

function pp_edit_taxonomies() {
	global $market_system, $wp_rewrite;

	check_admin_referer( 'pp_custom_taxonomy' );

	$pp_tax_name = strip_tags( $_POST[ 'pp_custom_tax' ] );

	if ( empty( $pp_tax_name ) ) {
		pp_edit_tax_page( __( 'Taxonomy name is required.', 'prospress' ), $_POST[ 'label' ], $_POST[ 'singular_label' ] );
		return;
	}

	$pp_new_tax[ 'label' ] 			= ( !$_POST[ 'label' ] ) ? $pp_tax_name : strip_tags( $_POST[ 'label' ] );
	$pp_new_tax[ 'object_type' ] 	= $market_system->name();
	$pp_new_tax[ 'capabilities' ] 	= array( 'assign_terms' => 'edit_prospress_posts' );
	$pp_new_tax[ 'labels' ] 		= array();
	$pp_new_tax[ 'labels' ][ 'singular_label' ]	= ( !$_POST[ 'singular_label' ] ) ? $pp_tax_name : strip_tags( $_POST[ 'singular_label' ] );
	//other taxonomy properties are defined dynamically to future proof

	$pp_taxonomies = get_option( 'pp_custom_taxonomies' );

	if( isset( $_POST[ 'pp_add_tax' ] ) ) {
		$edit_tax_url = '<a href="' . add_query_arg( array( 'post_type' => $market_system->name(), 'taxonomy' => $pp_tax_name ), admin_url( 'edit-tags.php' ) ) . '">' . $market_system->display_name() . '</a>';
		$msg = sprintf( __( 'Taxonomy created. You can add elements under the %s menu.', 'prospress' ), $edit_tax_url );
	} elseif ( isset( $_POST[ 'pp_edit_tax' ] ) ) {
		unset( $pp_taxonomies[ $_GET['edittax'] ] );
		$msg = __('Taxonomy updated.', 'prospress' );
	}

	$pp_taxonomies[ $pp_tax_name ] = $pp_new_tax;

	update_option( 'pp_custom_taxonomies' , $pp_taxonomies );

	$wp_rewrite->flush_rules();

	pp_manage_taxonomies( $msg );
}

function pp_delete_taxonomy() {

	check_admin_referer( 'pp_delete_tax' );

	$pp_taxonomies = get_option( 'pp_custom_taxonomies' );

	unset( $pp_taxonomies[ $_GET['deltax'] ] );

	update_option( 'pp_custom_taxonomies', $pp_taxonomies );

	pp_manage_taxonomies( __('Taxonomy deleted.', 'prospress' ) );
}

function pp_register_taxonomies() {
	global $market_system;

	$pp_tax_types = get_option('pp_custom_taxonomies');

	if ( empty( $pp_tax_types ) )
		return;

	foreach( $pp_tax_types as $pp_tax_name => $pp_tax_type ) {
		$pp_object_type = $pp_tax_type[ 'object_type' ];
		unset( $pp_tax_type[ 'object_type' ] );

		// Define most of the taxonomy parameters dynamically to improve forward compatability
		$pp_tax_type[ 'public' ] 		= true;
		$pp_tax_type[ 'hierarchical' ] 	= true;
		$pp_tax_type[ 'show_ui' ] 		= true;
		$pp_tax_type[ 'show_tagcloud' ]	= false;
		$pp_tax_type[ 'query_var' ] 	= $pp_tax_name;
		$pp_tax_type[ 'rewrite' ] 		= array( 'slug' => $pp_object_type . '/' . $pp_tax_name, 'with_front' => false );
		$pp_tax_type[ 'capabilities' ] 	= array( 'assign_terms' => 'edit_prospress_posts' );
		$pp_tax_type[ 'labels' ][ 'search_items' ] 	= __( 'Search ', 'prospress' ) . $pp_tax_type[ 'label' ];
		$pp_tax_type[ 'labels' ][ 'popular_items' ]	= __( 'Popular ', 'prospress' ) . $pp_tax_type[ 'label' ];
		$pp_tax_type[ 'labels' ][ 'all_items' ] 	= __( 'All ', 'prospress' ) . $pp_tax_type[ 'label' ];
		$pp_tax_type[ 'labels' ][ 'parent_item' ] 	= __( 'Parent ', 'prospress' ) . $pp_tax_type[ 'label' ];
		$pp_tax_type[ 'labels' ][ 'parent_item_colon' ] 	= __( 'Parent ', 'prospress' ) . $pp_tax_type[ 'label' ] . ':';
		$pp_tax_type[ 'labels' ][ 'edit_item' ]		= __( 'Edit ', 'prospress' ) . $pp_tax_type[ 'labels' ][ 'singular_label' ];
		$pp_tax_type[ 'labels' ][ 'update_item' ]	= __( 'Update ', 'prospress' ) . $pp_tax_type[ 'labels' ][ 'singular_label' ];
		$pp_tax_type[ 'labels' ][ 'add_new_item' ]	= __( 'Add New ', 'prospress' ) . $pp_tax_type[ 'labels' ][ 'singular_label' ];
		$pp_tax_type[ 'labels' ][ 'new_item_name' ]	= __( 'New ', 'prospress' ) . $pp_tax_type[ 'labels' ][ 'singular_label' ];

		register_taxonomy( $pp_tax_name,
			$pp_object_type,
			$pp_tax_type 
			);
	}
}
add_action( 'init', 'pp_register_taxonomies', 0 );
