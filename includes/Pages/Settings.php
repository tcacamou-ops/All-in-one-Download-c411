<?php
namespace AllI1D\C411\Pages;

use AllI1D\C411\Components\Credentials;

class Settings {
    public function render() {
        $credentials = new Credentials();
        echo '<div class="wrap">';
        echo '<h1>' . __('C411 Settings', 'all-in-one-download-c411') . '</h1>';
        $credentials->render();
        
        echo '</div>';
        
    }
}