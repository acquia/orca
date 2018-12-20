<?php

namespace Acquia\Orca\Task\TestFramework;

use Acquia\Orca\Task\TaskInterface;

/**
 * Provides an interface for defining test framework tasks.
 */
interface TestFrameworkInterface extends TaskInterface {

  /**
   * Sets the system under test (SUT).
   *
   * @param string|null $package_name
   *   (Optional) The system under test (SUT) in the form of its package name,
   *   e.g., "drupal/example", or NULL to unset the SUT.
   */
  public function setSut(?string $package_name = NULL): void;

  /**
   * Sets the SUT-only flag.
   *
   * @param bool $is_sut_only
   *   TRUE for SUT-only or FALSE for not.
   */
  public function setSutOnly(bool $is_sut_only): void;

}
