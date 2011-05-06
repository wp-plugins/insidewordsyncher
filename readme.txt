=== InsideWord Syncher ===
Contributors: InsideWord
Tags: plugin, syndication, synch, analytics, links, share, seo
Requires at least: 3.0.0
Tested up to: 3.1.2
Stable tag: 0.5.0.0

InsideWord Syncher helps promote your blog through InsideWord.com, a blog and news aggregator.

== Description ==

= READ THIS FIRST =
The plugin has been temporarily taken down since we've encountered several issues, among them an overload of our servers (we received 6,000+ articles!). We'd like to take the time to thank the many users who have downloaded this plugin. Everyone has been very patient and supportive. We didn't expect this many users would try and use the plugin in so short a time and were caught a little unprepared hehe :P

We will bring back the plugin once we've optimized the servers and added some much needed features such as organization of categories.

= Current features: =
* Synch all posts over to InsideWord.com.
* Automatically create a [profile with InsideWord.com](http://www.insideword.com/member/profile/38/0).
* Provide links back to your blog from InsideWord to help drive traffic back to you and help with SEO.
* Provide the current rank for each of your posts.

= Features being worked on: =
* Automatically assign blog posts to InsideWord Categories.

= Future features: =
* More options on settings page.
* More suggestions and analytics on what can be done to improve your articles.

= Have issues or Feedback? Don't hesitate to: =
* visit [InsideWord.com](http://www.insideword.com/) and chat live with us (that little orange chat bubble at the bottom of our site). 
* Or e-mail us at support@insideword.com.

We will gladly step through everything to help you fix the issue and possibly other issues we might find along the way.

== Installation ==

The plugin was built to be as easy to use as possible... which is to say, no setup time, no logins, no options! It just works out of the box. You only need to put the files in the wp-content/plugins directory like any other plugin and then activate it.

For those of you who have never used a plugin before these steps below are provided to help you along.

= Step 1: =
Copy "iwsyncher.php" file and "InsideWordApi" folder into your WordPress server's "plugins" folder. The "plugins" folder can be found in the "wp-content" folder.


= Step 2: =
Go to your WordPress administration webpage. Once there, select the Plugins menu.

If you copied the files to the right folder then you should see the "InsideWordSyncher" in the list of plugins. Now just Activate it!

Once activated that is it! The plugin will automatically connect to and communicate with the InsideWord servers and your posts will automatically be made available on InsideWord.

The upload process can take some time, especially for a WordPress server with 100+ posts, so be patient and don't panic.


= Settings Page: =
The settings page can be found in the Wordpress Settings section. It will be called InsideWordSyncher.

The settings page currently only shows your profile link at InsideWord.com, the category that the posts are being sent to, the current status of the plugin (mostly for debugging purposes) and error messages.

== Frequently Asked Questions ==

= The Plugin doesn't seem to be working =
* visit insideword.com and chat live with us (that little orange chat bubble at the bottom of our site). 
* Or e-mail us at support@insideword.com.

We will do everything we can to fix the issue.

== Changelog ==

= 0.5.0.0 =
* Disabled logging to improve plugin speed.
* Posts will now be loaded in Ascending order, which makes more sense.
* The plugin will automatically abort the synch process if it fails to identify the host after three attempts.
* Posts will be loaded in batches of 2 rather than 5 to help ease the load on WordPress servers.
* The plugin will avoid using the home page to perform identification to help ease the load on WordPress servers.
* General clean and optimizations.

= 0.4.4.366 =
* First release