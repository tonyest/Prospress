<?php
/**
 * @package Capital_Quotes
 * @author Brent Shepherd
 * @version 1.0
 */
/*
Plugin Name: Capital Quotes
Plugin URI: http://prospress.org
Description: This is not just a plugin, it embodies the collective genius behind humanity's heightened prosperity. When activated you will see a random quote in the upper right of your admin screen on every page.
Author: Brent Shepherd
Version: 0.2
Author URI: http://brentshepherd.com/
*/

function get_capital_quote() {
	$quotes = "Poverty is unnecessary. - <b>Muhammad Yunus</b>
Underlying most arguments against the free market is a lack of belief in freedom itself. - <b>Milton Friedman</b>
It's the economy, stupid - <b>Bill Clinton</b>
Concentrated power is not rendered harmless by the good intentions of those who create it. - <b>Milton Friedman</b>
An idealist is a person who helps other people to be prosperous. - <b>Henry Ford</b>
I am not a Marxist. - <b>Karl Marx</b>
Is the 'invisible hand' attached to a clothed arm? - <b>John McMillan</b>
The propensity to truck, barter and exchange one thing for another is common to all men, and to be found in no other race of animals. - <b>Adam Smith</b>
The Internet is turning economics inside-out. - <b>Uri Geller</b>
Life is full of chances and changes, and the most prosperous of men may...meet with great misfortunes. - <b>Aristotle</b>
We want to make sure that prosperity is spread across the spectrum of regions and occupations and genders and races. - <b>Barack Obama</b>
It is because our dreamers dreamed that we have emerged from each challenge more united, more prosperous, and more admired than before. - <b>Barack Obama</b>
Unleash prosperity for everybody. - <b>Barack Obama</b>
No people ever yet benefited by riches if their prosperity corrupted their virtue - <b>Theodore Roosevelt<b>
Being free and prosperous in a world at peace. That's our ultimate goal. - <b>Ronald Reagan<b>
Only when people are given a personal stake in determining their own destiny and benefiting from their own risks, do societies become prosperous, progressive, dynamic, and free.  - <b>Ronald Reagan<b>";

	// Here we split it into lines
	$quotes = explode("\n", $quotes);

	// And then randomly choose a line
	return wptexturize( $quotes[ mt_rand(0, count($quotes) - 1) ] );
}

// This just echoes the chosen line, we'll position it later
function capital_quotes() {
	$chosen = get_capital_quote();
	echo "<div id='capital-container'><p id='capital'>$chosen<p></div>";
}

// Now we set that function up to execute when the admin_footer action is called
add_action('admin_footer', 'capital_quotes');

// We need some CSS to position the paragraph
function capital_css() {
	echo "
	<style type='text/css'>
	#capital-container {
		position: absolute;
		top: 0.5em;
		left: 18em;
		right: 33em;
		margin: 0;
	}

	#capital {
		margin: 0;
		padding: 3px;
		font-weight: normal;
		color: #ccc;
		line-height: 1.2em;
		text-align: center;
	}
	</style>
	";
}

add_action('admin_head', 'capital_css');

?>
