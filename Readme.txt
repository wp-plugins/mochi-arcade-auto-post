=== Plugin Name ===
Contributors: Felps
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=JMX9RPNGSHX2Q&lc=US&item_name=Bionic%20Squirrels%20Technologies&item_number=Donation%3a&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted
Tags: mochi,auto post,flash,games
Requires at least: 3.3.2
Tested up to: 3.3.2
Stable tag: 1.0.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Allows you to use Mochimedia's auto post feature.

== Description ==

Mochi Arcade Auto Post is designed to help bloggers add a few mochi arcade games to their blogs.  This plugin interacts with Mochi's auto-post feature to allow you to easily gather information about the game you've selected and then even gives you a button to post it!

== Installation ==

1. Upload the `mochiArcadeAutoPost` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to http://www.mochimedia.com and create a publisher's account.
4. Go to https://www.mochimedia.com/pub/settings and scroll down to Auto Post Settings
5. Enter the web address of `mochiArcadeAutoPost.php` in the Auto Post URL section
eg. http://www.example.com/wp-content/plugins/mochiArcadeAutoPost/mochiArcadeAutoPost.php
6. Copy your publisher ID, and paste it into the form on the plugin's settings page. (dashboard->settings->Mochi Arcade Auto Post
7. Pick a game from http://www.mochimedia.com and click "post game to my site"
8. Go to your Dashboard->posts->Mochi Games Queue and click "post and publish" or "post"
9. There is no 9, you should have a wordpress post (published or unpublished) with the game's name, categories, tags, and a shortcode that will embed the game in valid xhtml.

== Frequently Asked Questions ==

= Why are there screenshots missing from some games? =

All of the data about games (including screenshots, descriptions, videos, etc) is entered into mochi's system by the game's developers, if they neglect to include some information, it won't be in Mochi's system, and as such, won't be in the plugin either.

= I wish the plugin could do something it currently doesn't =

Write me a comment on the plugin's page!  I'll be adding new features as time goes by, I already plan to add support for storing high scores locally so they can be accessed on user profiles, among other things.

= What if I want to delay the posting of a game? =

Click `post` on the game queue page, then click `edit`, and select a date to publish as you normally would for any post.  It won't even be published for a moment as the `post` button doesn't publish the post it creates.

= Will there ever be a games browser? =

Eventually there will be a games browser, as well as a shortcode to post a full arcade (eg. in a page), and an option to hide game posts on your homepage, although you could already if you wanted to using a plugin that excludes posts with specific tags, and specify the tag 'mAAPBS' since the plugin tags every game post with it. (this identifies them for such purposes)

= I use google adsense, is it ok to put games on posts that may have an ad? =

It is ok, however, google recommends that you place the ad at least 150 px from the flash game.  In the future there will be a section in options to add a google adsense snippet that will be placed exactly 150 px from the flash game.  As always though you will be responsible for ensuring that no more than 3 ads are placed on a page at any one time (IE: Set any other plugin that places adsense accordingly to ensure this does not happen).

If you get a lot of `accidental clicks` google may suspend your account if they believe you have placed the ads very close to the flash game specifically to farm accidental clicks.  IE: Don't do this, ever.

= The games queue page is empty! =

The games queue page defaults to showing only unposted games, clicking the "posted", or "all" buttons should show your already posted games.  Barring that, if you haven't already, you need to add games to the queue by clicking "Post game to my site" on http://www.mochimedia.com

== Screenshots ==

1. The game queue screen.

== Changelog ==

= 1.0.1 =
* The plugin now works. (v 1.0.0 didn't, which is why it was never released :P)

== Features ==

Unordered list:

* Adds information about mochi games to your database
* Creates posts to display said games
* Deletes those same posts, and all supporting documents (flash games/screenshots) from your website
* Erases all changes it made to the database should you decide to delete (not deactivate) the plugin. (This includes much of the data it gathers about the games, but does not include any posts made to your wordpress site, or media such as the swf files, and thumbnails (which ARE deleted if you click delete on the game queue page).