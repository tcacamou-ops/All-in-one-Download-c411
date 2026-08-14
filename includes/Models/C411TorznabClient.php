<?php
namespace AllI1D\C411\Models;

use AllI1D\Services\TorrentMetadataParser;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * Client for c411's native Torznab feed (RSS/XML), distinct from
 * `C411ApiClient`'s JSON REST API — same base URL, dispatched via `t=search`
 * instead of the `/torrents` path. Feeds the proactive catalog indexer
 * (`C411FeedCatalogIndexer`); the on-demand search path keeps using
 * `C411ApiClient` unchanged.
 */
class C411TorznabClient
{
    private const REQUEST_TIMEOUT = 10;

    /**
     * Maximum accepted size (in bytes) for a Torznab XML response body,
     * before it is handed to simplexml_load_string(). A legitimate Torznab
     * feed (up to `limit=100` items) stays well under this, so anything
     * larger is treated as suspicious/oversized and rejected outright.
     */
    private const MAX_TORZNAB_RESPONSE_SIZE = 5 * 1024 * 1024; // 5 MB

    /**
     * Expected format of a torrent identifier extracted from the feed: the
     * infoHash embedded in `<guid>`/`torznab:attr[name=infohash]`, a
     * hexadecimal SHA-1 hash (40 chars) — same pattern as
     * `C411ApiClient::TORRENT_ID_PATTERN`.
     */
    private const TORRENT_ID_PATTERN = '/^[a-fA-F0-9]{40}$/';

    /**
     * Torznab category ids, standard (same convention as tr4ker) — distinct
     * from c411's own JSON API, which uses a custom cat/subcat mapping.
     */
    private const CATEGORY_BY_TYPE = [
        'movie'  => 2000,
        'tvshow' => 5000,
    ];

    // @var Client
    private $client;
    private $baseUrl = 'https://c411.org/api';
    private $apiKey = '';
    private $defaultParams = [
        'limit' => 100,
    ];

    public function __construct(string $apiKey, ?Client $client = null)
    {
        $this->apiKey = $apiKey;
        $this->client = $client ?? new Client(['timeout' => self::REQUEST_TIMEOUT]);
    }

    /**
     * Fetch every torrent currently listed in the c411 Torznab feed for a
     * given content type, mapped to the common provider catalog contract.
     * @param string $type 'movie' | 'tvshow'
     * @return array|null Null on request failure; [] on empty/unknown-type/rejected result.
     */
    public function fetchFeed(string $type): ?array
    {
        $cat = self::CATEGORY_BY_TYPE[$type] ?? null;
        if (null === $cat) {
            return [];
        }

        try {
            $path = $this->baseUrl . '?' . $this->buildQueryString($cat);
            error_log('Requesting C411 Torznab feed with path: ' . $this->redact_url($path));
            $response = $this->client->request('GET', $path);
            $torrents = $this->parseTorznabResponse($response->getBody()->getContents());
        } catch (RequestException $e) {
            error_log('C411 Torznab feed request failed: ' . $this->redact_url($e->getMessage()));
            return null;
        }

        $parser = new TorrentMetadataParser();
        return array_map(static function ($torrent) use ($parser) {
            $seeders = (int) $torrent['seeders'];
            return [
                'provider' => 'c411',
                'title'    => $torrent['name'],
                'quality'  => $parser->extract_quality($torrent['name']),
                'language' => $parser->extract_language($torrent['name']),
                'id'       => $torrent['id'],
                'score'    => $seeders,
                'extra'    => array_filter(
                    [
                        'seeders' => $seeders,
                        'size'    => $torrent['size'],
                        'imdbid'  => $torrent['imdbid'],
                        'tmdbid'  => $torrent['tmdbid'],
                    ],
                    static function ($value) {
                        return null !== $value;
                    }
                ),
            ];
        }, $torrents);
    }

    private function buildQueryString(int $cat): string
    {
        $params = array_merge($this->defaultParams, [
            't'      => 'search',
            'cat'    => $cat,
            'apikey' => $this->apiKey,
        ]);
        return http_build_query($params);
    }

    /**
     * Parse a Torznab RSS/XML response into a flat list of torrents.
     * Same security guards as `Tr4kerApiClient::parseTorznabResponse()`:
     * max size, anti-DOCTYPE (Billion Laughs), LIBXML_NONET.
     * @param string $xml_content
     * @return array
     */
    private function parseTorznabResponse($xml_content): array
    {
        $torrents = [];

        if (strlen($xml_content) > self::MAX_TORZNAB_RESPONSE_SIZE) {
            error_log('C411 Torznab response rejected: response exceeds maximum allowed size');
            return $torrents;
        }

        // Legitimate Torznab feeds never carry a DOCTYPE. Reject any that do
        // before parsing, to rule out entity-expansion (Billion Laughs) DoS
        // payloads regardless of libxml's own protections.
        if (stripos($xml_content, '<!doctype') !== false) {
            error_log('C411 Torznab response rejected: DOCTYPE declaration found in response');
            return $torrents;
        }

        $xml = @simplexml_load_string($xml_content, \SimpleXMLElement::class, LIBXML_NONET);
        if ($xml === false || !isset($xml->channel->item)) {
            return $torrents;
        }

        foreach ($xml->channel->item as $item) {
            $torznab_attrs = $item->children('http://torznab.com/schemas/2015/feed');
            $attrs = [];
            foreach ($torznab_attrs->attr as $attr) {
                $attr_attributes = $attr->attributes();
                $attrs[(string) $attr_attributes['name']] = (string) $attr_attributes['value'];
            }

            // The infoHash is the canonical id: it is what `<guid>` carries
            // and what `C411ApiClient::downloadTorrent()` already expects,
            // so no download-URL parsing (with its embedded apikey) is
            // needed here, unlike torr9's Tr4ker feed.
            $id = (string) $item->guid;
            if ('' === $id || !preg_match(self::TORRENT_ID_PATTERN, $id)) {
                $id = $attrs['infohash'] ?? '';
            }
            if (!preg_match(self::TORRENT_ID_PATTERN, $id)) {
                continue;
            }

            $torrents[] = [
                'name'    => (string) $item->title,
                'id'      => $id,
                'seeders' => isset($attrs['seeders']) ? (int) $attrs['seeders'] : 0,
                'size'    => isset($attrs['size']) ? (int) $attrs['size'] : null,
                'imdbid'  => $attrs['imdbid'] ?? null,
                'tmdbid'  => $attrs['tmdbid'] ?? null,
            ];
        }

        return $torrents;
    }

    private function redact_url(string $url): string
    {
        return preg_replace(
            '/([?&](?:passkey|api_key|apikey|token|key)=)[^&]+/',
            '$1***',
            $url
        );
    }
}
