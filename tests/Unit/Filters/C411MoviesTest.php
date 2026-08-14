<?php
namespace AllI1D\C411\Tests\Unit\Filters;

use AllI1D\C411\Filters\C411FeedFetcher;
use AllI1D\C411\Filters\C411Movies;
use AllI1D\C411\Tests\UnitTestCase;
use AllI1D\Models\Repositories\FeedCatalogRepository;

class C411MoviesTest extends UnitTestCase {

	protected function setUp(): void {
		parent::setUp();
		FeedCatalogRepository::set_search_results( [] );
	}

	protected function tearDown(): void {
		FeedCatalogRepository::set_search_results( [] );
		$this->remove_temp_upload_dir();
		parent::tearDown();
	}

	private function remove_temp_upload_dir(): void {
		$dir = sys_get_temp_dir() . '/c411-movies-test/c411';
		if ( is_dir( $dir ) ) {
			foreach ( array_diff( scandir( $dir ) ?: [], [ '.', '..' ] ) as $entry ) {
				@unlink( $dir . '/' . $entry );
			}
			@rmdir( $dir );
			@rmdir( sys_get_temp_dir() . '/c411-movies-test' );
		}
	}

	private function stub_common_wp_functions(): void {
		\Brain\Monkey\Functions\stubs( [ 'do_action' ] );
		\Brain\Monkey\Functions\expect( 'wp_upload_dir' )
			->andReturn( [ 'basedir' => sys_get_temp_dir() . '/c411-movies-test' ] );
		\Brain\Monkey\Functions\expect( 'get_option' )
			->with( 'alli1d_c411_api_key', '' )
			->andReturn( '' );
	}

	public function test_catalog_hit_skips_live_fetch_and_downloads_the_catalog_match(): void {
		FeedCatalogRepository::set_search_results( [
			[ 'provider' => 'c411', 'id' => 'not-a-valid-hash', 'title' => 'The Matrix', 'quality' => '1080p', 'language' => 'VFF', 'score' => 5, 'extra' => [] ],
		] );

		\Brain\Monkey\Functions\expect( 'apply_filters' )
			->with( 'alli1d_torrent_matches_title', true, \Mockery::any() )
			->once()
			->andReturn( true );

		\Brain\Monkey\Functions\expect( 'apply_filters' )
			->with( 'alli1d_torrent_matches_quality', true, [
				'torrent_quality' => '1080p',
				'preference'      => 'any',
			] )
			->once()
			->andReturn( true );

		$this->stub_common_wp_functions();

		$fetcher = $this->createMock( C411FeedFetcher::class );
		$fetcher->expects( $this->never() )->method( 'get' );

		$movies = new C411Movies( $fetcher );
		$movie  = [ 'title' => 'The Matrix', 'audio_format' => 'VF', 'general_search_done' => false ];

		$result = $movies->process_movie( $movie );

		// The invalid torrent id makes downloadTorrent() fail fast without any
		// network call, so the download is reported as failed but the flow
		// still reached the download step — proving $items came from the
		// catalog match (the live fetcher above was asserted never called).
		$this->assertArrayNotHasKey( 'found', $result );
	}

	public function test_catalog_miss_and_general_search_not_done_runs_live_path(): void {
		FeedCatalogRepository::set_search_results( [] );

		$this->stub_common_wp_functions();

		$fetcher = $this->createMock( C411FeedFetcher::class );
		$fetcher->expects( $this->once() )
			->method( 'get' )
			->with( [
				'context'      => 'cron',
				'type'         => 'movie',
				'title'        => 'Inception',
				'audio_format' => 'VF',
			] )
			->willReturn( [
				[ 'id' => 'not-a-valid-hash', 'title' => 'Inception', 'quality' => '1080p' ],
			] );

		\Brain\Monkey\Functions\expect( 'apply_filters' )
			->with( 'alli1d_torrent_matches_title', true, \Mockery::any() )
			->once()
			->andReturn( true );

		\Brain\Monkey\Functions\expect( 'apply_filters' )
			->with( 'alli1d_torrent_matches_quality', true, [
				'torrent_quality' => '1080p',
				'preference'      => 'any',
			] )
			->once()
			->andReturn( true );

		$movies = new C411Movies( $fetcher );
		$movie  = [ 'title' => 'Inception', 'audio_format' => 'VF', 'general_search_done' => false ];

		$result = $movies->process_movie( $movie );

		// Live-fetched candidates are now run through the same title+quality
		// matching loop as catalog candidates, so a matched torrent still
		// reaches the download step (which fails fast on the invalid id, no
		// network call).
		$this->assertArrayNotHasKey( 'found', $result );
	}

