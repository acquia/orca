<?php

namespace Acquia\Orca\Task\TestFramework;

use Acquia\Orca\Task\TaskInterface;

/**
 * Provides an interface for defining test framework tasks.
 */
interface TestFrameworkInterface extends TaskInterface {

  /**
   * Gets the human-readable framework name.
   *
   * @return string
   *   The framework name.
   */
  public function name(): string;

  /**
   * Sets whether or not to limit to public tests.
   *
   * @param bool $limit
   *   TRUE to limit to public tests or FALSE to include private tests.
   */
  public function limitToPublicTests(bool $limit): void;

}
