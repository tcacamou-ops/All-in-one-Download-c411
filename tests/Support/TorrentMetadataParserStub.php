<?php
namespace AllI1D\Services;

/**
 * Stand-in for the core plugin's AllI1D\Services\TorrentMetadataParser,
 * which isn't part of this add-on's own codebase — only available at
 * runtime once the core plugin is loaded alongside it. Mirrors the core's
 * tag vocabulary/matching so tests exercise realistic quality/language
 * values without depending on the core plugin being present.
 */
if ( ! class_exists( __NAMESPACE__ . '\\TorrentMetadataParser' ) ) {
	class TorrentMetadataParser {
		private const QUALITY_TAGS = [ '2160p', '4k', '1080p', '720p', '480p' ];

		private const LANGUAGE_TAGS = [ 'truefrench', 'vostfr', 'subfrench', 'french', 'multi', 'vff', 'vf2', 'vfi', 'vfq' ];

		public function extract_quality( string $release_title ): ?string {
			return $this->find_tag( $release_title, self::QUALITY_TAGS );
		}

		public function extract_language( string $release_title ): ?string {
			$tag = $this->find_tag( $release_title, self::LANGUAGE_TAGS );
			return null === $tag ? null : strtoupper( $tag );
		}

		private function find_tag( string $release_title, array $tags ): ?string {
			foreach ( $tags as $tag ) {
				if ( preg_match( '/\b' . preg_quote( $tag, '/' ) . '\b/i', $release_title ) ) {
					return $tag;
				}
			}
			return null;
		}
	}
}
