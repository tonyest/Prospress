<?php
/**
 * Print the bid form
 *
 * @since 0.1
 * @global object $market_system The marketplace's market system.
 *
 * @return null Returns null if no bids appear
 */
function the_bid_form() {
	global $market_system;

	echo $market_system->bid_form();
}

