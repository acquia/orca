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
   * Whether or not to generate code coverage.
   *
   * @var bool
   */
  private $isToGenerateCodeCoverage = FALSE;

  /**
   * {@inheritdoc}
   */
  public function generateCodeCoverage(bool $generate): void {
    $this->isToGenerateCodeCoverage = $generate;
  }

  /**
   * {@inheritdoc}
   */
  public function limitToPublicTests(bool $limit): void {
    $this->isPublicTestsOnly = $limit;
  }

  /**
   * Determines whether or not to generate code coverage.
   *
   * @return bool
   *   TRUE to generate code coverage or FALSE not to.
   */
  protected function isToGenerateCodeCoverage(): bool {
    return $this->isToGenerateCodeCoverage;
  }

  /**
   * Determines whether or not to limit to public tests.
   *
   * @return bool
   *   TRUE to limit to public tests or FALSE not to.
   */
  protected function isPublicTestsOnly(): bool {
    return $this->isPublicTestsOnly;
  }

}
