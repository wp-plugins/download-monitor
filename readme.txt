=== Plugin Name ===
Contributors: jolley_small
Donate link: http://blue-anvil.com/archives/wordpress-download-monitor-plugin-2-wordpress-25-ready
Tags: download, downloads, monitor, hits, download monitor, tracking, admin, count, counter, files
Requires at least: 2.5
Tested up to: 2.7
Stable tag: 3.0

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
*	Danish translation - http://wordpress.blogos.dk/2008/03/14/wpdm-2-0-1-dansk/
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

= New/recommended tags/functions = 

Download monitor now uses shortcodes to output its downloads.

= `[download]` =

**Attributes:**

`id` - Required. Id of download to show.
`format` - Id of format to use. Leave blank to use default format.
`autop` - formats the output with autop. Do not use if showing the download link inline. Default: false

**Example:**

	[download id="1" autop="false" format="1"]

= `[downloads]` =

**Attributes:**

`query` - used to query the downloads. See the get_downloads template tag for the string options. Default: 'limit=5&orderby=rand'
`autop` - formats the output with autop. Do not use if showing the download link inline. Default: false
`wrap` - Set to 'ul' to wrap in ul tag. Set to '' to wrap with nothing. Default: 'ul'
`before` - Html/text before each download. Encode tags (e.g. &lt for &lt;) Default: '&lt;li&gt;'
`after` - Html/text after each download. Default: '&lt;/li&gt;'

= `get_downloads()` =

Returns downloads that match your query. Takes 1 argument containing the query string.

**Defaults:**

'limit' => '', 
'offset' => 0,
'vip' => 0
'category' => '', 
'orderby' => 'id',
'order' => 'ASC'
	
The vip argument will hide member only downloads if the user is not logged in.
	
**Example:**

(5 Random Downloads) <code>get_downloads('limit=5&orderby=random&order=desc');</code>
	
**Return Value:**

Returns an array object with attributes:
	
*	size
*	url
*	title
*	version
*	hits
*	image
*	desc
*	category
*	category_id
*	id
*	date
*	memberonly

**Full Example (Output a list of top downloads):**

	$dl = get_downloads('limit=5&amp;orderby=hits&amp;order=desc');
	
	if (!empty($dl)) {
		echo '&lt;ul class=&quot;downloadList&quot;&gt;';
		foreach($dl as $d) {
			$date = date(&quot;jS M Y&quot;, strtotime($d-&gt;date));
			echo '&lt;li&gt;&lt;a href=&quot;'.$d-&gt;url.'&quot; title=&quot;'.__('Version',&quot;wp-download_monitor&quot;).' '.$d-&gt;version.' '.__('downloaded',&quot;wp-download_monitor&quot;).' '.$d-&gt;hits.' '.__('times',&quot;wp-download_monitor&quot;).'&quot; &gt;'.$d-&gt;title.' ('.$d-&gt;hits.')&lt;/a&gt;&lt;/li&gt;';
		}
		echo '&lt;/ul&gt;';
	}



= Legacy tags = 

The following tags still work and use the old style from previous versions of the plugin. These are mainly here for backward compatibility.

**Output a download with a custom format:**

Use the admin panel to define custom formats to output your links and then use `[download#id#format=id]` or just [download#id] if you set one as default.

**Other output functions:** To **show download links**, use the following tags:

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

Add the tag `[#show_downloads]` to a page.
	
**Show downloads with category selector:**

Add the tag `[#advanced_downloads]` to a page.
	
**Show downloads in a single category:**
	
Use `[download_cat#id]` replacing id with the id of the category.