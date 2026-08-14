<?php
namespace AllI1D\C411\Tests\Unit\Models;

use AllI1D\C411\Models\C411TorznabClient;
use AllI1D\C411\Tests\UnitTestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

class C411TorznabClientTest extends UnitTestCase {

	private function client_with_response( string $body, int $status = 200 ): C411TorznabClient {
		$handler = HandlerStack::create( new MockHandler( [ new Response( $status, [], $body ) ] ) );
		return new C411TorznabClient( 'secret-key', new Client( [ 'handler' => $handler ] ) );
	}

	private function torznab_xml( string $guid, string $title, int $seeders = 10, ?string $imdbid = null ): string {
		$extra = $imdbid ? "<torznab:attr name=\"imdbid\" value=\"{$imdbid}\" />" : '';
		return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<rss xmlns:torznab="http://torznab.com/schemas/2015/feed">
  <channel>
    <item>
      <title>{$title}</title>
      <guid>{$guid}</guid>
      <torznab:attr name="seeders" value="{$seeders}" />
      <torznab:attr name="infohash" value="{$guid}" />
      {$extra}
    </item>
  </channel>
</rss>
XML;
	}

	public function test_fetch_feed_maps_torznab_items_to_the_common_catalog_contract(): void {
		$guid   = str_repeat( 'a', 40 );
		$client = $this->client_with_response( $this->torznab_xml( $guid, 'Movie.Title.2024.1080p.FRENCH', 42, '1234567' ) );

		$items = $client->fetchFeed( 'movie' );

		$this->assertCount( 1, $items );
		$this->assertSame( 'c411', $items[0]['provider'] );
		$this->assertSame( 'Movie.Title.2024.1080p.FRENCH', $items[0]['title'] );
		$this->assertSame( '1080p', $items[0]['quality'] );
		$this->assertSame( 'FRENCH', $items[0]['language'] );
		$this->assertSame( $guid, $items[0]['id'] );
		$this->assertSame( 42, $items[0]['score'] );
		$this->assertSame( 42, $items[0]['extra']['seeders'] );
		$this->assertSame( '1234567', $items[0]['extra']['imdbid'] );
	}

	public function test_fetch_feed_returns_empty_array_for_an_unknown_type(): void {
		$client = $this->client_with_response( '' );

		$this->assertSame( [], $client->fetchFeed( 'unknown' ) );
	}

	public function test_fetch_feed_rejects_response_with_doctype_declaration(): void {
		$client = $this->client_with_response( '<!doctype html><rss><channel></channel></rss>' );

		$this->assertSame( [], $client->fetchFeed( 'movie' ) );
	}

	public function test_fetch_feed_rejects_oversized_response(): void {
		$huge   = '<rss><channel>' . str_repeat( 'a', 6 * 1024 * 1024 ) . '</channel></rss>';
		$client = $this->client_with_response( $huge );

		$this->assertSame( [], $client->fetchFeed( 'movie' ) );
	}

	public function test_fetch_feed_skips_items_without_a_valid_infohash(): void {
		$xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<rss xmlns:torznab="http://torznab.com/schemas/2015/feed">
  <channel>
    <item>
      <title>No hash</title>
      <guid>not-a-valid-infohash</guid>
    </item>
  </channel>
</rss>
XML;
		$client = $this->client_with_response( $xml );

		$this->assertSame( [], $client->fetchFeed( 'movie' ) );
	}

	public function test_fetch_feed_returns_null_on_request_failure(): void {
		$handler = HandlerStack::create( new MockHandler( [
			new RequestException( 'boom', new Request( 'GET', 'https://c411.org/api' ) ),
		] ) );
		$client  = new C411TorznabClient( 'secret-key', new Client( [ 'handler' => $handler ] ) );

		$this->assertNull( $client->fetchFeed( 'movie' ) );
	}
}
