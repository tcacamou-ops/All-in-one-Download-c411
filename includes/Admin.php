<?php

namespace AllI1D\C411;
use AllI1D\C411\Pages\Settings;
use AllI1D\C411\Api;
use AllI1D\Components\ToastMessage;

class Admin {
    public function __construct() {
        add_action('admin_menu', [$this, 'register_admin_menu'], 99);
        add_action('admin_enqueue_scripts', [$this, 'admin_enqueue_scripts']);

    }

    public function register_admin_menu() {
        add_submenu_page(
            'all-in-one-download',
            __('C411 settings', 'all-in-one-download-c411'),
            __('C411', 'all-in-one-download-c411'),
            'alli1d_admin',
            'all-in-one-download-c411',
            [$this, 'settings_page'],
            99,
        );
    }

    public function admin_enqueue_scripts() {
        wp_enqueue_script(
            'allI1d-c411-admin',
            AllI1D_C411_URL . 'assets/js/components/credentials.js',
            ['jquery'],
            '1.0.0'
        );
        $api = Api::get_instance();
        wp_localize_script(
            'allI1d-c411-admin',
            'allI1d_c411', 
            [
                'api' => $api->get_data(),
            ]
        );
    }

    public function settings_page() {
        $toastMessage = new ToastMessage();
        $toastMessage->render();
        $settings = new Settings();
        $settings->render();
    }
}