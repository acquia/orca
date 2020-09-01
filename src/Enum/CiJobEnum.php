<?php

namespace Acquia\Orca\Enum;

use MyCLabs\Enum\Enum;

/**
 * Provides CI job special values.
 */
final class CiJobEnum extends Enum {

  public const STATIC_CODE_ANALYSIS = 'STATIC_CODE_ANALYSIS';

  public const INTEGRATED_TEST_ON_OLDEST_SUPPORTED = 'INTEGRATED_TEST_ON_OLDEST_SUPPORTED';

  public const INTEGRATED_TEST_ON_PREVIOUS_MINOR = 'INTEGRATED_TEST_ON_PREVIOUS_MINOR';

  public const INTEGRATED_TEST_ON_LATEST_LTS = 'INTEGRATED_TEST_ON_LATEST_LTS';

  public const ISOLATED_TEST_ON_CURRENT = 'ISOLATED_TEST_ON_CURRENT';

  public const INTEGRATED_TEST_ON_CURRENT = 'INTEGRATED_TEST_ON_CURRENT';

  public const INTEGRATED_UPGRADE_TEST_TO_NEXT_MINOR = 'INTEGRATED_UPGRADE_TEST_TO_NEXT_MINOR';

  public const INTEGRATED_UPGRADE_TEST_TO_NEXT_MINOR_DEV = 'INTEGRATED_UPGRADE_TEST_TO_NEXT_MINOR_DEV';

  public const ISOLATED_TEST_ON_CURRENT_DEV = 'ISOLATED_TEST_ON_CURRENT_DEV';

  public const INTEGRATED_TEST_ON_CURRENT_DEV = 'INTEGRATED_TEST_ON_CURRENT_DEV';

  public const LOOSE_DEPRECATED_CODE_SCAN = 'LOOSE_DEPRECATED_CODE_SCAN';

  public const STRICT_DEPRECATED_CODE_SCAN = 'STRICT_DEPRECATED_CODE_SCAN';

  public const DEPRECATED_CODE_SCAN_W_CONTRIB = 'DEPRECATED_CODE_SCAN_W_CONTRIB';

  public const ISOLATED_TEST_ON_NEXT_MINOR = 'ISOLATED_TEST_ON_NEXT_MINOR';

  public const INTEGRATED_TEST_ON_NEXT_MINOR = 'INTEGRATED_TEST_ON_NEXT_MINOR';

  public const ISOLATED_TEST_ON_NEXT_MINOR_DEV = 'ISOLATED_TEST_ON_NEXT_MINOR_DEV';

  public const INTEGRATED_TEST_ON_NEXT_MINOR_DEV = 'INTEGRATED_TEST_ON_NEXT_MINOR_DEV';

  public const ISOLATED_TEST_ON_NEXT_MAJOR_LATEST_MINOR_BETA_OR_LATER = 'ISOLATED_TEST_ON_NEXT_MAJOR_LATEST_MINOR_BETA_OR_LATER';

  public const INTEGRATED_TEST_ON_NEXT_MAJOR_LATEST_MINOR_BETA_OR_LATER = 'INTEGRATED_TEST_ON_NEXT_MAJOR_LATEST_MINOR_BETA_OR_LATER';

  public const ISOLATED_TEST_ON_NEXT_MAJOR_LATEST_MINOR_DEV = 'ISOLATED_TEST_ON_NEXT_MAJOR_LATEST_MINOR_DEV';

  public const INTEGRATED_TEST_ON_NEXT_MAJOR_LATEST_MINOR_DEV = 'INTEGRATED_TEST_ON_NEXT_MAJOR_LATEST_MINOR_DEV';

  public const ISOLATED_UPGRADE_TO_NEXT_MAJOR_BETA_OR_LATER = 'ISOLATED_UPGRADE_TO_NEXT_MAJOR_BETA_OR_LATER';

  public const ISOLATED_UPGRADE_TO_NEXT_MAJOR_DEV = 'ISOLATED_UPGRADE_TO_NEXT_MAJOR_DEV';

  /**
   * Descriptions for the jobs.
   *
   * @return array
   *   An associative array of job names and their descriptions.
   */
  public static function descriptions(): array {
    return [
      self::STATIC_CODE_ANALYSIS => 'Static code analysis',
      self::INTEGRATED_TEST_ON_OLDEST_SUPPORTED => 'Integrated test on oldest supported Drupal core version',
      self::INTEGRATED_TEST_ON_PREVIOUS_MINOR => 'Integrated test on previous minor Drupal core version',
      self::INTEGRATED_TEST_ON_LATEST_LTS => 'Integrated test on latest LTS Drupal core version',
      self::ISOLATED_TEST_ON_CURRENT => 'Isolated test on current Drupal core version',
      self::INTEGRATED_TEST_ON_CURRENT => 'Integrated test on current Drupal core version',
      self::INTEGRATED_UPGRADE_TEST_TO_NEXT_MINOR => 'Integrated upgrade test to next minor Drupal core version',
      self::INTEGRATED_UPGRADE_TEST_TO_NEXT_MINOR_DEV => 'Integrated upgrade test to next minor dev Drupal core version',
      self::ISOLATED_TEST_ON_CURRENT_DEV => 'Isolated test on current dev Drupal core version',
      self::INTEGRATED_TEST_ON_CURRENT_DEV => 'Integrated test on current dev Drupal core version',
      self::LOOSE_DEPRECATED_CODE_SCAN => 'Loose deprecated code scan',
      self::STRICT_DEPRECATED_CODE_SCAN => 'Strict deprecated code scan',
      self::DEPRECATED_CODE_SCAN_W_CONTRIB => 'Deprecated code scan w/ contrib',
      self::ISOLATED_TEST_ON_NEXT_MINOR => 'Isolated test on next minor Drupal core version',
      self::INTEGRATED_TEST_ON_NEXT_MINOR => 'Integrated test on next minor Drupal core version',
      self::ISOLATED_TEST_ON_NEXT_MINOR_DEV => 'Isolated test on next minor dev Drupal core version',
      self::INTEGRATED_TEST_ON_NEXT_MINOR_DEV => 'Integrated test on next minor dev Drupal core version',
      self::ISOLATED_TEST_ON_NEXT_MAJOR_LATEST_MINOR_BETA_OR_LATER => 'Isolated test on next major, latest minor beta-or-later Drupal core version',
      self::INTEGRATED_TEST_ON_NEXT_MAJOR_LATEST_MINOR_BETA_OR_LATER => 'Integrated test on next major, latest minor beta-or-later Drupal core version',
      self::ISOLATED_TEST_ON_NEXT_MAJOR_LATEST_MINOR_DEV => 'Isolated test on next major, latest minor dev Drupal core version',
      self::INTEGRATED_TEST_ON_NEXT_MAJOR_LATEST_MINOR_DEV => 'Integrated test on next major, latest minor dev Drupal core version',
      self::ISOLATED_UPGRADE_TO_NEXT_MAJOR_BETA_OR_LATER => 'Isolated upgrade to next major beta-or-later Drupal core version',
      self::ISOLATED_UPGRADE_TO_NEXT_MAJOR_DEV => 'Isolated upgrade to next major dev Drupal core version',
    ];
  }

}
