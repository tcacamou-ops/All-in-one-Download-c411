<?php
namespace AllI1D\C411\Tests\Unit\Filters;

use AllI1D\C411\Filters\C411FeedFetcher;
use AllI1D\C411\Tests\UnitTestCase;

class C411FeedFetcherTest extends UnitTestCase {

	public function test_get_returns_null_when_api_key_is_empty(): void {
		\Brain\Monkey\Functions\expect( 'get_option' )
			->once()
			->with( 'alli1d_c411_api_key', '' )
			->andReturn( '' );

		$fetcher = new C411FeedFetcher();
		$result  = $fetcher->get( [ 'context' => 'search', 'type' => 'movie', 'title' => 'Matrix' ] );

		$this->assertNull( $result );
	}
}
