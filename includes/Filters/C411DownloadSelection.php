<?php
namespace AllI1D\C411\Filters;

use AllI1D\C411\Models\C411ApiClient;
use AllI1D\C411\Helpers\UploadDirProtection;
use AllI1D\Helpers\Crypto;
use Throwable;

class C411DownloadSelection {

    public function __construct() {
    }

    public function download($null_default, $result) {
        try {
            $api_key = Crypto::decrypt( get_option('alli1d_c411_api_key', '') );
            if (empty($api_key)) {
                return null;
            }

            $apiClient = new C411ApiClient($api_key);
            $file_content = $apiClient->downloadTorrent($result['id']);
            if (null === $file_content) {
                return null;
            }

            $upload_dir = wp_upload_dir();
            $c411_dir = $upload_dir['basedir'] . '/c411';
            if (!file_exists($c411_dir)) {
                mkdir($c411_dir, 0755, true);
            }
            UploadDirProtection::protect($c411_dir);
            $file_name = preg_replace('/[^a-zA-Z0-9_-]/', '', str_replace(' ', '_', implode('-', [$result['title'], $result['quality']]))) . '.torrent';
            $file_path = $c411_dir . '/' . $file_name;
            file_put_contents($file_path, $file_content);

            return [
                'type' => 'torrent',
                'path' => $file_path,
            ];
        } catch (Throwable $e) {
            error_log('C411 API download selection failed: ' . $e->getMessage());
            return null;
        }
    }
}
