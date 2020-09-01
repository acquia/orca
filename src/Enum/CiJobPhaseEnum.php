<?php

namespace Acquia\Orca\Enum;

use MyCLabs\Enum\Enum;

/**
 * Provides CI build phase special values.
 */
final class CiJobPhaseEnum extends Enum {

  public const BEFORE_INSTALL = 'before_install';

  public const INSTALL = 'install';

  public const BEFORE_SCRIPT = 'before_script';

  public const SCRIPT = 'script';

  public const BEFORE_CACHE = 'before_cache';

  public const AFTER_SUCCESS = 'after_success';

  public const AFTER_FAILURE = 'after_failure';

  public const BEFORE_DEPLOY = 'before_deploy';

  public const DEPLOY = 'deploy';

  public const AFTER_DEPLOY = 'after_deploy';

  public const AFTER_SCRIPT = 'after_script';

  /**
   * Descriptions for the phases.
   *
   * @return array
   *   An associative array of phase names and their descriptions.
   */
  public static function descriptions(): array {
    return [
      self::BEFORE_INSTALL => 'Scripts to run before the install stage',
      self::INSTALL => 'Scripts to run at the install stage',
      self::BEFORE_SCRIPT => 'Scripts to run before the script stage',
      self::SCRIPT => 'Scripts to run at the script stage',
      self::BEFORE_CACHE => 'Scripts to run before storing a build cache',
      self::AFTER_SUCCESS => 'Scripts to run after a successful script stage',
      self::AFTER_FAILURE => 'Scripts to run after a failing script stage',
      self::BEFORE_DEPLOY => 'Scripts to run before the deploy stage',
      self::DEPLOY => 'Scripts to run at the deploy stage',
      self::AFTER_DEPLOY => 'Scripts to run after the deploy stage',
      self::AFTER_SCRIPT => 'Scripts to run as the last stage',
    ];
  }

}
