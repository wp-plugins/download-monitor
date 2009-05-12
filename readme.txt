=== Plugin Name ===
Contributors: jolley_small
Donate link: http://blue-anvil.com/archives/wordpress-download-monitor-plugin-2-wordpress-25-ready
Tags: download, downloads, monitor, hits, download monitor, tracking, admin, count, counter, files
Requires at least: 2.5
Tested up to: 2.7
Stable tag: 3.0.7

Plugin with interface for uploading and managing download files, inserting download links in posts, and monitoring download hits.

== Description ==

Download Monitor is a plugin for uploading and managing downloads, tracking download hits, and displaying links.

Download Monitor requires Wordpress version 2.5 or above. Version 3.0 is a major update and many of the template and post tags have been changed and improved. See the usage page for full details before upgrading.

For older versions of wordpress use the older Download Monitor version 2.2.3 which is available from http://wordpress.org/extend/plugins/download-monitor/download/ (tested and working in Wordpress 2.0 and 2.3).

= Features =

*	Records file download hits but does **not** count downloads by wordpress admin users.
*	Stats on downloads and a download log for viewing who downloaded when.
*	Uses shortcodes (backward compatible with old [download#id] style).
*	Editor button - upload and add a download stright from a post.
*	Custom redirects to downloads.
*	Add downloads to text widgets, the content, excerpts, and custom fields.
*	Mirror support (selected at random) + mirror deadlink checker
*	Download Categories.
*	Member only downloads, can also have a minimum user level using custom fields.
*	Localization support.
*	Admin for managing downloads and also changing hit counts - just in case you change servers or import old downloads that already have stats.
*	Custom URL's/URL hider using mod_rewrite.

= Localization =

Need it in a different language? Some users have been kind enough to provide some translation files. Note, I am not responsible for any of these.

*	Chinese translation - http://www.hpyer.cn/wordpress-plugin-download-monitor.html
*	Danish translation - http://wordpress.blogos.dk/2009/03/18/download-monitor-v3/ (version 3) | http://wordpress.blogos.dk/2008/03/14/wpdm-2-0-1-dansk/ (version 2)
*	Japanese translation - http://rp.exadge.com/2008/03/15/wp-download_monitor_v203_ja/
*	Italian translation - http://gidibao.net/index.php/2008/03/18/download-monitor-plugin-in-italiano/
*	Portuguese translation - http://www.viz.com.br/plugin-wp-download-monitor.html
*	Hebrew translation - http://www.cynican.com/plugins-i-translated/wordpress-download-monitor/
*	French translation - http://themes-du.net/download-monitor-pour-compter-les-telechargements-sur-wordpress/
*	Turkish translation - http://ramerta.com/
*	Ukrainian translation - http://kosivart.if.ua/2009/01/09/889/
*	Spanish Translation - http://download.es-xchange.com/wp/download-monitor-es_ES.zip
*	Russian Translation - http://blog.liri-site.ru/portfolio/wordpress-download-monitor
*	Korean Translation - http://incommunity.codex.kr/wordpress/?p=7 - Jong-In Kim
*	Lithuanian Translation - http://wordpresstvs.lt/wordpress-download-monitor-2/
*	German Translation - http://www.outsourcetoasia.de/download-monitor-3
*	Dutch Translation - http://www.marcovanveelen.nl/wp-content/plugins/download-monitor/download.php?id=57
	
== Installation ==

= First time installation instructions =

Installation is fast and easy. The following steps will guide get you started:

   1. Unpack the *.zip file and extract the /download-monitor/ folder and the files.
   2. Using an FTP program, upload the /download-monitor/ folder to your WordPress plugins directory (Example: /wp-content/plugins).
   3. Ensure the <code>/wp-content/uploads</code> directory exists and has correct permissions to allow the script to upload files.
   4. Open your WordPress Admin panel and go to the Plugins page. Locate the "Wordpress Download Monitor" plugin and
      click on the "Activate" link.
   5. Once activated, go to the Downloads admin section.

== Frequently Asked Questions ==

= My hits arn't showing up! =

Admin hits are not counted, log out and try! Also ensure that if you have set the 'custom url' option that the custom url does not actually match the physical location of the file.

= I get an 'error saving to database error' =

The download tables may not exist. Use the option in Downloads > configuration > Recreate Download Database.

= I want my downloads to be parsed in a custom field using get_post_meta() =

Wordpress does not have a filter I can hook into for this function, so to make this work wrap it in the relevant functions. For old style download links ([download#id]) use <code>wp_dlm_parse_downloads()</code> else use <code>do_shortcode()</code>. For example:

<code>echo do_shortcode(get_post_meta($post->ID, 'Download', true));</code>

== Screenshots ==

1. Wordpress 2.7 admin screenshot
2. Wordpress 2.5 admin screenshot

== Usage ==

Full Usage instructions and documentation can be found here: http://blue-anvil.com/archives/wordpress-download-monitor-3-documentation