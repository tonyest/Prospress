<?php
/**
 * Feedback Table Administration Panel.
 *
 * @package Prospress
 * @subpackage Feedback
 */
global $wpdb;

$link_url = esc_url_raw( remove_query_arg( array( 'post', 'filter' ), $_SERVER['REQUEST_URI'] ) );
?>

<div class="wrap feedback-history">
	<?php screen_icon(); ?>
	<h2><?php echo esc_html( $title ); ?></h2>
	<ul class="subsubsub">
	<?php
		$feedback_links = array();

		if( strpos( $_SERVER['REQUEST_URI'], 'uid' ) !== false ) {
			//$all_feedback = count( pp_get_feedback_user( $user_id ) );
			//$feedback_links[] = "<li><a href='" . $link_url . "'$class>" . sprintf( __( 'All (%s)', 'prospress' ), number_format_i18n( $all_feedback ) ) . '</a>';
		}

		$received_feedback = count( pp_get_feedback_user( $user_id, array( 'received' => 'true' ) ) );
		$given_feedback = count( pp_get_feedback_user( $user_id, array( 'given' => 'true' ) ) );
		$feedback_links[] = "<li><a href='" . add_query_arg( array( 'filter' => 'received' ), $link_url ) . "'$class>" . sprintf( __( 'Received (%s)', 'prospress' ), number_format_i18n( $received_feedback ) ) . '</a>';
		$feedback_links[] = "<li><a href='" . add_query_arg( array( 'filter' => 'given' ), $link_url ) . "'$class>" . sprintf( __( 'Given (%s)', 'prospress' ), number_format_i18n( $given_feedback ) ) . '</a>';

		echo implode( " |</li>\n", $feedback_links ) . '</li>';
		unset( $feedback_links );
	?>
	</ul>
		<table class="widefat fixed" cellspacing="0">
			<thead>
				<tr class="thead">
					<?php print_column_headers('feedback'); ?>
				</tr>
			</thead>
			<tfoot>
				<tr class="thead">
					<?php print_column_headers('feedback'); ?>
				</tr>
			</tfoot>
			<tbody id="users" class="list:user user-list">
				<?php pp_feedback_rows( $feedback ); ?>
			</tbody>
		</table>
</div>