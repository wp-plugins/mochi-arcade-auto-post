=== Plugin Name ===
Contributors: Felps
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=JMX9RPNGSHX2Q&lc=US&item_name=Bionic%20Squirrels%20Technologies&item_number=Donation%3a&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted
Tags: mochi,auto post,flash,games
Requires at least: 3.3.2
Tested up to: 3.3.2
Stable tag: 1.1.45
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

It is ok, however, google recommends that you place the ad at least 150 px from the flash game.  There is a section in options to add a google adsense snippet that will be placed exactly 150 px from the flash game.  As always though you will be responsible for ensuring that no more than 3 ads are placed on a page at any one time (IE: Set any other plugin that places adsense accordingly to ensure this does not happen).

If you get a lot of `accidental clicks` google may suspend your account if they believe you have placed the ads very close to the flash game specifically to farm accidental clicks.  IE: Don't do this, ever.
Google still may decide they don't like your site's ads and game placement, if this happens you should remove the ad code immediately.  This is a tool to help you comply, not a guarantee of anything.

= The games queue page is empty! =

You need to add some games!

== Screenshots ==

1. The game queue screen.

== Changelog ==

= 1.1.45 =
* The plugin works again
* Added stage3d, and addition_data columns to the plugin's WPDB table
* Added a check for unrecognized columns, which are json encoded and stored in additional_data (this will prevent the plugin from breaking again if mochi decides to add another incoming column)
* Added an option to hide games from homepage widgets as well as the home page itself (will only do so on the home page... Needs more work)
* The plugin no longer requires mochi to access its plugin page directly, the deprecated functionality will be removed in a later patch (update your mochimedia publisher settings accordingly)
* refetch/repost link has been repaired, this will delete all data (including the post) about a game, and repost it as a pending (not published) post.
* Numerous other small bug fixes and usability enhancements that probably nobody will notice but me :P
* A currently in-development feature is included which adds the game's thumbnail to the post title, it's unfinished but may work on some themes, to enable it, open up mAAPOptions.php, use find, and type in JPL-849, below it you'll find a second of code that's been commented out, uncomment it by removing the /* and */ from that code. That will allow you to see the option in your settings page, try it out, see if it works as expected in your theme, if not, turning it back to off is recommended (no need to re-comment it)

