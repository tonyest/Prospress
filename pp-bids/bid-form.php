<?php
// Do not delete these lines
	if (!empty($_SERVER['SCRIPT_FILENAME']) && 'bid-form.php' == basename($_SERVER['SCRIPT_FILENAME']))
		die ('Please do not load this page directly. Thanks!');
	if ( post_password_required() ) { ?>
		<p class="nobids">This post is password protected. Enter the password to view.</p>
	<?php
		return;
	}
// You can start editing here.
?>

<div class="make-bid">
	<h2><?php _e('Make a Bid'); ?></h2>
	<?php print_bid_messages(); ?>
	<p><?php _e('Current Bid: '); ?><span id="winning_bid_val"><?php the_winning_bid_value(); ?></span></p>
	<p><?php _e('Winning Bidder: '); ?><span id="winning_bidder"><?php the_winning_bidder(); ?></span></p>
	<form id="makebidform" method="get" action="<?php pp_bid_form_action(); ?>">
		<label for="bid_value" class="bid-label"><?php _e('Your max bid'); ?></label>
		<input type="text" aria-required="true" tabindex="1" size="22" value="" id="bid_value" name="bid_value"/>
		<input name="bid_submit" type="submit" id="bid_submit" tabindex="5" value="Make bid" />
		<?php bid_hidden_fields(); ?>
		<?php bid_extra_fields(); ?>
	</form>
</div>