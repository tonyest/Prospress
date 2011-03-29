=== Prospress ===
Contributors: Prospress, thenbrent, tonyest
Tags: marketplace, prospress, auction, ecommerce, e-commerce
Requires at least: 3.0.5
Tested up to: 3.1
Stable tag: 1.1.1
License: GPLv2 or later

Add an auction marketplace to your WordPress site.

== Description ==

Prospress is a new plugin that goes where no plugin has gone before.

After its 30 second install, your WordPress site will have its very own auction marketplace. Registered users will be able to post auctions, place bids, provide feedback and make payments. Everything needed for a fully functioning marketplace.

= Features =

**Auction Posts** - Prospress uses the WordPress publishing system, so publishing an auction is as enjoyable as publishing a blog post.

**Feedback** - Traders in your marketplace can rate each other after a transaction and view each other's feedback to have confidence about potential transactions.

**Invoicing & Payments** - To finalize transactions, Prospress provides an invoicing system that supports payment with PayPal, credit cards & bank transfers.

See all the great features on the Prospress [Marketplace Features](http://prospress.org/features/) page.


= Demo Marketplace =

If you want to kick the tires of Prospress before installing it, check out the [Demo Marketplace](http://demo.prospress.org/auctions/). You can register, post auctions & make bids on some priceless, stolen artworks.

= Props =

As a major WordPress plugin, Prospress has code and contributions by a number of developers. Special thanks must go to:

* [Scribu](http://profiles.wordpress.org/users/scribu/) for the filter and sort widgets and [Query Multiple Taxonomies](http://wordpress.org/extend/plugins/query-multiple-taxonomies/) plugin;
* [Andy Potatin](http://profiles.wordpress.org/users/andypotanin/) for customizing his excellent [WP-Invoice](http://wordpress.org/extend/plugins/wp-invoice/) plugin.

= Want to know more? =

Read the plugin's [FAQ](http://wordpress.org/extend/plugins/prospress/faq/) or visit the [Prospress Marketplace Plugin](http://prospress.org) website.

= Get Involved =

If you want to help give the world a free and open marketplace platform, you can also [contribute](http://prospress.org/contribute/) to the Prospress project.


== Installation ==

1. Upload everything into the "/wp-content/plugins/" directory of your WordPress installation.
2. Activate Prospress in the "Plugins" admin panel.

Prospress supports the default TwentyTen theme and will attempt to work with other themes. If you find quirks with your theme, make a post in the [Theme Compatibility forum](http://prospress.org/forums/forum/theme-compatibility/). 


== Frequently Asked Questions ==

If you have a question not answered here, please ask in the Prospress [support forums](http://prospress.org/forums/).

= How is Prospress different to other shopping cart plugins? =

There are many great shopping cart plugins for WordPress; Prospress isn’t one of them. 

With Prospress, registered users can post their own auctions, place bids on the auctions of others, provide feedback and make payments. It creates a many-to-many exchange that differs to the one-to-many exchange of an online store.

= Why doesn't a user need to be logged in to Buy Now? =

When a user returns from PayPal after having paid the buy now price for an auction, a user account is automatically created using their PayPal email address. This makes it super easy for anyone to buy and auction, while still creating an invoice and other records associated with the successful completion of an auction.

= Do I need a special theme to use Prospress? =

Prospress attempts to work with your existing theme. It's a primary goal of the Prospress project to support existing WordPress themes rather than require a new one.

Prospress is guaranteed to work with TwentyTen and TwentyEleven themes, but due to the variation in themes, it's likely the built-in templates will display with quirks on other themes. If this happens on your site, not to worry, it's really quite easy to make your own templates. 

If you're comfortable with HTML/CSS, check out the `pp-index-auctions.php` and `pp-single-auction.php` in the *Prospress/pp-posts* folder. These provide a guide for making your own templates. Just add two custom template files, named `index-auctions.php` and `single-auction.php`, to your theme's directory and Prospress will use them automatically.

If you need help, ask in the Prospress [Theme Compatibility](http://prospress.org/forums/forum/theme-compatibility/) forum.

= Where do I view Auctions? =

Prospress creates it's own special index page, called *auctions*. Once you publish your first auction, simply visit this page to view it live.

= Can any registered user post an auction? =

That's entirely up to you. All registered users with subscriber role or better will be able to place bids and make payments, but you control who can post auctions. 

You can host auctions from a privileged few or allow your entire community to trade items. Set permissions under the `Prospress | General Settings` admin menu.

= Where can I get support? =

In the Prospress [support forums](http://prospress.org/forums/ "Prospress Forums").

= Where can I find documentation? =

Don't you hate it when you can't learn how to use new software? Same, which is why Prospress *will* have a codex. But at this stage, too many hours were consumed writing code, leaving too few for writing documentation.

If have a knack for learning new software, and could improve this sentence, you can help document Prospress. [Contact us](http://prospress.org/contact/ "Prospress Contact Page") to get an early-stage author account for the upcoming Prospress Codex.

= Where can I report a bug? =

Please report bugs in the Prospress [Bug Report](http://prospress.org/forums/forum/bug-reports "Prospress Bug Report Forum") forum.

= Where can I get access to the development version? =

Prospress is hosted in a Git repository at [GitHub](http://github.com/Prospress/Prospress "Prospress GitHub Project"). GitHub is also the primary development site for the project.

If your dreams occasionally contain curly braces, you can contribute to the Prospress code base on [GitHub](http://github.com/Prospress/Prospress "Prospress GitHub Project"). GitHub makes it easy to contribute and they provide great [documentation](http://help.github.com/) and [getting started guides](http://help.github.com/) to get you up and running.

= Is that it? =

Nope. There are a few surprises but you'll have to download & explore Prospress to find them, or try the [Demo Marketplace](http://demo.prospress.org/auctions/).

== Screenshots ==

1. **Add an Auction** - Publishing an auction is just like publishing a post, except you also add a start price and end date.
2. **Give Feedback** - When an auction completes, the two parties can provide feedback for each other.
3. **Set Capabilities** - All registered users can make bids & payments, but administrators choose who can publish & edit auctions.


== Changelog ==

= 1.1 =
* PayPal Buy Now option available
* Live countdown in final 24 hours.
* Hooks added in preparation for Cubepoints and other plugins to interface.
* Changed the way prospress redirects the index template to fix problems with pre-existing pages with the same slug.
* Simplified Prospress capabilities.
* Taxonomies are now enabled by default.
* Added Hello World Auction on first install.
* Fixed bug in sorting taxonomies.
* Fixed link-tree for Taxonomies so as not to display empty Taxonomies.
* Changed Prospress settings to use WordPress standard register_settings() format. New method is simpler and more extensible.
* Assorted bug fixes.

= 1.0.2 =
* Fixed bug causing Price & Winning Bidder columns to display on the admin page for all custom post type

= 1.0.1 =
* Bug on bid form ajax fixed
* Bids are now a custom post type (and all bids are visible to admin)
* Internationalization fixes
* Market system now correctly uses an internal, non-localized, name
* For the full commit log, see here: https://github.com/Prospress/Prospress/commits/v1.0.1

= 1.0 =
* SSL & credit card payments (for USD only) now supported
* Prospress Admin Links widget now displays links dependant on user's capabilities
* Reduced the number of available currencies due to lack of support in PayPal
* Fixed bug preventing invoices being generated on manual post completion
* Feedback now a custom post type
* Markup fixes to Invoice pages
* For the full commit log, see here: https://github.com/Prospress/Prospress/commits/v1.0

= 0.2 =
* Beta 2 
* Now prevents activation on WP < 3.0 and PHP < 5.0
* Modified payment tables & internal semantics
* Post end time function now displays countown when post ending within a week
* New widget for quick links to backend tasks (adding an Auction, viewing bids etc.)

= 0.1 =
* Initial beta release. 


== Upgrade Notice ==

= 1.1 =
New version with buy now, bug fixes and 

= 1.0.2 =
Upgrade to fix bug displaying Price & Winning Bidder columns on the admin page for all custom post types.

= 1.0.1 =
Please upgrade to fix bid form & localization bugs. This release also changes the bids page to show the site admin all bids.

= 1.0 =
The first non-beta release ready for prime-time - enjoy! Please note, this is a breaking upgrade. If you need to preserve feedback from a beta installation, post in the prospress.org/forums/ to request an upgrade script.

= 0.2 =
Upgrade to fix bug when activating on WP < 3.0 or PHP < 5.0, ensure Prospress plays nice with other plugins and get new widgets.
