<?php
namespace AllI1D\C411\Tests\Unit\Filters;

use AllI1D\C411\Filters\C411FeedCatalogIndexer;
use AllI1D\C411\Models\C411TorznabClient;
use AllI1D\C411\Tests\UnitTestCase;

class C411FeedCatalogIndexerTest extends UnitTestCase {

	public function test_register_provider_appends_c411_to_the_provider_list(): void {
		$indexer = new C411FeedCatalogIndexer();

		$this->assertSame( [ 'tr4ker', 'c411' ], $indexer->register_provider( [ 'tr4ker' ] ) );
	}

	public function test_refresh_does_nothing_when_no_api_key_is_configured(): void {
		\Brain\Monkey\Functions\expect( 'get_option' )
			->once()
			->with( 'alli1d_c411_api_key', '' )
			->andReturn( '' );

		\Brain\Monkey\Functions\expect( 'alli1d_index_feed_catalog' )->never();

		$indexer = new C411FeedCatalogIndexer();
		$indexer->refresh();
	}

	public function test_refresh_indexes_movie_and_tvshow_items_returned_by_the_client(): void {
		$movie_items  = [ [ 'provider' => 'c411', 'title' => 'Movie', 'id' => str_repeat( 'a', 40 ), 'score' => 5, 'extra' => [] ] ];
		$tvshow_items = [ [ 'provider' => 'c411', 'title' => 'Show', 'id' => str_repeat( 'b', 40 ), 'score' => 3, 'extra' => [] ] ];

		$client = $this->createMock( C411TorznabClient::class );
		$client->method( 'fetchFeed' )->willReturnMap( [
			[ 'movie', $movie_items ],
			[ 'tvshow', $tvshow_items ],
		] );

		\Brain\Monkey\Functions\expect( 'alli1d_index_feed_catalog' )
			->once()
			->with( 'c411', 'movie', $movie_items );
		\Brain\Monkey\Functions\expect( 'alli1d_index_feed_catalog' )
			->once()
			->with( 'c411', 'tvshow', $tvshow_items );

		$indexer = new C411FeedCatalogIndexer( $client );
		$indexer->refresh();
	}

	public function test_refresh_skips_indexing_a_type_when_the_client_returns_null(): void {
		$client = $this->createMock( C411TorznabClient::class );
		$client->method( 'fetchFeed' )->willReturn( null );

		\Brain\Monkey\Functions\expect( 'alli1d_index_feed_catalog' )->never();

		$indexer = new C411FeedCatalogIndexer( $client );
		$indexer->refresh();
	}
}
