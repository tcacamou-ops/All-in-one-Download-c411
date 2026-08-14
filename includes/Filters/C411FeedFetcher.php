<?php
namespace AllI1D\C411\Filters;

use AllI1D\C411\Models\C411ApiClient;
use AllI1D\Helpers\Crypto;

class C411FeedFetcher {

    /**
     * Fetch a set of search criteria directly from the C411 API.
     *
     * @param array $criteria See C411ApiClient::fetchFeed().
     * @return array|null
     */
    public function get(array $criteria): ?array
    {
        $api_key = Crypto::decrypt(get_option('alli1d_c411_api_key', ''));
        if ('' === $api_key) {
            return null;
        }

        $client = new C411ApiClient($api_key);
        return $client->fetchFeed($criteria);
    }
}