= 1.1.1 =
* Thumbnail sizes are now normalized based on settings. (All thumbnails will be displayed at the same size regardless of actual image size)
* If a valid game_tag is encountered that was not previously added to the system, the game details will be downloaded automatically, and can be found in the game queue under "suggested" games (the swf and images won't be downloaded unless the game is explicitly posted though)
* Navigation in the game queue is now handled by a pull down menu
* Enhanced support for people with javascript disabled
* Images/swfs fall back to stored mochi urls if not otherwise available (in the case of the swf this may prevent you from being paid for that game, so make sure every game has a post!(Any post created by the plugin will automatically have downloaded the swf))
* Setting noad=true to prevent your adcode (in settings) from appearing no longer results in an unclosed div
* Games queue now shows whether or not the game was posted/how it got in the system
* Games queue now shows the time a game was added
* All thumbnails are now shown at small size on the games queue to save space
* Reposting a game now deletes that game entirely, and completely refetches it, preventing duplicate posts/swfs/images
* noad=false and flashscreen=keep are now automatically added to mochigame shortcodes created by the plugin, these are their default values anyway, but will make their existence more well known
* Added an error log, accessible from the admin menu tools->Mochi Log
* m-DONT CHANGE: excerpt now ignores what's after the colon, and processes the post that the excerpt belongs to for mochigame shortcodes, left the game tags in place, but they are no longer required
* Tables now have CSS to make them look nicer
* Further optimizations to allow for slimmer resolutions on the games queue page, screenshots are now all in one table element, but still side-by-side unless there isn't enough room
* Introducing JAVASCRIPT (all of the following requires javascript enabled)
* Added FlashScreen - a button to blow up the swf without changing its aspect ratio, it approximates fullscreen as close as possible, using HTML5 methods when available
* Added flashscreen option to shortcode, use false to prevent it showing up (not all games support changing their size well, simply add flashscreen=false if you find one that does funny things when you blow it up)
* flashscreen=deform will stretch the flash to fit the screen even if it would deform the swf
* flashscreen=keep will prevent deforming the flash (default)
* flashscreen=stage3d OR false will embed the flash in direct mode, but also disable flashscreen
* flashscreen=stagescreen will embed in direct mode, and keep flashscreen, but it should be noted that users may have trouble exiting fullscreen mode
* flashscreen will be improved upon as soon as possible.
* Changing the thumbnail to download for a game no longer requires a page refresh (if javascript is enabled)
* Games queue can now be sorted by clicking on the headers of the table
* The page is automatically reloaded after performing any action that might be accidentally repeated on page refresh


= 1.1.0 =
* Screenshots and thumbnails will now always be downloaded for each game
* If no unposted games are found, the admin menu will go to posted view
* The options to show screenshots and thumbnails on the post now work retroactively (except any games downloaded without screenshots won't have any available to post)
* The shortcode is now an enclosed type shortcode, the data in it will be displayed on the post, and in the excerpt, basically the shortcode echos it.  It is important as the excerpt contains that text only.
* Post excerpts are now set automatically to m-DONT CHANGE:(a game tag) - it uses the game tag to fetch data for that game.
* That excerpt is now replaced by a function that processes (only) the mochigame shortcode
* These changes should provide for broader theme compatibility
* The plugin on update will read your current game posts, strip the img tags it may have placed earlier, and add the closing /mochigame shortcode, so you shouldn't need to do anything. This may strip other html if you've added it to game posts as well though.
* Screenshots are shown as thumbnails, you can edit their size in the options.
* Default game thumbnail to download is selectable in options menu as well, you can still select which one you want in the games queue anyway though.
* If you wish to set your excerpt manually, you still can, but screenshots and such won't show up unless you put them there.
* This update introduces some fairly advanced interactions with wordpress, please report any bugs to felps@bionicsquirrels.com

= 1.0.9 =
* Added an option to post the game thumbnail directly to the game post (adding support for themes that do not use featured images)
* Added an option to post screenshots to the game post (in the form of thumbnail links to an attachment page with the full size image)

= 1.0.8 =
* Added a location for ad code in the settings that places the code just below the game (with a 150px top margin IE: The ad is placed 150px from the game)
* Added another option to the [mochigame] shortcode, noads=true will prevent the ad code you added into the settings screen from being placed (also removes the 150px border).  Leaving the ad code section blank will also prevent it from pushing everything down 150px, you could also place an html comment there if you don't want ads, but still want the space.
* Optimized the mochi games queue page for smaller resolutions - There's now a bit of CSS that keeps it from scrunching, and also condensed some items into fewer cells.

= 1.0.7 =
* Added an option to choose thumbnail size to post on games queue page.  Small is recommended as it is more consistently the game's logo.
* Fixed several (minor) bugs.
* The mochigame shortcode now wraps a &lt;div id="mochi_game"&gt;&lt;/div&gt; around its flash embed code so it can be more easily manipulated by CSS

= 1.0.6 =
* Added option to post games under only a single category (new posts only)
* Added game categories (genres) to tags as well (new posts only)
* Added three new parameters to the mochigames shortcode, author, authorlink, and overridewidth, they accept the values true or false (default to true if unrecognized or not specified), author shows the SWF author's name, and authorlink transforms that into a link to the author's mochi profile and/or website.  overridewidth=true will cause the game's default width to be used even if it exceeds the minimum or maximum width in the settings.
* The Genres the game fits into (categories) are now also added to the tags to increase game searchability, particularly when single category is turned on.
* Added minimum and maximum width settings on options screen
* If a custom size is specified it will override the minimum and maximum width settings in order to keep the game's aspect ratio, so if you set max width to 600, and a 640x480 game's height to 500, you will get a game embed that is wider than your maximum width setting, if you wish the aspect ratio to change, you'll have to set both width and height.
* NOTE: As with all game embed size altering functions, some games are hard coded to a specific size, and will experience issues (such as unused game elements appearing slightly off screen, or game elements clipping off the edges.

= 1.0.5 =
* Fixed swfs with sanitized names in the style 'name%20with%20spaces' to re-sanitize in a more wordpress-friendly format eg: 'name-with-spaces' Additionally any other % codes simply have the % removed.(Any game with such a swf will have to be reposted)
* Fixed a bug causing the game queue to improperly list games when there are more than 100 in the queue.
* Added an option to hide posts created by this plugin so they don't appear on the home page (but still appear in archives).

= 1.0.4 =
* Fixed the url on settings screen FOR REAL this time

= 1.0.3 =
* Fixed the url on settings screen for your plugin file to point to the correct file

= 1.0.2 =
* Made the settings screen more user friendly
* Added a password function

= 1.0.1 =
* The plugin now works. (v 1.0.0 didn't, which is why it was never released :P)

== Features ==

* Adds information about mochi games to your database
* Creates posts to display said games
* Deletes those same posts, and all supporting documents (flash games/screenshots) from your website
* Erases all changes it made to the database should you decide to delete (not deactivate) the plugin. (This includes much of the data it gathers about the games, but does not include any posts made to your wordpress site, or media such as the swf files, and thumbnails (which ARE deleted if you click delete on the game queue page).
* Includes a box for a code snippet to be placed 150 px from the game (such as an advertising code)