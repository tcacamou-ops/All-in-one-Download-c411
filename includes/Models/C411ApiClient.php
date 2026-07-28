<?php
namespace AllI1D\C411\Models;

use AllI1D\Services\TorrentMetadataParser;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;

class C411ApiClient
{
    private const REQUEST_TIMEOUT = 10;

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
        $this->client = new Client(['timeout' => self::REQUEST_TIMEOUT]);
    }

    /**
     * Test the connection to the C411 API
     * @return bool
     */
    public function testConnection()
    {
        try {
            $path = $this->baseUrl.'/torrents?' . $this->buildQueryString(['q' => 'test']);
            error_log('Testing C411 API connection with path: ' . $this->redact_url( $path ) );
            $headers = [
                'Authorization' => 'Bearer ' . $this->apiKey
            ];
            $response = $this->client->request('GET', $path, ['headers' => $headers]);
            return $response->getStatusCode() === 200;
        } catch (RequestException $e) {
            error_log('C411 API connection test failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * List torrents
     */
    public function listTorrents($params = [])
    {
        $response = $this->fetchTorrents($params);
        if ($response === null) {
            return null;
        }
        return $this->filter($response, $params);
    }

    /**
     * Keyword search for the guided-search modal, mapped to the common
     * provider result contract and capped to the top 10 by seeders.
     * Unlike listTorrents()/filter(), this does NOT apply title/season/episode
     * matching: the user picks manually, so raw keyword results are returned as-is.
     * @param array $criteria ['title'=>string, 'type'=>?string, 'saison'=>?int, 'episode'=>?int, 'audio_format'=>?string]
     * @return array
     */
    public function searchTorrents(array $criteria): array
    {
        $params = array_filter([
            'name' => $criteria['title'] ?? '',
            'type' => $criteria['type'] ?? null,
            'saison' => $criteria['saison'] ?? null,
            'episode' => $criteria['episode'] ?? null,
        ], static function ($value) {
            return $value !== null;
        });
        if (($criteria['audio_format'] ?? null) === 'VF') {
            $params['lang'] = 'VFF,TRUEFRENCH,FRENCH';
        }

        $response = $this->fetchTorrents($params);
        if ($response === null || !isset($response['data']) || count($response['data']) === 0) {
            return [];
        }

        $parser = new TorrentMetadataParser();
        $items = array_map(static function ($torrent) use ($parser) {
            $seeders = intval($torrent['seeders'] ?? 0);
            return [
                'provider' => 'c411',
                'title'    => $torrent['name'] ?? '',
                'quality'  => $parser->extract_quality($torrent['name'] ?? ''),
                'language' => $parser->extract_language($torrent['name'] ?? ''),
                'id'       => $torrent['infoHash'] ?? '',
                'score'    => $seeders,
                'extra'    => ['seeders' => $seeders, 'size' => $torrent['size'] ?? null],
            ];
        }, $response['data']);

        usort($items, static function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        return array_slice($items, 0, 10);
    }

    /**
     * Raw torrent listing request, without the title/season/episode filter
     * applied by listTorrents(). Shared by listTorrents() and searchTorrents().
     * @param array $params
     * @return array|null
     */
    private function fetchTorrents($params)
    {
        try {
            $path = $this->baseUrl.'/torrents?' . $this->buildQueryString($params);
            error_log('Requesting C411 API with path: ' . $this->redact_url( $path ) );
            $headers = [
                'Authorization' => 'Bearer ' . $this->apiKey
            ];
            $response = $this->client->request('GET', $path, ['headers' => $headers]);
            return json_decode($response->getBody()->getContents(), true);
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
            $path = sprintf("%s?t=get&id=%s&apikey=%s", $this->baseUrl, $torrent_id, urlencode($this->apiKey));
            error_log('Requesting C411 API download with path: ' . $this->redact_url( $path ) );
            $headers = [
                'Authorization' => 'Bearer ' . $this->apiKey
            ];
            $response = $this->client->request('GET', $path, ['headers' => $headers]);
            return $response->getBody()->getContents(); // Binary content of the .torrent
        } catch (RequestException $e) {
            error_log('C411 API download failed: ' . $this->redact_url( $e->getMessage() ));
            return null;
        }
    }

    private function redact_url( string $url ): string {
        return preg_replace(
            '/([?&](?:passkey|api_key|apikey|token|key)=)[^&]+/',
            '$1***',
            $url
        );
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
        $type = $params['type'] ?? null;
        $year = isset($params['year']) ? intval($params['year']) : null;
        $saison = isset($params['saison']) ? intval($params['saison']) : null;
        $episode = isset($params['episode']) ? intval($params['episode']) : null;
        $results = [];
        foreach ($response['data'] as $torrent) {
            if (!isset($torrent['name']) || stripos($torrent['name'], $what) === false) {
                continue;
            }

            $is_match = apply_filters('alli1d_torrent_matches_title', true, [
                'torrent_name' => $torrent['name'],
                'title'        => $params['name'],
                'year'         => $type === 'movie' ? $year : null,
                'saison'       => $type === 'tvshow' ? $saison : null,
                'episode'      => $type === 'tvshow' ? $episode : null,
            ]);
            if (!$is_match) {
                do_action('alli1d_torrent_rejected', [
                    'torrent_name' => $torrent['name'],
                    'title'        => $params['name'],
                    'reason'       => 'title_mismatch',
                ]);
                continue;
            }

            $results[] = $torrent;
        }
        return ['data' => $results];
    }
}