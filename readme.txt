=== Simple microblogging ===

Contributors: sgcoskey, vgitman
Donate link: http://boolesrings.org
Tags: tweet, tweets, microblog, microblogging, micropost
Requires at least: 3.0
Tested up to: 3.3.1
Stable tag: 0.1

Add a microblog to your site; display the microposts in a widget or using a shortcode.

== Description ==

This simple plugin allows you to easily post short messages such as thoughts and updates.  These messages will not appear in your stream of posts; instead you can display them in a widget in yours sidebar.  You can also display them in any post or page by using the `[microblog]` shortcode.

To get started, just look for the new `Microposts` administration panel in your dashboard.  Click `Add new` and then compose a short message in the same way that you normally compose your posts.  If you give the micropost a title, then it will be displayed in bold and used as the first few words of the micropost.

Then, either add the widget to your sidebar or add the `[microblog]` shortcode into your site, and that's it!

The `[microblog]` shortcode supports several options:

* **num**: The number of microposts to show.  Defaults to `5`.  Use `-1` to show all microposts.

* **null_text**: If no results are returned, shows this text.  Defaults to `(none)`.

* **show_date**: If defined, the creation date will precede the microposts.

* **date_format**: If showing the date, this php date format will be used.  The default is the Date Format value from the General Settings page.  I recommend `"F j"`, which displays as "May 12".

* **use_excerpt**: If defined, use the post excerpt instead of the entire contents.  We recommend writing short microposts, but if you prefer to write longer ones, this can be used to truncate them.  Unfortunately, Wordpress excerpts don't allow links or other html, use the plugin [Advanced Excerpt](http://wordpress.org/extend/plugins/advanced-excerpt/) to remedy this!

* **q**: Arbitrary &-separated arguments to add to the query.  See the [WP_Query](http://codex.wordpress.org/Class_Reference/WP_Query/#Parameters) page for available syntax.  For example, to show only posts from author `sam` in ascending instead of descending order, you would write `[microblog q="author_name=sam&order=ASC"]`.

The output can then be further formatted using CSS.  We recommend the plugin [Improved Simpler CSS](http://wordpress.org/extend/plugins/imporved-simpler-css/) for quickly styling your post list (and your site)!

Report bugs, give feedback, or fork this plugin on [GitHub](http://github.com/scoskey/Simple-microblogging-wordpress-plugin).

== Installation ==

Nothing unusual here!

== Screenshots ==

1. A rendered widget containing my two microposts
2. The widget configuration box

== Other notes ==

If you are having trouble viewing your microposts, try visiting your permalinks preference pane and clicking `Save changes`.

It is a known issue that some permalink structures do not work with Simple microblogging when the plugin `Salmon for wordpress` is installed.

== Changelog ==

`0.1` Added support for authors.  Added use_excerpt option to the shortcode

`0.0` initial release
