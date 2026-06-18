<?php
namespace AllI1D\C411\Filters;

use AllI1D\C411\Models\C411ApiClient;

class Status {

    public function __construct() {
    }

    public static function process_status($status) {
        $apiClient = new C411ApiClient(
            get_option('alli1d_c411_api_key', '')
        );
        $is_connected = $apiClient->testConnection();

        if ($is_connected) {
            $retour = ['status' => 'connected', 'success' => 'Connection to Torr9 API successful'];
        } else {
            $retour = [
                'error' => 'Failed to connect to C411 API. Please check your API key and token.',
                'Full Token connection' => $is_connected ? 'success' : 'failure',
            ];
        }
        $retour['settings_url'] = admin_url('admin.php?page=all-in-one-download-c411');

        
        $status['C411'] = $retour;
        return $status;
    }
}
