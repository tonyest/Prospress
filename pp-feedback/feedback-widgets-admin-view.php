<?php
/**
 * Feedback Widgets administration panel.
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
				<h3><?php _e('Available Feedback Widgets'); ?> <span id="removing-widget"><?php _e('Deactivate'); ?> <span></span></span></h3></div>
				<div class="widget-holder">
				<p class="description"><?php _e('Drag feedback widgets from here to a feedback bar on the right to activate them. Drag feedback widgets back here to deactivate them and delete their settings.'); ?></p>
				<div id="widget-list">
				<?php pp_list_feedback_widgets(); ?>
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
		error_log('$pp_registered_feedback_bars = ' . print_r($pp_registered_feedback_bars, true));
		if ( !empty( $pp_registered_feedback_bars ) ) {
			foreach ( $pp_registered_feedback_bars as $feedback_bar => $registered_feedback_bar ) {
				error_log('$feedback_bar = ' . print_r($feedback_bar, true));
				$closed = $i ? ' closed' : ''; ?>
				<div class="widgets-holder-wrap">
				<div class="sidebar-name">
				<div class="sidebar-name-arrow"><br /></div>
				<h3><?php echo esc_html( $registered_feedback_bar['name'] ); ?>
				<span><img src="images/wpspin_dark.gif" class="ajax-feedback" title="" alt="" /></span></h3></div>
				<?php wp_list_widget_controls( $feedback_bar ); // Show the control forms for each of the widgets in this sidebar ?>
				</div>
			<?php
			}
		} else { ?>
			<div class="widgets-holder-wrap">
			<div class="">
				<h3><?php _e( 'No feedback bars registered.' ); ?></h3>
			</div>
			</div>
		<?php } ?>
		</div>
	</div>
	<form action="" method="post">
	<?php wp_nonce_field( 'save-sidebar-widgets', '_wpnonce_widgets', false ); ?>
	</form>
	<br class="clear" />
</div>