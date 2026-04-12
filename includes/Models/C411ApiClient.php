<?php
namespace AllI1D\C411\Models;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;

class C411ApiClient
{
    // @var Client
    private $client;
    private $baseUrl = 'https://c411.org/api';
    private $apiKey = '';
    private $defaultParams = [
        'page' => 1,
        'perPage' => 100,
        'sortBy' => 'seeders',
        'lang' => 'MULTI,VOSTFR',
        'options' => '',
    ];

    public function __construct($apiKey = '')
    {
        $this->apiKey = $apiKey;
        $this->client = new Client();
    }

    /**
     * List torrents
     */
    public function listTorrents($params = [])
    {
        try {
            $path = $this->baseUrl.'/torrents?' . $this->buildQueryString($params);
            error_log('Requesting C411 API with path: ' . $path);
            $headers = [
                'Authorization' => 'Bearer ' . $this->apiKey
            ];
            $response = $this->client->request('GET', $path, ['headers' => $headers]);
            return $this->filter(json_decode($response->getBody()->getContents(), true), $params); // Returns the raw response content
        } catch (RequestException $e) {
            error_log('C411 API request failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Download the .torrent file
     */
    public function downloadTorrent($torrent_id)
    {
        try {
            $path = sprintf("%s?t=get&id=%s&apikey=%s", $this->baseUrl, $torrent_id, $this->apiKey);
            error_log('Requesting C411 API download with path: ' . $path);
            $response = $this->client->request('GET', $path);
            return $response->getBody()->getContents(); // Binary content of the .torrent
        } catch (RequestException $e) {
            error_log('C411 API download failed: ' . $e->getMessage());
            return null;
        }
    }

    private function buildQueryString($params)
    {
        $params = array_merge($this->defaultParams, $params);
        $params = $this->whatToQuery($params);
        return http_build_query($params);
    }

    private function whatToQuery($params)
    {
        if (isset($params['type'])) {
            if ($params['type'] === 'movie') {
                $params['cat'] = 1; // Category for videos
                $params['subcat'] = 6; // Subcategory for movies
            } elseif ($params['type'] === 'tvshow') {
                $params['cat'] = 1; // Category for TV shows
                $params['subcat'] = 7; // Subcategory for TV shows
                $params = $this->saisonEtEpisodes($params);
            }
            unset($params['type']);
        }
        return $params;
    }

    private function saisonEtEpisodes($params)
    {
        if (isset($params['saison'])) {
            $params['options'] .= 120 + intval($params['saison']); // Option for the season
            unset($params['saison']);
        }
        if (isset($params['episode'])) {
            $params['options'] .= ',' . (96 + intval($params['episode'])); // Option for the episode
            unset($params['episode']);
        }
        return $params;
    }

    private function filter($response, $params)
    {
        if (!isset($response['data']) || count($response['data']) === 0) {
            return [];
        }
        $what = str_replace([' '], '.', strtolower($params['name']));
        $results = [];
        foreach ($response['data'] as $torrent) {
            if (isset($torrent['name']) && stripos($torrent['name'], $what) !== false) {
                $results[] = $torrent;
            }
        }
        return ['data' => $results];
    }
}