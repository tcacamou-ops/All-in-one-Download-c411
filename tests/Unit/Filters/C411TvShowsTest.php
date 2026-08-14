<?php
namespace AllI1D\C411\Tests\Unit\Filters;

use AllI1D\C411\Filters\C411FeedFetcher;
use AllI1D\C411\Filters\C411TvShows;
use AllI1D\C411\Tests\UnitTestCase;
use AllI1D\Models\Repositories\FeedCatalogRepository;

class C411TvShowsTest extends UnitTestCase {

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
		$dir = sys_get_temp_dir() . '/c411-tvshows-test/c411';
		if ( is_dir( $dir ) ) {
			foreach ( array_diff( scandir( $dir ) ?: [], [ '.', '..' ] ) as $entry ) {
				@unlink( $dir . '/' . $entry );
			}
			@rmdir( $dir );
			@rmdir( sys_get_temp_dir() . '/c411-tvshows-test' );
		}
	}

	private function stub_common_wp_functions(): void {
		\Brain\Monkey\Functions\stubs( [ 'do_action' ] );
		\Brain\Monkey\Functions\expect( 'wp_upload_dir' )
			->andReturn( [ 'basedir' => sys_get_temp_dir() . '/c411-tvshows-test' ] );
		\Brain\Monkey\Functions\expect( 'get_option' )
			->with( 'alli1d_c411_api_key', '' )
			->andReturn( '' );
	}

	public function test_catalog_hit_skips_live_fetch_and_downloads_the_catalog_match(): void {
		FeedCatalogRepository::set_search_results( [
			[ 'provider' => 'c411', 'id' => 'not-a-valid-hash', 'title' => 'Breaking Bad S01E01', 'quality' => '1080p', 'language' => 'VFF', 'score' => 5, 'extra' => [] ],
		] );

		\Brain\Monkey\Functions\expect( 'apply_filters' )
			->with( 'alli1d_torrent_matches_title', true, [
				'torrent_name' => 'Breaking Bad S01E01',
				'title'        => 'Breaking Bad',
				'year'         => null,
				'saison'       => 1,
				'episode'      => 1,
			] )
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

		$tvshows = new C411TvShows( $fetcher );
		$tvshow  = [
			'title'               => 'Breaking Bad',
			'audio_format'        => 'VF',
			'saison'              => 1,
			'episode'             => 1,
			'general_search_done' => false,
		];

		$result = $tvshows->process_tv_show( $tvshow );

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
				'type'         => 'tvshow',
				'title'        => 'Breaking Bad',
				'audio_format' => 'VF',
				'saison'       => 1,
				'episode'      => 1,
			] )
			->willReturn( [
				[ 'id' => 'not-a-valid-hash', 'title' => 'Breaking Bad S01E01', 'quality' => '1080p' ],
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

		$tvshows = new C411TvShows( $fetcher );
		$tvshow  = [
			'title'               => 'Breaking Bad',
			'audio_format'        => 'VF',
			'saison'              => 1,
			'episode'             => 1,
			'general_search_done' => false,
		];

		$result = $tvshows->process_tv_show( $tvshow );

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

		$tvshows = new C411TvShows( $fetcher );
		$tvshow  = [
			'title'               => 'Breaking Bad',
			'audio_format'        => 'VF',
			'saison'              => 1,
			'episode'             => 1,
			'general_search_done' => true,
		];

		$result = $tvshows->process_tv_show( $tvshow );

		$this->assertSame( $tvshow, $result );
		$this->assertArrayNotHasKey( 'found', $result );
	}

	public function test_live_fetch_candidate_failing_quality_check_leaves_found_false(): void {
		FeedCatalogRepository::set_search_results( [] );

		$this->stub_common_wp_functions();

		$fetcher = $this->createMock( C411FeedFetcher::class );
		$fetcher->expects( $this->once() )
			->method( 'get' )
			->willReturn( [
				[ 'id' => 'not-a-valid-hash', 'title' => 'Breaking Bad S01E01', 'quality' => '720p' ],
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

		$tvshows = new C411TvShows( $fetcher );
		$tvshow  = [
			'title'               => 'Breaking Bad',
			'audio_format'        => 'VF',
			'saison'              => 1,
			'episode'             => 1,
			'quality'             => '1080p,2160p',
			'general_search_done' => false,
		];

		$result = $tvshows->process_tv_show( $tvshow );

		$this->assertSame( $tvshow, $result );
		$this->assertArrayNotHasKey( 'found', $result );
	}

	public function test_catalog_candidate_matching_title_but_not_quality_is_skipped(): void {
		FeedCatalogRepository::set_search_results( [
			[ 'provider' => 'c411', 'id' => 'not-a-valid-hash', 'title' => 'Breaking Bad S01E01', 'quality' => '720p', 'language' => 'VFF', 'score' => 5, 'extra' => [] ],
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

		$tvshows = new C411TvShows( $fetcher );
		$tvshow  = [
			'title'               => 'Breaking Bad',
			'audio_format'        => 'VF',
			'saison'              => 1,
			'episode'             => 1,
			'quality'             => '1080p,2160p',
			'general_search_done' => true,
		];

		$result = $tvshows->process_tv_show( $tvshow );

		$this->assertSame( $tvshow, $result );
		$this->assertArrayNotHasKey( 'found', $result );
	}
}
