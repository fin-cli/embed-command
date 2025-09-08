<?php

namespace FP_CLI\Embeds;

use FP_CLI;
use FP_CLI\Formatter;
use FP_CLI_Command;

/**
 * Retrieves embed handlers.
 *
 * ## EXAMPLES
 *
 *     # List id,regex,priority fields of available handlers.
 *     $ fp embed handler list --fields=priority,id
 *     +----------+-------------------+
 *     | priority | id                |
 *     +----------+-------------------+
 *     | 10       | youtube_embed_url |
 *     | 9999     | audio             |
 *     | 9999     | video             |
 *
 * @package fp-cli
 */
class Handler_Command extends FP_CLI_Command {
	protected $default_fields = array(
		'id',
		'regex',
	);

	/**
	 * Lists all available embed handlers.
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
	 * ## AVAILABLE FIELDS
	 *
	 * These fields will be displayed by default for each handler:
	 *
	 * * id
	 * * regex
	 *
	 * These fields are optionally available:
	 *
	 * * callback
	 * * priority
	 *
	 * ## EXAMPLES
	 *
	 *     # List id,regex,priority fields of available handlers.
	 *     $ fp embed handler list --fields=priority,id
	 *     +----------+-------------------+
	 *     | priority | id                |
	 *     +----------+-------------------+
	 *     | 10       | youtube_embed_url |
	 *     | 9999     | audio             |
	 *     | 9999     | video             |
	 *
	 * @subcommand list
	 */
	public function list_handlers( $args, $assoc_args ) {
		/** @var \FP_Embed $fp_embed */
		global $fp_embed;

		$all_handlers = array();

		ksort( $fp_embed->handlers );
		foreach ( $fp_embed->handlers as $priority => $handlers ) {
			foreach ( $handlers as $id => $handler ) {
				$all_handlers[] = array(
					'id'       => $id,
					'regex'    => $handler['regex'],
					'callback' => $handler['callback'],
					'priority' => $priority,
				);
			}
		}

		$formatter = $this->get_formatter( $assoc_args );
		$formatter->display_items( $all_handlers );
	}

	/**
	 * Get Formatter object based on supplied parameters.
	 *
	 * @param array $assoc_args Parameters passed to command. Determines formatting.
	 * @return \FP_CLI\Formatter
	 */
	protected function get_formatter( &$assoc_args ) {
		return new Formatter( $assoc_args, $this->default_fields );
	}
}
