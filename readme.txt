=== Plugin Name ===
Contributors: prospress, thenbrent
Tags: marketplace, prospress, auction, ecommerce
Requires at least: WordPress 3.0
Tested up to: 3.0
Stable tag: 0.1

Add an auction marketplace to your WordPress site.

== Description ==

Publishing and trade - two prosperous human endeavours. WordPress advances the first, Prospress the second. 

You can add an auction marketplace to your WordPress site with Prospress.

After a 30 second install, your community can post auctions, make bids, give each other feedback and make payments to each other.

Well at least they *should* be able to. Prospress is so new, it's still in beta. 

Please only use Prospress on test sites for now. There will be bugs. Those bugs will be fixed, but it's better they occur in a sandbox.

If you want to hasten the official release of Prospress, you can help by [contributing](http://prospress.org/contribute) to the project.

Want to know more? See the [FAQ](http://wordpress.org/extend/plugins/prospress/faq/) or visit the project's homepage - [Prospress.org](http://prospress.org).


== Installation ==

1. Upload everything into the "/wp-content/plugins/" directory of your installation.
2. Activate Prospress in the "Plugins" admin panel.

Upon activation, Prospress sets sensible defaults, so there is nothing to configure. Just because Prospress does so much doesn't mean you do.


== Frequently Asked Questions ==

Prospress is so new, there are no frequently asked questions, so these are preemptions. 

If you have a question, please ask in the Prospress [support forums](http://prospress.org/forums).

= Is this an auction site in a box? =

Yep. You can pretty much create your own eBay, right along side your existing WordPress site.

= Do I need a special them to use Prospress? =

Hopefully not. Prospress attempts to work out of the box with your existing theme. 

However, if Prospress doesn't fit in with your theme's style, you only need to add two additional template files to your theme's directory - `index-auctions.php` and `single-auction.php`.

If you're comfortable with HTML/CSS, check out the `pp-index-auctions.php` and `pp-single-auction.php` for a guide. A codex article will eventually be available to explain everything you can do with Prospress templates.

If you need help, make a post in the Prospress [Theme Compatibility forum](http://prospress.org/forums/forum/theme-compatibility).

= Where do I view Auctions? =

Prospress creates it's own special index page, called `Auctions`. Visit this page to view your auction's index.

= Can any registered user post an auction? =

That's entirely up to you. All registered users will be able to place bids and make payments, but you control who can post auctions. 

You can host auctions from a privileged few or allow your entire community to trade items. Set permissions under `Prospress | General Settings` admin menu.

= Is this stable enough for use on my live site? =

Not yet. Prospress is a major plugin that has been in development for long time. It's been tested on a variety of setups, but never in production. 

There will be bugs. Those bugs will be fixed, but it's better they occur in a sandbox.

Please download & explore Prospress in a test environment, but wait until a non-beta release before using on your live site. 

If you want to hasten the official release of Prospress, you can help by [contributing](http://prospress.org/contribute) to the project.

= Where can I get support? =

In the Prospress [support forums](http://prospress.org/forums "Prospress Forums").

= Where can I find documentation? =

Don't you hate it when you can't learn how to use new software? So do we, which is why Prospress *will* have a codex. But at this stage, too many hours were spent writing code and not enough writing documentation. 

If you're savvy at learning new software, and could improve this sentence, you can help document Prospress. [Contact us](http://prospress.org/contact "Prospress Contact Page") to get an early-stage author account for the Prospress Codex.

= Where can I report a bug? =

Please report bugs in the Prospress [Bug Report forum](http://prospress.org/forums/forum/bug-reports "Prospress Bug Report Forum").

= Where can I try the latest? =

Prospress is hosted in a git repository at [GitHub](git@github.com:Prospress/Prospress.git "Prospress GitHub Project"). GitHub is also the primary development site for the project.

If your dreams occasionally contain curly braces, and you want to help give the world a free and open marketplace platform, you can contribute to the Prospress code base.

GitHub makes it easy to contribute. They also have great [documentation](http://help.github.com/ "GitHub Documentation") and [getting started guides](http://help.github.com/ "GitHub Getting Started Docs") that will help you learn to use Git and GitHub.

= Is that it? =

Nope. There are a few surprises you'll have to download & explore Prospress to discover.


== Screenshots ==

1. **Add an Auction** - Publishing an auction is just like publishing a post. You can add a start price and end date.
2. **Give Feedback** - Once an auction completes, the two parties can provide feedback for each other.
3. **Set Capabilities** - All registered users can make bids/payments, but admins can choose which roles can publish & edit auctions.


== Changelog ==

= 0.1 =
* Initial release. 
