<?php

namespace FP_CLI\Embeds;

use FP_CLI;
use FP_CLI\Utils;
use FP_CLI_Command;

class Fetch_Command extends FP_CLI_Command {
	/**
	 * Attempts to convert a URL into embed HTML.
	 *
	 * In non-raw mode, starts by checking the URL against the regex of the registered embed handlers.
	 * If none of the regex matches and it's enabled, then the URL will be given to the FP_oEmbed class.
	 *
	 * In raw mode, checks the providers directly and returns the data.
	 *
	 * ## OPTIONS
	 *
	 * <url>
	 * : URL to retrieve oEmbed data for.
	 *
	 * [--width=<width>]
	 * : Width of the embed in pixels.
	 *
	 * [--height=<height>]
	 * : Height of the embed in pixels.
	 *
	 * [--post-id=<id>]
	 * : Cache oEmbed response for a given post.
	 *
	 * [--discover]
	 * : Enable oEmbed discovery. Defaults to true.
	 *
	 * [--skip-cache]
	 * : Ignore already cached oEmbed responses. Has no effect if using the 'raw' option, which doesn't use the cache.
	 *
	 * [--skip-sanitization]
	 * : Remove the filter that FinPress from 4.4 onwards uses to sanitize oEmbed responses. Has no effect if using the 'raw' option, which by-passes sanitization.
	 *
	 * [--do-shortcode]
	 * : If the URL is handled by a registered embed handler and returns a shortcode, do shortcode and return result. Has no effect if using the 'raw' option, which by-passes handlers.
	 *
	 * [--limit-response-size=<size>]
	 * : Limit the size of the resulting HTML when using discovery. Default 150 KB (the standard FinPress limit). Not compatible with 'no-discover'.
	 *
	 * [--raw]
	 * : Return the raw oEmbed response instead of the resulting HTML. Ignores the cache and does not sanitize responses or use registered embed handlers.
	 *
	 * [--raw-format=<json|xml>]
	 * : Render raw oEmbed data in a particular format. Defaults to json. Can only be specified in conjunction with the 'raw' option.
	 * ---
	 * options:
	 *   - json
	 *   - xml
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # Get embed HTML for a given URL.
	 *     $ fp embed fetch https://www.youtube.com/watch?v=dQw4w9WgXcQ
	 *     <iframe width="525" height="295" src="https://www.youtube.com/embed/dQw4w9WgXcQ?feature=oembed" ...
	 *
	 *     # Get raw oEmbed data for a given URL.
	 *     $ fp embed fetch https://www.youtube.com/watch?v=dQw4w9WgXcQ --raw
	 *     {"author_url":"https:\/\/www.youtube.com\/user\/RickAstleyVEVO","width":525,"version":"1.0", ...
	 */
	public function __invoke( $args, $assoc_args ) {
		/** @var \FP_Embed $fp_embed */
		global $fp_embed;

		$url        = $args[0];
		$raw        = Utils\get_flag_value( $assoc_args, 'raw' );
		$raw_format = Utils\get_flag_value( $assoc_args, 'raw-format' );

		/**
		 * @var string|null $post_id
		 */
		$post_id             = Utils\get_flag_value( $assoc_args, 'post-id' );
		$discover            = Utils\get_flag_value( $assoc_args, 'discover' );
		$response_size_limit = Utils\get_flag_value( $assoc_args, 'limit-response-size' );

		/**
		 * @var string $width
		 */
		$width = Utils\get_flag_value( $assoc_args, 'width' );

		/**
		 * @var string $height
		 */
		$height = Utils\get_flag_value( $assoc_args, 'height' );

		// The `$key_suffix` used for caching is part based on serializing the attributes array without normalizing it first so need to try to replicate that.
		$oembed_args = array();

		if ( null !== $width ) {
			$oembed_args['width'] = (int) $width;
		}
		if ( null !== $height ) {
			$oembed_args['height'] = (int) $height;
		}
		if ( null !== $discover ) {
			$oembed_args['discover'] = $discover ? '1' : '0'; // Make it a string as if from a shortcode attribute.
		}

		$discover = null === $discover ? true : (bool) $discover; // Will use to set `$oembed_args['discover']` when not calling `FP_Embed::shortcode()`.

		if ( ! $discover && null !== $response_size_limit ) {
			FP_CLI::error( "The 'limit-response-size' option can only be used with discovery." );
		}

		if ( ! $raw && null !== $raw_format ) {
			FP_CLI::error( "The 'raw-format' option can only be used with the 'raw' option." );
		}

		if ( $response_size_limit ) {
			add_filter(
				'oembed_remote_get_args',
				function ( $args ) use ( $response_size_limit ) {
					$args['limit_response_size'] = $response_size_limit;

					return $args;
				},
				PHP_INT_MAX
			);
		}

		// If raw, query providers directly, by-passing cache.
		if ( $raw ) {
			$oembed = new \FP_oEmbed();

			$oembed_args['discover'] = $discover;

			// Make 'oembed_dataparse' filter a no-op so get raw unsanitized data.
			remove_all_filters( 'oembed_dataparse' ); // Save a few cycles.
			add_filter(
				'oembed_dataparse',
				function ( $ret, $data, $url ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
					return $data;
				},
				PHP_INT_MAX,
				3
			);

			// Allow `fp_filter_pre_oembed_result()` to provide local URLs (FP >= 4.5.3).
			// phpcs:ignore FinPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Using FP Core hook.
			$data = apply_filters( 'pre_oembed_result', null, $url, $oembed_args );

			if ( null === $data ) {
				$provider = $oembed->get_provider( $url, $oembed_args );

				if ( ! $provider ) {
					if ( ! $discover ) {
						FP_CLI::error( 'No oEmbed provider found for given URL. Maybe try discovery?' );
					} else {
						FP_CLI::error( 'No oEmbed provider found for given URL.' );
					}
				}

				$data = $oembed->fetch( $provider, $url, $oembed_args );
			}

			if ( false === $data ) {
				FP_CLI::error( 'There was an error fetching the oEmbed data.' );
			}

			if ( 'xml' === $raw_format ) {
				if ( ! class_exists( 'SimpleXMLElement' ) ) {
					FP_CLI::error( "The PHP extension 'SimpleXMLElement' is not available but is required for XML-formatted output." );
				}
				FP_CLI::log( (string) $this->oembed_create_xml( (array) $data ) );
			} else {
				FP_CLI::log( (string) json_encode( $data ) );
			}

			return;
		}

		if ( $post_id ) {
			// phpcs:ignore FinPress.FP.GlobalVariablesOverride.Prohibited -- the request is asking for a post id directly so need to override the global.
			$GLOBALS['post'] = get_post( (int) $post_id );
			if ( null === $GLOBALS['post'] ) {
				FP_CLI::warning( sprintf( "Post id '%s' not found.", $post_id ) );
			}
		}

		if ( Utils\get_flag_value( $assoc_args, 'skip-sanitization', false ) ) {
			remove_filter( 'oembed_dataparse', 'fp_filter_oembed_result', 10 );
		}

		if ( Utils\get_flag_value( $assoc_args, 'skip-cache', false ) ) {
			$fp_embed->usecache = false;
			// In order to skip caching, also need `$cached_recently` to be false in `FP_Embed::shortcode()`, so set TTL to zero.
			add_filter( 'oembed_ttl', '__return_zero', PHP_INT_MAX );
		} else {
			$fp_embed->usecache = true;
		}

		// `FP_Embed::shortcode()` sets the 'discover' attribute based on 'embed_oembed_discover' filter, no matter what's passed to it.
		add_filter( 'embed_oembed_discover', $discover ? '__return_true' : '__return_false', PHP_INT_MAX );

		$html = $fp_embed->shortcode( $oembed_args, $url );

		if ( false !== $html && '[' === substr( $html, 0, 1 ) && Utils\get_flag_value( $assoc_args, 'do-shortcode' ) ) {
			$html = do_shortcode( $html, true );
		}

		if ( false === $html ) {
			FP_CLI::error( 'There was an error fetching the oEmbed data.' );
		}

		FP_CLI::log( $html );
	}

	/**
	 * Creates an XML string from a given array.
	 *
	 * Same as `\_oembed_create_xml()` in "fp-includes\embed.php" introduced in FP 4.4.0. Polyfilled as marked private (and also to cater for older FP versions).
	 *
	 * @see _oembed_create_xml()
	 *
	 * @param array            $data The original oEmbed response data.
	 * @param \SimpleXMLElement $node Optional. XML node to append the result to recursively.
	 * @return string|false XML string on success, false on error.
	 */
	protected function oembed_create_xml( $data, $node = null ) {
		if ( ! is_array( $data ) || empty( $data ) ) {
			return false;
		}

		if ( null === $node ) {
			$node = new \SimpleXMLElement( '<oembed></oembed>' );
		}

		foreach ( $data as $key => $value ) {
			if ( is_numeric( $key ) ) {
				$key = 'oembed';
			}

			if ( is_array( $value ) ) {
				$item = $node->addChild( $key );
				$this->oembed_create_xml( $value, $item );
			} else {
				$node->addChild( $key, esc_html( $value ) );
			}
		}

		return $node->asXML();
	}
}
