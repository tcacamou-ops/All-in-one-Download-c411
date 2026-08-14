=== All-in-one Download C411 ===
Contributors: tcacamou
Tags: download, torrent, c411, all-in-one-download
Requires at least: 5.0
Tested up to: 6.7
Stable tag: 0.0.14
Requires PHP: 8.2
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

== Using a Different PHP Version ==

The `vendor/` directory included in the release ZIP has been compiled for **PHP 8.2**. If your server runs a different PHP version, Composer dependencies must be regenerated to match your environment.

**Steps to recompile Composer dependencies:**

1. Make sure [Composer](https://getcomposer.org/) is installed on your machine and that your local PHP version matches your server's version.
2. Clone or download the plugin source code from the [GitHub repository](https://github.com/Honemo/All-in-one-Download-c411).
3. Delete the existing `vendor/` directory:
   `rm -rf vendor/`
4. Regenerate the dependencies and the autoloader:
   `composer install --no-dev --optimize-autoloader`
5. Repackage the plugin — create a ZIP of the entire plugin directory (including the newly generated `vendor/`).
6. Install the repackaged ZIP via **WordPress Admin > Plugins > Add New > Upload Plugin**.

== Frequently Asked Questions ==

= Where do I get a C411 API key? =

You need to register on [C411](https://c411.org) and obtain an API key from your account settings.

= Where are the downloaded torrent files stored? =

Torrent files are saved in the `wp-content/uploads/c411/` directory on your server.

= Does this plugin work without All-in-one Download? =

No. This plugin is an add-on and requires the All-in-one Download plugin to be installed and active.

== Changelog ==
= 0.0.14 =
* Feat: apply the movie/TV show quality preference (`alli1d_torrent_matches_quality`) when matching C411 results
* Fix: live-fetched results are now title- and quality-matched like catalog results instead of blindly using the first item

= 0.0.13 =
* Feat: add keyword search capability for C411 provider (`alli1d_search_providers`)
* Feat: add `C411DownloadSelection` filter to download guided-search results

= 0.0.12 =
* Feat: filter C411 results by title match in addition to substring match

= 0.0.11 =
* No functional changes.

= 0.0.10 =
* No functional changes (release process tooling update).

= 0.0.9 =
* No functional changes.

= 0.0.8 =
* Fix: add missing API key parameter to torrent download request
* Security: redact API key from download request logs

= 0.0.7 =
* No functional changes.

= 0.0.6 =
* Security: validate REST API args at registration level
* Security: encrypt C411 API key at rest with AES-256-CBC
* Security: secure URL handling
* Security: various hardening fixes
* Security: remove API key from logs
* Feat: expose credentials as modal on Status page

= 0.0.5 =
* Feat Add the status feature

= 0.0.4 =
* Fix Composer issues again

= 0.0.3 =
* Fix Composer issues

= 0.0.2 =
* Fix Update feature

= 0.0.1 =
* Initial release.

== Upgrade Notice ==

= 0.0.1 =
Initial release.
