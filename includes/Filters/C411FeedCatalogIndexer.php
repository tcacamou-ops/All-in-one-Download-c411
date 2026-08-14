<?php
namespace AllI1D\C411\Filters;

use AllI1D\C411\Models\C411TorznabClient;
use AllI1D\Helpers\Crypto;

/**
 * Feeds the core's proactive catalog cron (`alli1d_refresh_feed_catalog`):
 * pulls c411's native Torznab feed for each content type and pushes the
 * results into the shared catalog via `alli1d_index_feed_catalog()`.
 * Complementary to the on-demand search path (`C411Search`/`C411Movies`/
 * `C411TvShows`), which is untouched by this class.
 */
class C411FeedCatalogIndexer
{
    private const TYPES = ['movie', 'tvshow'];

    /** @var C411TorznabClient|null */
    private $client;

    public function __construct(?C411TorznabClient $client = null)
    {
        $this->client = $client;
    }

    public function refresh(): void
    {
        $client = $this->client ?? $this->build_client();
        if (null === $client) {
            return;
        }

        foreach (self::TYPES as $type) {
            $items = $client->fetchFeed($type);
            if (null === $items) {
                continue;
            }
            alli1d_index_feed_catalog('c411', $type, $items);
        }
    }

    public function register_provider(array $providers): array
    {
        $providers[] = 'c411';
        return $providers;
    }

    private function build_client(): ?C411TorznabClient
    {
        $api_key = Crypto::decrypt(get_option('alli1d_c411_api_key', ''));
        if ('' === $api_key) {
            return null;
        }
        return new C411TorznabClient($api_key);
    }
}
