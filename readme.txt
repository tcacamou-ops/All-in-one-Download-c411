=== All-in-one Download C411 ===
Contributors: tcacamou
Tags: download, torrent, c411, all-in-one-download
Requires at least: 5.0
Tested up to: 6.7
Stable tag: 0.0.1
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Add-on for All-in-one Download that allows downloading torrents from C411.

== Description ==

All-in-one Download C411 is an add-on for the All-in-one Download plugin. It integrates with the C411 API to automatically search and download torrent files for movies and TV shows.

**Features:**

* Automatically searches C411 for matching torrents when processing a movie or TV show.
* Supports French audio language filtering (VF: VFF, TRUEFRENCH, FRENCH).
* Downloads `.torrent` files directly to the WordPress uploads directory.
* Configurable via a dedicated settings page in the WordPress admin.
* Secure API key storage using WordPress options.

== Requirements ==

* [All-in-one Download](https://github.com/tcacamou-ops/All-in-one-Download) plugin must be installed and activated.
* A valid C411 API key.

== Installation ==

1. Download the latest release ZIP from the [GitHub releases page](https://github.com/tcacamou-ops/All-in-one-Download-c411/releases).
2. Go to **WordPress Admin > Plugins > Add New**.
3. Click **Upload Plugin** and select the downloaded ZIP file.
4. Click **Install Now**, then **Activate**.
5. Go to **All-in-one Download > C411** in the admin menu.
6. Enter your C411 API key and click **Save**.

== Configuration ==

After activation, navigate to **All-in-one Download > C411** in the WordPress admin sidebar.

Enter your C411 API key in the provided field and save. The plugin will then automatically query the C411 API whenever All-in-one Download processes a movie or TV show request.

== Frequently Asked Questions ==

= Where do I get a C411 API key? =

You need to register on [C411](https://c411.org) and obtain an API key from your account settings.

= Where are the downloaded torrent files stored? =

Torrent files are saved in the `wp-content/uploads/c411/` directory on your server.

= Does this plugin work without All-in-one Download? =

No. This plugin is an add-on and requires the All-in-one Download plugin to be installed and active.

== Changelog ==

= 0.0.1 =
* Initial release.

== Upgrade Notice ==

= 0.0.1 =
Initial release.
