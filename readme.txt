=== WP Extreme Shortlinks ===
Contributors: chmac
Donate link: http://www.callum-macdonald.com/code/donate/
Tags: shortlinks, shorturi, shorturl
Requires at least: 3.0
Tested up to: 3.4.2
Stable tag: 0.3

Looking for regular shortlinks? KEEP WALKING. Something a little different you say? You like the dark and dangerous do you? Well then, step right this way...

== Description ==

Looking for regular shortlinks? KEEP WALKING. Something a little different you say? You like the dark and dangerous do you? Well then, step right this way...

**Warning**: I've been using this plugin on [cal.io](http://cal.io/ "Callum Macdonald") for over 2 years without incident, but it has only been downloaded a few times, and so not thoroughly tested. I strongly recommend you test it extensively before using it full time. You can install, test, then remove it without leaving any trace. Please share your results [here](http://www.callum-macdonald.com/code/wp-extreme-shortlinks/ "WP Extreme Shortlinks WordPress plugin homepage").

This plugin is not for the faint hearted. It creates truly whoppingly short shortlinks on your own domain. If you'd like slightly tamer shortlinks, try these [excellent plugins](http://wordpress.org/extend/plugins/search.php?q=shortlinks "Other, less extreme, shortlink plugins").

== Installation ==

Set your options below, then copy the code into wp-config.php, just above the`/* That's all, stop editing! Happy blogging. */` line.

**Very important: Set these options only once and do not change them.** If you change them, you will probably break your old shortlinks. See below for the full info.

`define('SHORTLINK_USE_UPPERCASE', true);
define('SHORTLINK_DOMAIN', false);
define('SHORTLINK_EXTREME', false);
define('SHORTLINK_MAX_ID_LENGTH', 3);
define('SHORTLINK_SHOW_MENU', true);`

= Summary =

	* SHORTLINK_USE_UPPERCASE
	Do you want to use both lowercase and UPPERCASE letters in your shortlinks? Set to true or false.
	* SHORTLINK_DOMAIN
	If you have a custom short domain, point it at your WordPress install and enter it like `define('SHORTLINK_DOMAIN', 'http://ex.mp/');`. Set this as false if you do not have a different domain.
	* SHORTLINK_EXTREME
	Do you want to enable extreme shortlinks for posts? This option makes post links 1 character shorter by living on the edge! Set to true or false.
	* SHORTLINK_MAX_ID_LENGTH
	This defines how long your shortlinks can be. Set this as an integer (a number with no quotes), you can safely increase it later.
	* SHORTLINK_SHOW_MENU
	Show this admin menu and page. Set it to true initially, then this page will validate your options. Once they're correct, set it to false and hide this page.

= Full explanation =

This plugin uses a simple approach to shortlinks. The links are are /?a=X for author pages, /?c=X for category pages, /?p=X for post and pages, and /?t=X for tags. X is the object ID encoded in base36 or base 62 depending on the SHORTLINK_USE_UPPERCASE setting.

If you enable SHORTLINK_EXTREME, then post and page shortlinks will be shortened to simply /X.

SHORTLINK_USE_UPPERCASE controls whether we use only lower case or both lower and UPPER case letters to create shortlinks. Shortlinks using only lower case letters are easier to tell people verbally. That makes them better when printed, shared by phone, etc. When clicking links, it makes no difference. Using upper case letters makes the links shorter.

Here's some numbers to put this in perspective:

* Lowercase, 3 characters = 46'656 posts
* Uppercase, 3 characters = 238'328
* Lowercase, 4 characters = 1'679'616
* Uppercase, 4 characters = 14'776'336

Once you've installed this plugin and started sharing your shortlinks, it's important you keep to the same format. You can use another plugin so long as it supports the same format. This is important. Think long and hard before you decide which shortlink format to use, you'll break all your old shortlinks if you change later.

If your domain name is a long one, you might want to buy a shorter domain just for shortlinks. If you do that, point it at the same WordPress site in your web server configuration and then set the new domain in SHORTLINK_DOMAIN. Your shortlinks will now be set with the short domain. The shortlinks will actually work on both domains, so you can safely change domains without breaking any old shortlinks.

I'm using this plugin on the site [alts.to](http://alts.to/ "Find alternatives to just about anything"). So long as that site uses WordPress, I'll have to maintain this plugin for my own needs. So, hopefully, this plugin will be around for a while. If I stop maintaining it for any reason, I've written the code to be as forwards compatible as possible, so hopefully it will "just work" for many versions of WordPress to come. If it does break for any reason, the code is all released under the GPL and available [wordpress.org](http://wordpress.org/extend/plugins/wp-extreme-shortlinks/ "WP Extreme Shortlinks on WordPress.org"), so another developer could fix problems in the future.

Happy shortlinking.

== Changelog ==

= 0.3 =
* No changes whatsoever, just a version bump to keep wp.org happy.

= 0.2 =
* This was actually released, 0.1 was not.

= 0.1 =
* First, mostly untested, release.

== Upgrade Notice ==

= 0.3 =
No changes whatsoever, just a version bump to keep wp.org happy.

= 0.2 =
Revamped release. 0.1 was never actually released.

= 0.1 =
First version, no need to upgrade.


