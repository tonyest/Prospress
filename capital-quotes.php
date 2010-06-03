<?php
/**
 * @package Capital_Quotes
 * @author Brent Shepherd
 * @version 1.0
 */
/*
Plugin Name: Capital Quotes
Plugin URI: http://prospress.org
Description: This is not just a plugin, it embodies the collective genius behind humanity's heightened prosperity. When activated you will see a random economics related quote at the top of admin page. Its like Hello Dolly, but for budding economists. 
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
The Internet is turning economics inside-out. - <b>Uri Geller</b>
Life is full of chances and changes, and the most prosperous of men may...meet with great misfortunes. - <b>Aristotle</b>
Unleash prosperity for everybody. - <b>Barack Obama</b>
No people ever yet benefited by riches if their prosperity corrupted their virtue - <b>Theodore Roosevelt<b>
Being free and prosperous in a world at peace. That's our ultimate goal. - <b>Ronald Reagan<b>
All money is a matter of belief. - <b>Adam Smith</b>
Freedom granted only when it is known beforehand that its effects will be beneficial is not freedom. - <b>Friedrich von Hayek</b>";

	// Here we split it into lines
	$quotes = explode("\n", $quotes);

	// And then randomly choose a line
	return wptexturize( $quotes[ mt_rand(0, count($quotes) - 1) ] );
}

// This just echoes the chosen line, we'll position it later
function capital_quotes() {
	$chosen = get_capital_quote();
	echo "<div id='capital-container'><blockquote id='capital'>$chosen</blockquote></div>";
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
		left: 28em;
		right: 25em;
		margin: 0;
	}

	#capital {
		margin: 0;
		font-weight: normal;
		color: #acacac;
		line-height: 1.5em;
		text-align: center;
	}
	</style>
	";
}

add_action('admin_head', 'capital_css');
