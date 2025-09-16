Feature: Manage oEmbed providers.

  Background:
    Given a FIN install
    And a filter-providers.php file:
      """
      <?php
      FIN_CLI::add_fin_hook( 'oembed_providers', function ( $providers ) {
        $providers['http://example.com/*'] = [ 'http://example.com/api/oembed.{format}', false ];
        return $providers;
      });
      """

  Scenario: List oEmbed providers
    When I run `fin embed provider list --require=filter-providers.php`
    And save STDOUT as {DEFAULT_STDOUT}
    Then STDOUT should contain:
      """
      format
      """
    And STDOUT should contain:
      """
      endpoint
      """
    And STDOUT should not contain:
      """
      regex
      """
    And STDOUT should contain:
      """
      http://example.com/*
      """
    And STDOUT should contain:
      """
      http://example.com/api/oembed.{format}
      """
    And STDOUT should contain:
      """
      youtube\.com/watch.*
      """
    And STDOUT should contain:
      """
      //www.youtube.com/oembed
      """
    And STDOUT should contain:
      """
      flickr\.com/
      """
    And STDOUT should contain:
      """
      flickr.com
      """
    And STDOUT should contain:
      """
      twitter\.com/
      """
    And STDOUT should contain:
      """
      twitter.com
      """
    And STDOUT should contain:
      """
      \.spotify\.com/
      """
    And STDOUT should contain:
      """
      spotify.com
      """

    When I run `fin embed provider list --fields=format,endpoint --require=filter-providers.php`
    Then STDOUT should be:
      """
      {DEFAULT_STDOUT}
      """

    When I run `fin embed provider list --force-regex --require=filter-providers.php`
    Then STDOUT should contain:
      """
      #https?\://example\.com/(.+)#i
      """
    And STDOUT should contain:
      """
      http://example.com/api/oembed.{format}
      """
    And STDOUT should not contain:
      """
      http://example.com/*
      """
    And STDOUT should match /^#http/m

    When I run `fin embed provider list --fields=format`
    Then STDOUT should contain:
      """
      format
      """
    And STDOUT should not contain:
      """
      endpoint
      """
    And STDOUT should contain:
      """
      youtube\.com/watch.*
      """
    And STDOUT should not contain:
      """
      youtube.com
      """

    When I run `fin embed provider list --field=format`
    Then STDOUT should not contain:
      """
      format
      """
    And STDOUT should not contain:
      """
      endpoint
      """
    And STDOUT should contain:
      """
      youtube\.com/watch.*
      """
    And STDOUT should not contain:
      """
      youtube.com
      """

    When I run `fin embed provider list --field=regex`
    Then STDOUT should match /^(?:(?:1|0)\n)+$/

  @require-fin-4.0
  Scenario: Match an oEmbed provider
    # Provider not requiring discovery
    When I run `fin embed provider match https://www.youtube.com/watch?v=dQw4w9WgXcQ`
    Then STDOUT should contain:
      """
      //www.youtube.com/oembed
      """

    # Provider requiring discovery
    # Old versions of FIN_oEmbed can trigger PHP "Only variables should be passed by reference" notices on discover so use "try" to cater for these.
    When I try `fin embed provider match https://audio.com/audio-com/collections/ambient-focus`
    And save STDOUT as {DEFAULT_STDOUT}
    Then the return code should be 0
    And STDERR should not contain:
      """
      Error:
      """
    And STDOUT should contain:
      """
      audio.com/
      """
    And STDOUT should contain:
      """
      json
      """
    And STDOUT should not contain:
      """
      xml
      """

    # Old versions of FIN_oEmbed can trigger PHP "Only variables should be passed by reference" notices on discover so use "try" to cater for these.
    When I try `fin embed provider match https://audio.com/audio-com/collections/ambient-focus --link-type=json`
    Then the return code should be 0
    And STDERR should not contain:
      """
      Error:
      """
    And STDOUT should be:
      """
      {DEFAULT_STDOUT}
      """

    # Old versions of FIN_oEmbed can trigger PHP "Only variables should be passed by reference" notices on discover so use "try" to cater for these.
    When I try `fin embed provider match https://audio.com/audio-com/collections/ambient-focus --link-type=xml`
    Then the return code should be 0
    And STDERR should not contain:
      """
      Error:
      """
    And STDOUT should not contain:
      """
      {DEFAULT_STDOUT}
      """
    And STDOUT should contain:
      """
      audio.com/
      """
    And STDOUT should contain:
      """
      xml
      """
    And STDOUT should not contain:
      """
      json
      """

  # Depends on `oembed_remote_get_args` filter introduced in FIN 4.0 https://core.trac.finpress.org/ticket/23442
  @require-fin-4.0
  Scenario: Discover a provider with limited response size
    When I run `fin embed provider match https://audio.com/audio-com/collections/ambient-focus`
    Then save STDOUT as {DEFAULT_STDOUT}

    # Response limit too small
    When I try `fin embed provider match https://audio.com/audio-com/collections/ambient-focus --limit-response-size=10`
    Then the return code should be 1
    And STDERR should be:
      """
      Error: No oEmbed provider found for given URL.
      """

    # Response limit big enough
    When I run `fin embed provider match https://audio.com/audio-com/collections/ambient-focus --limit-response-size=50000`
    Then STDOUT should be:
      """
      {DEFAULT_STDOUT}
      """

  Scenario: Fail to match an oEmbed provider
    When I try `fin embed provider match https://www.example.com/watch?v=dQw4w9WgXcQ --no-discover`
    Then the return code should be 1
    And STDERR should be:
      """
      Error: No oEmbed provider found for given URL. Maybe try discovery?
      """
    And STDOUT should be empty

    When I try `fin embed provider match https://www.example.com/watch?v=dQw4w9WgXcQ`
    Then the return code should be 1
    # Old versions of FIN_oEmbed can trigger PHP "Only variables should be passed by reference" notices on discover so use "contain" to ignore these.
    And STDERR should contain:
      """
      Error: No oEmbed provider found for given URL.
      """
    And STDOUT should be empty

    When I try `fin embed provider match https://www.example.com/watch?v=dQw4w9WgXcQ --discover`
    Then the return code should be 1
    # Old versions of FIN_oEmbed can trigger PHP "Only variables should be passed by reference" notices on discover so use "contain" to ignore these.
    And STDERR should contain:
      """
      Error: No oEmbed provider found for given URL.
      """
    And STDOUT should be empty

  @require-fin-4.0
  Scenario: Only match an oEmbed provider if discover
    When I try `fin embed provider match https://audio.com/audio-com/collections/ambient-focus --no-discover`
    Then the return code should be 1
    And STDERR should be:
      """
      Error: No oEmbed provider found for given URL. Maybe try discovery?
      """
    And STDOUT should be empty

    # Old versions of FIN_oEmbed can trigger PHP "Only variables should be passed by reference" notices on discover so use "try" to cater for these.
    When I try `fin embed provider match https://audio.com/audio-com/collections/ambient-focus`
    Then the return code should be 0
    And STDERR should not contain:
      """
      Error:
      """
    And STDOUT should contain:
      """
      audio.com/
      """
    And STDOUT should contain:
      """
      collection
      """

    # Old versions of FIN_oEmbed can trigger PHP "Only variables should be passed by reference" notices on discover so use "try" to cater for these.
    When I try `fin embed provider match https://audio.com/audio-com/collections/ambient-focus --discover`
    Then the return code should be 0
    And STDERR should not contain:
      """
      Error:
      """
    And STDOUT should contain:
      """
     audio.com/
      """

  Scenario: Incompatible or wrong options
    When I try `fin embed provider match https://www.example.com/watch?v=dQw4w9WgXcQ --no-discover --limit-response-size=50000`
    Then the return code should be 1
    And STDERR should be:
      """
      Error: The 'limit-response-size' option can only be used with discovery.
      """
    And STDOUT should be empty

    When I try `fin embed provider match https://www.example.com/watch?v=dQw4w9WgXcQ --no-discover --link-type=json`
    Then the return code should be 1
    And STDERR should be:
      """
      Error: The 'link-type' option can only be used with discovery.
      """
    And STDOUT should be empty

    When I try `fin embed provider match https://www.example.com/watch?v=dQw4w9WgXcQ --no-discover --limit-response-size=50000 --link-type=json`
    Then the return code should be 1
    And STDERR should be:
      """
      Error: The 'limit-response-size' and 'link-type' options can only be used with discovery.
      """
    And STDOUT should be empty

    When I try `fin embed provider match https://www.example.com/watch?v=dQw4w9WgXcQ --no-discover --link-type=blah`
    Then the return code should be 1
    And STDERR should contain:
      """
      Error: Parameter errors:
       Invalid value specified for 'link-type'
      """
    And STDOUT should be empty
