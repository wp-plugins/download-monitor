=== Plugin Name ===
Contributors: jolley_small
Author URI: http://blue-anvil.com/archives/wordpress-download-monitor-plugin-2-wordpress-25-ready
Plugin URI: http://blue-anvil.com/archives/wordpress-download-monitor-plugin-2-wordpress-25-ready
Donate link: http://blue-anvil.com/archives/wordpress-download-monitor-plugin-2-wordpress-25-ready
Tags: download, downloads, monitor, hits, download monitor, tracking, admin, count, counter, files
Requires at least: 2.0
Tested up to: 2.5
Stable tag: 2.0.4

Plugin with interface for uploading and managing download files, inserting download links in posts, and monitoring download hits.

== Description ==

Download Monitor is a plugin for uploading and managing downloads, tracking download hits, and displaying links. 

Features

    * New - Localization support
    * Fixed - Sorting and pagination of downloads in admin
    * Support for wordpress 2+ (including 2.5)
    * Re-upload files, handy for updating versions!
    * Change hits, just in case you change servers or import old downloads that already have stats
    * URL hider using mod_rewrite
    * Image display mode (show a link like the download link image on this page!)
    * Admin page for uploading/linking to downloads, and specifying information (title and version).
    * Records download hits.
    * Does **not** count downloads by wordpress admin users.
    * Template tags for showing popular, recent, and random downloads in your web site's sidebar.
    * Post tags for outputting download links e.g [download#id]
    * Drop-down menu in non-rich text wordpress editor for adding links.
	
	
== Installation ==

First time installation instructions

Installation is fast and easy. The following steps will guide get you started:

   1. Unpack the *.zip file and extract the /wp-download-monitor/ folder and the files.
   2. Using an FTP program, upload the /wp-download-monitor/ folder to your WordPress plugins directory (Example: /wp-content/plugins).
   3. In the directory, /wp-download-monitor/, using FTP or your server admin panel,
      change the permission of the user_uploads directory to 777, or you will not be able to upload files.
   4. Open your WordPress Admin panel and go to the Plugins page (link on the
      top menu). Locate the "Wordpress Download Monitor" plugin and
      click on the "Activate" link.
   5. Once activated, go to the Manage > Downloads.
   6. That's it, you're done. You can now add downloads.

Upgrade instructions

Already using download monitor? This release has a new folder/file name so:

   1. upload the new files to the plugin directory (due not put in old download monitor folder)
   2. Copy the contents of the old user_uploads folder into the new via ftp (and don't forget to set the permissions of the folder to 777)
   3. de-activate the old version
   4. Activate the new and your done.

It uses the same database; nothing in the database has changed. After your made sure its working you can remove the old download monitor plugin directory.

== Frequently Asked Questions ==

= My hits arn't showing up! =

Admin hits are not counted, log out and try!

= Can I upload files other than .zip and .rar? =

The admin interface now allows you to change extensions.

== Screenshots ==

1. Wordpress 2.3 admin screenshot
2. Wordpress 2.5 admin screenshot

== Usage ==

To **show download links**, use the following tags:

   1. Link/hits - [download#id]
   2. Link w/o hits - [download#id#nohits]
   3. URL only - [download#id#url]
   4. Hits only - [download#id#hits]
   5. Link with image - [download#id#image]
   6. New - Link/hits/filesize - [download#id#size]
   7. New - Link/filesize - [download#id#size#nohits]
   
There are a few other **template tags** to use in your wordpress templates. Replace '$no' with the amount of downloads to show.

   1. Most downloaded - `<?php wp_dlm_show_downloads(1,$no); ?>`
   2. Most recent - `<?php wp_dlm_show_downloads(2,$no); ?>`
   3. Random - `<?php wp_dlm_show_downloads(3,$no); ?>`
   
**Show all downloads:**

	Simply add the tag [#show_downloads] to a page.

