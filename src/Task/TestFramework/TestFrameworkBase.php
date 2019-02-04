<?php

namespace Acquia\Orca\Task\TestFramework;

use Acquia\Orca\Task\TaskBase;

/**
 * Provides a base test framework task implementation.
 */
abstract class TestFrameworkBase extends TaskBase implements TestFrameworkInterface {

  /**
   * Whether or not to limit to public tests.
   *
   * @var bool
   */
  private $isPublicTestsOnly = TRUE;

  /**
   * {@inheritdoc}
   */
  public function limitToPublicTests(bool $limit): void {
    $this->isPublicTestsOnly = $limit;
  }

  /**
   * Determines whether or not to limit to public tests.
   *
   * @return bool
   */
  protected function isPublicTestsOnly(): bool {
    return $this->isPublicTestsOnly;
  }

}
