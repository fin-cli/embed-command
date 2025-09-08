<?php

if ( ! class_exists( 'FP_CLI' ) ) {
	return;
}

$fpcli_embed_autoloader = __DIR__ . '/vendor/autoload.php';

if ( file_exists( $fpcli_embed_autoloader ) ) {
	require_once $fpcli_embed_autoloader;
}

if ( class_exists( 'FP_CLI\Dispatcher\CommandNamespace' ) ) {
	FP_CLI::add_command( 'embed', '\FP_CLI\Embeds\Embeds_Namespace' );
}

FP_CLI::add_command( 'embed fetch', '\FP_CLI\Embeds\Fetch_Command' );

FP_CLI::add_command( 'embed provider', '\FP_CLI\Embeds\Provider_Command' );

FP_CLI::add_command( 'embed handler', '\FP_CLI\Embeds\Handler_Command' );

FP_CLI::add_command( 'embed cache', '\FP_CLI\Embeds\Cache_Command' );
