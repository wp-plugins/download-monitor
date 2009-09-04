=== Plugin Name ===
Contributors: jolley_small
Donate link: http://blue-anvil.com/archives/wordpress-download-monitor-plugin-2-wordpress-25-ready
Tags: download, downloads, monitor, hits, download monitor, tracking, admin, count, counter, files
Requires at least: 2.5
Tested up to: 2.8.4
Stable tag: 3.2

Plugin with interface for uploading and managing download files, inserting download links in posts, and monitoring download hits.

== Description ==

Download Monitor is a plugin for uploading and managing downloads, tracking download hits, and displaying links.

Download Monitor requires Wordpress version 2.5 or above. Version 3.0 is a major update and many of the template and post tags have been changed and improved. See the usage page for full details before upgrading.

For older versions of wordpress use the older Download Monitor version 2.2.3 which is available from http://wordpress.org/extend/plugins/download-monitor/download/ (tested and working in Wordpress 2.0 and 2.3).

= Features =

*	NEW: Built in Download Page function with built in sorting, pagination, and search. This was going to be a paid addon but i'm too nice - so please donate if you use it!
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

*	Chinese translation - http://hpyer.cn/wordpress-plugin-download-monitor.html
*	Danish translation - http://wordpress.blogos.dk/2009/03/18/download-monitor-v3/ (version 3) | http://wordpress.blogos.dk/2008/03/14/wpdm-2-0-1-dansk/ (version 2)
*	Japanese translation - http://rp.exadge.com/2008/03/15/wp-download_monitor_v203_ja/
*	Italian translation - http://gidibao.net/index.php/2008/03/18/download-monitor-plugin-in-italiano/
*	Portuguese translation - http://www.viz.com.br/plugin-wp-download-monitor.html
*	Hebrew translation - http://www.cynican.com/plugins-i-translated/wordpress-download-monitor/
*	French translation - http://themes-du.net/download-monitor-pour-compter-les-telechargements-sur-wordpress/
*	Turkish translation - http://ramerta.com/
*	Ukrainian translation - http://kosivart.if.ua/2009/01/09/889/
*	Spanish (Guatemala) Translation - http://download.es-xchange.com/wp/download-monitor-es_ES.zip
*	Russian Translation - http://blog.liri-site.ru/portfolio/wordpress-download-monitor
*	Korean Translation - http://incommunity.codex.kr/wordpress/?p=7 - Jong-In Kim
*	Lithuanian Translation - http://wordpresstvs.lt/wordpress-download-monitor-2/
*	German Translation - http://www.outsourcetoasia.de/download-monitor-3
*	Dutch Translation - http://www.marcovanveelen.nl/wp-content/plugins/download-monitor/download.php?id=57
*	Croatian Translation - http://www.eugen-bozic.net/download-monitor-plugin-prijevod/
*	Norwegian Translation - http://www.aanvik.net/2009/01/wordpress-download-monitor-pa-norsk/
*	Russian Translation - http://www.wpbloging.com/plugins/russkij-download-monitor-i-ego-opisanie.html
*	Czech Translation - http://wordpress.mantlik.cz/plugins/download-monitor/
*	Polish Translation (included) by Maciej Baur - http://www.baur.com.pl/?p=155
*	Spanish (spain) translation by FraguelsRock - http://www.gremlins.es/download/16/
	
== Installation ==

= First time installation instructions =

Installation is fast and easy. The following steps will guide get you started:

   1. Unpack the *.zip file and extract the /download-monitor/ folder and the files.
   2. Using an FTP program, upload the /download-monitor/ folder to your WordPress plugins directory (Example: /wp-content/plugins).
   3. Ensure the <code>/wp-content/uploads</code> directory exists and has correct permissions to allow the script to upload files.
   4. Open your WordPress Admin panel and go to the Plugins page. Locate the "Wordpress Download Monitor" plugin and
      click on the "Activate" link.
   5. Once activated, go to the Downloads admin section.
   
Note: If you encounter any problems when downloading files it is likely to be a file permissions issue. Change the file permissions of the download-monitor folder and contents to 755 (check with your host if your not sure how).

== Frequently Asked Questions ==

You can now view the FAQ in the documentation: http://blue-anvil.com/archives/wordpress-download-monitor-3-documentation.

== Screenshots ==

1. Wordpress 2.7 admin screenshot
2. Wordpress 2.5 admin screenshot
3. Download page listings
4. More download page listings
5. Download page single listing

== Changelog ==

= 3.2 =
*	{user} tag added for custom formats
*	'autop' option fix
*	Download page buttons applied with CSS so they are easier to customise/translate.
*	Fix for pagination bug after editing a download
*	Category output fix on edit downloads screen
*	Category urls on download page use ID rather than name to prevent errors when cats have the same names.
*	exclude_cat added to download_page shortcode
*	Localised 'hits' 'date' 'title' on download page
*	Option to disable the download logging
*	Read file 'chunked' some people found large files were corrupted so this should help (fingers crossed)
*	Added show_tags option to download page - displays x amount of tags on the download page.
*	File Browser root setting and download.php logic/mime types modified thanks to Jim Isaacs (jidd.jimisaacs.com)
*	Interface Improvements
*	Bulk edit categories, custom fields, tags, member only downloads
*	Added roles for download monitor admin - should be able to use with a role manager plugin if you want anyone other than admin to access the admin section e.g. http://wordpress.org/extend/plugins/capsman/
*	Change redirect after add
*	Edit Cat names/parents
*	Dedicated tags and thumbnails fields (they still use meta table though)

= 3.1.6 =
*	Nothing major - unreleased

= 3.1.5 =
*	Changed custom urls to make them more friendly for people with wordpress in a sub directory.
*	wp_die on download.php to make cleaner error messages
*	Much better pagination in admin
*	Order by 'meta' in downloads shortcode/get_downloads function - also must provide 'meta_name' and define the meta field to sort by. e.g. [downloads query="orderby=meta&meta_name=meta_sort"]

= 3.1.4 =
*	Added {referrer} option to the member redirect - now you could redirect to http://yourdomain.com/wp-login.php?redirect_to={referrer} for instance and they will go straight to the download right after.
*	Updated 'force' logic.
*	Moved mo/po file.

== Usage ==

Full Usage instructions and documentation can be found here: http://blue-anvil.com/archives/wordpress-download-monitor-3-documentation