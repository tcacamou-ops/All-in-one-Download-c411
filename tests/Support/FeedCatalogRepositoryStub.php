<?php
namespace AllI1D\Models\Repositories;

/**
 * Stand-in for the core plugin's AllI1D\Models\Repositories\FeedCatalogRepository,
 * which isn't part of this add-on's own codebase — only available at runtime
 * once the core plugin is loaded alongside it. Exposes a test-only setter so
 * individual tests can control what search() returns without touching a
 * database.
 */
if ( ! class_exists( __NAMESPACE__ . '\\FeedCatalogRepository' ) ) {
	class FeedCatalogRepository {

		private static ?self $instance = null;

		/** @var array<int, array<string, mixed>> */
		private static array $search_results = [];

		public static function get_instance(): self {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Test-only helper: sets the results the next search() call(s) return.
		 *
		 * @param array<int, array<string, mixed>> $results
		 */
		public static function set_search_results( array $results ): void {
			self::$search_results = $results;
		}

		public function search( string $title, ?string $type = null, ?string $provider = null ): array {
			return self::$search_results;
		}
	}
}
