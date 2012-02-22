=== Simple microblogging ===

Contributors: sgcoskey
Donate link: http://boolesrings.org
Tags: tweet, tweets, microblog, microblogging, micropost
Requires at least: 3.0
Tested up to: 3.3
Stable tag: 0.0

Use your wordpress site as a microblog; display the microposts in a widget or using a shortcode.

== Description ==

This simple plugin allows you to easily post short messages.  Rather than
appearing in your stream of posts, they can be displayed either in a widget
or using the `[microblog]` shortcode on any post or page.  To get started,
simply make a new post and assign it the category `microposts`.  If you give
the post a title, then it will displayed in bold and used as the first
few words of the micropost.

Note that in the future, we intend to use a new `post_type` rather than a
special category to segregate the microposts.

The `[microblog]` shortcode supports several options:

* **null_text**: If no results are returned, shows this text.
Defaults to `(none)`.

* **show_date**: If defined, the post date will precede the microposts.

* **date_format**: If showing the date, this php date format will be
used.  The default is the Date Format value from the General Settings
page.  I recommend `"F j, Y"`, which displays as "May 12, 2012".

The output can then be further formatted using CSS.  We recommend the
plugin [Improved Simpler
CSS](http://wordpress.org/extend/plugins/imporved-simpler-css/) for
quickly styling your post list (and your site)!

Report bugs, give feedback, or fork this plugin on
[GitHub](http://github.com/scoskey/Simple-microblogging-wordpress-plugin).

== Installation ==

Nothing unusual here!

== Changelog ==

`0.0` initial release