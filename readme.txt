=== Plugin Name ===
Contributors: jolley_small
Donate link: http://blue-anvil.com/archives/wordpress-download-monitor-plugin-2-wordpress-25-ready
Tags: download, downloads, monitor, hits, download monitor, tracking, admin, count, counter, files
Requires at least: 2.0
Tested up to: 2.7
Stable tag: 2.2

Plugin with interface for uploading and managing download files, inserting download links in posts, and monitoring download hits.

== Description ==

Download Monitor is a plugin for uploading and managing downloads, tracking download hits, and displaying links.

= Features =

*	New - WP2.7 Support
*	New - Editor button - upload and add a download stright from a post.
*	New - Custom redirects.
*	New - Custom download image.
*	New - Localised files include redirects.
*	New - Added support to add downloads to text widgets
*	New - Mirror support (selected at random)
*	New - Mirror deadlink checker
*	Custom Field support.
*	Download Categories.
*	Member only downloads.
*	Localization support.
*	Fixed - Sorting and pagination of downloads in admin.
*	Re-upload files, handy for updating versions!
*	Change hits, just in case you change servers or import old downloads that already have stats.
*	URL hider using mod_rewrite.
*	Image display mode (show a link like the download link image on this page!).
*	Admin page for uploading/linking to downloads, and specifying information (title and version).
*	Records download hits.
*	Does **not** count downloads by wordpress admin users.
*	Template tags for showing popular, recent, and random downloads in your web site's sidebar.
*	Post tags for outputting download links e.g [download#id].
*	Drop-down menu in non-rich text wordpress editor for adding links.
	
	
== Installation ==

= First time installation instructions =

Installation is fast and easy. The following steps will guide get you started:

   1. Unpack the *.zip file and extract the /download-monitor/ folder and the files.
   2. Using an FTP program, upload the /download-monitor/ folder to your WordPress plugins directory (Example: /wp-content/plugins).
   3. In the wp-content directory, using FTP or your server admin panel,
      you may need to change the permission or create the uploads directory to 777, or you will not be able to upload files.
   4. Open your WordPress Admin panel and go to the Plugins page (link on the
      top menu). Locate the "Wordpress Download Monitor" plugin and
      click on the "Activate" link.
   5. Once activated, go to the Manage > Downloads.
   6. That's it, you're done. You can now add downloads.

== Frequently Asked Questions ==

= My hits arn't showing up! =

Admin hits are not counted, log out and try!

= Can I upload files other than .zip and .rar? =

The admin interface now allows you to change extensions.

= I get an 'error saving to database error' =

The download tables may not exist. Use the option in manage/tools > downloads > recreate download database.

= I want my downloads to be parsed in a custom field using get_post_meta() =

Wordpress does not have a filter I can hook into for this function, so to make this work wrap it in the <code>wp_dlm_parse_downloads()</code> function. For example:

<code>echo wp_dlm_parse_downloads(get_post_meta($post->ID, 'Download', true));</code>

== Screenshots ==

1. Wordpress 2.7 admin screenshot
2. Wordpress 2.5 admin screenshot
3. Wordpress 2.3 admin screenshot

== Usage ==

**New Method**: Use the admin panel to define custom formats to output your links and then use `[download#id#format=id]` or just [download#id] if you set one as default.

**Traditional Method:** To **show download links**, use the following tags:

   1. Link/hits - `[download#id]`
   2. Link w/o hits - `[download#id#nohits]`
   3. URL only - `[download#id#url]`
   4. Hits only - `[download#id#hits]`
   5. Link with image - `[download#id#image]`
   6. Link/hits/filesize - `[download#id#size]`
   7. Link/filesize - `[download#id#size#nohits]`
   
There are a few other **template tags** to use in your wordpress templates. Replace '$no' with the amount of downloads to show.

   1. Most downloaded - `<?php wp_dlm_show_downloads(1,$no); ?>`
   2. Most recent - `<?php wp_dlm_show_downloads(2,$no); ?>`
   3. Random - `<?php wp_dlm_show_downloads(3,$no); ?>`
   
**Show all downloads:**

	Simply add the tag [#show_downloads] to a page.
	
**Show downloads with category selector:**

	Simply add the tag [#advanced_downloads] to a page.
	
**Show downloads in a single category:**
	
	Use <code>[download_cat#id]</code> replacing id with the id of the category.