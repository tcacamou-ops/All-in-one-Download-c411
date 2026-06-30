<?php
namespace AllI1D\C411\Components;

use AllI1D\Helpers\Crypto;

class Credentials {
    public function get_html(): string {
        ob_start();
        $this->render();
        return ob_get_clean() ?: '';
    }

    public function render() {
        echo '<label for="c411_api_key">' . __('C411 API Key', 'all-in-one-download-c411') . '</label>';
        echo '<input type="password" id="c411_api_key" name="c411_api_key" placeholder="' . esc_attr( __( 'C411 API Key', 'all-in-one-download-c411' ) ) . '" required value="' . esc_attr( Crypto::decrypt( get_option( 'alli1d_c411_api_key', '' ) ) ) . '" />';
        echo '<br /><br />';
        echo '<button type="button" id="submit-c411-credentials">' . __('Save', 'all-in-one-download-c411') . '</button>';
        echo '<div id="url-message" style="margin-top: 10px;"></div>';
    }
}