=== Post Hit Counter ===
Contributors: hlashbrooke
Donate link: http://www.hughlashbrooke.com/donate
Tags: post, views, counter, hits, analytics, stats, statistics, count
Requires at least: 4.0
Tested up to: 4.1.1
Stable tag: 1.3.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A counter to track how many times your posts are viewed.

== Description ==

"Post Hit Counter" does what it says on the tin - it counts how many hits your posts receive. Hit counts are displayed in a new column in the post list table, in the submit box on the post edit screen and in the admin bar on the frontend.

**Plugin Features**

- Easily visible hit counts in the dashboard and in the admin bar on the frontend
- Shortcode to display view count for current or specified post
- Widget to display your most viewed posts anywhere on your site
- Dashboard widget to show your most viewed posts at a glance
- No complicated analytics and statistics to wade through
- Option to select which post types must be counted
- Option to select which user roles should not trigger the hit counter

This is not meant as a replacement for more advanced analytics plugins - it is a counter so you can see which of your posts are more popular without diving too deeply into complicated viewing statistics.

Want to contribute? [Fork the GitHub repository](https://github.com/hlashbrooke/Post-Hit-Counter).

*This plugin was initially created as a demo for [a workshop](http://2014.capetown.wordcamp.org/session/building-your-first-wordpress-plugin/) I ran at WordCamp Cape Town 2014. I have since fleshed it out and enhanced it to be more useful.*

== Installation ==

Installing "Post Hit Counter" can be done either by searching for "Post Hit Counter" via the "Plugins > Add New" screen in your WordPress dashboard, or by using the following steps:

1. Download the plugin via WordPress.org
2. Upload the ZIP file through the 'Plugins > Add New > Upload' screen in your WordPress dashboard
3. Activate the plugin through the 'Plugins' menu in WordPress

== Screenshots ==

1. The 'Hits' column displayed on the posts list table
2. The hit count displayed on the post edit screen
3. The hit count displayed in the admin bar on the frontend
4. The available settings with their default values
5. The widget displaying the most viewed posts (displayed using the Twenty Fifteen theme)
6. The available options for the widget
7. The dashboard widget showing the most viewed posts

== Frequently Asked Questions ==

= When I order posts by hit count in the dashboard it doesn't show all of the posts - what gives? =

When you order posts by hit count in the post list table in the dashboard it will not show posts that have 0 hits. This is due to a limitation in WordPress itself, which will (most likely) be fixed in future versions. Once WordPress is able to accommodate this then I will update the plugin to work accordingly

= What is the shortcode for displaying the post hit count? =

The shortcode to display an individual post's hit count is: `[hit_count]`. If you use the shortcode without any parameters then it will displaying the hit count for the current post. Alternatively you can specify which post's hit count you would like to display by using the shortcode like this: `[hit_count post=123]` where `123` is the ID of the post.

= How do I reset the hit count for a post? =

On the post edit screen, there is a refresh icon next to the hit count - simply click that and the hit count for that post will be reset to 0 without reloading the page.

== Changelog ==

= 1.3.1 =
* 2015-02-19
* [FIX] Fixing 'Undefined property' on post edit screen

= 1.3 =
* 2014-12-10
* [NEW] Adding button to reset hit count for specific post

= 1.2.1 =
* 2014-12-05
* [TWEAK] Renaming shortcode for consistency

= 1.2 =
* 2014-11-28
* [NEW] Shortcode to display view count for current or specified post
* [NEW] Widget to display your most viewed posts anywhere on your site
* [NEW] Dashboard widget so you can see your most viewed posts at a glance
* [FIX] Fixing ordering of posts by hits in the post list table

= 1.1.1 =
* 2014-11-27
* [TWEAK] Changing 'Hits' to 'Hit' in admin bar when hit counter is on 1

= 1.1 =
* 2014-11-24
* [NEW] Adding support for all post types
* [NEW] Adding option to select which post types will be active
* [NEW] Adding option to prevent specified user roles from triggering hit counter
* [NEW] Adding hit counter to admin bar on frontend
* [TWEAK] Renaming 'Views' to 'Hits' for consistency

= 1.0 =
* 2014-10-22
* Initial release #boom

== Upgrade Notice ==

= 1.3.1 =
* Minor bug fix.