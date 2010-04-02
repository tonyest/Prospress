<?php
/**
 * Bid Widgets administration panel.
 *
 * @package Prospress
 */

if ( ! current_user_can('switch_themes') )
	wp_die( __( 'Cheatin&#8217; uh?' ));
?>

<div class="wrap">
	<?php screen_icon(); ?>
	<h2><?php echo esc_html( $title ); ?></h2>

	<?php if ( isset($_GET['message']) && isset($messages[$_GET['message']]) ) { ?>
		<div id="message" class="updated fade"><p><?php echo $messages[$_GET['message']]; ?></p></div>
	<?php } ?>
	<?php if ( isset($_GET['error']) && isset($errors[$_GET['error']]) ) { ?>
		<div id="message" class="error"><p><?php echo $errors[$_GET['error']]; ?></p></div>
	<?php } ?>

	<div class="widget-liquid-left">
		<div id="widgets-left">
			<div id="available-widgets" class="widgets-holder-wrap">
				<div class="sidebar-name">
				<div class="sidebar-name-arrow"><br /></div>
				<h3><?php _e('Available Bid Widgets'); ?> <span id="removing-widget"><?php _e('Deactivate'); ?> <span></span></span></h3></div>
				<div class="widget-holder">
				<p class="description"><?php _e('Drag bid widgets from here to a bid bar on the right to activate them. Drag bid widgets back here to deactivate them and delete their settings.'); ?></p>
				<div id="widget-list">
				<?php pp_list_bid_widgets(); ?>
				</div>
				<br class='clear' />
				</div>
				<br class="clear" />
			</div>
		</div>
	</div>

	<div class="widget-liquid-right">
		<div id="widgets-right">
		<?php
		error_log('$pp_registered_bidbars = ' . print_r($pp_registered_bidbars, true));
		foreach ( $pp_registered_bidbars as $bidbar => $registered_bidbar ) {
			error_log('$bidbar = ' . print_r($bidbar, true));
			$closed = $i ? ' closed' : ''; ?>
			<div class="widgets-holder-wrap">
			<div class="sidebar-name">
			<div class="sidebar-name-arrow"><br /></div>
			<h3><?php echo esc_html( $registered_bidbar['name'] ); ?>
			<span><img src="images/wpspin_dark.gif" class="ajax-feedback" title="" alt="" /></span></h3></div>
			<?php wp_list_widget_controls( $bidbar ); // Show the control forms for each of the widgets in this sidebar ?>
			</div>
		<?php
		} ?>
		</div>
	</div>
	<form action="" method="post">
	<?php wp_nonce_field( 'save-sidebar-widgets', '_wpnonce_widgets', false ); ?>
	</form>
	<br class="clear" />
</div>