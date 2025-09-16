<?php

if ( ! class_exists( 'FIN_CLI' ) ) {
	return;
}

$fincli_embed_autoloader = __DIR__ . '/vendor/autoload.php';

if ( file_exists( $fincli_embed_autoloader ) ) {
	require_once $fincli_embed_autoloader;
}

if ( class_exists( 'FIN_CLI\Dispatcher\CommandNamespace' ) ) {
	FIN_CLI::add_command( 'embed', '\FIN_CLI\Embeds\Embeds_Namespace' );
}

FIN_CLI::add_command( 'embed fetch', '\FIN_CLI\Embeds\Fetch_Command' );

FIN_CLI::add_command( 'embed provider', '\FIN_CLI\Embeds\Provider_Command' );

FIN_CLI::add_command( 'embed handler', '\FIN_CLI\Embeds\Handler_Command' );

FIN_CLI::add_command( 'embed cache', '\FIN_CLI\Embeds\Cache_Command' );
