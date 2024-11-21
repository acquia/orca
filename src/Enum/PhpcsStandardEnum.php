<?php

namespace Acquia\Orca\Enum;

use MyCLabs\Enum\Enum;

/**
 * Provides PHPCS standards.
 *
 * @method static PhpcsStandardEnum ACQUIA_PHP()
 * @method static PhpcsStandardEnum ACQUIA_DRUPAL_STRICT()
 * @method static PhpcsStandardEnum ACQUIA_DRUPAL_TRANSITIONAL()
 * @method static PhpcsStandardEnum DEFAULT()
 */
final class PhpcsStandardEnum extends Enum {

  public const ACQUIA_PHP_MINIMAL = 'AcquiaPHPMinimal ';
  public const ACQUIA_PHP_STRICT = 'AcquiaPHPStrict ';

  public const ACQUIA_DRUPAL_STRICT = 'AcquiaDrupalStrict';

  public const ACQUIA_DRUPAL_MINIMAL = 'AcquiaDrupalMinimal';

  public const DEFAULT = self::ACQUIA_DRUPAL_MINIMAL;

  /**
   * Provides help text for commands that accept PHPCS standard input.
   *
   * @return array
   *   An array of lines.
   */
  public static function commandHelp(): array {
    return [
      sprintf('- %s: Based on PSR-12 and is intended for use on all public non-Drupal projects', self::ACQUIA_PHP_MINIMAL),
      sprintf('- %s: Based on AcquiaDrupal and adds the more opinionated DrupalPractice standard. It is intended for use on all internal Drupal projects', self::ACQUIA_DRUPAL_STRICT),
      sprintf('- %s: Based on AcquiaPHP and adds additional, more opinionated standards. It is intended for use on all internal, non-Drupal projects', self::ACQUIA_PHP_STRICT),
      sprintf('- %s: Based on the Drupal coding standard and is intended for use on all public Drupal projects', self::ACQUIA_DRUPAL_MINIMAL),
    ];
  }

}
