<?php

namespace Acquia\Orca\Tool;

/**
 * Provides an interface for defining test framework tasks.
 */
interface TestFrameworkInterface extends TaskInterface {

  /**
   * Sets whether or not to generate code coverage.
   *
   * @param bool $generate
   *   TRUE to generate code coverage or FALSE not to.
   */
  public function generateCodeCoverage(bool $generate): void;

  /**
   * Sets whether or not to limit to public tests.
   *
   * @param bool $limit
   *   TRUE to limit to public tests or FALSE to include private tests.
   */
  public function limitToPublicTests(bool $limit): void;

}
