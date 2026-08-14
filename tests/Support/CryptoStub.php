<?php
namespace AllI1D\Helpers;

/**
 * Stand-in for the core plugin's AllI1D\Helpers\Crypto, which isn't part of
 * this add-on's own codebase — only available at runtime once the core
 * plugin is loaded alongside it. Good enough for unit tests: option values
 * here are never actually encrypted.
 */
if ( ! class_exists( __NAMESPACE__ . '\\Crypto' ) ) {
	class Crypto {
		public static function decrypt( string $value ): string {
			return $value;
		}

		public static function encrypt( string $value ): string {
			return $value;
		}
	}
}