	public function test_catalog_miss_and_general_search_already_done_returns_early(): void {
		FeedCatalogRepository::set_search_results( [] );

		\Brain\Monkey\Functions\stubs( [ 'do_action' ] );
		\Brain\Monkey\Functions\expect( 'get_option' )->never();
		\Brain\Monkey\Functions\expect( 'wp_upload_dir' )->never();

		$fetcher = $this->createMock( C411FeedFetcher::class );
		$fetcher->expects( $this->never() )->method( 'get' );

		$movies = new C411Movies( $fetcher );
		$movie  = [ 'title' => 'Inception', 'audio_format' => 'VF', 'general_search_done' => true ];

		$result = $movies->process_movie( $movie );

		$this->assertSame( $movie, $result );
		$this->assertArrayNotHasKey( 'found', $result );
	}

	public function test_live_fetch_candidate_failing_quality_check_leaves_found_false(): void {
		FeedCatalogRepository::set_search_results( [] );

		$this->stub_common_wp_functions();

		$fetcher = $this->createMock( C411FeedFetcher::class );
		$fetcher->expects( $this->once() )
			->method( 'get' )
			->willReturn( [
				[ 'id' => 'not-a-valid-hash', 'title' => 'Inception', 'quality' => '720p' ],
			] );

		\Brain\Monkey\Functions\expect( 'apply_filters' )
			->with( 'alli1d_torrent_matches_title', true, \Mockery::any() )
			->once()
			->andReturn( true );

		\Brain\Monkey\Functions\expect( 'apply_filters' )
			->with( 'alli1d_torrent_matches_quality', true, [
				'torrent_quality' => '720p',
				'preference'      => '1080p,2160p',
			] )
			->once()
			->andReturn( false );

		$movies = new C411Movies( $fetcher );
		$movie  = [ 'title' => 'Inception', 'audio_format' => 'VF', 'quality' => '1080p,2160p', 'general_search_done' => false ];

		$result = $movies->process_movie( $movie );

		$this->assertSame( $movie, $result );
		$this->assertArrayNotHasKey( 'found', $result );
	}

	public function test_catalog_candidate_matching_title_but_not_quality_is_skipped(): void {
		FeedCatalogRepository::set_search_results( [
			[ 'provider' => 'c411', 'id' => 'not-a-valid-hash', 'title' => 'The Matrix', 'quality' => '720p', 'language' => 'VFF', 'score' => 5, 'extra' => [] ],
		] );

		\Brain\Monkey\Functions\stubs( [ 'do_action' ] );
		\Brain\Monkey\Functions\expect( 'get_option' )->never();
		\Brain\Monkey\Functions\expect( 'wp_upload_dir' )->never();

		\Brain\Monkey\Functions\expect( 'apply_filters' )
			->with( 'alli1d_torrent_matches_title', true, \Mockery::any() )
			->once()
			->andReturn( true );

		\Brain\Monkey\Functions\expect( 'apply_filters' )
			->with( 'alli1d_torrent_matches_quality', true, [
				'torrent_quality' => '720p',
				'preference'      => '1080p,2160p',
			] )
			->once()
			->andReturn( false );

		$fetcher = $this->createMock( C411FeedFetcher::class );
		$fetcher->expects( $this->never() )->method( 'get' );

		$movies = new C411Movies( $fetcher );
		$movie  = [ 'title' => 'The Matrix', 'audio_format' => 'VF', 'quality' => '1080p,2160p', 'general_search_done' => true ];

		$result = $movies->process_movie( $movie );

		$this->assertSame( $movie, $result );
		$this->assertArrayNotHasKey( 'found', $result );
	}
}
