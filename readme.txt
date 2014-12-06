=== Plugin Name ===
Contributors: robert@peakepro.com
Tags: random,post,category
Requires at least: 3.0.0
Tested up to: 4.0.1
Stable tag: 1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Based on the original Random Redirect, this plugin enables random redirection to a post in a manner that won't overload your MySQL database for a large website under a high volume of clicks. Also perfectly suitable for smaller sites as well. Supports picking a random post from a specific category, and setting your own redirector URL.

== Description ==

Based on the original Random Redirect, this plugin enables random redirection to a post in a manner that won't overload your MySQL database for a large website under a high volume of clicks.

This is because many random redirect plugins rely on the 'orderby' => 'rand' constraint in Wordpress, or manually use 'ORDER BY RAND()' in their MySQL queries. This results in notoriously poor performance and can really cause problems with your MySQL server if this operation is heavily repeated on a website with lots of posts.

This plugin uses a more efficient approach, including transient caching of all eligible posts for a random selection, to minimise the time it takes to pick a true random post. Supports picking a random post from a specific category, and setting your own redirector URL.

Based on the original Random Redirect by Matt Mullenweg https://wordpress.org/plugins/random-redirect/

Special thanks to Tim Green for providing additional quality assurance testing on the popular rattle.com website.

== Installation ==

Install as normal for WordPress plugins.

== Frequently Asked Questions ==

= Another random post redirection script, really? =

Yep, really. So many of the ones currently out there are not suitable for large websites with lots of traffic. 

This is because many random redirect plugins rely on the <code>'orderby' => 'rand'</code> constraint in Wordpress, or directly use <code>'ORDER BY RAND()'</code> in their MySQL queries. This results in notoriously poor performance and can really cause problems with your MySQL server if this operation is heavily repeated on a website with lots of eligible posts.

This plugin uses a more efficient approach, including transient caching of all eligible posts for its random selection, to minimise the time it takes to pick a true random post.

= How do I set the URL? =

Go to Settings > Better Random Redirect and change the URL slug from the default of "random" to whatever you want it to be.

= How do I create buttons or navigation menu links to random posts? =

Simply use the URL you set up in the configuration as above as the link for the navigation item or button.

= How do I tell the randomiser to use a particular category? =

For random results in e.g. category 'foo', append ?cat=foo on the URL line of the URL you configured above. The randomiser will then select a random post from that category.

== Screenshots ==

1. Configuration options screen

== Changelog ==

= 1.0 =

* Initial release
