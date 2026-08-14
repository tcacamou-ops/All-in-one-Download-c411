<?php
namespace AllI1D\C411\Filters;

use AllI1D\C411\Models\C411ApiClient;
use AllI1D\C411\Helpers\UploadDirProtection;
use AllI1D\Actions\Logs;
use AllI1D\Helpers\Crypto;
use AllI1D\Models\Repositories\FeedCatalogRepository;

class C411Movies {

    /** @var C411FeedFetcher */
    private $feed_fetcher;

    public function __construct(C411FeedFetcher $feed_fetcher) {
        $this->feed_fetcher = $feed_fetcher;
    }

    public function process_movie($movie) {
        $catalog_matches = FeedCatalogRepository::get_instance()->search($movie['title'], 'movie', 'c411');
        $matched = $this->find_match($catalog_matches, $movie);

        if (null !== $matched) {
            $items = [$matched];
        } elseif (!empty($movie['general_search_done'])) {
            do_action('alli1d_log', 'C411 API - Skipped (general search already done, no catalog match)', Logs::DEBUG, Logs::FILMS_LOG);
            return $movie;
        } else {
            $live_items = $this->feed_fetcher->get(
                [
                    'context'      => 'cron',
                    'type'         => 'movie',
                    'title'        => $movie['title'],
                    'audio_format' => $movie['audio_format'],
                ]
            );

            if (empty($live_items)) {
                do_action('alli1d_log', 'C411 API - No response', Logs::DEBUG, Logs::FILMS_LOG);
                return $movie;
            }
            do_action('alli1d_log', 'C411 API - ' .count($live_items). ' results', Logs::DEBUG, Logs::FILMS_LOG);

            $matched = $this->find_match($live_items, $movie);

            if (null === $matched) {
                do_action('alli1d_log', 'C411 API - No matching torrent title', Logs::DEBUG, Logs::FILMS_LOG);
                return $movie;
            }

            $items = [$matched];
        }

        $upload_dir = wp_upload_dir();
        $c411_dir = $upload_dir['basedir'] . '/c411';
        // Create the c411 directory if it doesn't exist
        if (!file_exists($c411_dir)) {
            mkdir($c411_dir, 0755, true);
        }
        UploadDirProtection::protect($c411_dir);
        $file_name = preg_replace('/[^a-zA-Z0-9_-]/', '', str_replace(' ', '_', implode('-', [$movie['title'], $movie['audio_format']]))) . '.torrent';
        // Full path of the torrent file
        $file_path = $c411_dir . '/' . $file_name;
        $apiClient = new C411ApiClient(Crypto::decrypt( get_option('alli1d_c411_api_key', '') ));
        $file_content = $apiClient->downloadTorrent($items[0]['id']);
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

    /**
     * Find the first candidate in $items whose title and quality both match
     * $movie, using the same matching rules regardless of whether $items
     * came from the local catalog or a live C411 fetch.
     *
     * @param array<int, array<string, mixed>> $items
     * @param array<string, mixed>             $movie
     * @return array<string, mixed>|null
     */
    private function find_match(array $items, array $movie): ?array {
        foreach ($items as $candidate) {
            $is_match = apply_filters('alli1d_torrent_matches_title', true, [
                'torrent_name' => $candidate['title'],
                'title'        => $movie['title'],
                'year'         => $movie['year'] ?? null,
                'saison'       => null,
                'episode'      => null,
            ]);
            if (!$is_match) {
                continue;
            }

            $quality_ok = apply_filters('alli1d_torrent_matches_quality', true, [
                'torrent_quality' => $candidate['quality'] ?? null,
                'preference'      => $movie['quality'] ?? 'any',
            ]);
            if (!$quality_ok) {
                continue;
            }

            return $candidate;
        }
        return null;
    }
}