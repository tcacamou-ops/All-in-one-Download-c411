<?php
namespace AllI1D\C411\Filters;

use AllI1D\Helpers\Crypto;
use Throwable;

class C411Search {

    /** @var C411FeedFetcher */
    private $feed_fetcher;

    public function __construct(C411FeedFetcher $feed_fetcher) {
        $this->feed_fetcher = $feed_fetcher;
    }

    public function search($results, $criteria) {
        $api_key = Crypto::decrypt( get_option('alli1d_c411_api_key', '') );
        if (empty($api_key)) {
            $results['errors']['c411'] = 'missing_credentials';
            return $results;
        }

        try {
            $items = $this->feed_fetcher->get(
                array_merge($criteria, ['context' => 'search'])
            ) ?? [];
            $results['items'] = array_merge($results['items'], $items);
        } catch (Throwable $e) {
            error_log('C411 API search failed: ' . $e->getMessage());
            $results['errors']['c411'] = 'search_failed';
        }

        return $results;
    }
}
