=== Text2Tag ===

Contributors: reuzel
Tags: text, title, tag, automation
Requires at least: 2.7?
Tested up to: 2.8.4
Stable tag: 1.2

This plugin automatically converts title and post contents to tags.

== Description ==

This plugin will convert every word that occurs in a post title or content into a term (tag). By default the plugin will add these terms to a new taxonomy called "words". Optionally, the terms can be added to the "post_tags" taxonomy, used for normal tagging in WordPress.

The plugin provides the option to create a so-called stop list. Words in this list will be kept (or even removed) from the taxonomy. This allows authors to ignore meaningless, but often occuring, words like 'a' or 'the'.

This plugin makes sure that the tags will actually cover a blog's content. This is great in combination with tag-clouds as the tag-clouds will better show what the blog is about.

== Installation ==

Installation of Text2Tag is straightforward:

1. Extract the zip-file in your plugins directory (typically '/wp-content/plugins/'). Or through the automatic install functions of WordPress.
1. Activate the plugin through the 'Plugins' menu in WordPress

After activation an option page will be added to your admin menu. Here you can set two options:

1. Taxonomy: Change this to the taxonomy you wish to use. By default a new taxonomy "Words" is selected. You can change this to other taxonomies like "Post Tags" or "Categories". **Warning** Changing the taxonomy will erase all terms from that taxonomy...
1. Stop words: Enter a space separated list of words to ignore. Adding words to this list will remove any reference to them from the taxonomy. Removing words will add the term back to the taxonomy including refernces to those posts that contain that word. The list of most occuring words is shown below the input box to assist in editing the stop word list.

== Frequently Asked Questions == 

= My tags are all gone! =
This may happen when you switch the taxonomy for this plugin. Whenever you switch to a new taxonomy (for examply your 'post tags', that taxonomy will be completely replaced. Likewise, the old taxonomy will be completedly cleared. Moving back and forth between taxonomies therefore may have the result that your tags are all removed... This is by design. 

To prevent deletetion of your tags you can do two things:

1. Keep using the newly created taxonomy "Words". Most WordPress taxonomy related functions like `wp_tag_cloud()` have an option to select a specific taxonomy. This allows you to use both your old tags and the new word taxonomy (e.g. to create tag_clouds).
2. Make a backup before you start using this plugin in combination with your post tags.

= Can I get back my old tags? =
This plugin provides no mechanism to restore your old tags. If you decide to use this plugin to set your post tags it may be wise to do a backup first...

== Screenshots ==

1. This screenshot shows the option panel for Text2Tag (on a Dutch photo blog).

== Changelog ==

= 1.2 =
* Added option to ignore numeric values

= 1.1 =
* Fix to solve taxonomy resolution bug in option page

= 1.0 =
* Initial version
