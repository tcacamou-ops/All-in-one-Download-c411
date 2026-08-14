<?php
namespace AllI1D\Actions;

/**
 * Stand-in for the core plugin's AllI1D\Actions\Logs, which isn't part of
 * this add-on's own codebase — only available at runtime once the core
 * plugin is loaded alongside it. Only the constants referenced by this
 * add-on's filters are needed for unit tests.
 */
if ( ! class_exists( __NAMESPACE__ . '\\Logs' ) ) {
	class Logs {
		public const NOTICE  = 'notice';
		public const WARNING = 'warning';
		public const ERROR   = 'error';
		public const DEBUG   = 'debug';

		public const MEDIAS_LOG = 'medias.log';
		public const SERIES_LOG = 'series.log';
		public const FILMS_LOG  = 'films.log';
	}
}
