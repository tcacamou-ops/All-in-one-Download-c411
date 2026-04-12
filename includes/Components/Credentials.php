<?php
namespace AllI1D\C411\Components;

class Credentials {
    public function render() {
        echo '<label for="c411_api_key">' . __('C411 API Key', 'all-in-one-download-c411') . '</label>';
        echo '<input type="password" id="c411_api_key" name="c411_api_key" placeholder="'.__('C411 API Key', 'all-in-one-download-c411').'" required value="'.get_option('alli1d_c411_api_key', '').'" />';
        echo '<br /><br />';
        echo '<button type="button" id="submit-c411-credentials">' . __('Save', 'all-in-one-download-c411') . '</button>';
        echo '<div id="url-message" style="margin-top: 10px;"></div>';
    }
}