<?php

namespace Acquia\Orca\Tests;

/**
 * Provides an interface for defining an automated test.
 */
interface TestInterface {

  /**
   * Executes the test.
   *
   * @throws \Acquia\Orca\Tests\TestFailureException
   */
  public function execute(): void;

}
