<?php
namespace AllI1D\C411\Filters;

use AllI1D\C411\Models\C411ApiClient;
use AllI1D\C411\Helpers\UploadDirProtection;
use AllI1D\Actions\Logs;
use AllI1D\Helpers\Crypto;
use AllI1D\Models\Repositories\FeedCatalogRepository;

class C411TvShows {

    /** @var C411FeedFetcher */
    private $feed_fetcher;

    public function __construct(C411FeedFetcher $feed_fetcher) {
        $this->feed_fetcher = $feed_fetcher;
    }

    public function process_tv_show($tvshow) {
        $catalog_matches = FeedCatalogRepository::get_instance()->search($tvshow['title'], 'tvshow', 'c411');
        $matched = $this->find_match($catalog_matches, $tvshow);

        if (null !== $matched) {
            $items = [$matched];
        } elseif (!empty($tvshow['general_search_done'])) {
            do_action('alli1d_log', 'C411 API - Skipped (general search already done, no catalog match)', Logs::DEBUG, Logs::SERIES_LOG);
            return $tvshow;
        } else {
            $live_items = $this->feed_fetcher->get(
                [
                    'context'      => 'cron',
                    'type'         => 'tvshow',
                    'title'        => $tvshow['title'],
                    'audio_format' => $tvshow['audio_format'],
                    'saison'       => $tvshow['saison'],
                    'episode'      => $tvshow['episode'],
                ]
            );

            if (empty($live_items)) {
                do_action('alli1d_log', 'C411 API - No response', Logs::DEBUG, Logs::SERIES_LOG);
                return $tvshow;
            }
            do_action('alli1d_log', 'C411 API - ' .count($live_items). ' results', Logs::DEBUG, Logs::SERIES_LOG);

            $matched = $this->find_match($live_items, $tvshow);

            if (null === $matched) {
                do_action('alli1d_log', 'C411 API - No matching torrent title', Logs::DEBUG, Logs::SERIES_LOG);
                return $tvshow;
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
        $file_name = preg_replace('/[^a-zA-Z0-9_-]/', '', str_replace(' ', '_', implode('-', [$tvshow['title'],$tvshow['audio_format'],$tvshow['saison'],$tvshow['episode']]))) . '.torrent';
        // Full path of the torrent file
        $file_path = $c411_dir . '/' . $file_name;
        $apiClient = new C411ApiClient(Crypto::decrypt( get_option('alli1d_c411_api_key', '') ));
        $file_content = $apiClient->downloadTorrent($items[0]['id']);
        if (null !== $file_content ) {
            file_put_contents($file_path, $file_content);
            $tvshow['found'] = true;
            $tvshow['results'][] = [
                'type'=> 'torrent',
                'path' => $file_path,
            ];
            do_action('alli1d_log', 'C411 API - Torrent found : ' . $file_name, Logs::DEBUG, Logs::SERIES_LOG);
        } else {
            do_action('alli1d_log', 'C411 API - Failed to download torrent', Logs::ERROR, Logs::SERIES_LOG);
        }
        return $tvshow;
    }

    /**
     * Find the first candidate in $items whose title and quality both match
     * $tvshow, using the same matching rules regardless of whether $items
     * came from the local catalog or a live C411 fetch.
     *
     * @param array<int, array<string, mixed>> $items
     * @param array<string, mixed>             $tvshow
     * @return array<string, mixed>|null
     */
    private function find_match(array $items, array $tvshow): ?array {
        foreach ($items as $candidate) {
            $is_match = apply_filters('alli1d_torrent_matches_title', true, [
                'torrent_name' => $candidate['title'],
                'title'        => $tvshow['title'],
                'year'         => null,
                'saison'       => $tvshow['saison'],
                'episode'      => $tvshow['episode'],
            ]);
            if (!$is_match) {
                continue;
            }

            $quality_ok = apply_filters('alli1d_torrent_matches_quality', true, [
                'torrent_quality' => $candidate['quality'] ?? null,
                'preference'      => $tvshow['quality'] ?? 'any',
            ]);
            if (!$quality_ok) {
                continue;
            }

            return $candidate;
        }
        return null;
    }
}