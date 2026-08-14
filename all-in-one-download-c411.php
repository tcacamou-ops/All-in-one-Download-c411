<?php
/**
 * Plugin Name: All-in-one Download C411
 * Plugin URI: https://github.com/tcacamou-ops/All-in-one-Download-c411
 * Description: Add-on for All-in-one Download that allows downloading torrents from C411.
 * Version: 0.0.15
 * Author: tcacamou
 * Author URI: https://github.com/tcacamou-ops
 * Text Domain: all-in-one-download-c411
 * Domain Path: /languages
 */

namespace AllI1D\C411;

use AllI1D\C411\Components\Credentials;
use AllI1D\C411\Filters\C411Movies;
use AllI1D\C411\Filters\C411TvShows;
use AllI1D\C411\Filters\C411Search;
use AllI1D\C411\Filters\C411DownloadSelection;
use AllI1D\C411\Filters\C411FeedFetcher;
use AllI1D\C411\Filters\C411FeedCatalogIndexer;
use AllI1D\C411\Filters\Status;
use AllI1D\Helpers\Crypto;
use honemo\updater\Updater;

// Security: prevent direct file access.
if (!defined('ABSPATH')) {
    exit;
}

// Define the plugin's absolute path constant.
if (!defined('AllI1D_C411_DIR')) {
    define('AllI1D_C411_DIR', plugin_dir_path(__FILE__));
}

// Define the plugin's URL constant.
if (!defined('AllI1D_C411_URL')) {
    define('AllI1D_C411_URL', plugin_dir_url(__FILE__));
}

// Include Composer autoloader.
require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';

class Plugin {
    public function __construct() {
        $this->initialize_admin();
        $this->initialize_api();
        $this->initialize_filters();
    }

    private function initialize_admin() {
        if ( is_admin() ) {
            new Admin();
            $updater = new Updater(
                __FILE__,                                      // Main plugin file.
                'https://github.com/tcacamou-ops/All-in-one-Download-c411'  // Repository URL.
            );

            $updater->init();
        }
    }

    private function initialize_api() {
        Api::get_instance();
    }

    private function initialize_filters() {
        $C411FeedFetcher = new C411FeedFetcher();
        $C411ApiMovies  = new C411Movies( $C411FeedFetcher );
        $C411ApiTvShows = new C411TvShows( $C411FeedFetcher );
        $C411ApiSearch  = new C411Search( $C411FeedFetcher );
        $C411ApiDownloadSelection = new C411DownloadSelection();
        $C411FeedCatalogIndexer = new C411FeedCatalogIndexer();
        add_filter( 'alli1d_process_tvshow', [$C411ApiTvShows, 'process_tv_show'] );
        add_filter( 'alli1d_process_movie', [$C411ApiMovies, 'process_movie'] );
        add_filter( 'alli1d_process_status', [Status::class, 'process_status'] );
        add_filter( 'alli1d_search_providers', [$C411ApiSearch, 'search'], 10, 2 );
        add_filter( 'alli1d_download_selected_result_c411', [$C411ApiDownloadSelection, 'download'], 10, 2 );
        add_filter( 'alli1d_provider_settings_modals', [$this, 'register_modal'] );
        add_action( 'alli1d_refresh_feed_catalog', [$C411FeedCatalogIndexer, 'refresh'] );
        add_filter( 'alli1d_feed_catalog_providers', [$C411FeedCatalogIndexer, 'register_provider'] );
        add_action( 'admin_init', [$this, 'migrate_credentials_encryption'] );
    }

    public function migrate_credentials_encryption(): void {
        $migrated_key = 'alli1d_c411_credentials_encrypted_v1';
        if ( get_option( $migrated_key ) ) {
            return;
        }
        $api_key = get_option( 'alli1d_c411_api_key', '' );
        if ( '' !== $api_key && 0 !== strpos( $api_key, 'enc:' ) ) {
            update_option( 'alli1d_c411_api_key', Crypto::encrypt( $api_key ) );
        }
        update_option( $migrated_key, true );
    }

    public function register_modal( array $modals ): array {
        $credentials = new Credentials();
        $modals['C411'] = [
            'title' => __( 'C411 Settings', 'all-in-one-download-c411' ),
            'html'  => $credentials->get_html(),
        ];
        return $modals;
    }
}


// Initialize the plugin.
new Plugin();