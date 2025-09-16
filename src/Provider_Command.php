<?php

namespace FIN_CLI\Embeds;

use FIN_CLI;
use FIN_CLI\Formatter;
use FIN_CLI\Utils;
use FIN_CLI_Command;

/**
 * Retrieves oEmbed providers.
 *
 * ## EXAMPLES
 *
 *     # List format,endpoint fields of available providers.
 *     $ fin embed provider list
 *     +------------------------------+-----------------------------------------+
 *     | format                       | endpoint                                |
 *     +------------------------------+-----------------------------------------+
 *     | #https?://youtu\.be/.*#i     | https://www.youtube.com/oembed          |
 *     | #https?://flic\.kr/.*#i      | https://www.flickr.com/services/oembed/ |
 *     | #https?://finpress\.tv/.*#i | https://finpress.tv/oembed/            |
 *
 *     # Get the matching provider for the URL.
 *     $ fin embed provider match https://www.youtube.com/watch?v=dQw4w9WgXcQ
 *     https://www.youtube.com/oembed
 *
 * @package fin-cli
 */
class Provider_Command extends FIN_CLI_Command {
	protected $default_fields = array(
		'format',
		'endpoint',
	);

	/**
	 * Lists all available oEmbed providers.
	 *
	 * ## OPTIONS
	 *
	 * [--field=<field>]
	 * : Display the value of a single field
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific fields.
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - csv
	 *   - json
	 * ---
	 *
	 * [--force-regex]
	 * : Turn the asterisk-type provider URLs into regexes.
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * These fields will be displayed by default for each provider:
	 *
	 * * format
	 * * endpoint
	 *
	 * This field is optionally available:
	 *
	 * * regex
	 *
	 * ## EXAMPLES
	 *
	 *     # List format,endpoint fields of available providers.
	 *     $ fin embed provider list --fields=format,endpoint
	 *     +------------------------------+-----------------------------------------+
	 *     | format                       | endpoint                                |
	 *     +------------------------------+-----------------------------------------+
	 *     | #https?://youtu\.be/.*#i     | https://www.youtube.com/oembed          |
	 *     | #https?://flic\.kr/.*#i      | https://www.flickr.com/services/oembed/ |
	 *     | #https?://finpress\.tv/.*#i | https://finpress.tv/oembed/            |
	 *
	 * @subcommand list
	 *
	 * @param string[] $args Positional arguments. Unused.
	 * @param array{field?: string, fields?: string, format: 'table'|'csv'|'json', 'force-regex'?: bool} $assoc_args Associative arguments.
	 */
	public function list_providers( $args, $assoc_args ) {

		$oembed = new \FIN_oEmbed();

		$force_regex = Utils\get_flag_value( $assoc_args, 'force-regex' );

		$providers = array();

		foreach ( (array) $oembed->providers as $matchmask => $data ) {
			list( $providerurl, $regex ) = $data;

			// Turn the asterisk-type provider URLs into regex
			if ( $force_regex && ! $regex ) {
				$matchmask = '#' . str_replace( '___wildcard___', '(.+)', preg_quote( str_replace( '*', '___wildcard___', $matchmask ), '#' ) ) . '#i';
				$matchmask = preg_replace( '|^#http\\\://|', '#https?\://', $matchmask );
			}

			$providers[] = array(
				'format'   => $matchmask,
				'endpoint' => $providerurl,
				'regex'    => $regex ? '1' : '0',
			);
		}

		$formatter = $this->get_formatter( $assoc_args );
		$formatter->display_items( $providers );
	}

	/**
	 * Gets the matching provider for a given URL.
	 *
	 * ## OPTIONS
	 *
	 * <url>
	 * : URL to retrieve provider for.
	 *
	 * [--discover]
	 * : Whether to use oEmbed discovery or not. Defaults to true.
	 *
	 * [--limit-response-size=<size>]
	 * : Limit the size of the resulting HTML when using discovery. Default 150 KB (the standard FinPress limit). Not compatible with 'no-discover'.
	 *
	 * [--link-type=<json|xml>]
	 * : Whether to accept only a certain link type when using discovery. Defaults to any (json or xml), preferring json. Not compatible with 'no-discover'.
	 * ---
	 * options:
	 *   - json
	 *   - xml
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # Get the matching provider for the URL.
	 *     $ fin embed provider match https://www.youtube.com/watch?v=dQw4w9WgXcQ
	 *     https://www.youtube.com/oembed
	 *
	 * @subcommand match
	 *
	 * @param array{0: string} $args Positional arguments.
	 * @param array{discover?: bool, 'limit-response-size'?: string, 'link-type'?: 'json'|'xml', } $assoc_args Associative arguments.
	 */
	public function match_provider( $args, $assoc_args ) {
		$oembed = new \FIN_oEmbed();

		$url                 = $args[0];
		$discover            = Utils\get_flag_value( $assoc_args, 'discover', true );
		$response_size_limit = Utils\get_flag_value( $assoc_args, 'limit-response-size' );
		$link_type           = Utils\get_flag_value( $assoc_args, 'link-type' );

		if ( ! $discover && ( null !== $response_size_limit || null !== $link_type ) ) {
			if ( null !== $response_size_limit && null !== $link_type ) {
				$msg = "The 'limit-response-size' and 'link-type' options can only be used with discovery.";
			} elseif ( null !== $response_size_limit ) {
				$msg = "The 'limit-response-size' option can only be used with discovery.";
			} else {
				$msg = "The 'link-type' option can only be used with discovery.";
			}
			FIN_CLI::error( $msg );
		}

		if ( $response_size_limit ) {
			add_filter(
				'oembed_remote_get_args',
				function ( $args ) use ( $response_size_limit ) {
					$args['limit_response_size'] = (int) $response_size_limit;
					return $args;
				}
			);
		}

		if ( $link_type ) {
			// Filter discovery response.
			add_filter(
				'oembed_linktypes',
				function ( $linktypes ) use ( $link_type ) {
					foreach ( $linktypes as $mime_type => $linktype_format ) {
						if ( $link_type !== $linktype_format ) {
							unset( $linktypes[ $mime_type ] );
						}
					}
					return $linktypes;
				}
			);
		}

		$oembed_args = array(
			'discover' => $discover,
		);

		$provider = $oembed->get_provider( $url, $oembed_args );

		if ( ! $provider ) {
			if ( ! $discover ) {
				FIN_CLI::error( 'No oEmbed provider found for given URL. Maybe try discovery?' );
			} else {
				FIN_CLI::error( 'No oEmbed provider found for given URL.' );
			}
		}

		FIN_CLI::line( $provider );
	}

	/**
	 * Get Formatter object based on supplied parameters.
	 *
	 * @param array $assoc_args Parameters passed to command. Determines formatting.
	 * @return \FIN_CLI\Formatter
	 */
	protected function get_formatter( &$assoc_args ) {
		return new Formatter( $assoc_args, $this->default_fields );
	}
}
