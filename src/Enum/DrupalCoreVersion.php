<?php

namespace Acquia\Orca\Enum;

use MyCLabs\Enum\Enum;

/**
 * Provides Drupal core version special values.
 */
final class DrupalCoreVersion extends Enum {

  public const PREVIOUS_RELEASE = 'PREVIOUS_RELEASE';

  public const PREVIOUS_DEV = 'PREVIOUS_DEV';

  public const CURRENT_RECOMMENDED = 'CURRENT_RECOMMENDED';

  public const CURRENT_DEV = 'CURRENT_DEV';

  public const NEXT_RELEASE = 'NEXT_RELEASE';

  public const NEXT_DEV = 'NEXT_DEV';

  /**
   * Provides help text for commands that accept Drupal core version input.
   *
   * @return array
   *   An array of lines.
   */
  public static function commandHelp() {
    return [
      sprintf('- %s: The latest release of the previous minor version, e.g., "8.5.14" if the current minor version is 8.6', self::PREVIOUS_RELEASE),
      sprintf('- %s: The development version of the previous minor version, e.g., "8.5.x-dev"', self::PREVIOUS_DEV),
      sprintf('- %s: The current recommended release, e.g., "8.6.14"', self::CURRENT_RECOMMENDED),
      sprintf('- %s: The current development version, e.g., "8.6.x-dev"', self::CURRENT_DEV),
      sprintf('- %s: The next release version if available, e.g., "8.7.0-beta2"', self::NEXT_RELEASE),
      sprintf('- %s: The next development version, e.g., "8.7.x-dev"', self::NEXT_DEV),
    ];
  }

}
