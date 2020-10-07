<?php

namespace Acquia\Orca\Enum;

use MyCLabs\Enum\Enum;

/**
 * Provides Drupal core version special values.
 *
 * @method static DrupalCoreVersionEnum OLDEST_SUPPORTED()
 * @method static DrupalCoreVersionEnum PREVIOUS_MINOR()
 * @method static DrupalCoreVersionEnum CURRENT()
 * @method static DrupalCoreVersionEnum CURRENT_DEV()
 * @method static DrupalCoreVersionEnum NEXT_MINOR()
 * @method static DrupalCoreVersionEnum NEXT_MINOR_DEV()
 * @method static DrupalCoreVersionEnum NEXT_MAJOR_LATEST_MINOR_BETA_OR_LATER()
 * @method static DrupalCoreVersionEnum NEXT_MAJOR_LATEST_MINOR_DEV()
 */
final class DrupalCoreVersionEnum extends Enum {

  public const OLDEST_SUPPORTED = 'OLDEST_SUPPORTED';

  public const PREVIOUS_MINOR = 'PREVIOUS_MINOR';

  public const CURRENT = 'CURRENT';

  public const CURRENT_DEV = 'CURRENT_DEV';

  public const NEXT_MINOR = 'NEXT_MINOR';

  public const NEXT_MINOR_DEV = 'NEXT_MINOR_DEV';

  public const NEXT_MAJOR_LATEST_MINOR_BETA_OR_LATER = 'NEXT_MAJOR_LATEST_MINOR_BETA_OR_LATER';

  public const NEXT_MAJOR_LATEST_MINOR_DEV = 'NEXT_MAJOR_LATEST_MINOR_DEV';

  /**
   * Descriptions for the versions.
   *
   * @return array
   *   An associative array of version names and their descriptions.
   */
  public static function descriptions(): array {
    return [
      self::OLDEST_SUPPORTED => 'Oldest supported Drupal core version',
      self::PREVIOUS_MINOR => 'Previous minor Drupal core version',
      self::CURRENT => 'Current Drupal core version',
      self::CURRENT_DEV => 'Current dev Drupal core version',
      self::NEXT_MINOR => 'Next minor Drupal core version',
      self::NEXT_MINOR_DEV => 'Next minor dev Drupal core version',
      self::NEXT_MAJOR_LATEST_MINOR_BETA_OR_LATER => 'Next major, latest minor beta-or-later Drupal core version',
      self::NEXT_MAJOR_LATEST_MINOR_DEV => 'Next major, latest minor dev Drupal core version',
    ];
  }

}
