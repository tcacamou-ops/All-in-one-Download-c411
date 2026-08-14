<?php
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/wordpress/' );
}

require_once __DIR__ . '/Support/CryptoStub.php';
require_once __DIR__ . '/Support/TorrentMetadataParserStub.php';
require_once __DIR__ . '/Support/LogsStub.php';
require_once __DIR__ . '/Support/FeedCatalogRepositoryStub.php';
