<?php
namespace AllI1D\C411\Filters;

use AllI1D\C411\Models\C411ApiClient;
use AllI1D\Actions\Logs;

class C411Movies {


    public function __construct() {
    }

    public function process_movie($movie) {
        $apiClient = new C411ApiClient(get_option('alli1d_c411_api_key', ''));
        $params = [
            'name'=> $movie['title'],
            'type'=>'movie',
        ];
        if ($movie['audio_format'] === 'VF') {
            $params['lang'] = 'VFF,TRUEFRENCH,FRENCH';
        }
		
		$response = $apiClient->listTorrents($params);
		if ($response === null || count($response) === 0 || !isset($response['data']) || count($response['data']) === 0) {
            do_action('alli1d_log', 'C411 API - No response', Logs::DEBUG, Logs::FILMS_LOG);
			return $movie;
		}
		do_action('alli1d_log', 'C411 API - ' .count($response['data']). ' results', Logs::DEBUG, Logs::FILMS_LOG);
		
        $upload_dir = wp_upload_dir();
        $c411_dir = $upload_dir['basedir'] . '/c411';
        // Create the c411 directory if it doesn't exist
        if (!file_exists($c411_dir)) {
            mkdir($c411_dir, 0755, true);
        }
        $file_name = preg_replace('/[^a-zA-Z0-9_-]/', '', str_replace(' ', '_', implode('-', [$movie['title'], $movie['audio_format']]))) . '.torrent';
        // Full path of the torrent file
        $file_path = $c411_dir . '/' . $file_name;
        $file_content = $apiClient->downloadTorrent($response['data'][0]['infoHash']);
        if (null !== $file_content) {
            file_put_contents($file_path, $file_content);
            $movie['found'] = true;
            $movie['results'][] = [
                'type'=> 'torrent',
                'path' => $file_path,
            ];
            do_action('alli1d_log', 'C411 API - Torrent found : ' . $file_name, Logs::DEBUG, Logs::FILMS_LOG);
        } else {
            do_action('alli1d_log', 'C411 API - Failed to download torrent', Logs::ERROR, Logs::FILMS_LOG);
        }
        return $movie;
    }
}