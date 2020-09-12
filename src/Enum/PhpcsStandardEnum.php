<?php

namespace Acquia\Orca\Enum;

use MyCLabs\Enum\Enum;

/**
 * Provides PHPCS standards.
 */
final class PhpcsStandardEnum extends Enum {

  public const ACQUIA_PHP = 'AcquiaPHP';

  public const ACQUIA_DRUPAL_STRICT = 'AcquiaDrupalStrict';

  public const ACQUIA_DRUPAL_TRANSITIONAL = 'AcquiaDrupalTransitional';

  public const DEFAULT = self::ACQUIA_DRUPAL_TRANSITIONAL;

  /**
   * Provides help text for commands that accept Drupal core version input.
   *
   * @return array
   *   An array of lines.
   */
  public static function commandHelp(): array {
    return [
      sprintf('- %s: Contains sniffs applicable to all PHP projects', self::ACQUIA_PHP),
      sprintf('- %s: Recommended for new Drupal projects and teams familiar with Drupal coding standards', self::ACQUIA_DRUPAL_STRICT),
      sprintf('- %s: A relaxed standard for legacy Drupal codebases or teams new to Drupal coding standards', self::ACQUIA_DRUPAL_TRANSITIONAL),
    ];
  }

}
