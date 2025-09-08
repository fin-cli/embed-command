<?php

namespace FP_CLI\Embeds;

use FP_CLI\Dispatcher\CommandNamespace;

/**
 * Inspects oEmbed providers, clears embed cache, and more.
 *
 * ## EXAMPLES
 *
 *     # Get embed HTML for a given URL.
 *     $ fp embed fetch https://www.youtube.com/watch?v=dQw4w9WgXcQ
 *     <iframe width="525" height="295" src="https://www.youtube.com/embed/dQw4w9WgXcQ?feature=oembed" ...
 *
 *     # Find cache post ID for a given URL.
 *     $ fp embed cache find https://www.youtube.com/watch?v=dQw4w9WgXcQ --width=500
 *     123
 *
 *     # List format,endpoint fields of available providers.
 *     $ fp embed provider list
 *     +------------------------------+-----------------------------------------+
 *     | format                       | endpoint                                |
 *     +------------------------------+-----------------------------------------+
 *     | #https?://youtu\.be/.*#i     | https://www.youtube.com/oembed          |
 *     | #https?://flic\.kr/.*#i      | https://www.flickr.com/services/oembed/ |
 *     | #https?://finpress\.tv/.*#i | https://finpress.tv/oembed/            |
 *
 *     # List id,regex,priority fields of available handlers.
 *     $ fp embed handler list --fields=priority,id
 *     +----------+-------------------+
 *     | priority | id                |
 *     +----------+-------------------+
 *     | 10       | youtube_embed_url |
 *     | 9999     | audio             |
 *     | 9999     | video             |
 *     +----------+-------------------+
 *
 * @package fp-cli
 */
class Embeds_Namespace extends CommandNamespace {
}
