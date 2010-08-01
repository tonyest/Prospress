=== Plugin Name ===
Contributors: Prospress.org, thenbrent
Tags: marketplace, prospress, auction, ecommerce
Requires at least: 3.0
Tested up to: 3.0.1
Stable tag: 0.1

Add an auction marketplace to your WordPress site.

== Description ==

Prospress is a new plugin that goes where no plugin has gone before.

After its 30 second install, your WordPress site will have its very own auction marketplace. Your registered users will be able to post auctions, place bids, provide feedback and make payments. Everything needed for a fully functioning marketplace.

Well, hopefully. Prospress is so new, it's still in beta. So please only use Prospress on test sites for now. There will be unforeseen bugs. Those bugs will be fixed, but it's better they occur in a sandbox.

= Features =

**Custom Post** - Prospress uses WordPress' publishing system, so it's as enjoyable to publish auctions as blog posts.

**Invoicing & Payments** - To finalize a transaction, currency needs to change hands. Prospress makes this easy with support for PayPal, credit card gateways & bank transfers.

**Feedback** - Traders in your marketplace can rate each other after a transaction and view sellers feedback to be confident in making a purchase.

**Templates not Themes** - Your site is already stunning, so instead of requiring a new theme, Prospress uses a few simple templates to fit in with your existing look and feel.

See all the great features on the [Prospress Features page](http://prospress.org/features/).

= Why is Prospress here when it's beta? =

For those who can't wait to try a game changer, this beta is for you. Download, install & explore what you'll soon be able to do with WordPress, but please do so on a test site.

If you'd prefer to wait for a stable release, we've made a special [demo site](http://demo.prospress.org/auctions/) for you. You can register, post auctions & make bids on some priceless paintings in the safety of a Prospress.org sandbox.

= Want to know more? =

Read the plugin's [FAQ](http://wordpress.org/extend/plugins/prospress/faq/) or visit the project's site - [Prospress.org](http://prospress.org).

= Get Involved =

To hasten the official release of Prospress, or just help give the world a free and open marketplace platform, you can [contribute](http://prospress.org/contribute/) to the Prospress project.


== Installation ==

1. Upload everything into the "/wp-content/plugins/" directory of your WordPress installation.
2. Activate Prospress in the "Plugins" admin panel.

Prospress supports the default TwentyTen theme and will attempt to work with other themes. If you find quirks with your theme, make a post in the [Theme Compatibility forum](http://prospress.org/forums/forum/theme-compatibility/). 


== Frequently Asked Questions ==

If you have a question not answered here, please ask in the Prospress [support forums](http://prospress.org/forums/).

= How is Prospress different to other shopping cart plugins? =

There are many great shopping cart plugins for WordPress; Prospress isn’t one of them. 

With Prospress, registered users can post their own auctions, place bids on the auctions of others, provide feedback and make payments. It creates a many-to-many exchange that differs to the one-to-many exchange of an online store.

= Do I need a special theme to use Prospress? =

Prospress attempts to work with your existing theme. It's a primary goal of the Prospress project to support existing WordPress themes rather than require a new one.

Due to the gamut of themes, it's not possible to support them all just yet, so the built-in templates may display with quirks. If this happens on your site, not to worry, you can add two custom template files to your theme's directory - `index-auctions.php` and `single-auction.php`.

If you're comfortable with HTML/CSS, check out the `pp-index-auctions.php` and `pp-single-auction.php` for a guide. It's really quite easy to make your own templates. 

If you need help, ask in the Prospress [Theme Compatibility forum](http://prospress.org/forums/forum/theme-compatibility/). 

= Where do I view Auctions? =

Prospress creates it's own special index page, called, appropriately enough, Auctions. Once you've published a few auctions, visit this page to see them live.

= Can any registered user post an auction? =

That's entirely up to you. All registered users will be able to place bids and make payments, but you control who can post auctions. 

You can host auctions from a privileged few or allow your entire community to trade items. Set permissions under the `Prospress | General Settings` admin menu.

= Is this stable enough for use on my live site? =

Not yet. Prospress is a major plugin that has been in development for some time. It's been tested on a variety of setups, but never in production. 

There will be bugs. Those bugs will be fixed, but it's better they occur in a sandbox.

Please download & explore Prospress in a test environment, but wait until a non-beta release before using on your live site. If you'd prefer to wait for a stable release before even downloading, you can still explore Prospress on the [demo site](http://demo.prospress.org/auctions/).

To hasten the release of a stable version, you can help by [contributing](http://prospress.org/contribute/) to the project.

= Where can I get support? =

In the Prospress [support forums](http://prospress.org/forums/ "Prospress Forums").

= Where can I find documentation? =

Don't you hate it when you can't learn how to use new software? Same, which is why Prospress *will* have a codex. But at this stage, too many hours were consumed writing code, leaving too few for writing documentation.

If have a knack for learning new software, and could improve this sentence, you can help document Prospress. [Contact us](http://prospress.org/contact/ "Prospress Contact Page") to get an early-stage author account for the upcoming Prospress Codex.

= Where can I report a bug? =

Please report bugs in the Prospress [Bug Report forum](http://prospress.org/forums/forum/bug-reports "Prospress Bug Report Forum").

= Where can I try the latest? =

During beta, regular updates will be checked-in to the WordPress plugin directory. But if you can't wait, Prospress is hosted in a git repository at [GitHub](http://github.com/Prospress/Prospress "Prospress GitHub Project"). GitHub is also the primary development site for the project.

If your dreams occasionally contain curly braces, you can contribute to the Prospress code base on [GitHub](http://github.com/Prospress/Prospress "Prospress GitHub Project"). GitHub makes it easy to contribute. They also have great [documentation](http://help.github.com/ "GitHub Documentation") and [getting started guides](http://help.github.com/ "GitHub Getting Started Docs") to get you up and running.

= Is that it? =

Nope. There are a few surprises but you'll have to download & explore Prospress to find them or at least try the [demo](http://demo.prospress.org/auctions/).


== Screenshots ==

1. **Add an Auction** - Publishing an auction is just like publishing a post, except you also need to add a start price and end date.
2. **Give Feedback** - When an auction completes, the two parties can provide feedback for each other.
3. **Set Capabilities** - All registered users can make bids & payments, but admins can choose which roles can publish & edit auctions.


== Changelog ==

= 0.2 =
* Beta 2 
* Now prevents activation on WP < 3.0 and PHP < 5.0
* Modified payment tables & internal semantics
* Post end time function now displays countown when post ending within a week
* New widget for quick links to backend tasks (adding an Auction, viewing bids etc.)

= 0.1 =
* Initial beta release. 


== Upgrade Notice ==

= 0.2 =
Upgrade to fix bug when activating on WP < 3.0 or PHP < 5.0, ensure Prospress plays nice with other plugins and get new widgets.
