<?php
/**
 * Plugin Name: All-in-one Download C411
 * Plugin URI: https://github.com/tcacamou-ops/All-in-one-Download-c411
 * Description: Add-on for All-in-one Download that allows downloading torrents from C411.
 * Version: 0.0.3
 * Author: tcacamou
 * Author URI: https://github.com/tcacamou-ops
 * Text Domain: all-in-one-download-c411
 * Domain Path: /languages
 */

namespace AllI1D\C411;

use AllI1D\C411\Filters\C411Movies;
use AllI1D\C411\Filters\C411TvShows;
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
		$C411ApiMovies = new C411Movies();
		$C411ApiTvShows = new C411TvShows();
        add_filter( 'alli1d_process_tvshow', [$C411ApiTvShows,'process_tv_show']);
        add_filter( 'alli1d_process_movie', [$C411ApiMovies,'process_movie']);
    }
}


// Initialize the plugin.
new Plugin();